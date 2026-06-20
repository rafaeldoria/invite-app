# RSVP Overview

## Goal

Provide a short public mobile-first flow for an invited guest to confirm or decline attendance, with adult and child companion counts.

## Entry Modes

- **Individual invitation URL:** resolves an existing guest by an opaque token and pre-fills/locks the organizer-provided name as appropriate.
- **General event URL:** accepts a guest name and creates one guest response. The server returns a signed/opaque response-management capability so the same browser/link can update it safely.

Never find an editable RSVP by event plus name alone.

## Form Contract

| Field | Rule |
| --- | --- |
| `name` | Required for general links, 1–120 characters; prefilled for individual invitations. |
| `attendance` | Required enum: `confirmed` or `declined`. |
| `adult_companions` | Integer 0–20; enabled only when confirmed. |
| `child_companions` | Integer 0–20; enabled only when confirmed. |

The invitee is not included in companion counts. Declining forces both counts to zero. The computed total party size for a confirmation is `1 + adult_companions + child_companions`.

## UX Flow

1. Show concise event identity and RSVP action.
2. Collect name if needed and attendance choice.
3. Reveal companion controls only for confirmation.
4. Submit once with disabled/loading state.
5. Show a clear localized receipt summarizing the response and how to update it.

## Out of Scope

- Guest accounts, phone/email collection, dietary restrictions, free-form notes, waitlists, per-companion names, reminders, or identity matching by personal data.

## Parallelization

Backend and frontend can run in parallel after event public props and guest schema/token behavior are fixed. RSVP depends on event management and guest management backend contracts.

## Acceptance Criteria

- Both entry modes can create/update exactly the intended response without authentication.
- Invalid/expired/tampered capabilities fail safely without revealing guest data.
- Validation and counts follow the form contract, including forced zero counts on decline.
- Duplicate submissions are handled idempotently enough to avoid duplicate guests for the same individual invitation/capability.
- Flow is fast, keyboard accessible, mobile-first, localized, and provides a durable confirmation state.

