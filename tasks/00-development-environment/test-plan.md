# Development Environment Test Plan

## Clean Bootstrap

1. Clone/copy without `.env`, dependency directories, volumes, or build assets.
2. Follow only documented setup commands.
3. Confirm containers become healthy, key generation/migration is deliberate, and `/up` succeeds through Nginx.
4. Confirm Laravel/Inertia root page and Vite HMR work.

## Runtime Matrix

- PHP reports 8.4+ and Composer platform check passes.
- PostgreSQL connection, fresh migration, rollback strategy, and persistent restart.
- Backend tests on intended SQLite-fast path (if retained) and mandatory PostgreSQL integration path.
- Node clean install, development HMR, and production build.
- S3 synthetic upload, temporary/public delivery according to selected strategy, and deletion.

## Security/Operations

- No tracked secrets or usable credentials in images/history/example env.
- Only necessary host ports exposed; PostgreSQL need not be publicly reachable.
- Hidden files denied and Laravel document root is `public/`.
- Correct client IP/scheme under trusted Cloudflare/Nginx path without trusting arbitrary proxies.
- Secure cookie behavior under HTTPS.
- Backup, restore, deploy health check, and rollback rehearsed on disposable environment.

## Exit Criteria

- Two clean setup runs succeed from documentation.
- PostgreSQL and S3 integration smoke tests pass outside production.
- Production assets load with Vite server absent.
- Environment choices and unresolved provider-specific values are explicitly documented.

