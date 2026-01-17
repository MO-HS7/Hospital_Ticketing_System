/**
 * Run Endpoint - Direct You.com API Proxy
 * 
 * POST /agent/run
 * 
 * Proxies requests to You.com v1/agents/runs API.
 * Used for direct AI queries from Laravel backend.
 */

const express = require('express');
const router = express.Router();
const { runAgent } = require('../services/youApi');

/**
 * POST /agent/run
 * 
 * Body: {
 *   query: "user's question",
 *   locale: "en" | "ar",
 *   session_context: { ... } // optional
 * }
 * 
 * Returns:
 * {
 *   ok: true|false,
 *   data?: { content, citations },
 *   error?: "string",
 *   request_id: "uuid",
 *   latency_ms: number,
 *   provider: "you",
 *   provider_status: "ok|error|missing_key|failed"
 * }
 */
router.post('/run', async (req, res) => {
    const { query, locale = 'en', session_context = {} } = req.body;

    if (!query) {
        return res.status(400).json({
            ok: false,
            error: 'query is required',
            request_id: req.requestId,
        });
    }

    try {
        const result = await runAgent({
            query,
            locale,
            sessionContext: session_context,
        });

        // Set appropriate status code
        const statusCode = result.ok ? 200 : (result.status || 500);

        res.status(statusCode).json({
            ...result,
            request_id: req.requestId,
        });

    } catch (error) {
        console.error('Run endpoint error:', error.message);
        res.status(500).json({
            ok: false,
            error: 'Internal server error',
            request_id: req.requestId,
            provider: 'you',
            provider_status: 'failed',
        });
    }
});

module.exports = router;
