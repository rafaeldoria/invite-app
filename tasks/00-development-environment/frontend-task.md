# Frontend Task: Vite Container and Asset Runtime

## Objective

Make the existing React/Inertia/Tailwind frontend build and hot reload reliably in the Docker development environment and build deterministic production assets.

## Implementation Steps

1. Decide between a dedicated pinned Node container and a documented host Node requirement. Prefer a Node container if it avoids host-version drift without making filesystem/HMR behavior fragile.
2. Use `npm ci` from the committed lockfile for deterministic installs. Keep `node_modules` out of bind-mount conflicts and Git.
3. Configure Vite host/port/HMR values through environment variables that work when the browser accesses Nginx from the host.
4. Preserve Laravel Vite refresh behavior without watching generated storage/vendor paths excessively.
5. Define production asset build as a separate reproducible step; Nginx serves built versioned assets and Laravel emits the manifest references.
6. Document supported Node/npm versions and the exact local commands for install, dev server, and build.
7. Do not add a frontend framework, package manager, proxy layer, or component tooling as part of environment setup.

## Acceptance Criteria

- A `.tsx` and Tailwind edit updates in the browser through HMR from the documented local URL.
- `npm ci` and `npm run build` succeed in a clean environment.
- Production Nginx/Laravel serves versioned assets without the Vite dev server.
- Node dependency state remains deterministic and no root-owned workspace files are produced.
- Environment variables contain no secret and use the existing Vite conventions.

## Task Test Plan

- Clean install/build twice and compare that both complete from the lockfile.
- Start Docker stack, edit a component/style manually, observe HMR, then revert the test edit.
- Stop Vite and verify development failure is clear; build production assets and verify normal page load.
- Inspect browser console/network for websocket, mixed-content, manifest, and 404 errors.

