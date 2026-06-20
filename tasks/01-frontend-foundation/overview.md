# Frontend Foundation Overview

## Goal

Create the smallest reusable UI foundation required by the MVP: public and authenticated shells, navigation, accessible interaction primitives, consistent forms and feedback, responsive behavior, and light/dark/system themes.

## Scope

- App shell and document-level theme initialization.
- Authenticated layout with compact mobile navigation and clear event/dashboard destinations.
- Public layout optimized for event viewing and RSVP without organizer navigation.
- Shared primitives actually needed by MVP screens: button, link, text field, textarea, select, checkbox where needed, field error, card, status badge, alert, toast/flash notice, modal/dialog, spinner/skeleton, empty state, and page-level error state.
- Consistent form spacing, required indicators, help text, disabled/submitting behavior, and server validation errors.
- `system`, `light`, and `dark` theme modes with an accessible switcher and no first-paint flash where practical.
- A conventional `resources/js` structure for pages, layouts, components, hooks, types, and utilities.

## Out of Scope

- A generic design-system package, Storybook, visual page builder, animation framework, or global state library.
- Feature-specific event, guest, RSVP, authentication, or dashboard screens.
- Custom backend APIs.

## Proposed Structure

```text
resources/js/
├── components/
│   ├── feedback/
│   ├── forms/
│   ├── navigation/
│   └── ui/
├── hooks/
├── layouts/
├── pages/
├── types/
└── utils/
```

Use kebab-case filenames for non-component utilities and PascalCase filenames for exported React components. Pages and layouts export a single primary component. Hooks start with `use`. Do not add a folder until it has a real consumer.

## UX Direction

- Mobile-first, content-focused, calm visual hierarchy, and one obvious primary action per view.
- Authenticated shell: top bar on mobile; a simple sidebar or wider header on desktop. Navigation should not dominate event work.
- Public shell: event identity and content first, no organizer controls, prominent RSVP action.
- Toasts acknowledge completed actions; inline errors explain recoverable problems; modals are reserved for focused confirmation or destructive actions.
- Loading indicators preserve layout and prevent duplicate submissions.

## Parallelization

The backend and frontend tasks can run in parallel after agreeing on shared Inertia props and flash keys. The frontend is the primary workstream; the backend task must not grow into feature implementation.

## Acceptance Criteria

- Public and authenticated layouts render correctly from 320px through desktop widths.
- Shared primitives expose accessible labels, focus states, disabled states, and validation affordances.
- Theme defaults to system, can be switched, persists across navigation/reload, and works before React hydration.
- Navigation works with Inertia and indicates the current section accessibly.
- Flash, loading, modal, empty, and page error patterns are documented and demonstrable without feature logic.
- No user-facing string is hardcoded outside the temporary translation interface agreed with task 02.
- TypeScript strict mode remains clean and `npm run build` succeeds.

