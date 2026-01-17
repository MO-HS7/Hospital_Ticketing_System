# Hospital Agent

Express.js proxy service for You.com API integration with the hospital chatbot.

## Features

- **POST /agent/run** - Direct You.com v1/agents/runs API proxy
- **POST /agent/answer** - Structured chatbot response with citations
- **POST /agent/search** - Web search
- **GET /agent/health** - Health check

## Security

- API key is read from environment only (`YOU_API_KEY`)
- Keys are never logged or exposed
- All requests have unique request IDs for tracing

## Setup

```bash
# Install dependencies
npm install

# Copy environment file
cp .env.example .env

# Edit .env and add your API key
# YOU_API_KEY=your-key-here

# Start development server
npm run dev

# Or production
npm start
```

## Endpoints

### GET /agent/health

```json
{
  "ok": true,
  "service": "hospital-agent",
  "provider": "you",
  "api_key_configured": true,
  "timestamp": "2026-01-14T00:00:00.000Z"
}
```

### POST /agent/run

Direct proxy to You.com v1/agents/runs.

```bash
curl -X POST http://localhost:3100/agent/run \
  -H "Content-Type: application/json" \
  -d '{"query": "What are good sleep tips?", "locale": "en"}'
```

### POST /agent/answer

Structured chatbot response.

```bash
curl -X POST http://localhost:3100/agent/answer \
  -H "Content-Type: application/json" \
  -d '{"query": "Sleep tips", "locale": "en", "intent": "medical_question"}'
```

Response:
```json
{
  "provider": "you",
  "intent": "medical_question",
  "reply": "Here are some tips...",
  "citations": [{"id": 1, "title": "...", "url": "..."}],
  "should_offer_booking": false,
  "ui": {"type": "none", "items": []},
  "provider_status": "ok"
}
```

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `YOU_API_KEY` | Yes | Your You.com API key |
| `PORT` | No | Server port (default: 3100) |
| `AGENT_TIMEOUT_MS` | No | API timeout in ms (default: 10000) |

## Logging

All requests are logged with:
- request_id
- method, path, status
- latency_ms
- No sensitive data (keys never logged)
