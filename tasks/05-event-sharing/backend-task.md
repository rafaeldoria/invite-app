# Backend Task: Public Event and Share Message

## Objective

Expose an allowlisted public event page through an opaque stable URL and persist/generate safe organizer share messages.

## Prerequisites

- Event schema, ownership policy, public ID, timezone, and image delivery strategy complete.
- Public prop contract and Inertia page name agreed with frontend.

## Implementation Steps

1. Add a named public GET route bound only by `public_id`, with no sequential ID fallback. Rate-limit only if measured abuse requires it; public viewing must remain low friction.
2. Build an explicit public event presenter/resource containing exactly the overview fields. Generate cover delivery URL at request time.
3. Add a localized public event page response with correct status handling and no auth redirect.
4. Add Open Graph/title/description data to the Inertia/Blade head path. Sanitize/truncate metadata as plain text. Do not expose guest-specific data in generic metadata.
5. Add an owner-only endpoint/Form Request for custom `share_message` (nullable plain text, trim, maximum 500 characters).
6. Define a single server or shared pure contract for the default message inputs: event name, localized date/time, location, and canonical URL. Prefer passing structured inputs and building display text consistently rather than storing generated localized text.
7. Generate canonical absolute URLs from trusted application configuration, never request-controlled Host headers without trusted proxy/host configuration.
8. Add cache headers conservatively. Do not cache personalized RSVP/invitation responses as the generic public page.
9. Treat public IDs as secrets in logs where practical: avoid logging full tokens in custom messages and never log individual invitation tokens.

## Acceptance Criteria

- Valid public ID returns allowlisted event data without authentication; invalid IDs return localized 404.
- Internal ID, owner data, guest data/counts, image key, and invitation tokens never appear in props/metadata.
- Only owner can update custom message; length/plain-text rules are enforced.
- Canonical URL is stable and based on trusted config.
- Default message can be rendered in both locales without persisting translated output.
- Public page remains usable if the cover image is missing or temporarily unavailable.

## Task Test Plan

- Public success/404 and serializer allowlist assertions.
- Owner/cross-owner/guest custom-message updates and validation boundaries.
- Canonical URL behind expected proxy configuration.
- Metadata escaping with HTML-like names/descriptions.
- Locale-specific default message inputs and S3 URL behavior.

