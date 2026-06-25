# Authentication and Security Notes

## Session Cookies

The application uses Laravel's session guard for organizer authentication. Production must set:

- `SESSION_SECURE_COOKIE=true` when traffic reaches Laravel over HTTPS or through a trusted HTTPS-terminating proxy.
- `SESSION_HTTP_ONLY=true`.
- `SESSION_SAME_SITE=lax`.
- `SESSION_PATH=/`.
- `SESSION_DOMAIN` only when a specific deployment domain needs cookie scoping.

The default configuration derives secure cookies from `APP_ENV=production` unless an environment value overrides it. Local development keeps non-secure cookies so `http://localhost:8080` remains usable.

## Security Headers

Laravel owns the initial security headers through `App\Http\Middleware\AddSecurityHeaders`:

- `X-Frame-Options: DENY`.
- `X-Content-Type-Options: nosniff`.
- `Referrer-Policy: strict-origin-when-cross-origin`.
- `Permissions-Policy` denying camera, microphone, and geolocation.
- A conservative `Content-Security-Policy` that allows the current Inertia/Vite asset model and future HTTPS cover images.

If Nginx later owns these headers, remove the Laravel middleware or keep the Nginx values identical. Do not emit conflicting duplicate header policies.

## Mail Verification

Local and automated tests use Laravel's safe `log` or fake mail transports. Production must verify that registration, email verification, and password reset messages are delivered by the configured provider before release.

## Abuse Response

Registration, login, reset requests, and verification resend actions are throttled. CAPTCHA is intentionally not part of the MVP. Add it only if measured abuse continues after tuning throttles and operational monitoring.
