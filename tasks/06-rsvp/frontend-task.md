# Frontend Task: Mobile-First RSVP Experience

## Objective

Create a concise, accessible public RSVP form and receipt for both general event and individual invitation links.

## Prerequisites

- Foundation/i18n available.
- RSVP entry props, field names, endpoints, and receipt shape agreed.

## Implementation Steps

1. Embed or link to RSVP from the public event page without requiring login.
2. For a general link, request guest name. For an individual link, show the invited name and make editability match the backend contract; do not expose an internal guest ID.
3. Present attendance as two large, semantic radio options: confirm and decline. Do not use ambiguous toggle styling.
4. Reveal adult/child companion number controls only after confirmation. Explain that the invited person is not included. Use numeric input constraints but handle pasted/typed invalid values through server errors.
5. When decline is selected, set/submit companion counts as zero and remove stale confirmed counts from the visible summary.
6. Use Inertia form state with duplicate-submit prevention, clear progress, field/general errors, and focus management.
7. Render a receipt page/state with event, guest name, localized status, companion summary, and an explicit update action based on the returned management URL.
8. Persist only the minimum update capability needed. Do not place it in analytics/log messages or expose it to unrelated components.
9. Handle invalid/used/tampered links with a localized non-disclosing error and a way back to the generic public event only when that event is safely known.
10. Keep the primary path within one short form; do not add a multi-step wizard unless mobile usability testing proves it necessary.

## Acceptance Criteria

- A guest can complete RSVP comfortably at 320px with one hand/touch and keyboard.
- Individual and general entry modes render the correct fields without private data leakage.
- Confirm/decline and companion behavior match backend invariants.
- Submission, double click, server validation, throttle, offline, invalid link, success, and update states are clear.
- Receipt accurately summarizes party size and remains reachable through the management capability.
- No guest account, extra personal-data field, per-companion form, or notes field is introduced.

## Task Test Plan

- General confirm/decline and update; individual confirm/decline and change response.
- Counts 0, 1, 20, invalid values, and switching confirm→decline→confirm.
- Slow/double submit, 422, 429, 404, 419, and network failure.
- Keyboard/screen-reader labels, mobile touch targets, both locales/themes.
- Verify capability never appears in visible text, copied event link, or client logs.

