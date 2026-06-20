# Guest Management Overview

## Goal

Cover the `AGENTS.md` MVP requirement for manual guest registration and individual invitation links, while keeping guest management intentionally small.

## Guest Contract

- Belongs to one event and is accessible only through that event's owner.
- Required name, 1–120 characters.
- RSVP status: `pending`, `confirmed`, or `declined`; default `pending`.
- Adult and child companion counts default to zero.
- Server-generated unique opaque invitation token.
- No email, phone, address, tags, groups, imports, or custom fields in MVP.

## Organizer Flow

- View a paginated guest list with name, status, companion summary, and invitation-link action.
- Add one guest manually.
- Edit the guest name; organizer status override is optional and should be included only if required to correct support mistakes. The default task includes it because an organizer must manage the list.
- Delete a guest through a confirmation dialog.
- Copy the individual invitation link.
- Filter by one status using a query parameter; omit search until list size demonstrates the need.

## Out of Scope

- CSV import/export, contacts, groups/families, bulk actions, table assignments, messaging, check-in, QR codes, or invitation delivery tracking.

## Parallelization

Backend starts after event ownership exists. Frontend can proceed with the fixed guest prop contract, but integration depends on frontend foundation, i18n, and event detail navigation.

## Acceptance Criteria

- Organizer can manually create, list, update, and delete guests only within owned events.
- Each guest has a non-enumerable unique invitation URL that can be copied.
- Pagination and status filters preserve predictable navigation and empty states.
- Status/count invariants match RSVP behavior.
- Guest list data is never exposed by the public event route.

