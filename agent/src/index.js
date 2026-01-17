/**
 * Hospital Agent - Express.js Service
 * 
 * Proxy service for You.com API integration.
 * Handles medical Q&A, search, and chatbot responses.
 * 
 * SECURITY: API keys never logged or exposed.
 */

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const { v4: uuidv4 } = require('uuid');
const { isConfigured } = require('./services/youApi');

const app = express();
const PORT = process.env.PORT || 3100;

// Middleware
app.use(cors());
app.use(express.json());

// Request ID middleware
app.use((req, res, next) => {
    req.requestId = uuidv4();
    res.setHeader('X-Request-ID', req.requestId);
    next();
});

// Log level: 'debug' = verbose, otherwise minimal
const LOG_LEVEL = process.env.LOG_LEVEL || 'info';
const isDebugMode = () => LOG_LEVEL === 'debug';

// Request logging (only in debug mode to reduce noise)
app.use((req, res, next) => {
    if (!isDebugMode()) return next();

    const start = Date.now();
    res.on('finish', () => {
        console.log(JSON.stringify({
            request_id: req.requestId,
            method: req.method,
            path: req.path,
            status: res.statusCode,
            latency_ms: Date.now() - start,
        }));
    });
    next();
});

// Import routes
const healthRouter = require('./routes/health');
const searchRouter = require('./routes/search');
const answerRouter = require('./routes/answer');
const runRouter = require('./routes/run');

// Mount routes (NO /agent prefix - Laravel calls directly: YOU_AGENT_URL + /answer)
app.use('/', healthRouter);
app.use('/', searchRouter);
app.use('/', answerRouter);
app.use('/', runRouter);

// Root health check
app.get('/', (req, res) => {
    res.json({
        service: 'hospital-agent',
        version: '1.0.0',
        status: 'ok',
    });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        error: 'Not found',
        request_id: req.requestId,
    });
});

// Error handler
app.use((err, req, res, next) => {
    console.error('Unhandled error:', err.message);
    res.status(500).json({
        error: 'Internal server error',
        request_id: req.requestId,
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`Express Agent running on port ${PORT}`);
    console.log(`API Key configured: ${isConfigured()}`);
    console.log(`Endpoints:`);
    console.log(`  GET  /health - Health check`);
    console.log(`  POST /run    - Direct You.com API proxy`);
    console.log(`  POST /answer - Structured chatbot response`);
    console.log(`  POST /search - Web search`);
});

module.exports = app;
