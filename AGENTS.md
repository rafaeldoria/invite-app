# Project

This project is a web platform for creating, managing, and sharing invitations and events.

Its primary goals are to let organizers:

- Create events.
- Share invitations through links.
- Manage guest lists.
- Receive attendance responses (RSVPs).
- Quickly understand which guests are confirmed, declined, or pending.

The current priority is to validate the product quickly with a lean MVP. Product decisions should favor learning and delivery over speculative flexibility.

## Language

- All source code, identifiers, comments, tests, commit-facing technical text, and generated files must be written in English.
- User-facing copy should also default to English unless a product requirement explicitly defines another locale.
- Keep domain terminology consistent: use `Event`, `Guest`, `Invitation`, and `RSVP` throughout the codebase.

# Technology Stack

## Verified in This Repository

### Backend

- PHP `^8.4.1`, enforced by Composer and used by Docker and CI.
- Laravel 13.
- Inertia.js Laravel adapter 3.
- PHPUnit 12 for automated tests.

### Frontend

- React 19.
- Inertia.js React adapter 3.
- TypeScript with strict mode enabled.
- Tailwind CSS 4.
- Vite 8.

### Database

- PostgreSQL is the intended application database.
- The repository currently defaults to SQLite in `.env.example`, and the test suite uses in-memory SQLite. Do not assume PostgreSQL-specific behavior is already covered by tests.

## Target Infrastructure

- Docker and Docker Compose for local development.
- Linux VPS for production.
- Nginx as the web server and reverse proxy.
- Cloudflare for DNS and SSL.

Docker, Nginx, and Cloudflare configuration is not currently present in this directory. Treat these as target infrastructure choices, not existing implementations.

# Current Architecture

The application is at an early scaffold stage:

- Laravel owns routing, HTTP concerns, validation, authorization, persistence, and business workflows.
- Inertia connects Laravel routes and controllers to React pages without a separate REST API unless one is genuinely required.
- React pages live under `resources/js/pages` and are resolved by `resources/js/app.tsx`.
- Shared frontend components, hooks, types, and utilities should be introduced only as the application needs them, using consistent directories under `resources/js`.
- Tailwind is the styling system; global CSS lives in `resources/css/app.css`.
- Feature and unit tests live in `tests/Feature` and `tests/Unit`.

Prefer Laravel and Inertia conventions over custom architectural layers. Preserve existing patterns when extending a feature.

# Project Philosophy

Prioritize:

- Simplicity.
- Clarity.
- Maintainability.
- A strong user experience.
- Low operational cost.
- Fast delivery of value.

Avoid:

- Overengineering.
- Unnecessary complexity.
- Premature abstractions.
- Features that have not been validated.
- New dependencies when the framework or existing stack already solves the problem well.

Always implement the simplest solution that correctly solves the current problem. Build for the known MVP, while keeping code clean enough to change when real requirements emerge.

# Laravel Practices

Before developing or changing any backend code, read `backend_best_practices.md` and apply it together with this `AGENTS.md`. The backend guide is the project playbook for Actions, controller boundaries, Form Requests, policies, persistence side effects, and keeping MVP code simple.

Every implementation task must start by reading this `AGENTS.md` from the repository so current project rules are loaded before inspecting or editing code.

## Controllers and Business Logic

- Keep controllers small and focused on HTTP orchestration: authorize, validate, call the relevant application logic, and return a response.
- Do not place substantial business rules or complex queries in controllers.
- Keep simple single-use operations close to the framework conventions. Introduce a service or action class only when logic is complex, reused, transactional, or benefits from isolated testing.
- Prefer dependency injection over service location and static application access when practical.
- Use database transactions for workflows that must succeed or fail as a unit.

## Validation and Authorization

- Use dedicated Form Request classes for create/update operations and any non-trivial validation.
- Keep validation messages and field names clear and consistent.
- Authorize access explicitly with policies, gates, or Form Request authorization. Never rely only on hidden UI controls.
- Treat public invitation links as untrusted input. Use opaque, non-sequential tokens and avoid exposing private guest or organizer data.

## Eloquent and Queries

- Keep models readable and focused on relationships, casts, scopes, and domain behavior that naturally belongs to the model.
- Define relationship return types and useful attribute casts.
- Prefer expressive Eloquent queries over clever or deeply nested query code.
- Prevent N+1 queries with intentional eager loading (`with`, `load`, or `loadMissing`).
- Select only the data needed when queries or payloads become large, but do not prematurely micro-optimize small MVP queries.
- Use pagination for guest lists and other collections that can grow.
- Do not hide expensive database access inside accessors or loops.

## Migrations, Factories, and Seeders

- Make migrations explicit, reversible where practical, and safe for the current deployment strategy.
- Add foreign keys, indexes, nullability, uniqueness, and delete behavior intentionally.
- Never edit a migration that may already have run in a shared or production environment; add a new migration instead.
- Keep factories capable of creating valid records with sensible defaults.
- Use factory states for meaningful variants such as confirmed, declined, and pending guests.
- Keep seeders deterministic enough for local development and demonstrations; never include real personal data or secrets.

## Tests

