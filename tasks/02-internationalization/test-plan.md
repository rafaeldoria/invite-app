# Internationalization Test Plan

## Backend Cases

- No preference/header returns `pt-BR`.
- Supported `Accept-Language` selects the matching locale only when no explicit preference exists.
- Explicit `pt-BR`/`en-US` switch persists.
- Unsupported, empty, mixed-case, and injection-like locale inputs fall back safely.
- Validation, throttling, password reset, and verification messages use each active locale.
- Inertia prop always uses canonical values.

## Frontend Cases

- Catalog key parity and no undefined leaf values.
- Interpolation escapes/render text safely and reports missing variables in development.
- Singular/plural labels for 0, 1, and multiple guests/companions.
- Date/time formatting around day boundaries with an explicit event timezone.
- Long `pt-BR` and `en-US` strings do not overflow controls at mobile widths.
- Locale switch works with keyboard, reload, back/forward navigation, and validation errors.

## Content Audit

Search relevant source files for newly hardcoded strings and manually inspect exceptions. Do not use a brittle regex as the only proof. Verify email subjects/body, flash messages, page titles, buttons, labels, errors, empty states, and statuses.

## Exit Criteria

- Both catalogs are complete and equivalent.
- Locale precedence and persistence pass automated tests.
- Representative end-to-end flows pass in both locales.
- No localized string is persisted as authoritative domain state.

