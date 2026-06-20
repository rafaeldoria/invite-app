# Frontend Task: Event Dashboard

## Objective

Present the four required RSVP metrics clearly, responsively, and accessibly without unnecessary charts.

## Prerequisites

- Foundation, i18n, event navigation, guest filtered routes, and metrics prop contract available.

## Implementation Steps

1. Define typed dashboard props using the backend `metrics` shape and minimal event summary.
2. Build event-scoped dashboard page with a clear title/context and four cards in this order: total, confirmed, declined, pending.
3. Each card contains a visible localized label, numeric value, and optional short explanation. Icons/colors are supplementary only.
4. If guest filtering is implemented, make cards/secondary links navigate to the corresponding filtered list. The total card links to all guests.
5. Render an informative empty state when total is zero with a manual-add/share action chosen from routes that already exist.
6. Optionally show `expected_attendees` in a secondary, clearly labelled element explaining it includes confirmed guests plus companions. Omit it if backend does not return it.
7. Preserve a stable card layout during Inertia navigation. A real-time socket/polling indicator is not required; data refreshes on visit/reload.
8. Ensure large counts, localized labels, 200% zoom, and 320px width do not truncate meaning.

## Acceptance Criteria

- Required metrics match props and remain distinguishable without color.
- Empty and non-empty states provide useful next actions.
- Status navigation opens the correct guest filter when enabled.
- Layout works in both themes/locales and target viewports with logical reading order.
- No chart, trend, percentage, forecast, or real-time infrastructure is added.

## Task Test Plan

- Render zero, mixed, and large values; optional expected-attendees absent/present.
- Follow all status links and verify query filters.
- Keyboard and screen-reader card/link naming.
- Both locales/themes at 320/768/1280px and 200% zoom.
- Run build and integration flow after RSVP changes.

