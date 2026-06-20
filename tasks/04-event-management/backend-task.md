# Backend Task: Event CRUD and S3 Cover Images

## Objective

Implement the event persistence, authorization, validation, CRUD routes, and reliable S3 image lifecycle defined in the overview.

## Prerequisites

- Auth/verified middleware and locale behavior complete.
- PostgreSQL and Laravel S3 disk configuration available in the integration environment.
- Event prop/route contract agreed with frontend.

## Implementation Steps

1. Add an `events` migration with owner foreign key, indexed opaque `public_id`, field limits from the overview, timezone, nullable theme/image metadata/share message, and timestamps. Choose delete behavior explicitly.
2. Create `Event` model relationships, casts, fillable/guarded strategy, and a factory with useful states (with/without cover, future/past).
3. Generate `public_id` server-side using a UUID/ULID or cryptographically random token with a unique index. Never derive it from the name.
4. Create an `EventPolicy` covering view/update/delete and ensure scoped route model binding or explicit policy checks prevent cross-owner access.
5. Create separate Store/Update Form Requests with localized rules:
   - Parse local date/time with the submitted IANA timezone.
   - Reject invalid timezone, impossible date, unsupported image MIME/type, and oversized images.
   - Decide whether past dates are allowed consistently. For MVP creation, reject clearly past starts; permit editing an event that has since passed without forcing a date change.
6. Implement thin resource controllers and named Inertia routes for index/create/store/show/edit/update/destroy.
7. Upload images through Laravel filesystem using generated object names. Store key, disk, MIME, size, and optional dimensions; never trust original filename as a path.
8. Replacement order: validate/upload new object, persist new reference transactionally where possible, then delete old object after commit. On persistence failure, remove the newly uploaded orphan. Log cleanup failure for retry/operations.
9. Explicit image removal and event deletion remove referenced objects after successful database mutation without turning a storage cleanup failure into data resurrection.
10. Generate temporary or configured delivery URLs at serialization time; do not persist expiring URLs. Select one S3 visibility strategy and document required bucket/CORS settings.
11. Serialize organizer and public event props through explicit resources/arrays; do not pass the model wholesale.
12. Seed only synthetic development events. Add feature tests and an S3 fake test suite.

## Acceptance Criteria

- Schema, validation, CRUD, authorization, timezone, and image behavior match the overview.
- Cross-owner access returns the project's chosen non-disclosing 403/404 behavior consistently.
- Failed image replacement keeps the previous usable image and cleans the failed new upload.
- Successful replacement/removal/delete does not leave an expected old object behind (allow logged retry for simulated provider deletion failure).
- Organizer and public serializers expose only their documented fields.
- PostgreSQL migration and feature suite pass.

## Task Test Plan

- Factory/model relationship and cast tests where valuable.
- CRUD success/validation/ownership tests.
- Date/timezone boundary and past-date update cases.
- S3 fake: upload, invalid MIME/size, replace, remove, delete, database failure cleanup, storage deletion failure logging.
- Query count check on index/show if relationships are introduced.
- Run migrations/tests against PostgreSQL before handoff.

