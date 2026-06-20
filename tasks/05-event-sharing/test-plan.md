# Event Sharing Test Plan

## Public Boundary Tests

- Valid/invalid/malformed public IDs.
- Public response allowlist and absence of owner, guest, internal ID, image key, share-draft internals, and invitation tokens.
- HTML/script-like event content remains escaped in body and metadata.
- Missing S3 object displays fallback without exposing storage details.
- Canonical URL correct under production proxy/HTTPS config.

## Share Content Cases

- Empty custom message uses localized default.
- Custom message at 1 and 500 characters; 501 rejected.
- Whitespace-only custom value normalizes to null/default.
- Accents, emoji, ampersand, question mark, hash, percent, and line breaks encode correctly.
- Event URL and summary appear once; no accidental `undefined` fields.
- Copy link, copy full message, WhatsApp, native share success/cancel/failure.

## UX/Accessibility

- 320px public view has a visible primary RSVP action.
- Logical heading/landmark order, meaningful cover alt behavior, keyboard-accessible sharing controls.
- Long translated/custom text wraps without horizontal scrolling.
- Both themes/locales and 200% zoom.

## Exit Criteria

- Public privacy assertions and ownership tests pass.
- Shared outputs exactly match preview in representative cases.
- Public page works without authentication/cookies beyond locale preference.
- No out-of-scope tracking or sharing integration is introduced.

