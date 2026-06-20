# Guest Management Test Plan

## Backend Cases

- Owner CRUD and guest/unverified/cross-owner denial.
- Guest route nested under wrong event cannot disclose/mutate the record.
- Name required and length boundaries; duplicate names allowed.
- Pending default; all valid status transitions; decline/pending count invariants.
- Integer count boundaries consistent with RSVP.
- Token generation uniqueness and non-sequential shape; no mass assignment/serialization/log leakage.
- Pagination boundary, stable sorting, exact valid filters, invalid filter behavior.
- Query count bounded with 20 guests.

## Frontend Cases

- No guests vs no results for selected filter.
- Create then copy link; edit name/status/counts; delete and pagination recovery.
- Long/identical names and screen-reader row/action labels.
- Filter and page in browser history.
- Clipboard unavailable/rejected.
- Mobile cards, desktop layout, themes/locales, keyboard-only.

## Integration Cases

1. Organizer creates a pending guest and copies individual link.
2. Invitee responds through that link; organizer reloads and sees status/counts.
3. Organizer corrects response, then invitee updates again according to documented latest-write behavior.
4. Organizer deletes guest; old invitation and response-management URLs no longer work.

## Exit Criteria

- Authorization/nesting/token tests pass on PostgreSQL.
- RSVP integration uses the same status/count definitions.
- No public route exposes the guest collection.
- List remains usable with at least several paginated pages of factory data.

