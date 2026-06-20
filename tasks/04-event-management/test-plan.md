# Event Management Test Plan

## Backend Matrix

| Area | Cases |
| --- | --- |
| Authorization | Guest, unverified owner, verified owner, different owner for every mutation/read. |
| Validation | Required/length boundaries, invalid date/time/timezone, past create, optional theme, malformed upload. |
| Persistence | Create/update/delete, owner relation, unique opaque public ID, correct UTC instant + timezone. |
| Images | JPEG/PNG/WebP, MIME spoof, >5 MiB, replace, remove, delete, upload/database/delete failure cleanup. |
| Serialization | Organizer fields allowed; public fields separately allowlisted; no model leakage. |
| Database | Fresh PostgreSQL migration, rollback where applicable, indexes/foreign key behavior. |

## Frontend Matrix

- Index empty/one/many and long names.
- Create/edit validation errors and successful redirects/flashes.
- Native date/time input in representative browsers; event timezone differs from device timezone.
- Cover preview lifecycle, progress, replace/remove distinction, alt behavior.
- Delete dialog keyboard/touch and backend failure.
- 320px, 768px, 1280px; both themes/locales; 200% zoom.

## Integration Scenarios

1. Register/verify, create an event without image, and view it.
2. Create with S3 image, edit metadata only, verify image remains.
3. Replace then remove image, verifying object lifecycle.
4. Attempt cross-account event URLs and mutations.
5. Edit a now-past event without changing its start, then attempt to set another invalid past time.

## Exit Criteria

- Automated suite passes on PostgreSQL with filesystem fakes and targeted real-S3 smoke test in a non-production bucket.
- No orphan or accidental old-image deletion in failure scenarios.
- Public/organizer data boundaries are verified.
- Build, Pint, and responsive/accessibility checks pass.

