# Development Environment Overview

## Goal

Make the declared MVP stack reproducible for development and production validation before feature integration: PHP 8.4+, Laravel, Node/Vite, PostgreSQL, S3 configuration, Nginx, and documented Cloudflare/VPS boundaries.

This is an enabling track, not product functionality. It can run in parallel with frontend foundation and must stay minimal.

## Current Gap

- Composer currently permits PHP `^8.3`; target is PHP 8.4+.
- `.env.example` and PHPUnit default to SQLite; target application database is PostgreSQL.
- No Docker, Compose, or Nginx files exist in this directory.
- Laravel contains an S3 driver configuration path, but the MVP environment contract is not documented or verified.

## Minimal Local Topology

- PHP 8.4 application container with required extensions and Composer.
- Nginx container serving Laravel `public/` and proxying PHP-FPM.
- PostgreSQL container with a named development volume and health check.
- Node container or documented host command for Vite. Prefer the smallest setup that gives reliable HMR across the team's operating systems.
- Optional Mailpit only if it materially improves auth email testing; do not add Redis, queues, workers, LocalStack, or monitoring before a feature requires them.

## Environment Rules

- Commit only `.env.example` placeholders, never secrets.
- Use separate AWS buckets/prefixes and credentials per environment with least privilege.
- Use PostgreSQL in at least one automated/integration test path. Fast SQLite unit/portable tests may remain.
- Production TLS terminates at Cloudflare/Nginx according to one documented trust model; Laravel trusted proxies and secure cookies must match it.

## Acceptance Criteria

- A new developer can build, migrate, run Vite, access Laravel through Nginx, and run tests using documented commands.
- Runtime reports PHP 8.4+ and required extensions.
- Application integration path uses PostgreSQL, not a silent SQLite fallback.
- S3 credentials/bucket/CORS/visibility requirements are documented without secrets.
- Production handoff states responsibilities for VPS, Nginx, Cloudflare, TLS, persistent data, backups, and deploy commands without building a complex platform.

