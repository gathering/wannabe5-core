# AGENTS.md

This file defines how contributors and coding agents should work safely in `wannabe5-core`.

## Project Intent

`wannabe5-core` is the consolidation API for Wannabe5.
It exposes:

- native domain endpoints under `/api/{access|auth|application|communication|crew|profile|tasking}/...`,
- core resources (pages, UserProfile, metrics), and
- compatibility adapter endpoints for legacy microservices via `/api/ms/{service}/...`.

`wannabe5-frontend` targets the native `/api/{service}/...` paths (env cutover only; see `docs/parity/frontend-cutover.md`).
Local Keycloak + core + frontend: `make stack-up` / `make frontend-up` (`docs/development/local-stack.md`).

## Architecture Boundaries

- Native API controllers live in `app/Http/Controllers`.
- Domain services, jobs, and parity workflows live in `app/Domain`.
- Route entrypoint is `routes/api.php`.
- Parity tracking lives in `docs/parity/matrix.md`.
- Doc index: `docs/README.md`.

## Required Local Checks Before PR

Run all checks from repository root:

```bash
composer run-script test:lint
composer run-script test:static
composer test
composer run-script test:contracts
composer run-script check:parity
composer run-script check:openapi
```

When changing frontend-facing routes, auth/session behavior, or user/admin page surfaces, also run browser e2e (requires local stack):

```bash
make e2e-playwright
```

## Testing Rules For New Features

- New native API behavior needs feature tests under `tests/Feature` (and contract/parity updates when `/api/ms` or matrix rows change).
- New cross-domain workflow listeners/jobs need `*WorkflowDispatchTest` / domain service coverage (see Event Workflow Rules).
- **New user-facing pages, admin surfaces, or browser flows must get Playwright coverage** under `e2e/playwright/` (update `fixtures/pages-map.md`, `helpers/pages.ts`, and add/adjust specs). Do not ship UI-facing work with only HTTP smoke.
- Prefer extending the role page maps (guest / `normalbruker` / `testbruker`) over one-off scripts.

## API Parity Rules

- Prefer native `/api/{service}` implementations for new work; keep `/api/ms/{service}` behavior intact unless intentionally retiring it.
- Do not remove or rename compatibility adapter routes without updating parity docs and contract tests.
- If an endpoint behavior intentionally differs from legacy backend, document it in `docs/parity/matrix.md`.
- Preserve upstream status codes and response body in compatibility paths unless there is a security or correctness reason not to.
- Profile avatar GETs under `.../profile-image/image*` must stream image bytes (not JSON metadata).

## Event Workflow Rules

- Cross-domain parity side effects are triggered by `App\Domain\Shared\Events\BackendRequestForwarded` (adapter path) and native domain jobs/listeners.
- New workflow listeners/jobs must be covered by feature tests in `tests/Feature/*WorkflowDispatchTest.php` (and/or the relevant `*ServiceTest.php`).

## Documentation Update Rules

When changing API behavior, update:

- `openapi.json` (via `composer run-script check:openapi`),
- `docs/parity/matrix.md`,
- relevant docs under `docs/api`, `docs/testing`, `docs/architecture`, or `docs/parity`,
- `docs/parity/frontend-cutover.md` / `docs/testing/e2e.md` when frontend-facing paths or response contracts change,
- `e2e/playwright/fixtures/pages-map.md` when adding or renaming pages a user/admin can open.
