# End-to-End / Frontend Integration Harness

## Current State

1. Native feature tests per domain (`tests/Feature/*ServiceTest.php`).
2. Frontend path smoke tests (`tests/Feature/FrontendPathSmokeTest.php`).
3. **Local-stack black-box HTTP e2e** (`make e2e` → `scripts/e2e/local_stack_e2e.py`) against live Keycloak + core + frontend.
4. **Playwright browser e2e** (`make e2e-playwright`) in a dedicated Docker stack under `e2e/playwright/`.
5. Compatibility adapter contracts for `/api/ms/{service}`.

## Local stack HTTP e2e

Prerequisites: `/etc/hosts` entries, `make stack-up`, `make frontend-up`, seeded fixtures.

```bash
make e2e          # LocalStackSeeder + scripts/e2e/local_stack_e2e.py (~100+ checks)
make core-seed    # full DatabaseSeeder
```

Covers (black-box against live stack):

- Public health/frontpage/events; guest hidden-event filter
- OIDC login → `wannabe_jwt` claims (`name`, admin `attr:*`)
- Authed crew/event/page/crew/tasking/profile/access/communication/application APIs + `{data:…}` envelopes
- Frontend SSR smoke for event/home/profile/welcome/access/communication/tasks/lists/notify/edit/crew/application routes (fail on Next runtime errors)
- Logged-out FE pages

Postgres uses named volume `wannabe5_core_pgdata`. Container start re-applies `LocalStackSeeder`. Wipe with `docker compose down -v`.

## Playwright browser e2e (Docker)

Dedicated compose stack: `e2e/playwright/compose.yml` (frontend container + Playwright runner). Core and Keycloak still come from `make stack-up`.

```bash
make stack-up              # Keycloak + core
make e2e-playwright        # seed users, ensure Keycloak normalbruker, run Playwright in Docker
# or split:
make e2e-playwright-up     # Dockerized Next on :3000 (skip if make frontend-up already serves)
make e2e-playwright-down
make e2e-playwright-host   # run Playwright on the host instead of the runner container
```

### What it covers

- **PoC**: real Chromium OIDC login (Keycloak form) → `/home` with `wannabe_jwt`
- **Guest** pages: `/`, `/events`, `/goodbye`, `/liveness`
- **Normal user** (`normalbruker` / `normalbruker`): mapped user routes + admin sidenav hidden
- **Admin** (`testbruker` / `testbruker`): user + admin route map + seeded detail routes (crew/task/page/list/template)

Page inventory: `e2e/playwright/fixtures/pages-map.md` (keep in sync with `helpers/pages.ts`).

### Accounts

| Role | Keycloak user | DB attributes (LocalStackSeeder) |
|---|---|---|
| Admin | `testbruker` | full local-stack set (`event:owner`, `access:owner`, …) |
| Normal | `normalbruker` | `user:owner` + `profile:owner` only |

`normalbruker` is in backend `realm.json` for fresh Keycloak imports; existing stacks get the user via `e2e/playwright/scripts/ensure-e2e-users.sh` (fixed UUID aligned with the seeder).

### New feature rule

**Every new user-facing page or admin surface must get a Playwright coverage entry** (update `pages-map.md` + `helpers/pages.ts` + a test under `e2e/playwright/tests/`). Same for meaningful browser flows (forms, nav, role-gated UI). See `AGENTS.md`.

## Frontend URL Contract

```text
${API_URL}/${service}/${route}
```

Local stack uses `API_URL=/api` (browser) + `API_INTERNAL_URL` / `API_PROXY_DESTINATION` → core.

Details: `docs/parity/frontend-cutover.md`, `docs/development/local-stack.md`.

## Profile Paths

- `/api/profile/*` — frontend-compatible profile microservice paths + core UserProfile `{numeric|uuid}`
- Avatar GETs stream JPEG under `.../profile-image/image*`

## Commands

```bash
make e2e
make e2e-playwright
php artisan test --filter=FrontendPathSmokeTest
composer test
```
