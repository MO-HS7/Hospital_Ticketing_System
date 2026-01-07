# Project Overview

## Purpose

Hospital Ticketing & Booking System is a multi-portal application for handling patient requests and hospital staff workflows.

- **Patients** create and track tickets (appointments, maintenance, etc.).
- **Staff** (doctor/maintenance/reception) accept and complete tickets based on role and ticket type.
- **Admins** manage users, departments, and review operational metrics/audit trails.

## High-Level Architecture

- **Frontend**: `frontend/` (Nuxt + Vue + TypeScript)
- **Backend API**: `backend/` (Laravel 11, REST API)
- **Database**: MySQL (Docker)
- **Cache/Session**: Redis (Docker)

## Authentication & Authorization

- **Auth**: Laravel Sanctum (API tokens)
- **RBAC**: `spatie/laravel-permission`

### Roles

- **admin**
- **patient**
- **doctor**
- **maintenance**
- **reception**

### Key Authorization Rules (examples)

- Patients can create tickets and add notes.
- Staff can accept/complete tickets only when permitted by role.
- Ticket type restrictions are enforced via policy logic (e.g., doctors for appointment tickets, maintenance for maintenance tickets).

## Core Backend Domains

- **Departments**
  - CRUD for departments
  - Localized names/descriptions (English/Arabic)
- **Tickets**
  - Create, view, update
  - Accept/complete lifecycle
  - Notes and event/audit trail
- **Health**
  - `GET /api/health` checks:
    - DB connectivity
    - required tables
    - cache operability (set/get/forget probe)

## Data Model (Conceptual)

- `users`
- `departments`
- `tickets`
- `ticket_notes`
- `ticket_events`
- `personal_access_tokens` (Sanctum)
- Permission tables (Spatie): `roles`, `permissions`, `model_has_roles`, etc.

## Repository Structure

```text
.
├── backend/            # Laravel API
├── frontend/           # Nuxt app
├── docker/             # Container configs (MySQL init, PHP ini, nginx)
├── docker-compose.yml  # Local stack orchestration
├── docs/               # Project documentation
└── scripts/            # Utility scripts (e.g. API sanity)
```

### Backend Structure (high level)

```text
backend/
├── app/Http/Controllers/Api/  # API controllers
├── app/Models/                # Eloquent models
├── app/Policies/              # Authorization policies
├── database/migrations/       # Schema
├── database/seeders/          # Role/user/department seed data
├── routes/api.php             # API routes
└── tests/                     # PHPUnit Feature/Unit tests
```

## Testing

- PHPUnit Feature tests live in `backend/tests/Feature`.
- Tests are intended to run in Docker against a dedicated MySQL database (e.g. `hospital_ticketing_testing`) with Redis available.

## Portals

### Patient Portal
- View and track tickets
- Create new appointment tickets
- AI-powered symptom chatbot for department suggestions
- View ticket details and status updates

### Doctor Portal
- View assigned appointment tickets
- Accept and complete tickets
- Report maintenance issues (creates maintenance tickets)

### Maintenance Portal
- View assigned maintenance tickets
- Accept and complete tickets
- Add work notes

### Reception Portal
- Create tickets on behalf of patients
- View created tickets

### Admin Portal
- Dashboard with KPI metrics
- Departments management (full CRUD)
- Users management (create staff, activate/deactivate)
- Audit log (ticket events timeline)

## Chatbot Behavior

The patient chatbot helps guide users to the appropriate department based on their symptoms.

### How it works

1. **User describes symptoms** - The patient types their symptoms in natural language (English or Arabic)
2. **Analysis** - The system analyzes the input using:
   - **AI Mode** (if enabled): Google Gemini 2.0 Flash for intelligent symptom analysis
   - **Rule-based Mode** (fallback): Keyword matching for common symptoms
3. **Department suggestions** - Returns 1-3 relevant departments from the database
4. **Booking action** - User can click a department to navigate to ticket creation with department pre-filled

### Configuration

| Environment Variable | Default | Description |
|---------------------|---------|-------------|
| `CHATBOT_AI_ENABLED` | `false` | Enable/disable AI integration |
| `GEMINI_API_KEY` | (empty) | Google Gemini API key |
| `GEMINI_MODEL` | `gemini-2.0-flash` | Gemini model to use |

### Security Notes

- AI is disabled by default - the chatbot works without any API keys using rule-based matching
- Patient symptom text is not logged when using AI to protect privacy
- Rate limiting should be applied to the `/api/chatbot/analyze` endpoint
