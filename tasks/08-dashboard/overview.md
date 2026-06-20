# Dashboard Overview

## Goal

Give an organizer a simple event-level snapshot of total, confirmed, declined, and pending guests using the same status definitions as RSVP and guest management.

## Metrics Contract

- `total`: number of guest records for the event.
- `confirmed`: guests whose status is `confirmed`.
- `declined`: guests whose status is `declined`.
- `pending`: guests whose status is `pending`.
- The invariant `total = confirmed + declined + pending` must always hold.
- Companion counts are not included in these four cards. An optional “expected attendees” value (`confirmed guests + their companions`) may be shown only if clearly labeled and implemented without adding another workflow.

## Screen

- Event context and navigation back to event management.
- Four readable status cards with text labels and counts.
- Empty state explaining that guests must be added/shared before counts change.
- Optional link from each status card to the guest list filtered by that status.
- No charts are required; cards communicate four exact values more clearly.

## Out of Scope

- Analytics history, conversion funnels, charts, exports, real-time sockets, cross-event business intelligence, or attendance forecasts.

## Parallelization

Backend aggregation and frontend cards are parallelizable after the metric prop shape is fixed. Final integration depends on stable guest/RSVP statuses.

## Acceptance Criteria

- Only the event owner can view its dashboard.
- Counts are correct for empty and mixed-status datasets and satisfy the invariant.
- Dashboard updates on the next Inertia visit after guest or RSVP changes; real-time updates are not required.
- Status links, if included, open the correctly filtered guest list.
- UI is accessible, responsive, localized, and does not rely on color alone.

