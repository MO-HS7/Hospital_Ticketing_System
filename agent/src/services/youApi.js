/**
 * You.com API Service
 * 
 * Integrates with You.com's official API v1/agents/runs endpoint.
 * Handles authentication, retries, timeouts, and error formatting.
 * 
 * SECURITY: API key never logged. Read from env only.
 */

const { v4: uuidv4 } = require('uuid');

// Configuration
const YOU_API_BASE = 'https://api.you.com/v1';
const TIMEOUT_MS = parseInt(process.env.AGENT_TIMEOUT_MS, 10) || 10000;
const MAX_RETRIES = 1;
const RETRY_DELAY_MS = 1000;

/**
 * Get API key from environment (never expose in logs).
 */
function getApiKey() {
    return process.env.YOU_API_KEY || null;
}

/**
 * Check if API key is configured.
 */
function isConfigured() {
    return !!getApiKey();
}

/**
 * Sleep for given milliseconds.
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Call You.com Agents API (v1/agents/runs).
 * 
 * @param {Object} options
 * @param {string} options.query - User's query/message
 * @param {string} options.locale - Locale (en/ar)
 * @param {Object} options.sessionContext - Session context for continuity
 * @returns {Promise<Object>} Normalized response
 */
async function runAgent({ query, locale = 'en', sessionContext = {} }) {
    const requestId = uuidv4();
    const startTime = Date.now();

    const apiKey = getApiKey();
    if (!apiKey) {
        return {
            ok: false,
            error: 'API key not configured',
            status: 503,
            request_id: requestId,
            provider: 'you',
            provider_status: 'missing_key',
        };
    }

    const url = `${YOU_API_BASE}/agents/runs`;

    // Build conversation history for context (last 10 messages = 5 turns)
    let historyContext = '';
    if (sessionContext.history && Array.isArray(sessionContext.history) && sessionContext.history.length > 0) {
        // Format history as Q&A pairs
        const historyLines = sessionContext.history
            .slice(-10) // Keep last 10 messages (5 user + 5 assistant)
            .map(msg => {
                const role = msg.role === 'user' ? 'User' : 'Assistant';
                const content = (msg.content || msg.message || '').substring(0, 200);
                return `${role}: ${content}`;
            })
            .join('\n');

        historyContext = `[Previous conversation context:\n${historyLines}\n]\n\n`;
    }

    // Build request body for You.com API  
    // API expects 'input' field as a string, not 'messages' array
    const instructions = locale === 'ar'
        ? '[تعليمات: أجب بالعربية وقدم مصادر موثوقة للمعلومات الصحية. استمر في سياق المحادثة السابقة إذا كان ذلك مناسباً.]'
        : '[Instructions: Respond in English and cite reliable sources for medical information. Continue the context of the previous conversation if relevant.]';

    const body = {
        agent: 'express',
        stream: false,
        input: `${historyContext}Current question: ${query}\n\n${instructions}`
    };

    let lastError = null;

    for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${apiKey}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            const latencyMs = Date.now() - startTime;

            // Log request (no secrets)
            console.log(JSON.stringify({
                request_id: requestId,
                action: 'you_api_call',
                attempt: attempt + 1,
                status_code: response.status,
                latency_ms: latencyMs,
            }));

            // Handle rate limiting and server errors with retry
            if ((response.status === 429 || response.status >= 500) && attempt < MAX_RETRIES) {
                console.log(JSON.stringify({
                    request_id: requestId,
                    action: 'retry_scheduled',
                    reason: response.status === 429 ? 'rate_limit' : 'server_error',
                    delay_ms: RETRY_DELAY_MS,
                }));
                await sleep(RETRY_DELAY_MS * (attempt + 1)); // Exponential backoff
                continue;
            }

            if (!response.ok) {
                const errorText = await response.text().catch(() => 'Unknown error');
                return {
                    ok: false,
                    error: `You.com API error: ${response.status}`,
                    status: response.status,
                    details: errorText.substring(0, 200),
                    request_id: requestId,
                    latency_ms: latencyMs,
                    provider: 'you',
                    provider_status: 'error',
                };
            }

            const data = await response.json();
            const responseSize = JSON.stringify(data).length;

            // Log success
            console.log(JSON.stringify({
                request_id: requestId,
                action: 'you_api_success',
                latency_ms: latencyMs,
                response_size: responseSize,
            }));

            return {
                ok: true,
                data: normalizeResponse(data),
                request_id: requestId,
                latency_ms: latencyMs,
                provider: 'you',
                provider_status: 'ok',
            };

        } catch (error) {
            lastError = error;
            const latencyMs = Date.now() - startTime;

            // Log error (no sensitive data)
            console.error(JSON.stringify({
                request_id: requestId,
                action: 'you_api_error',
                attempt: attempt + 1,
                error_type: error.name,
                error_message: error.message,
                latency_ms: latencyMs,
            }));

            // Retry on network errors
            if (attempt < MAX_RETRIES && error.name !== 'AbortError') {
                await sleep(RETRY_DELAY_MS * (attempt + 1));
                continue;
            }
        }
    }

    // All retries exhausted
    return {
        ok: false,
        error: lastError?.message || 'Request failed after retries',
        status: 500,
        request_id: requestId,
        latency_ms: Date.now() - startTime,
        provider: 'you',
        provider_status: 'failed',
    };
}

