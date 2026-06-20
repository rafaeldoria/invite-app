# Backend Task: PHP, PostgreSQL, S3, and Web Runtime

## Objective

Define a reproducible backend runtime and safe environment contract for local development and production deployment.

## Implementation Steps

1. Raise Composer's PHP constraint to the agreed PHP 8.4+ range only after confirming Laravel/dependencies install on that runtime.
2. Add a production-like PHP-FPM Dockerfile with required extensions (at minimum PDO PostgreSQL and image/file requirements actually used). Use pinned major/minor base versions and a non-root runtime where practical.
3. Add Compose services for PHP, Nginx, and PostgreSQL with health checks, named volume, explicit internal networking, and no committed credentials. Avoid `latest` tags.
4. Add development commands/entrypoint only for deterministic setup; do not run destructive migrations automatically against arbitrary environments.
5. Configure `.env.example` for PostgreSQL placeholders and preserve a deliberate testing override. Prevent missing PostgreSQL values from silently connecting to an unintended database.
6. Add a PostgreSQL test/integration command or CI matrix path. Verify migrations, foreign keys, aggregates, timestamps/timezones, and constraints there.
7. Document Laravel `s3` variables: region, bucket, endpoint/path-style only when needed, URL/temporary URL strategy, CORS origins, object prefix, and least-privilege actions. Do not provide real keys.
8. Add Nginx configs for local and production concerns: `public/` root, front controller, static asset caching, upload limit above the 5 MiB app limit, PHP timeout, denied hidden files, and forwarded HTTPS headers.
9. Document trusted proxy/host configuration, secure session cookies, app URL, scheduler/queue status (none required initially), cache/config optimization, storage permissions, and health endpoint.
10. Document VPS deployment/rollback and PostgreSQL backup/restore at a practical command/runbook level. Cloudflare owns DNS/proxy/edge TLS; origin TLS mode must be Full (strict) with a valid origin certificate.

## Acceptance Criteria

- Clean Docker build/start and Laravel health response through Nginx.
- PostgreSQL health gates migrations/application start appropriately.
- Composer platform resolves on PHP 8.4+.
- S3 and proxy contracts support event images and secure auth cookies.
- Production runbook includes backup-before-migrate, deploy, health check, rollback, and restore verification.
- No secret, production hostname assumption, Redis, Kubernetes, or unnecessary service is introduced.

## Task Test Plan

- Build from a clean cache, start stack, install dependencies, migrate, seed synthetic data, run tests.
- Restart containers and confirm PostgreSQL data persists.
- Run migration/test suite against PostgreSQL.
- Upload/read/delete a synthetic object in a non-production S3 prefix.
- Verify Nginx route handling, 6 MiB rejection path, hidden-file denial, forwarded HTTPS, and `/up`.
- Validate documented backup and restore on disposable data.

