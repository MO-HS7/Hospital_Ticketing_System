# Hospital Ticketing & Booking System

## Quick Start

### Prerequisites
- Node.js v18+
- Docker & Docker Compose
- Git

### Development Setup

```bash
# 1. Clone repo
git clone <repo-url>
cd Hospital_Ticketing_Booking_System

# 2. Start backend (Docker)
docker-compose up -d

# 3. Start frontend (local)
cd frontend
npm install
npm run dev
```

### URLs
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000/api
- Database: localhost:5432 (PostgreSQL)

## Project Structure

```
├── frontend/          # Nuxt + Vue + TypeScript
├── backend/           # Laravel API (Docker)
├── docker-compose.yml # Backend services
├── docs/              # Documentation
│   └── environment.md # Version tracking
└── README.md
```

## Documentation

- [Environment Setup](./docs/environment.md)
- [Implementation Plan](./.gemini/brain/implementation_plan.md)

## Tech Stack

- **Frontend**: Nuxt, Vue, TypeScript, Tailwind CSS
- **Backend**: Laravel, Sanctum, spatie/permission
- **Database**: PostgreSQL (or MySQL)
- **Cache**: Redis

## Features

- Multi-portal: Admin, Patient, Staff (Doctor/Maintenance/Reception)
- Bilingual: Arabic/English with RTL/LTR support
- Dark mode with system preference detection
- Chatbot for department suggestions and ticket creation
- SLA tracking with overdue detection
- Audit trail for ticket events

## License

All rights reserved.
