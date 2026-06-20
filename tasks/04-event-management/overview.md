# Event Management Overview

## Goal

Allow a verified organizer to create, view, edit, and delete only their own events, including an optional S3-hosted cover image.

## Event Contract

| Field | Rule |
| --- | --- |
| `name` | Required string, 1–120 characters. |
| `description` | Required plain text, 1–2,000 characters. Render safely with preserved line breaks. |
| `starts_at` | Required date/time interpreted in the event timezone and stored as an instant. |
| `timezone` | Required valid IANA timezone; default `America/Sao_Paulo`. A selector may initially expose only supported common values. |
| `location` | Required free-form string, 1–255 characters. No map/geocoding in MVP. |
| `theme` | Optional string, maximum 80 characters. It is descriptive, not a template engine. |
| `cover_image` | Optional JPEG, PNG, or WebP, maximum 5 MiB. Store object key and basic metadata. |
| `public_id` | Server-generated opaque identifier used in public URLs; never accepted from organizer forms. |

Owner ID, public ID, image key, and timestamps are server-managed. The custom share message belongs to sharing task 05 but may use an event column to avoid a separate model.

## Screens

- Organizer event list with empty state and create action.
- Create form.
- Organizer detail page with edit, share, guest, RSVP summary, and delete entry points as they become available.
- Edit form with current image preview, replace, and explicit remove controls.
- Accessible delete confirmation.

## Out of Scope

- Recurrence, end time, maps, address autocomplete, multiple images, rich-text editor, image editor/cropper, themes as templates, public discovery, or event collaborators.

## Parallelization

Backend and frontend can run in parallel after agreeing on routes, `Event` prop/form shape, upload mechanics, and validation limits in this document. Integration waits for authentication and i18n.

## Acceptance Criteria

- Verified organizers can perform CRUD on their own events and cannot access another organizer's events.
- Validation preserves form input and returns localized field errors.
- Date/time round-trips without timezone drift.
- Image upload, replacement, removal, deletion cleanup, and upload failure behavior are deterministic.
- Public props never expose S3 keys, owner details, or internal IDs.
- Event list/detail queries avoid N+1 access and work with PostgreSQL.

