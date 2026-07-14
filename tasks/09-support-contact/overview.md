# Support Contact and Welcome Testimonials

## Goal

Add a simple organizer support contact flow and improve the welcome page testimonial experience without expanding the product beyond invitation, event, guest, and RSVP management.

## Scope

- Improve welcome page testimonials with a fourth natural testimonial, 5-star rating display, and horizontal scrolling that shows three cards on desktop.
- Link the organizer support call-to-action to a public support form.
- Store support contact submissions and email the configured support recipient.
- Keep the support flow simple, secure, localized, responsive, and accessible.

## Out of Scope

- Ticket management.
- Support inbox UI.
- Live chat.
- Attachments.
- Third-party helpdesk integrations.
- AI support features.

## Acceptance Criteria

- Guests and authenticated organizers can open `/support`.
- Authenticated users see their name and email prefilled but editable.
- The support form collects name, contact, subject, and message.
- Contact accepts either a valid email address or a plausible WhatsApp number.
- Submissions are saved to the database before email is attempted.
- Successful guest submissions redirect to the home page.
- Successful authenticated submissions redirect to the events page.
- Failed email delivery keeps the saved record and returns generic feedback on the support page.
- User-provided text is rendered as text, not trusted HTML.
- The public support endpoint is rate limited.
- User-facing copy exists in `pt-BR` and `en-US`.
