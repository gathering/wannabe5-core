# Documentation Harness

Index of contributor docs for `wannabe5-core`.

## Architecture

- `docs/architecture/overview.md` — native APIs, compatibility adapter, workflow hooks
- `docs/development/local-stack.md` — Makefile stack: core + frontend + backend Keycloak

## API

- `docs/api/openapi-workflow.md` — Scramble export and CI drift check
- `docs/api/native-surfaces.md` — native `/api/{service}` surface summary

## Parity / Cutover

- `docs/parity/matrix.md` — backend → core capability matrix (source of truth)
- `docs/parity/frontend-cutover.md` — point `wannabe5-frontend` at core
- `docs/parity/readiness-report.md` — current readiness snapshot

## Testing

- `docs/testing/harness.md` — test layers and composer scripts
- `docs/testing/e2e.md` — HTTP e2e + Playwright Docker browser e2e

## Agent / PR Guidance

- `AGENTS.md` (repo root) — boundaries, required checks, doc update rules
- `DEVELOPMENT.md` (repo root) — local lint/test/OpenAPI workflow
