# Backend Task: Organizer Guest Management

## Objective

Implement the guest schema and owner-only manual CRUD needed by individual invitations and RSVP.

## Prerequisites

- Event ownership/policy complete.
- Guest/RSVP status and token contracts accepted.

## Implementation Steps

1. Add `guests` migration with event foreign key, name, indexed status, adult/child companion counts, unique opaque `invitation_token` (prefer storing a hash if lookup design remains simple), nullable response-management token hash if task 06 uses it, response timestamp, and timestamps.
2. Add database constraints where portable/appropriate for allowed statuses and non-negative counts; always enforce the same rules in application validation.
3. Create `Guest` model with event relation, status cast/enum, integer casts, and no token exposure in default serialization/logging.
4. Create factory states for pending, confirmed, declined, and companion variations. Declined/pending states must have zero counts unless organizer override semantics explicitly allow otherwise; preferred invariant is zero.
5. Generate cryptographically random invitation tokens with collision retry and a unique index. Never derive from guest name/ID.
6. Create owner-only nested routes/controllers/Form Requests for index/store/update/destroy. Scope guest binding through the event and authorize the event owner.
7. Index query:
   - Paginate with a modest default (for example 20).
   - Optional exact status filter from the three canonical values.
   - Stable ordering by name then ID, or newest first; document one choice and keep it consistent.
   - Select/eager-load only required values; no N+1.
8. Store/update name and organizer-managed status/counts. Apply the same decline/count invariants as RSVP. Do not allow token changes through mass assignment.
9. Return an individual invitation URL generated from trusted application URL configuration. Do not return raw token in other unrelated props.
10. Deleting a guest invalidates its invitation/response capabilities. Require deliberate frontend confirmation; backend remains idempotent under normal repeated navigation.
11. Explicitly avoid guest uniqueness-by-name constraints; households can share names and identity is token-based.

## Acceptance Criteria

- Owner-only CRUD, nesting, pagination, filtering, and invariants work as documented.
- Cross-event and cross-owner guest IDs cannot be used to read/mutate another guest.
- Every guest receives a unique non-sequential individual invitation capability.
- Tokens are not mass assignable, shown in logs, or serialized outside invitation URL needs.
- Guest query count remains bounded for a page of results.
- Factories generate internally valid state for all statuses.

## Task Test Plan

- Migration constraints/indexes and factory states.
- CRUD success/validation/authorization/cross-nesting.
- Pagination, stable order, valid/invalid filter, empty results.
- Token uniqueness/non-enumerability/non-mass-assignment and invalidation on delete.
- Status/count invariants shared with RSVP.
- Query count check and PostgreSQL run.

