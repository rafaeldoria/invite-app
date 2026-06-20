# Authentication and Security Test Plan

## Automated Feature Coverage

- Registration: page, valid account, normalized/duplicate email, validation, rate limit, password hashed, verification notification.
- Login: valid, remember flag, invalid credentials, unknown account generic behavior, rate limit key, session regeneration, safe intended redirect.
- Logout: POST + CSRF required, session invalidated, token regenerated, guest cannot misuse route.
- Verification: notice, valid signed link, tampered/expired link, already verified, resend and throttle.
- Password reset: request generic response, known-user mail, unknown-user privacy, valid reset, invalid/expired/replayed token, session implications.
- Middleware: guest/auth/verified route boundaries and cross-user authorization once events exist.
- Localization: representative validation/mail/status response in both locales.

## Security Checks

- CSRF failures do not mutate state.
- Session fixation attempt fails because ID rotates after login.
- External redirect targets are rejected.
- Cookies in production config are Secure, HTTP-only, and intentionally SameSite.
- Responses include the agreed headers without breaking Inertia navigation, Vite assets, S3 cover images, or email links.
- Logs contain no passwords, reset/verification tokens, cookies, CSRF values, or full credentials.

## Manual UX Matrix

- Mobile and desktop; keyboard-only; both themes/locales.
- Browser autofill/password manager.
- Slow request and double-click behavior.
- Generic forgot-password success for existing and non-existing email looks equivalent.
- Verification and reset links opened in a new browser session.

## Exit Criteria

- All auth tests pass with isolated rate limiter/session state.
- No high-severity issue in the security checks.
- Verified middleware protects every organizer mutation route.
- Email delivery/configuration has a documented production verification step.

