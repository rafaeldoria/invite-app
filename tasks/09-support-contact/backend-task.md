# Backend Task

## Objective

Implement the public support contact workflow using Laravel conventions and the smallest production-ready structure.

## Requirements

- Add `support_contacts` persistence for name, contact, subject, message, email sent timestamp, and timestamps.
- Add explicit validation and normalization with a Form Request.
- Add a focused Action for saving the submission and sending the support email.
- Add a text-only Mailable for support notifications.
- Read the support recipient from `SUPPORT_EMAIL` through config.
- Add a public route pair for rendering and submitting support contact.
- Add rate limiting for support submissions.

## Acceptance Criteria

- Valid submissions persist and send email to the configured support address.
- Missing `SUPPORT_EMAIL` persists the contact and returns generic error feedback.
- Email send exceptions do not leak message content to logs or users.
- The request rejects empty fields, invalid contact values, and oversized text.
- Subject whitespace is normalized before persistence and email.
- Tests cover success, failure, validation, throttling, and escaping.
