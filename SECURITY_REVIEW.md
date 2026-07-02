# Security Review

## Summary

This review covered the Laravel/Inertia/React invitation MVP with focus on practical web application risks: authentication, authorization, public invitation capabilities, RSVP flows, input validation, file uploads, CSRF, XSS, SQL injection, mass assignment, logging, storage, session/cookie settings, rate limiting, and public data exposure.

Overall, the application has a solid MVP security baseline:

- Organizer routes are protected with `auth` and `verified` middleware.
- Event ownership is enforced through `EventPolicy` and Form Request authorization.
- Public event, invitation, and RSVP routes use opaque `public_id`, invitation tokens, and response capabilities instead of sequential IDs.
- Public presenters are allowlisted and do not expose internal event IDs, owner data, guest lists, invitation tokens, or response token hashes.
- React rendering relies on normal escaping; no `dangerouslySetInnerHTML` or manual HTML injection was found.
- CSRF protection remains on the web middleware stack.
- SQL queries use Eloquent or bound raw expressions.
- Session cookies are HTTP-only, SameSite `lax`, and default to secure cookies in production.
- Security headers and CSP middleware are present.

## Risks Found

### Medium: Public capability GET endpoints were not rate limited

Public POST/PATCH RSVP endpoints were rate limited, but some public GET endpoints that generate or check RSVP/invitation capabilities were not. This made it cheaper to brute-force unknown invitation/management URLs or generate many RSVP form capabilities from one event/IP.

Impact: increased application-level abuse risk, noisy token probing, and avoidable load on public endpoints.

Status: fixed by applying the existing `public-rsvp` limiter to public RSVP creation, management view, invitation page, and invitation RSVP form routes.

### Medium: Cover image validation did not enforce file extension or image validity explicitly

The cover image rule checked file upload, MIME type, and size, but did not explicitly require Laravel's `image` rule or an allowed client extension.

Impact: a mismatched or malformed upload could be accepted more easily than intended, depending on PHP/fileinfo behavior and storage backend behavior.

Status: fixed by adding Laravel-native `image`, `mimes:jpg,jpeg,png,webp`, and `extensions:jpg,jpeg,png,webp` rules while keeping the existing MIME and 5 MiB size limits.

### Medium: Invitation tokens are stored in plaintext

Guest invitation tokens are random, non-sequential, hidden from model serialization, and only exposed through owner-facing invitation URLs. However, they are stored directly in the database, unlike general RSVP response tokens which are stored as SHA-256 hashes.

Impact: a database leak would expose active invitation links.

Status: documented for future decision. Hashing invitation tokens would require a migration/backfill and changes to lookup logic, so it was not changed during this review to avoid behavior risk.

### Medium: Public share content can be used for misleading invitations

Event names, descriptions, locations, themes, and custom share messages are organizer-controlled and publicly displayed/shared. React and Blade escaping protect against XSS, and the canonical URL is generated from trusted `APP_URL`, but organizers can still write misleading content or include external URLs in custom messages.

Impact: phishing/social-engineering risk through legitimate public invitation pages or WhatsApp share text.

Status: documented for product decision. Possible mitigations include organizer identity display, reporting workflows, URL warnings in custom share messages, or verified domains. These change product behavior and were not added.

### Low: Generic public event pages are intentionally unthrottled

The public event page remains unauthenticated and not rate limited, matching the current low-friction sharing requirement.

Impact: basic DDoS/load risk remains for public pages.

Status: documented for infra/deploy. Cloudflare, Nginx limits, caching strategy, and monitoring should handle broad request floods. Application throttling can be added later if measured abuse appears.

### Low: Production storage and proxy settings depend on deployment configuration

The code uses Laravel storage abstractions and temporary URLs where available. Secure behavior depends on deploying with private S3 buckets/object ACLs, least-privilege credentials, correct `APP_URL`, and trusted HTTPS proxy configuration.

Impact: misconfigured storage or proxy settings can expose files or generate insecure links.

Status: documented for deploy/infra.

## Files Analyzed

