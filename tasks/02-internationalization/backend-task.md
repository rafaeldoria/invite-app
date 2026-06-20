# Backend Task: Laravel Localization

## Objective

Resolve and persist supported locales, localize server messages, and expose the active locale to Inertia.

## Prerequisites

- Locale contract in `overview.md` accepted.
- Foundation shared-prop contract available.

## Implementation Steps

1. Set application fallback/default behavior to `pt-BR` without storing localized domain values.
2. Add a locale middleware to resolve only the allowlisted `pt-BR`/`en-US` values in the documented precedence order and call `App::setLocale` per request.
3. Add a POST/PATCH locale-switch endpoint protected by CSRF and rate limits appropriate to a preference action.
4. Persist guest locale in session/cookie. If adding a user locale column is chosen, add a constrained string column and update it for authenticated users; otherwise explicitly defer database persistence and use session/cookie for all users.
5. Share the canonical active locale through Inertia as `locale`.
6. Create Laravel catalogs for validation, password reset, authentication, email verification, pagination, and server flash messages in both locales.
7. Use locale-aware queued mail behavior if auth emails are queued later; current synchronous mail must still use the request/user locale.
8. Keep logs and exception internals in English; localization is for user-facing output.

## Acceptance Criteria

- Resolution precedence and allowlist behavior match the overview.
- Explicit choice persists across logout/login according to the selected persistence strategy.
- All server-side validation and auth flow messages exist in both locales.
- Locale switch is CSRF protected and cannot load arbitrary paths or locale names.
- Inertia receives exactly one canonical locale value.
- Automated tests restore global locale state between cases.

## Task Test Plan

- Test default, supported browser header, explicit switch, persistence, authenticated behavior, and unsupported values.
- Submit invalid forms in both locales and assert representative translated messages.
- Test password/verification mail locale once authentication exists.
- Run backend regression commands.

