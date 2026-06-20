# Frontend Foundation Test Plan

## Automated Coverage

- Laravel feature tests for shared Inertia props, flash lifecycle, and error response status/pages.
- TypeScript compilation through the production Vite build.
- If a component test tool is added, prioritize theme resolution, dialog keyboard behavior, flash dismissal, and field error associations. Do not add a large test stack solely to snapshot static markup.

## Manual Matrix

| Area | Cases |
| --- | --- |
| Viewports | 320px, 375px, 768px, 1280px; no horizontal overflow. |
| Input | Keyboard-only, mouse, and touch-sized targets. |
| Themes | System/light/dark, reload persistence, OS mode change in system mode. |
| Navigation | Current state, mobile open/close, Escape, focus return, Inertia visits. |
| Forms | Empty, help text, required, invalid, disabled, submitting, server error. |
| Feedback | Success/error flash, page error, empty state, loading state. |
| Dialog | Open, tab loop, Escape, cancel, destructive confirm, trigger focus restore. |

## Accessibility Checks

- Semantic landmarks and heading hierarchy.
- Form labels and errors announced through `aria-describedby`/live regions as appropriate.
- Status and errors understandable without color.
- Focus contrast and text contrast in both themes.
- Zoom to 200% without loss of controls or content.

## Regression Commands

```bash
composer test
./vendor/bin/pint --test
npm run build
```

## Exit Criteria

- All acceptance criteria pass.
- No severe keyboard/accessibility issue remains.
- Shared props contain only allowlisted data.
- The foundation has at least one real usage for every retained primitive.