- `AGENTS.md`
- `backend_best_practices.md`
- `routes/web.php`
- `bootstrap/app.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Middleware/AddSecurityHeaders.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Controllers/Auth/*`
- `app/Http/Controllers/EventController.php`
- `app/Http/Controllers/EventDashboardController.php`
- `app/Http/Controllers/EventShareMessageController.php`
- `app/Http/Controllers/GuestController.php`
- `app/Http/Controllers/PublicEventController.php`
- `app/Http/Controllers/PublicInvitationController.php`
- `app/Http/Controllers/PublicInvitationRsvpController.php`
- `app/Http/Controllers/PublicRsvpController.php`
- `app/Http/Controllers/PublicRsvpManagementController.php`
- `app/Http/Requests/Auth/*`
- `app/Http/Requests/Events/*`
- `app/Http/Requests/Guests/*`
- `app/Http/Requests/Rsvp/SubmitRsvpRequest.php`
- `app/Actions/Auth/*`
- `app/Actions/Events/*`
- `app/Actions/Rsvp/SubmitPublicRsvp.php`
- `app/Models/User.php`
- `app/Models/Event.php`
- `app/Models/Guest.php`
- `app/Models/GuestCompanion.php`
- `app/Policies/EventPolicy.php`
- `app/Support/Events/*`
- `app/Support/Guests/GuestPresenter.php`
- `app/Support/Rsvp/RsvpPresenter.php`
- `database/migrations/*create_events_table.php`
- `database/migrations/*create_guests_table.php`
- `database/migrations/*create_guest_companions_table.php`
- `config/app.php`
- `config/auth.php`
- `config/database.php`
- `config/events.php`
- `config/filesystems.php`
- `config/logging.php`
- `config/session.php`
- `.env.example`
- `resources/views/app.blade.php`
- `resources/js/pages/PublicEvent/Show.tsx`
- `resources/js/pages/Rsvp/Form.tsx`
- `resources/js/pages/Events/*`
- `resources/js/pages/Guests/Index.tsx`
- `resources/js/components/events/EventForm.tsx`
- `resources/js/components/ui/Button.tsx`
- `tests/Feature/Auth/SecurityHardeningTest.php`
- `tests/Feature/EventManagementTest.php`
- `tests/Feature/EventSharingTest.php`
- `tests/Feature/GuestManagementTest.php`
- `tests/Feature/PublicRsvpTest.php`

## Corrections Implemented

- Added `throttle:public-rsvp` to:
  - `GET /e/{event:public_id}/rsvp`
  - `GET /e/{event:public_id}/rsvp/{token}`
  - `GET /e/{event:public_id}/invitation/{token}`
  - `GET /e/{event:public_id}/invitation/{token}/rsvp`
- Strengthened cover image upload validation with:
  - `image`
  - `mimes:jpg,jpeg,png,webp`
  - `mimetypes:image/jpeg,image/png,image/webp`
  - `extensions:jpg,jpeg,png,webp`
  - `max:5120`
- Added focused tests for:
  - rejecting an image upload with an unsupported extension
  - throttling repeated public RSVP creation page requests
  - throttling repeated public invitation capability page requests

## Points That Need Future Decisions

- Decide whether to hash existing and future `guests.invitation_token` values. This improves breach resistance but requires a migration/backfill and token lookup changes.
- Decide whether organizer share messages should restrict or warn about third-party URLs to reduce phishing risk.
- Decide whether public event pages should get a separate read-only rate limiter after real traffic is measured.
- Decide whether to display organizer identity or trust indicators on public invitation pages.
- Decide operational retention rules for logs containing IP addresses and hashed email identifiers.

## Extra Deploy and Infrastructure Suggestions

- Production must use `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, and an HTTPS `APP_URL`.
- Configure the reverse proxy/trusted proxy layer so Laravel sees the correct scheme and generates HTTPS URLs.
- Keep session cookies secure in production: `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, and `SESSION_SAME_SITE=lax` unless a specific cross-site requirement is introduced.
- Prefer Redis or another shared store for rate limiting if the app runs on multiple processes/servers.
- Use Cloudflare and Nginx request limits for broad public page floods; application throttles are not a substitute for edge protection.
- Keep S3/event cover buckets private, use least-privilege IAM credentials, and avoid public write ACLs.
- Ensure storage directories are not web-executable and are writable only by the application user.
- Keep logs out of public paths, rotate them, and avoid shipping raw request bodies to external logging services.
- Verify CSP in production after asset/CDN/storage URL changes, especially for cover images and Vite-built assets.
- Monitor repeated 404s on public invitation and RSVP capability routes as token probing signals.
