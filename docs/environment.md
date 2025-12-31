# Environment Documentation

## Confirmed Local Environment

| Tool | Version | Location | Notes |
|------|---------|----------|-------|
| Node.js | v24.8.0 | System PATH | Frontend runtime |
| npm | v11.6.0 | System PATH | Package manager |
| nuxi | v3.31.3 | npm global | Nuxt CLI |
| Docker | 28.3.2 | System PATH | Container runtime |
| Docker Compose | v2.39.1 | Bundled with Docker | Multi-container orchestration |
| PHP | 8.0.30 | C:\xampp\php\php.exe | XAMPP only - NOT for Laravel dev |

## Development Strategy

### Frontend (Local)
- Runs directly on local Node.js
- Uses npm for package management
- Hot reload via Nuxt dev server
- Port: 3000 (default)

### Backend (Docker)
- Laravel runs in Docker container (PHP 8.2+)
- PostgreSQL/MySQL in container
- Redis in container
- Port: 8000 (mapped)

## Docker Services

```yaml
services:
  app:        # Laravel PHP-FPM
  nginx:      # Web server
  db:         # PostgreSQL or MySQL
  redis:      # Cache/Queue/Sessions
```

## Version Policy

- No version pinning in planning docs
- Use stable compatible versions
- Document actual versions here after setup
- Lock files (composer.lock, package-lock.json) commit to repo

## Actual Versions (Updated During Setup)

| Component | Version | Date Verified |
|-----------|---------|---------------|
| Nuxt | TBD | - |
| Vue | TBD | - |
| TypeScript | TBD | - |
| Tailwind CSS | TBD | - |
| Laravel | TBD | - |
| PHP (Docker) | TBD | - |
| PostgreSQL | TBD | - |
| Redis | TBD | - |

---

*Last updated: 2025-12-30*
