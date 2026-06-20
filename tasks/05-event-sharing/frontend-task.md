# Frontend Task: Public Event and Sharing Controls

## Objective

Build the public event presentation and organizer sharing UI, optimized for mobile and WhatsApp.

## Prerequisites

- Public/organizer prop shapes, canonical URL, and custom message endpoint agreed.
- Foundation and i18n complete.

## Implementation Steps

1. Create a `PublicEvent/Show` page using `PublicLayout` with cover, name, optional theme, localized date/time/timezone, location, description, and prominent RSVP action.
2. Preserve description line breaks as text; never render organizer input as HTML.
3. Create organizer share panel on the event detail/share page:
   - Textarea for optional custom message with count/limit.
   - Localized default when custom message is empty.
   - Live plain-text preview of final message, summary, and link.
   - Save state separate from share actions so unsaved text is not implied to be persisted.
4. Construct the WhatsApp URL with `encodeURIComponent` over the final message once. Use `https://wa.me/?text=` and open safely without an opener reference where applicable.
5. Implement copy-link and copy-message actions using Clipboard API with a safe fallback and localized success/error feedback.
6. Use `navigator.share` only when supported and invoked by user action; catch cancellation without showing it as an application error.
7. Ensure canonical URL appears exactly once in final text and visible preview.
8. Keep event facts scannable and RSVP action reachable without excessive scrolling on mobile.
9. Add page title/meta props through Inertia `Head`; backend/Blade owns tags requiring server-rendered metadata behavior.

## Acceptance Criteria

- Public page communicates what, when, and where before secondary details.
- RSVP action is clear and keyboard/touch accessible.
- Default/custom message preview exactly matches copied/shared text.
- WhatsApp link handles accents, emoji, line breaks, ampersands, and long valid messages.
- Clipboard/native-share success, cancellation, unsupported, and failure states are handled.
- No QR code, analytics, bulk send, contact picker, or calendar integration appears.

## Task Test Plan

- Test public page with missing image/theme, long text/location, and event timezone differing from device.
- Validate WhatsApp/copy output character-for-character for both locales and custom/default content.
- Mock supported/unsupported/rejected Clipboard and Web Share APIs where frontend testing exists.
- Manual mobile checks on at least one iOS-like and Android-like viewport/browser behavior.
- Run build and integration checks.

