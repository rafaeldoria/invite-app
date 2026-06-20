# Frontend Task: Authentication Screens

## Objective

Build accessible, localized Inertia pages for registration, login, password recovery/reset, and email verification.

## Prerequisites

- Foundation layouts/form primitives and translation layer available.
- Backend route/form/redirect contract agreed.

## Implementation Steps

1. Create pages for register, login, forgot password, reset password, and verification notice using `GuestLayout` or the agreed minimal auth layout.
2. Use Inertia form helpers with exact backend field names:
   - Registration: `name`, `email`, `password`, `password_confirmation`.
   - Login: `email`, `password`, `remember`.
   - Forgot: `email`.
   - Reset: `token`, `email`, `password`, `password_confirmation`.
3. Add correct autocomplete attributes (`name`, `email`, `current-password`, `new-password`) and password-manager-friendly inputs.
4. Show field errors, generic status messages, submitting/disabled state, and throttling feedback without exposing account existence.
5. Preserve email where safe after validation, but always clear password values after failed submission.
6. Verification notice explains the next step, offers throttled resend, shows success feedback, and exposes logout.
7. Redirect authenticated users away from guest-only pages according to backend behavior; do not implement auth decisions only in React.
8. Ensure page titles, links, helper copy, and validation/status surfaces exist in both locales.
9. Do not add password-strength libraries. Use clear minimum rules and browser/password-manager compatibility.

## Acceptance Criteria

- Every auth flow is complete with keyboard and touch at mobile widths.
- Form fields have visible labels, autocomplete metadata, and associated localized errors.
- Duplicate submit is prevented; password fields do not remain populated after failure.
- Reset/forgot UI uses generic confirmation wording.
- Verification resend communicates success and rate limiting.
- No social-login control, CAPTCHA, MFA, or unrelated profile page is added.

## Task Test Plan

- Exercise each flow against the backend in both locales.
- Test invalid credentials, duplicate email, weak/mismatched password, expired/tampered reset link, unverified account, resend throttle, and offline/server error.
- Test password manager/autofill behavior and keyboard order.
- Run frontend build and available tests.