- Add or update automated tests for business rules, authorization, validation, persistence, RSVP flows, and regressions.
- Prefer feature tests for HTTP and Inertia behavior; use unit tests for isolated domain logic with real value.
- Test success paths and important failure paths without testing framework internals.
- Remember that tests currently run on SQLite. Validate database-sensitive behavior against PostgreSQL when introducing PostgreSQL-specific SQL, constraints, or data types.

# React Practices

## Components and Organization

- Keep components small, focused, and easy to understand.
- Keep page-level components in `resources/js/pages`.
- Place reusable UI components in a consistent shared directory such as `resources/js/components` once reuse actually exists.
- Group feature-specific components, hooks, and types together when that improves discoverability.
- Extract components because they represent a meaningful unit or are reused, not merely to reduce line count.
- Avoid duplicated markup and behavior, but do not create generic abstractions before the common shape is clear.

## State and Hooks

- Prefer server-provided Inertia props for server-owned state.
- Keep transient UI state local to the component that owns it.
- Use Inertia forms and navigation tools for Laravel-backed workflows.
- Use hooks only at the top level and keep dependency arrays correct.
- Extract custom hooks when stateful behavior is reused or makes a component materially easier to understand.
- Avoid effects for values that can be derived during rendering.
- Do not introduce a global state library unless the application has a demonstrated need that Inertia and local state cannot address cleanly.

## TypeScript and UI Logic

- Keep strict TypeScript enabled.
- Fully type component props, page props, form data, shared domain shapes, and function boundaries.
- Avoid `any`; use `unknown` and narrow it when data is genuinely uncertain.
- Keep complex transformations, validation rules, and business decisions outside presentational components.
- Do not duplicate authoritative backend business rules in the frontend. Client-side validation may improve feedback, but the server remains authoritative.

# Interface and User Experience

## Frontend/UI Requirements

- Use the Impeccable skill whenever creating or modifying UI.
- Run `critique` before considering a screen complete.
- Run `audit` before opening a pull request.
- Run `polish` before final delivery.
- Prioritize UX, readability, and responsiveness over visual effects.

The application must be:

- Responsive.
- Mobile-first.
- Intuitive.
- Fast.
- Modern.
- Accessible.

Prioritize user experience over elaborate visual effects.

- Build layouts for small screens first, then enhance them for larger viewports.
- Use semantic HTML, visible labels, logical heading order, and keyboard-accessible controls.
- Maintain sufficient color contrast and clear focus states.
- Do not use color as the only way to communicate RSVP status or errors.
- Provide clear loading, success, empty, validation, and error states.
- Keep primary actions obvious and destructive actions deliberate.
- Minimize form friction, especially in the public RSVP flow.
- Respect reduced-motion preferences and avoid animation that delays interaction.

# Initial MVP

Implement only the minimum required for the following scope.

## Event

- Name.
- Description.
- Date.
- Time.
- Location.
- Cover image.

## Guests

- Manual guest registration.
- An individual invitation link for each guest.

## RSVP

- Confirm attendance.
- Decline attendance.
- Provide the number of accompanying guests.

## Dashboard

- Total guests.
- Confirmed guests.
- Declined guests.
- Pending guests.

Keep status definitions centralized and explicit. Dashboard totals must be derived consistently from the same RSVP states used by guest management.

# Out of Scope for the Initial Release

Do not implement these features unless the product scope is explicitly changed:

- Payments.
- Gift lists.
- QR codes.
- Check-in.
- Artificial intelligence features.
- Complex integrations.
- Native mobile applications.
- Multi-company support.
- Multi-tenancy.

Do not add schema, services, dependencies, or generalized abstractions in anticipation of these features.

# Development Workflow

Before implementing any feature:

1. Understand the user problem and the relevant existing code.
2. Identify the smallest complete solution.
3. Reuse existing code and framework features whenever possible.
4. Avoid unnecessary abstractions and dependencies.
5. Follow established project patterns.
6. Preserve architectural and naming consistency.
7. Define the important acceptance cases, including failures and empty states.

While working:

- Keep changes focused on the requested task.
- Do not refactor unrelated code without a clear need.
- Never commit secrets, credentials, `.env` files, or real guest data.
- Preserve backward compatibility unless the task explicitly requires a breaking change.
- Update tests when behavior changes.
- Run the narrowest relevant checks first, then broader checks when warranted.

Useful repository commands include:

```bash
composer test
./vendor/bin/pint --test
npm run build
```

The repository does not currently define a dedicated frontend lint or test script. Do not claim those checks ran unless the tooling has been added and executed.

When finishing a task, briefly report:

- What changed.
- The main decisions or tradeoffs.
- Which checks were run and their results.
- Any relevant limitation, follow-up, or assumption.

# Guidance for AI Agents

- Read this file and inspect the relevant code before making changes.
- Treat the repository as the source of truth when documentation and implementation differ.
- Ask for clarification only when a missing product decision materially changes the solution; otherwise make the simplest reasonable assumption and state it at handoff.
- Do not invent existing infrastructure, conventions, endpoints, models, or completed features.
- Do not expand the MVP scope as part of an unrelated task.
- Use English for every new or modified code artifact.
- Keep public invitation and RSVP flows privacy-conscious and resistant to token guessing.
- Explain decisions concisely when handing work back.

# Final Objective

Build a simple, modern, and scalable invitation and event management platform, beginning with an extremely lean MVP centered on RSVP collection and guest management. Scalability here means a clear design that can evolve with validated demand, not complexity added in advance.
