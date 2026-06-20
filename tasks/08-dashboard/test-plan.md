# Dashboard Test Plan

## Dataset Matrix

| Dataset | Expected |
| --- | --- |
| No guests | All four values `0`; empty state shown. |
| Pending only | Total equals pending; other statuses zero. |
| One each | Total `3`; each status `1`. |
| Mixed | Exact grouped counts; invariant holds. |
| Confirmed companions | Four guest counts unaffected; optional expected attendees includes companions. |
| Large values | Constant query count; UI values do not overflow. |

## Authorization and Privacy

- Owner succeeds.
- Guest, unverified user, and other organizer receive no metrics.
- Generic public event/RSVP props contain no dashboard totals unless product scope explicitly changes.

## Integration

1. Start empty, add pending guest, verify total/pending.
2. Confirm through individual RSVP, revisit dashboard, verify pending decreases and confirmed increases.
3. Decline another guest and verify invariant.
4. Delete guest and verify total/status decrease.
5. Follow each card to the matching guest filter.

## UX/Accessibility

- Labels announced with their values and not communicated solely by color/icon.
- Empty state actions exist only when target routes are available.
- Both themes/locales, keyboard, 320/768/1280px, and 200% zoom.

## Exit Criteria

- Aggregate tests pass on PostgreSQL with constant query count.
- End-to-end changes appear on the next Inertia visit.
- Required four metrics and invariant are correct in all datasets.
- No analytics scope expansion is present.

