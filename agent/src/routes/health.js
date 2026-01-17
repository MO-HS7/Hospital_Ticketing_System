/**
 * Health Endpoint
 * 
 * GET /health
 */

const express = require('express');
const router = express.Router();
const { isConfigured } = require('../services/youApi');

router.get('/health', (req, res) => {
    res.json({
        ok: true,
        service: 'hospital-agent',
        provider: 'you',
        api_key_configured: isConfigured(),
        timestamp: new Date().toISOString(),
        request_id: req.requestId,
    });
});

module.exports = router;
