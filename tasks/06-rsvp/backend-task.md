# Backend Task: Public RSVP Workflow

## Objective

Implement secure, idempotent-enough RSVP creation and updates for individual and general event links.

## Prerequisites

- Event public route/presenter complete.
- Guest schema and opaque invitation token from task 07 complete.
- Form and response capability contract agreed with frontend.

## Implementation Steps

1. Add public RSVP routes nested under opaque event/guest capabilities, never numeric IDs:
   - Create response from general event link.
   - Submit/update response through individual invitation token.
   - Update a general-link response through a signed/opaque management capability.
2. Decide the general-response capability mechanism before coding. Preferred MVP: create a random response token stored hashed on the guest record (or a separate minimal capability record), return raw token only in the management URL, and rotate only for an explicit reason. Laravel signed URLs are acceptable if expiry/update UX is explicitly handled.
3. Create a Form Request with overview rules and localized messages. Normalize trim/Unicode safely; do not collapse distinct guests by normalized name.
4. Enforce invariants in one domain operation:
   - Only `confirmed`/`declined` accepted publicly.
   - Declined means both companion counts zero.
   - Counts are integers 0–20 and party-size calculations are derived.
   - Individual token updates its one guest; general submission creates one guest and capability.
5. Use a transaction and database uniqueness/locking where needed to prevent rapid duplicate submission for the same invitation/capability. Return the existing result for safe replays rather than creating another guest.
6. Rate-limit by event plus IP/capability with limits generous enough for real households. Do not key only on a shared IP.
7. Return a minimal receipt prop: event summary, display name, status, companion counts, updated timestamp, and safe update URL/capability. Never return guest list or organizer data.
8. Ensure generic public page caching cannot cache personalized RSVP props.
9. Log aggregate/security-relevant failures without raw invitation/response tokens or full guest names.

## Acceptance Criteria

- Entry and update modes behave exactly as documented and never use name as authorization.
- Tampered, mismatched-event, unknown, or expired capabilities reveal no guest/event relationship.
- Status/count invariants are enforced server-side and in the database/domain model where practical.
- Repeated submits for the same capability do not create duplicate guests.
- Receipt/update responses expose only the intended guest's data.
- Organizer dashboard/list sees changes on the next request.

## Task Test Plan

- General create and management update; individual pending/confirmed/declined update.
- Validation boundaries and decline-zero invariant.
- Token hashing/non-disclosure, tampering, event mismatch, unknown token, and replay.
- Concurrent/double submit simulation around uniqueness/transaction logic.
- Rate-limit behavior and localized errors.
- Public serializer/cache privacy assertions.

