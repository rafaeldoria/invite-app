# Frontend Foundation

## Shared Inertia Props

Every Inertia page receives `app`, `auth`, `flash`, and `locale` using the shape defined in `resources/js/types/shared.ts`. Authenticated users are projected to `id`, `name`, `email`, and `email_verified_at`. Flash keys are `success` and `error` and are consumed by the first rendered response.

## Navigation Routes

Use Laravel's conventional resource route names (`events.index`, `events.create`, and similar) and a singular name for a single destination such as `dashboard`. Layout navigation accepts resolved URLs and must receive only destinations whose routes exist. The root brand destination is the named `home` route.

## Feedback Patterns

- Use `FlashNotice` for completed server actions and session-level failures.
- Use `Alert` and field errors for recoverable problems that need nearby context.
- Use `FormErrorSummary` for long forms; it receives focus when errors appear and links to each invalid field.
- Use `Skeleton` when content layout is known and `LoadingIndicator` for short indeterminate operations.
- Use `EmptyState` to explain what is missing and provide the first useful action.
- Use `PageError` for full-page 403, 404, 419, 429, and 500 responses.

## Dialogs

`Dialog` uses the native modal dialog behavior, labels its title and description, supports Escape and a contained tab order, and restores focus to the opening control. Reserve it for focused confirmation, especially destructive actions; prefer inline interaction for ordinary editing.

## Temporary Locale Adapter

Task 01 centralizes all foundation copy in `resources/js/utils/translations.ts`. The adapter exposes `pt-BR` and `en-US` and synchronizes mounted controls. Task 02 owns server-side locale resolution, persistence, and the final catalog structure.
