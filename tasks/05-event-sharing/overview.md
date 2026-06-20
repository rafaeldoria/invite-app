# Event Sharing Overview

## Goal

Let an organizer share an attractive, unlisted public event page through a stable link and a prefilled WhatsApp message.

## Public Page Contract

- Route uses the event's opaque `public_id`, not its database ID or name.
- Public fields are explicitly serialized: name, description, localized date/time, timezone, location, optional theme, cover image URL, and RSVP call to action.
- No owner email, guest list, counts, internal IDs, S3 object key, or custom organizer-only data is public.
- Unknown identifiers return the normal localized 404 response.
- The page contains appropriate title/description and Open Graph metadata. Cover-image previews are best effort; do not add a separate image-generation pipeline.

## Share Message Contract

- Organizer may save an optional custom plain-text message up to 500 characters.
- The final share text combines custom/default message, essential event summary, and canonical public URL exactly once.
- WhatsApp sharing uses an encoded `https://wa.me/?text=...` URL and degrades to copy-link/copy-message actions.
- The message preview is visible before sharing.

## Simple MVP Enhancements

- One-tap copy link with success feedback.
- Native Web Share API when supported, with WhatsApp and copy actions always available as fallbacks.
- “Add to calendar” is deliberately deferred because timezone/calendar-file behavior expands scope.

## Out of Scope

- QR codes, short-link service, click analytics, contact importing, bulk messaging, scheduled reminders, social network integrations, or public event search.

## Parallelization

Backend and frontend can run in parallel after event management fixes the public route and prop allowlist. Frontend sharing controls can use the documented prop shape while backend work proceeds.

## Acceptance Criteria

- Public link is stable, non-sequential, and displays only allowed event information.
- Organizer can save, preview, and share a localized default or custom message.
- WhatsApp URL is correctly encoded and includes summary/link once.
- Copy and native share actions provide accessible success/failure feedback.
- Public page and controls work without authentication, mobile-first, in both themes/locales.

