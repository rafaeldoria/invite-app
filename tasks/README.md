# MVP Delivery Plan

This directory is the implementation source of truth for the invitation platform MVP. It describes work only; it does not include implementation code.

## Analysis Summary

The repository is an early Laravel/Inertia scaffold. It currently has no event, guest, invitation, RSVP, authentication, localization, or dashboard domain code. The verified stack and differences from the target stack are documented in the root `AGENTS.md`.

The requested product references were reviewed only for publicly visible product and UX patterns. The useful recurring patterns are: a visual event page, a short mobile-first RSVP path, clear date/time/location presentation, share-first actions (especially WhatsApp), guest status visibility, and low-friction onboarding. Their templates, code, paid features, QR/check-in flows, gifts, and other out-of-scope capabilities must not be copied or introduced.

Reference availability during analysis:

- FestaLab: public landing content confirmed invitation creation, sharing, event sites, and attendance confirmation.
- Hapy: public landing metadata/content was available, but interactive authenticated flows were not inspected.
- Convitin: the requested domain was not reliably accessible during analysis; no unverified behavior from it is treated as a requirement.
- Convite.in: public content confirmed digital invitations, RSVP, guest quantities, and individualized invitation examples. QR code and check-in were observed but remain explicitly out of scope.

These observations informed usability priorities, not implementation design. Laravel, Inertia, React, and project conventions remain authoritative.

## Fixed MVP Decisions

These decisions let backend and frontend tasks proceed without repeating product analysis:

- Source code and translation keys are English. UI supports `pt-BR` (default) and `en-US`.
- The organizer must authenticate and verify their email before managing events.
- Dates are entered in the event timezone and persisted as an instant plus an IANA timezone. The initial default is `America/Sao_Paulo`.
- An event contains: owner, name, description, start date/time, timezone, location, optional theme, optional cover image, opaque public identifier, and optional custom share message.
- A public event link is unlisted rather than searchable. Knowing the opaque URL allows viewing its public fields.
- A guest contains a display name, RSVP status (`pending`, `confirmed`, or `declined`), adult companion count, child companion count, and an opaque invitation token.
- The organizer can create guests manually. An individual invitation URL preselects that guest.
- The general public event URL also permits RSVP. A first response creates a guest record; repeated updates use a server-issued, signed management capability rather than name matching.
- `companion_count` is derived as adults plus children and is never a third independently editable value.
- The named guest is not included in companion counts. When declining, both companion counts are stored as zero.
- No phone number, email address, dietary restriction, notes, RSVP deadline, end time, address geocoding, or map integration is required for the initial MVP.
- Cover images use the Laravel `s3` disk. Store object keys, not expiring URLs. URL generation is a presentation concern.
- The MVP uses session authentication and Laravel's built-in password reset/email verification capabilities. No social login or token API is needed.
- Theme switching is device-aware (`system`, `light`, `dark`) and persisted in a cookie/local storage for guests and in the user preference only if that persistence is already justified during implementation. Do not add a settings subsystem for it.

## Cross-Feature Contracts

Before parallel backend/frontend work begins for a feature, both implementers must agree on a small written contract in that feature branch or task handoff:

- Route names, methods, and URL parameters.
- Inertia page names and prop shapes.
- Form field names, enum values, validation limits, and error bag behavior.
- Success redirect and flash message keys.
- Authorization and public/private boundaries.
- Empty, loading, success, validation, forbidden, and not-found states.

The task documents below already define the intended contract. Deviations require a short recorded reason and coordinated updates to both sides.

## Dependency Graph

```text
00 Development environment ──> PostgreSQL/S3 integration and production validation
01 Frontend foundation ─┬─> 02 i18n frontend ─┬─> 03 auth frontend
                       │                     ├─> 04 event frontend
                       │                     ├─> 05 sharing frontend
                       │                     ├─> 06 RSVP frontend
                       │                     ├─> 07 guests frontend
                       │                     └─> 08 dashboard frontend
02 i18n backend ────────┼─> 03 auth backend ──> 04 event backend
                       │                      ├─> 07 guests backend
                       │                      └─> 08 dashboard backend
04 event backend ──────┬─> 05 sharing backend
                       ├─> 06 RSVP backend
                       ├─> 07 guests backend
                       └─> 08 dashboard backend
07 guests backend ─────┴─> 06 RSVP backend ───> 08 dashboard backend
```

