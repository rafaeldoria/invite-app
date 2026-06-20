# Internationalization Overview

## Goal

Support `pt-BR` and `en-US` across Laravel and React while keeping all code and translation identifiers in English. Brazilian Portuguese is the default locale.

## Locale Contract

- Canonical locale values: `pt-BR` and `en-US`.
- Default: `pt-BR`.
- Resolution order for authenticated requests: explicit user/session preference, then supported browser preference, then default.
- Resolution order for guests: locale cookie/session, then supported browser preference, then default.
- An explicit locale switch always wins and persists for future requests.
- Unsupported or malformed locale values fall back to `pt-BR`; they never become dynamic filesystem paths.
- The active locale is provided as an Inertia shared prop. Dates are formatted for display from structured values, never stored as localized strings.

## Catalog Strategy

- Backend validation, auth, mail, and server-only messages use Laravel language files under `lang/{locale}`.
- Frontend navigation, forms, pages, statuses, and feedback use typed frontend catalogs under `resources/js/locales`.
- Keys are English, semantic, and grouped by domain, for example `auth.login.title` and `events.form.name.label`.
- Do not reuse a key merely because two current strings happen to match.
- Avoid a new i18n dependency unless the task implementer proves the minimal typed catalog cannot meet pluralization/interpolation needs.

## Out of Scope

- Translation management SaaS, automatic translation, runtime remote catalogs, locale-specific routes, or more than two locales.

## Parallelization

Backend and frontend tasks are parallelizable after the locale values, resolution order, prop name, endpoint, and catalog key convention above are accepted.

## Acceptance Criteria

- Both locales cover all implemented user-facing messages with no hardcoded UI copy.
- Locale switching persists and updates the next Inertia response without a full product-state reset.
- Unsupported values safely fall back to `pt-BR`.
- Laravel validation/auth/password/email-verification messages use the active locale.
- Dates, times, numbers, and pluralized companion/guest labels render correctly for both locales.
- Missing frontend translation keys fail visibly in development and are covered by a catalog parity test.

