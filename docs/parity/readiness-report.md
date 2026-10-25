# Parity Readiness Report

## Status

- Date: 2026-10-25
- Branch: `feature/microservice-parity-native`
- Scope: Native lift-in of legacy microservice domains into core, plus compatibility adapter retained for gradual cutover.
- CI gates: lint, static analysis, tests, contract tests, parity matrix check, OpenAPI diff (`.github/workflows/ci.yaml`).

## Done

- Native domain APIs for access, auth, application, communication, crew, profile, and tasking under `/api/{service}/...`.
- Compatibility adapter `/api/ms/{service}/...` with explicit route inventory (`config/backend_compat_routes.php`, 157 operations).
- Keycloak OIDC login/register/callback/token refresh when `KEYCLOAK_*` is configured; `wannabe_jwt` cookie refresh.
- Communication delivery providers (mail / SMS / Slack) with log fallback.
- Nested crew attribute expansion for `_internal/access`.
- Profile image upload, moderation (approve/decline), crop metadata, and **binary JPEG streaming** for default/`thumb`/`orginal` (frontend `<img>` compatible).
- Frontend path alias: `/api/profile/*` serves the profile microservice surface; `/api/profileservice/*` kept as alias; core UserProfile remains on `/api/profile/{numeric\|uuid}`.
- Frontend path smoke tests (`tests/Feature/FrontendPathSmokeTest.php`).
- Parity matrix, OpenAPI export gate, contributor docs (`AGENTS.md`, `docs/*`).

## Remaining (outside this baseline)

- Live Keycloak + SMS gateway credentials in real environments.
- Deterministic seed data for full SSR page walks.
- Playwright (or similar) browser e2e in `wannabe5-frontend`.
- Optional deeper endpoint-level matrix rows beyond capability clusters.
- Retire `/api/ms/*` only after external consumers no longer need it.

## Cutover pointers

- Frontend: `docs/parity/frontend-cutover.md`
- E2E/smoke: `docs/testing/e2e.md`
- Matrix: `docs/parity/matrix.md`