## Recommended Execution Order

1. Start `00-development-environment` and `01-frontend-foundation` in parallel. The environment track must be ready before PostgreSQL/S3 integration, while foundation's backend task is limited to the Inertia shell contract.
2. Complete both `02-internationalization` tracks in parallel.
3. Complete both `03-authentication-security` tracks in parallel after agreeing on routes and props.
4. Complete both `04-event-management` tracks in parallel. PostgreSQL and S3 must be ready before integration testing.
5. Implement `07-guest-management` backend while the event frontend is being finished; then complete its frontend.
6. Implement `05-event-sharing` and `06-rsvp` in parallel after event public identifiers exist. RSVP also depends on the guest schema.
7. Implement `08-dashboard` last because its counts depend on final guest/RSVP semantics.
8. Run the end-to-end regression plan across both locales, both themes, mobile and desktop, PostgreSQL, and S3.

Backend and frontend tasks marked parallelizable can start together after their shared contract and prerequisites are stable. “Parallel” does not mean “independent of a contract.”

## Directory Index

- `00-development-environment`: PHP/Docker/PostgreSQL/S3/Nginx runtime and production handoff.
- `01-frontend-foundation`: layouts, shared UI, feedback primitives, themes, and conventions.
- `02-internationalization`: locale resolution and centralized backend/frontend messages.
- `03-authentication-security`: registration, sessions, verification, password reset, and MVP hardening.
- `04-event-management`: event CRUD and S3 cover images.
- `05-event-sharing`: public event page, stable public URL, custom message, and WhatsApp sharing.
- `06-rsvp`: public confirmation/decline flow and safe response updates.
- `07-guest-management`: manual guest records, individual invitation links, and status list.
- `08-dashboard`: event-level totals for total, confirmed, declined, and pending guests.

## Global Definition of Done

- Acceptance criteria in the relevant overview and implementation task are satisfied.
- Tests in the feature test plan pass, including authorization and validation failures.
- No new functionality outside the MVP has been added.
- New code, identifiers, files, comments, and translation keys are English.
- User-facing copy exists in both supported locales and is not hardcoded in React or domain code.
- UI is keyboard accessible, mobile-first, and usable in light and dark themes.
- Queries avoid N+1 behavior and do not expose private organizer or guest data.
- Relevant `composer test`, Pint, TypeScript/build, and manual checks are reported truthfully.

## Technical Risks and Simplifications

- **PostgreSQL mismatch:** local/test defaults are currently SQLite. Add a PostgreSQL integration path before relying on database-specific behavior; keep fast SQLite tests only where behavior is portable.
- **PHP mismatch:** Composer currently permits PHP 8.3 although the target is 8.4+. Align the runtime constraint as an infrastructure/setup change before release.
- **Missing containers:** Docker files are not present in this directory. Environment setup is a prerequisite outside these feature implementations and must not be silently invented inside a feature task.
- **S3 failures:** uploads can partially succeed around database failures. Use deterministic replacement/deletion handling, test failure behavior, and do not delete the previous image until the replacement is safely persisted.
- **Public privacy:** opaque identifiers reduce enumeration but are not authorization. Public props must use an explicit allowlist and never serialize the owner, guest list, or internal IDs.
- **RSVP identity:** matching by name is ambiguous and unsafe. Individual tokens plus a signed response-management capability keep the MVP simple without requiring invitees to create accounts.
- **Timezones:** a date and time without an IANA timezone will drift in sharing and localization. Store the timezone now; keep the UI to one sensible default if a selector is not validated.
- **Translations:** backend and frontend can use separate catalogs, but keys and meaning can drift. Keep a short shared key convention and test both locales instead of introducing a translation platform.
- **Design system scope:** build only primitives consumed by the listed features. Do not create a general-purpose component library, Storybook, global state library, or headless UI dependency by default.
- **Audit scope:** use normal application/security logs for significant authentication events without storing passwords, reset tokens, invitation tokens, or sensitive request bodies. Do not build an audit-log product.
