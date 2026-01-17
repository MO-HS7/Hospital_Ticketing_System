/**
 * Answer Endpoint - Structured Medical Q&A with Citations
 * 
 * POST /answer
 * 
 * Returns normalized structured response for chatbot consumption.
 * Uses You.com API for internet-grounded answers.
 */

const express = require('express');
const router = express.Router();
const { runAgent, search, isConfigured } = require('../services/youApi');

/**
 * POST /answer
 * 
 * Body: {
 *   query: "user's question",
 *   locale: "en" | "ar",
 *   intent: "medical_question|medical_news|identity|hospital_faq",
 *   mode: "qa|booking",
 *   context?: {}
 * }
 * 
 * Returns normalized chatbot response:
 * {
 *   provider: "you",
 *   intent: "...",
 *   reply: "string (with inline citations)",
 *   citations: [{ id, title, url }],
 *   should_offer_booking: boolean,
 *   ui: { type: "none|chips|cards", items: [] },
 *   provider_status: "ok|degraded|failed|missing_key"
 * }
 */
router.post('/answer', async (req, res) => {
    const {
        query,
        locale = 'en',
        intent = 'medical_question',
        mode = 'qa',
        context = {}
    } = req.body;

    const isArabic = locale === 'ar';

    if (!query) {
        return res.status(400).json({
            ok: false,
            error: 'query is required',
            request_id: req.requestId,
        });
    }

    try {
        // Handle identity intent locally (no API call needed)
        if (intent === 'identity') {
            return res.json(buildIdentityResponse(isArabic, req.requestId));
        }

        // Check if API is configured
        if (!isConfigured()) {
            return res.json(buildFallbackResponse(query, intent, isArabic, req.requestId, 'missing_key'));
        }

        // Call You.com API
        const result = await runAgent({
            query: buildSearchQuery(query, intent, isArabic),
            locale,
            sessionContext: context,
        });

        // Log result (no secrets)
        console.log(JSON.stringify({
            request_id: req.requestId,
            action: 'answer_generated',
            intent,
            locale,
            ok: result.ok,
            latency_ms: result.latency_ms,
        }));

        if (!result.ok) {
            // Return fallback on API failure
            return res.json(buildFallbackResponse(query, intent, isArabic, req.requestId, result.provider_status));
        }

        // Build response from You.com result
        const response = buildResponseFromApi(result.data, intent, isArabic, req.requestId);

        res.json(response);

    } catch (error) {
        console.error('Answer endpoint error:', error.message);
        res.json(buildFallbackResponse(query, intent, isArabic, req.requestId, 'failed'));
    }
});

/**
 * Build search query based on intent.
 */
function buildSearchQuery(query, intent, isArabic) {
    const lang = isArabic ? 'Arabic' : 'English';

    switch (intent) {
        case 'medical_news':
            return `Latest medical and health news: ${query}. Respond in ${lang}.`;
        case 'medical_question':
            return `Medical health advice question: ${query}. Provide accurate, sourced information. Respond in ${lang}.`;
        case 'hospital_faq':
            return `Hospital services FAQ: ${query}. Respond in ${lang}.`;
        default:
            return query;
    }
}

/**
 * Strip markdown formatting tokens while keeping readable text.
 * Removes: ####, **, *, backticks, blockquotes
 * Preserves: URLs, line breaks, list structure, emoji
 */
