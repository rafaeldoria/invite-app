# Backend Best Practices

This guide defines how backend work should be shaped in this Laravel application. Keep it practical: use the smallest structure that makes the current MVP feature clear, testable, and safe to change.

## Core Principles

- Start from `AGENTS.md`, the relevant task under `tasks/`, and the current code before designing a change.
- Prefer Laravel conventions over custom architecture: routing, controllers, Form Requests, policies, Eloquent, filesystem, queues, events, and tests should do the heavy lifting.
- Keep the MVP lean. Add a class only when it removes real controller/model complexity, isolates a workflow, or is reused by more than one entry point.
- Use English for class names, method names, comments, test names, logs, and documentation added to the repository.
- Avoid speculative flexibility for out-of-scope features.

## Action Pattern

Use Actions for application workflows that are more than simple CRUD assignment inside a controller. Good Action candidates include:

- Creating, updating, or deleting a model together with related side effects.
- Workflows that need database transactions.
- File upload, replacement, or cleanup tied to persistence.
- Security-sensitive flows such as authentication, RSVP tokens, or public invitation links.
- Reused backend workflows called by multiple controllers, commands, or jobs.

Actions should:

- Live under `app/Actions/<Domain>`.
- Be `final` by default.
- Expose one public `handle(...)` method.
- Receive dependencies through constructor injection.
- Accept typed inputs such as Form Requests, models, DTO-like arrays, or value objects already validated at the boundary.
- Return the domain result needed by the caller, usually a model or `void`.
- Keep HTTP response decisions out of the Action.
- Handle necessary cleanup for side effects it created, then rethrow unexpected exceptions so the caller can choose the HTTP response.

Avoid creating Actions for one-line assignments, pure presentation formatting, or framework behavior already expressed cleanly by Form Requests, policies, model relationships, or Eloquent.

## Controllers

Controllers should coordinate HTTP concerns:

- Authorize access when authorization is not already handled by a Form Request.
- Call the relevant Action for business workflows.
- Render Inertia pages or return redirects/responses.
- Attach flash messages and validation-friendly redirects.
- Avoid complex queries, file lifecycle logic, and manual payload assembly when those concerns are large enough to name.

Thin controllers are the goal, not empty controllers. A direct Eloquent call is acceptable when it is the clearest MVP solution.

## Form Requests

Use dedicated Form Requests for create/update operations and non-trivial validation.

Form Requests should:

- Authorize the current user against the route model or operation.
- Trim and normalize simple input before validation.
- Define all server-side validation rules.
- Convert validated form fields into persistence attributes when that conversion is request-specific.
- Keep cross-field validation close to the request.

Do not duplicate authoritative backend rules in React. Client-side validation may improve feedback, but Laravel remains the source of truth.

## Policies and Security

- Use policies or gates for model ownership and sensitive actions.
- Never rely only on hidden UI controls for access control.
- Treat public invitation and RSVP links as untrusted input.
- Use opaque, non-sequential tokens for public identifiers.
- Serialize only the fields the screen or public flow needs; never pass whole models to Inertia.

## Persistence and Side Effects

- Use database transactions when multiple database writes must succeed or fail together.
- When file uploads are tied to database writes, upload first, persist inside a transaction where practical, and delete the new file if persistence fails.
- Delete replaced or removed files after the database mutation succeeds.
- Log storage cleanup failures without resurrecting deleted data.
- Keep generated storage keys server-side and never trust original filenames as object paths.

## Eloquent and Queries

- Keep models focused on relationships, casts, scopes, and natural domain behavior.
- Define relationship return types.
- Prefer expressive Eloquent queries over clever query code.
- Eager-load relationships intentionally when a page would otherwise trigger N+1 queries.
- Paginate lists that can grow, especially guest lists.
- Do not hide expensive database access in accessors or loops.

## Organization

Use these locations unless an existing local pattern clearly says otherwise:

- `app/Actions/<Domain>` for named backend workflows.
- `app/Http/Requests/<Domain>` for Form Requests and shared request helpers.
- `app/Policies` for authorization.
- `app/Support/<Domain>` for focused helpers such as presenters, serializers, token generators, or storage helpers.
- `tests/Feature` for HTTP/Inertia/business flow coverage.
- `tests/Unit` only for isolated domain logic with real value.

## Simplicity Checklist

Before adding a backend abstraction, confirm:

- The current controller/model/request would otherwise hold more than one clear responsibility.
- The new class has a specific domain name, not a generic framework name.
- The new class reduces duplication or isolates a workflow that needs tests.
- The behavior is in the MVP scope or explicitly requested by the task.
- Existing Laravel features cannot express the behavior more simply.

Before finishing backend work, confirm:

- `AGENTS.md` and this file were followed.
- Relevant task acceptance criteria are still covered.
- Authorization is explicit.
- Validation failure, success, and important error paths are tested or intentionally unchanged.
- The narrowest relevant checks were run and reported honestly.
