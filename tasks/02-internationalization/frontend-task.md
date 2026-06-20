# Frontend Task: Typed Translation Layer

## Objective

Provide centralized, typed frontend translations and locale-aware formatting without hardcoded page copy.

## Prerequisites

- Locale endpoint, shared prop, supported values, and key convention agreed with backend.

## Implementation Steps

1. Create complete `pt-BR` and `en-US` catalog modules grouped by feature/domain.
2. Define a source-locale-derived key type so invalid literal keys fail TypeScript compilation.
3. Implement a small translation provider/hook that:
   - Reads the active locale from Inertia props.
   - Resolves typed keys.
   - Supports named interpolation safely.
   - Supports the limited plural forms needed for guests/companions.
   - Surfaces missing keys clearly in development and falls back predictably in production.
4. Add `Intl.DateTimeFormat`, `Intl.NumberFormat`, and `Intl.PluralRules` helpers accepting canonical locale and explicit timezone where relevant.
5. Build an accessible locale switcher showing language names (`Português (Brasil)`, `English (US)`), submitting through Inertia and preserving the current page when safe.
6. Move all existing and newly introduced UI copy into catalogs. Translation values may contain text, not trusted HTML.
7. Add a catalog parity check that recursively verifies matching key sets.
8. Document how new keys, interpolation variables, and plural forms are added.

## Acceptance Criteria

- No React page/component contains user-facing hardcoded prose except proper nouns or explicitly documented external values.
- Invalid translation keys fail at build time where statically known.
- Both catalogs have identical key shapes and interpolation variables.
- Switching locale updates navigation, forms, status labels, and formatting consistently.
- Date/time helpers format in the event timezone rather than the browser timezone by accident.
- UI remains stable when translated text is substantially longer.

## Task Test Plan

- Run catalog parity and interpolation tests.
- Test date/time/number/plural helpers for both locales and a fixed timezone.
- Manually switch locale on public/auth pages and reload/navigate.
- Run `npm run build` and any introduced frontend tests.