function stripMarkdown(text) {
    if (!text) return '';

    let result = text;

    // Remove heading markers (#### Header -> Header)
    result = result.replace(/^#{1,6}\s*/gm, '');

    // Remove bold/italic markers (** ** and * *)
    result = result.replace(/\*\*([^*]+)\*\*/g, '$1');
    result = result.replace(/\*([^*]+)\*/g, '$1');

    // Remove backticks (inline code)
    result = result.replace(/`([^`]+)`/g, '$1');

    // Remove blockquotes (> text -> text) 
    result = result.replace(/^>\s*/gm, '');

    // Preserve list bullets but clean up
    result = result.replace(/^\s*[-*+]\s+/gm, '• ');

    // Clean up excessive whitespace while preserving paragraph breaks
    result = result.replace(/\n{3,}/g, '\n\n');

    return result.trim();
}

/**
 * Build response from You.com API result.
 */
function buildResponseFromApi(data, intent, isArabic, requestId) {
    let reply = data.content || '';
    const citations = data.citations || [];

    // Strip markdown formatting for clean UI rendering
    reply = stripMarkdown(reply);

    // Add citation markers if we have sources
    if (citations.length > 0 && !reply.includes('[1]')) {
        // Append citation references
        const sourceList = citations.slice(0, 3).map((c, i) => `[${i + 1}]`).join(' ');
        reply += `\n\nSources: ${sourceList}`;
    }

    // Add disclaimer for medical content
    if (intent === 'medical_question' || intent === 'medical_news') {
        const disclaimer = isArabic
            ? '\n\n⚠️ هذه معلومات عامة للتثقيف الصحي. استشر طبيباً للحصول على نصيحة شخصية.'
            : '\n\n⚠️ This is general health information. Consult a doctor for personalized advice.';
        reply += disclaimer;
    }

    // Determine if booking should be offered
    const shouldOfferBooking = detectNeedsBooking(data.content);

    return {
        provider: 'you',
        intent,
        reply,
        citations: citations.map(c => ({
            id: c.id,
            title: c.title,
            url: c.url,
        })),
        should_offer_booking: shouldOfferBooking,
        ui: buildUI(intent, isArabic),
        provider_status: 'ok',
        request_id: requestId,
    };
}

/**
 * Build identity response (local, no API).
 */
function buildIdentityResponse(isArabic, requestId) {
    const reply = isArabic
        ? `أنا **مسار** 🏥، مساعدك الطبي الذكي في المستشفى.

**ما يمكنني مساعدتك فيه:**
• الإجابة على الأسئلة الطبية العامة (مع مصادر موثوقة من الإنترنت)
• تقديم آخر الأخبار الصحية
• مساعدتك في حجز موعد مع طبيب
• الإجابة عن أسئلة حول خدمات المستشفى

⚠️ تذكر: أنا لست طبيباً ولا أقدم تشخيصاً أو وصفات طبية.

كيف يمكنني مساعدتك اليوم؟`
        : `I'm **Masar** 🏥, your intelligent hospital assistant.

**What I can help you with:**
• Answer general medical questions (with reliable internet sources)
• Provide the latest health news
• Help you book an appointment with a doctor
• Answer questions about hospital services

⚠️ Remember: I'm not a doctor and don't provide diagnoses or prescriptions.

How can I help you today?`;

    return {
        provider: 'local',
        intent: 'identity',
        reply,
        citations: [],
        should_offer_booking: false,
        ui: {
            type: 'quick_replies',
            items: isArabic
                ? [{ id: 'news', label: 'أخبار طبية' }, { id: 'book', label: 'حجز موعد' }]
                : [{ id: 'news', label: 'Medical News' }, { id: 'book', label: 'Book Appointment' }],
        },
        provider_status: 'ok',
        request_id: requestId,
    };
}

/**
 * Build fallback response when API unavailable.
 */
function buildFallbackResponse(query, intent, isArabic, requestId, providerStatus) {
    let reply = '';

    if (providerStatus === 'missing_key') {
        reply = isArabic
            ? 'عذراً، خدمة الذكاء الاصطناعي غير متوفرة حالياً. يمكنني مساعدتك في حجز موعد.'
            : 'Sorry, AI service is currently unavailable. I can help you book an appointment.';
    } else {
        reply = isArabic
            ? 'عذراً، لم أتمكن من الوصول إلى مصادر موثوقة حالياً. هل تريد المحاولة مرة أخرى أو حجز موعد مع طبيب؟'
            : 'Sorry, I couldn\'t access reliable sources right now. Would you like to try again or book an appointment with a doctor?';
    }

    return {
        provider: 'fallback',
        intent,
        reply,
        citations: [],
        should_offer_booking: true,
        ui: {
            type: 'quick_replies',
            items: isArabic
                ? [{ id: 'retry', label: 'حاول مرة أخرى' }, { id: 'book', label: 'حجز موعد' }]
                : [{ id: 'retry', label: 'Try Again' }, { id: 'book', label: 'Book Appointment' }],
        },
        provider_status: providerStatus,
        request_id: requestId,
    };
}

/**
 * Build UI based on intent.
 */
function buildUI(intent, isArabic) {
    if (intent === 'medical_news') {
        return {
            type: 'chips',
            items: isArabic
                ? [
                    { id: 'heart', label: 'صحة القلب' },
                    { id: 'diabetes', label: 'السكري' },
                    { id: 'mental', label: 'الصحة النفسية' },
                ]
                : [
                    { id: 'heart', label: 'Heart Health' },
                    { id: 'diabetes', label: 'Diabetes' },
                    { id: 'mental', label: 'Mental Health' },
                ],
        };
    }

    return { type: 'none', items: [] };
}

/**
 * Detect if query suggests user needs booking.
 */
function detectNeedsBooking(content) {
    if (!content) return false;
    const lc = content.toLowerCase();
    const indicators = ['pain', 'symptom', 'fever', 'consult', 'doctor', 'ألم', 'طبيب', 'حرارة'];
    return indicators.some(ind => lc.includes(ind));
}

module.exports = router;
