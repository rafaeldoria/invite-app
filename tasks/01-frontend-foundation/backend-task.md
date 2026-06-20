# Backend Task: Inertia Application Shell

## Objective

Define only the Laravel/Inertia support needed by the shared frontend shell. Do not implement feature endpoints.

## Prerequisites

- Root `AGENTS.md` reviewed.
- Shared prop names agreed with the frontend implementer.

## Implementation Steps

1. Review `HandleInertiaRequests` and define a typed-friendly shared prop contract:
   - `app.name`.
   - `auth.user` as `null` or an explicit safe user projection (`id`, `name`, `email`, `email_verified_at` only).
   - `flash.success` and `flash.error`, consumed once.
   - `locale` placeholder compatible with task 02.
2. Ensure shared props use lazy closures where they would otherwise query or read session state unnecessarily.
3. Define localized 403, 404, 419, 429, and 500 rendering behavior for Inertia requests and normal fallback behavior for non-Inertia requests.
4. Document the navigation route-name convention. Do not create placeholder feature routes just to satisfy the UI.
5. Confirm CSRF and Inertia middleware remain in the web middleware group.
6. Add feature tests for safe shared user fields, flash consumption, and representative error responses.

## Contract

```ts
type SharedPageProps = {
    app: { name: string };
    auth: { user: null | { id: number; name: string; email: string; email_verified_at: string | null } };
    flash: { success: string | null; error: string | null };
    locale: 'pt-BR' | 'en-US';
};
```

The final locale behavior belongs to task 02. If UUIDs replace numeric user IDs, update the contract once rather than supporting both.

## Acceptance Criteria

- Every Inertia page receives the documented safe shared shape.
- Flash values are absent/null when unused and do not repeat after consumption.
- No password, remember token, full model serialization, or unrelated user fields reach frontend props.
- Error responses preserve correct HTTP status and render an appropriate Inertia error page in production behavior.
- Existing root page continues to render during incremental implementation.

## Task Test Plan

- Feature-test guest and authenticated shared props.
- Set success/error flashes, follow redirects, and verify single consumption.
- Trigger representative 403/404 responses as Inertia and non-Inertia requests.
- Run `composer test` and `./vendor/bin/pint --test`.

