# Frontend Task: Event Management Screens

## Objective

Build localized organizer screens for listing, creating, viewing, editing, and deleting events, including cover-image handling.

## Prerequisites

- Foundation, i18n, and authentication frontend complete.
- Backend route names, props, fields, and validation agreed.

## Implementation Steps

1. Define typed `EventSummary`, `EventDetail`, and `EventFormData` shapes matching explicit backend serializers.
2. Build event index with responsive cards/list, localized date/time, image placeholder, empty state, and create action.
3. Build one reusable event form composition used by create/edit without hiding page-specific behavior in a generic schema engine.
4. Form fields: name, description, date, time, timezone (visible or clear default), location, optional theme, and optional cover image.
5. Use native date/time controls where they provide accessible behavior; send structured local inputs plus timezone, not browser-converted ambiguous strings.
6. Add cover preview using an object URL, validate obvious type/size client-side for fast feedback, revoke object URLs, and treat backend validation as authoritative.
7. Edit page shows current cover with separate replace and remove actions. Do not silently remove an existing image when no new file is selected.
8. Use multipart Inertia submission with progress, disabled controls, error mapping, and navigation warning only when there are meaningful unsaved changes.
9. Build organizer detail view with safe line-break rendering and links/placeholders for share/guests/dashboard only when their routes exist.
10. Implement accessible delete confirmation displaying event name and handling errors without closing prematurely.
11. Ensure image alt text uses the event context or is empty when decorative; never repeat filename as alt text.

## Acceptance Criteria

- All screens match the event contract and work at mobile/desktop sizes in both themes/locales.
- Date/time is displayed in event timezone and submitted without browser-timezone drift.
- File progress, preview, replace, remove, success, validation, and provider failure states are understandable.
- Delete requires deliberate confirmation and returns to the event list on success.
- Forms prevent double submit and preserve non-file values after validation errors.
- No map, rich editor, cropper, template engine, or other out-of-scope control is added.

## Task Test Plan

- Test create/edit/view/delete end to end with and without a cover.
- Test invalid fields, oversized/wrong-type image, slow upload, failed upload, replace, explicit remove, and cancel.
- Test dates around midnight with browser timezone differing from event timezone.
- Test empty and multi-event list, long content, keyboard behavior, and both locales/themes.
- Run frontend build and relevant tests.

