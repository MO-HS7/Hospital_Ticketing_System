# Hospital Ticketing System Backend

Laravel API backend for the Hospital Ticketing & Booking System.

## Requirements

- Docker & Docker Compose
- No local PHP required (runs in Docker)

## Quick Start

```bash
# From project root
docker-compose up -d

# First-time setup
docker-compose exec app composer install
docker-compose exec app cp .env.docker .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed

# Backend available at http://localhost:8000
# Mailpit UI at http://localhost:8025
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/health` | Health check |
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/register` | Register (patients) |
| GET | `/api/me` | Get current user |
| GET | `/api/tickets` | List tickets |
| POST | `/api/tickets` | Create ticket |
| GET | `/api/departments` | List departments |
| GET | `/api/admin/metrics` | Admin dashboard |

## Useful Commands

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Run tests
docker-compose exec app php artisan test

# Create controller
docker-compose exec app php artisan make:controller Api/MyController

# Clear cache
docker-compose exec app php artisan optimize:clear
```

## Structure

```
backend/
├── app/
│   ├── Http/Controllers/Api/
│   ├── Models/
│   ├── Policies/
│   └── Services/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── tests/
```
