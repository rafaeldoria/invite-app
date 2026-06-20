# Backend Task: Event RSVP Summary

## Objective

Return correct owner-only guest status totals for one event with a simple efficient aggregate query.

## Prerequisites

- Event policy and final guest status semantics complete.
- Dashboard prop shape agreed with frontend.

## Implementation Steps

1. Add an owner-only named dashboard route nested under the event.
2. Authorize through the event policy before querying guest data.
3. Compute `total`, `confirmed`, `declined`, and `pending` in one aggregate query where practical (conditional counts) or one clearly bounded query approach. Do not load guest models to count them in PHP.
4. Return explicit integer props under a stable `metrics` key plus a minimal event summary.
5. Optionally compute `expected_attendees` as confirmed named guests plus adult/child companions in the same aggregate. Include only if the frontend labels it clearly and it does not delay the four required metrics.
6. Provide filter URLs/route data for status cards rather than duplicating guest arrays.
7. Do not cache initially. The database query is small and next-visit freshness is required; add caching only after measurement.
8. Add factory-backed feature tests for empty and mixed datasets, ownership, invariant, and query behavior.

## Prop Contract

```ts
type DashboardMetrics = {
    total: number;
    confirmed: number;
    declined: number;
    pending: number;
    expected_attendees?: number;
};
```

## Acceptance Criteria

- Metrics exactly match database statuses and always satisfy the invariant.
- Counts are integers and zero rather than null for an empty event.
- Only owner can view metrics; public users and other owners receive no count data.
- Query count is constant as guest volume grows.
- No unnecessary cache or analytics table is introduced.

## Task Test Plan

- Empty, one-per-status, mixed/high companion, and larger factory datasets.
- Invariant assertion in every dataset.
- Guest/unverified/cross-owner access.
- Query count and PostgreSQL aggregate behavior.
- RSVP update followed by fresh dashboard request.

