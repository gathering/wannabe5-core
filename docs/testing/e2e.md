# End-to-End / Frontend Integration Harness

## Current State

1. Native feature tests per domain (`tests/Feature/*ServiceTest.php`).
2. Frontend path smoke tests (`tests/Feature/FrontendPathSmokeTest.php`).
3. **Local-stack black-box e2e** (`make e2e` → `scripts/e2e/local_stack_e2e.py`) against live Keycloak + core + frontend.
4. Compatibility adapter contracts for `/api/ms/{service}`.

Browser Playwright in `wannabe5-frontend` remains optional; the Python harness covers the login/cookie/API/SSR happy path without a browser driver.

## Local stack e2e

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
php artisan test --filter=FrontendPathSmokeTest
composer test
```
