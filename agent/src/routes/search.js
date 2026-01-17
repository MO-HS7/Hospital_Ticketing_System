/**
 * Search Endpoint
 * 
 * POST /agent/search
 * 
 * Web search via You.com API.
 */

const express = require('express');
const router = express.Router();
const { search, isConfigured } = require('../services/youApi');

/**
 * POST /agent/search
 * 
 * Body: { query: "search terms", count?: 5 }
 */
router.post('/search', async (req, res) => {
    const { query, count = 5 } = req.body;

    if (!query) {
        return res.status(400).json({
            ok: false,
            error: 'query is required',
            request_id: req.requestId,
        });
    }

    if (!isConfigured()) {
        return res.json({
            ok: false,
            results: [],
            error: 'API key not configured',
            provider_status: 'missing_key',
            request_id: req.requestId,
        });
    }

    try {
        const result = await search({ query, count });

        res.json({
            ...result,
            request_id: req.requestId,
        });

    } catch (error) {
        console.error('Search error:', error.message);
        res.status(500).json({
            ok: false,
            results: [],
            error: 'Search failed',
            request_id: req.requestId,
        });
    }
});

module.exports = router;
