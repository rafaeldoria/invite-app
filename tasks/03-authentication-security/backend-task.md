# Backend Task: Session Authentication and MVP Hardening

## Objective

Implement Laravel-native organizer authentication and security controls without social login or a separate API.

## Prerequisites

- Backend localization and Inertia shared props complete.
- Mail transport configured for the target environment or a safe local mailer available for testing.

## Implementation Steps

1. Use the existing `User` model and Laravel session guard. Add only fields required for name, unique normalized email, password, and email verification.
2. Implement named web routes/controllers/Form Requests for:
   - Registration create/store.
   - Login create/store.
   - Logout (POST only).
   - Forgot-password create/store.
   - Reset-password create/store.
   - Email verification notice/handler/resend.
3. Keep controllers orchestration-only. Use framework brokers/events/contracts; introduce an action only where registration or password reset logic materially benefits.
4. On login, validate credentials, apply a per-email-and-IP rate-limit key, regenerate the session, and redirect only to a local intended URL.
5. On logout, call guard logout, invalidate the session, regenerate CSRF token, and redirect safely.
6. Require `auth` and `verified` middleware for organizer mutation routes. Keep verification notice/logout available to unverified authenticated users.
7. Normalize email consistently before uniqueness/auth checks. Use generic forgot-password responses to reduce enumeration.
8. Configure throttles for login, registration, reset request, and verification resend. Return localized validation-style feedback and `429` where appropriate.
9. Review session/cookie config for production: `secure=true`, HTTP-only, appropriate SameSite, scoped domain/path, sensible idle lifetime, and trusted proxies/HTTPS handling.
10. Add response headers through one middleware/configured Nginx boundary. Define ownership to avoid conflicting duplicate headers. Test CSP with Inertia/Vite/S3 images before enforcing it.
11. Emit structured security logs/events without passwords, credentials, tokens, full request bodies, or invitation capabilities. Retain framework auth events where sufficient.
12. Do not add CAPTCHA initially. Document it as a response if measured abuse defeats throttling.

## Acceptance Criteria

- All overview flows and security invariants work with localized responses.
- Duplicate email registration is safe; reset requests do not disclose account existence.
- Protected routes enforce both authentication and email verification.
- Login rotates session ID; logout invalidates it and rotates CSRF token.
- Rate limits are deterministic and tested without relying on sleeps.
- Cookies/headers have production configuration guidance and do not break local Vite development.
- Logs contain useful event context but no secrets or personal data beyond the minimum account identifier.

## Task Test Plan

- Feature-test every route's success, validation, unauthorized, throttled, invalid/expired token, and localization cases.
- Assert password hashing and session regeneration/invalidation behavior.
- Assert signed verification URLs reject tampering/expiry.
- Assert password reset changes the hash and token cannot be replayed.
- Inspect representative logs for secret leakage.
- Run the full backend test suite and Pint.

