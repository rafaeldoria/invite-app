# Frontend Task: Guest List and Manual Management

## Objective

Build a compact organizer guest list with manual CRUD, status filtering, and individual invitation-link copying.

## Prerequisites

- Event detail navigation and shared foundation/i18n complete.
- Backend paginated prop, form, status, and route contracts fixed.

## Implementation Steps

1. Define typed paginated guest props without internal/token fields; use a backend-provided invitation URL for copy action.
2. Build event-scoped guest page with:
   - Event context/back link.
   - Total/list context without duplicating dashboard analytics.
   - Status filter for all/pending/confirmed/declined encoded in the URL.
   - Responsive list/cards showing name, text status badge, and companion summary.
   - Pagination preserving the filter.
3. Add a focused manual-create form/dialog with name as the required field and pending status by default.
4. Add edit form/dialog for name, status, and companion counts only if confirmed. Explain organizer override and preserve RSVP invariants.
5. Add delete confirmation naming the guest and explaining that their invitation link stops working.
6. Add per-row copy invitation action with accessible label including guest name, Clipboard fallback, and success/error feedback.
7. Avoid putting raw invitation URLs into visible tables by default; expose only through intentional copy/action UI.
8. Handle first-guest empty state, filter-empty state, paginated empty-after-delete state, validation, stale/deleted record, and network errors.
9. Keep mobile actions reachable without horizontal tables; a simple card/list is preferred over a dense data grid.

## Acceptance Criteria

- Organizer can create, edit, delete, filter, paginate, and copy an invitation link on mobile/desktop.
- Status is understandable without color; companion counts appear only where meaningful.
- URL filter/pagination survives reload and back/forward navigation.
- Delete and status overrides are deliberate and preserve backend invariants.
- Invitation link is copied exactly and never appended to the generic event share message accidentally.
- No bulk import/action, search, grouping, contact data, or check-in UI is added.

## Task Test Plan

- Empty/create/edit/delete flows and backend validation/authorization failures.
- Every status/filter, pagination preservation, long/duplicate names.
- Confirmed counts and confirmed→declined clearing.
- Clipboard success/failure/fallback and invalidated link after deletion.
- Responsive, keyboard, both themes/locales, build/tests.

