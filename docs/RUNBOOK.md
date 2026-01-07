# Runbook

## What this runbook is

This document describes how to run the system locally (Docker + local frontend), how to run tests, and common troubleshooting steps.

## Prerequisites

- Docker Desktop + Docker Compose
- Node.js + npm (frontend runs locally)
- Git

## Ports (default)

- **Backend API (nginx)**: `http://localhost:8000`
- **Frontend (Nuxt dev)**: `http://localhost:3000`
- **MySQL (host access)**: `localhost:3307`
- **Redis (host access)**: `localhost:6380`
- **Mailpit UI**: `http://localhost:8025`
- **Mailpit SMTP**: `localhost:1025`

## Local development (recommended)

### 1) Start backend stack (Docker)

From the repository root:

```bash
docker-compose up -d --build
```

If you use Docker Compose v2:

```bash
docker compose up -d --build
```

### 2) Backend first-time setup

```bash
docker-compose exec app composer install
docker-compose exec app cp .env.docker .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

### 3) Start frontend (local)

```bash
cd frontend
npm install
npm run dev
```

## Health check

- `GET /api/health`
  - Confirms database connectivity
  - Confirms required tables exist
  - Confirms cache is writable/readable (set/get/forget probe)

## Running tests (PHPUnit)

Tests are intended to run in Docker.

```bash
docker-compose exec app php artisan test
```

### Test database

- Tests are configured to use a dedicated MySQL database:
  - `DB_DATABASE=hospital_ticketing_testing`

The MySQL init script creates this database on **first container startup**. If your MySQL container/volume already existed before this was added, the init script won’t re-run.

To create the testing database manually:

```bash
docker-compose exec mysql mysql -uroot -prootsecret -e "CREATE DATABASE IF NOT EXISTS hospital_ticketing_testing; GRANT ALL PRIVILEGES ON hospital_ticketing_testing.* TO 'hospital'@'%'; FLUSH PRIVILEGES;"
```

## Cache / Redis

The backend is configured to use Redis for cache (and Spatie permission caching). If you change cache env vars, clear config/cache in the container:

```bash
docker-compose exec app php artisan optimize:clear
```

## Useful operational commands

### View logs

```bash
docker-compose logs -f app
```

### Run migrations / seed

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

### API sanity script

From repository root (PowerShell):

```powershell
.\scripts\api-sanity.ps1
```

## Troubleshooting

### Docker commands fail (Docker Desktop not running)

If you see errors like “open `//./pipe/...dockerDesktop...`: The system cannot find the file specified”, start Docker Desktop and ensure the Linux engine is running.

### MySQL database not created

If `hospital_ticketing_testing` does not exist, create it manually (see the **Test database** section above).

### Configuration changes not taking effect

Laravel may use cached configuration. Run:

```bash
docker-compose exec app php artisan optimize:clear
```

### Rebuilding containers

```bash
docker-compose up -d --build
```

### Resetting Docker volumes (destructive)

This will delete the MySQL/Redis volumes and all stored data.

```bash
docker-compose down -v
```

### Frontend: "#app-manifest" import error

If Nuxt fails to start with an error about `#app-manifest`, ensure this is in `frontend/nuxt.config.ts`:

```typescript
export default defineNuxtConfig({
  experimental: {
    appManifest: false,
  },
  // ... rest of config
})
```

Then clean and restart:

```bash
cd frontend
rmdir /s /q .nuxt .output  # Windows
# or
rm -rf .nuxt .output       # Linux/Mac
npm run dev
```

### Frontend: Navigation buttons not working

If Login/Register buttons don't navigate:

1. Check browser console for JavaScript errors
2. Ensure no invisible overlay is blocking clicks (modals, backdrops)
3. Verify routes exist in `frontend/pages/auth/`
4. Clear browser cache and Nuxt cache (`.nuxt` folder)

## Environment Variables

### Backend (.env)

Required variables (copy from `.env.docker`):

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | MySQL host | `mysql` (Docker) |
| `DB_DATABASE` | Database name | `hospital_ticketing` |
| `REDIS_HOST` | Redis host | `redis` (Docker) |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend domains | `localhost:3000` |

### Gemini AI (Optional)

To enable AI-powered chatbot symptom analysis:

```env
GEMINI_API_KEY=your_actual_key_here
CHATBOT_AI_ENABLED=true
```

> **IMPORTANT**: Never commit API keys to version control. The chatbot works without AI using rule-based keyword matching.

### Frontend

| Variable | Description | Default |
|----------|-------------|---------|
| `NUXT_PUBLIC_API_BASE` | Backend API URL | `http://localhost:8000/api` |