/**
 * Normalize You.com API response to our standard format.
 */
function normalizeResponse(data) {
    // Handle different response structures from You.com
    let content = '';
    let citations = [];

    // You.com API returns: { output: [{ text: "...", type: "message.answer" }] }
    if (data.output && Array.isArray(data.output) && data.output.length > 0) {
        // Concatenate all output texts
        content = data.output
            .filter(item => item.text)
            .map(item => item.text)
            .join('\n\n');
    }
    // Fallback to other common formats
    else if (data.choices && data.choices[0]?.message?.content) {
        content = data.choices[0].message.content;
    } else if (data.answer) {
        content = data.answer;
    } else if (data.content) {
        content = data.content;
    } else if (typeof data === 'string') {
        content = data;
    }

    // Extract citations if available
    if (data.citations) {
        citations = data.citations.map((c, i) => ({
            id: i + 1,
            title: c.title || c.name || '',
            url: c.url || c.link || '',
            snippet: c.snippet || c.description || '',
        }));
    } else if (data.sources) {
        citations = data.sources.map((s, i) => ({
            id: i + 1,
            title: s.title || '',
            url: s.url || '',
            snippet: s.snippet || '',
        }));
    }
    // You.com may include citations inline in the text - we can extract URLs
    else if (content && content.includes('http')) {
        // Try to extract URLs from markdown-style links [text](url)
        const urlMatches = content.matchAll(/\[([^\]]*)\]\((https?:\/\/[^)]+)\)/g);
        for (const match of urlMatches) {
            citations.push({
                id: citations.length + 1,
                title: match[1] || 'Source',
                url: match[2],
                snippet: '',
            });
        }
    }

    return {
        content,
        citations,
        raw: data, // Keep raw for debugging
    };
}

/**
 * Search using You.com Search API (legacy endpoint for compatibility).
 */
async function search({ query, count = 5, safesearch = 'moderate' }) {
    const requestId = uuidv4();
    const startTime = Date.now();

    const apiKey = getApiKey();
    if (!apiKey) {
        return {
            ok: false,
            results: [],
            provider_status: 'missing_key',
        };
    }

    const params = new URLSearchParams({
        q: query,
        num: count.toString(),
        safesearch,
    });

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        const response = await fetch(`${YOU_API_BASE}/search?${params}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${apiKey}`,
                'Accept': 'application/json',
            },
            signal: controller.signal,
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            return {
                ok: false,
                results: [],
                provider_status: 'error',
                error: `Search failed: ${response.status}`,
            };
        }

        const data = await response.json();

        console.log(JSON.stringify({
            request_id: requestId,
            action: 'you_search',
            latency_ms: Date.now() - startTime,
            results_count: data.results?.length || 0,
        }));

        return {
            ok: true,
            results: (data.results || data.hits || []).map(r => ({
                title: r.title || r.name || '',
                url: r.url || r.link || '',
                snippet: r.snippet || r.description || '',
                source: r.source || extractDomain(r.url),
            })),
            provider_status: 'ok',
        };

    } catch (error) {
        console.error(JSON.stringify({
            request_id: requestId,
            action: 'you_search_error',
            error: error.message,
        }));

        return {
            ok: false,
            results: [],
            provider_status: 'failed',
            error: error.message,
        };
    }
}

/**
 * Extract domain from URL.
 */
function extractDomain(url) {
    if (!url) return null;
    try {
        return new URL(url).hostname.replace('www.', '');
    } catch {
        return null;
    }
}

module.exports = {
    runAgent,
    search,
    isConfigured,
    getApiKey: () => !!getApiKey(), // Only return boolean, never the actual key
};
