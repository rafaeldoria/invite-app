# Authentication and Security Overview

## Goal

Provide secure session-based organizer authentication for the MVP: registration, login, logout, password reset, and email verification, with proportionate protection against common abuse.

## User Flows

1. Register with name, email, password, and password confirmation.
2. Enter an authenticated but unverified state and receive a verification email.
3. Verify email through Laravel's signed URL before creating or managing events.
4. Log in with email/password and optional remember-me.
5. Request a password reset without revealing whether an account exists.
6. Reset the password using Laravel's broker token; invalidate/rotate the active session as appropriate.
7. Log out through POST and invalidate the session.

## Security Baseline

- Laravel session guard, CSRF middleware, password hashing, signed verification URLs, password broker, and session regeneration.
- Per-email/IP login throttling and throttles for registration, reset requests, and verification resend.
- Generic authentication/reset responses where account enumeration is possible.
- Secure, HTTP-only, same-site cookies in production; HTTPS enforced at the proxy/application boundary.
- Explicit validation, normalized email handling, authorization middleware, and safe redirects.
- Security headers appropriate to the final asset model. Start with frame protection, content type protection, referrer policy, permissions policy, and a tested CSP rather than a copied policy that breaks Vite/Inertia.
- Basic structured logs for registration, login failure/success, logout, reset request/completion, and verification without secrets or raw tokens.

## Out of Scope

- Social login, MFA, passkeys, API tokens, device management, admin roles, or a user-facing audit log.

## Parallelization

Backend and frontend are parallelizable after route names, page props, form fields, throttled error behavior, and redirects are fixed. The frontend may use mocked typed props until endpoints are available.

## Acceptance Criteria

- Every listed flow works in both locales and on mobile/desktop.
- Unauthenticated users cannot access organizer pages; unverified users cannot mutate protected event resources.
- Login and other sensitive endpoints are rate limited and return usable localized feedback.
- Sessions rotate on login and are invalidated on logout; CSRF protection remains enabled.
- Password reset and verification use expiring framework mechanisms and do not leak account existence.
- Production cookie and security-header expectations are documented and testable.

