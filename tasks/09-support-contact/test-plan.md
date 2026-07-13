# Test Plan

## Automated Checks

- `npm run test:frontend`
- `npm run build`
- `composer test`
- `./vendor/bin/pint --test`

## Backend Scenarios

- Guest can render support contact page.
- Authenticated organizer gets name and email defaults.
- Valid guest submission saves, emails support, and redirects home.
- Valid authenticated submission saves, emails support, and redirects to events.
- Invalid, blank, and oversized payloads are rejected.
- Subject whitespace is normalized.
- HTML-like message content is escaped in email rendering.
- Public support submissions are throttled.
- Missing support email saves the record and returns generic feedback.

## Frontend/UX Scenarios

- Welcome testimonials show star ratings and scroll controls.
- Organizer support CTA opens `/support`.
- Support form has accessible labels and error summary behavior.
- Support page works on mobile and desktop without overflow.
