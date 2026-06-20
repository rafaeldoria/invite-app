# Frontend Task: Shared UI Foundation

## Objective

Implement the layouts, components, feedback patterns, theme system, and conventions consumed by every MVP frontend feature.

## Prerequisites

- Backend shared-prop contract reviewed.
- Locale API from task 02 may be represented temporarily by a typed adapter, not hardcoded page strings.

## Implementation Steps

1. Add shared TypeScript types for Inertia page props and safe authenticated user data.
2. Create `PublicLayout`, `AuthenticatedLayout`, and a minimal `GuestLayout` for authentication pages if public event chrome would distract from login.
3. Build navigation:
   - Brand/home destination.
   - Authenticated event/dashboard destinations only when routes exist.
   - Mobile menu with focus management, Escape close, and current-page indication.
   - Locale and theme controls positioned consistently.
4. Build shared UI primitives needed by planned pages:
   - `Button`/button-like link variants, text input, textarea, select, field wrapper/error/help.
   - Alert, flash notice/toast region, status badge, card, empty state, loading indicator/skeleton, page error.
   - Accessible dialog with labelled title/description, focus trap/restore, Escape behavior, and explicit destructive confirmation.
5. Define form behavior: stable labels, `aria-describedby`, server error mapping, dirty/submitting state, duplicate-submit prevention, and first-error focus/summary for long forms.
6. Implement theme mode:
   - `system` is default.
   - Apply a root class/data attribute before paint.
   - Persist the explicit preference and respond to OS changes only in system mode.
   - Use semantic Tailwind tokens/classes so screens do not branch by theme in JSX.
7. Establish spacing, typography, container widths, focus rings, status colors, and reduced-motion behavior in the existing Tailwind setup.
8. Replace the scaffold Welcome page only with a neutral foundation demonstration if needed; do not build unrequested marketing functionality.
9. Document component APIs briefly near complex primitives; avoid barrel files that create circular imports.

## Acceptance Criteria

- Layouts and primitives meet the overview criteria at 320, 768, and 1280 pixel viewport widths.
- All controls can be completed with a keyboard and show visible focus.
- Dialog focus does not escape, returns to its trigger, and does not use a home-grown inaccessible click overlay alone.
- Forms show server errors next to fields and prevent repeat submissions.
- Theme changes without page reload, persists, and does not visibly flash the wrong theme on reload.
- Components have explicit props and no `any`.
- No feature business logic or global state library is introduced.

## Task Test Plan

- Add focused component tests only if a frontend test runner is intentionally introduced; otherwise keep logic small and cover integration in feature pages.
- Manually test keyboard navigation, dialog focus, screen-reader labels, reduced motion, and theme persistence.
- Check mobile menu and layout at the three target widths.
- Run `npm run build`; run any added lint/typecheck/test scripts and report them.

