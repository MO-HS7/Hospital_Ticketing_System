# Decisions Log

## 2026-01-01: Use database cache store

### Context

The backend relies on caching at runtime (including permissions caching via spatie/laravel-permission). During end-to-end API verification, the application failed with a database error due to missing cache tables.

### Decision

We use the database cache store.

### Consequences

- The `cache` and `cache_locks` tables are mandatory.
- The migration `backend/database/migrations/0001_01_01_000007_create_cache_table.php` must be applied in all environments.
- If these tables are missing, the API can fail at runtime when caching is used.
