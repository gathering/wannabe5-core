# Local stack: core + frontend + Keycloak

Run `wannabe5-core` with `wannabe5-frontend` pointed at native `/api/{service}` routes, using the **existing local Keycloak** from `wannabe5-backend/microservices/keycloak`.

## Prerequisites

- Docker + Docker Compose
- Sibling checkouts (default paths):
  - `../wannabe5-backend`
  - `../wannabe5-frontend`
- Node/pnpm for the frontend (`nvm use` in the frontend repo)
- `/etc/hosts` entries (same as backend INSTALL):

```ini
127.0.0.1 wannabe5.gathering.org keycloak.wannabe5.gathering.org
```

## One-shot bring-up

From `wannabe5-core`:

```bash
make stack-up      # network + Keycloak + env merge + realm key sync + core compose
make frontend-up   # separate terminal: writes .env.local, pnpm install, pnpm dev
```

If the frontend shows **503 Unable to reach backend**:

1. Confirm core answers: `curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8000/api` (expect `200`).
2. Core must serve on `0.0.0.0` inside Docker (`compose.yml` uses `php artisan serve --host=0.0.0.0`).
3. Core `.env` must use `DB_HOST=db` (not `127.0.0.1`) when running in compose.
4. After fixing env: `docker compose up -d --force-recreate` then `docker compose exec devcontainer php artisan migrate --force`.
5. Restart the frontend so `.env.local` is reloaded.

Then open (use these hostnames — not `localhost` — so OIDC cookies/redirects match):

| Service | URL |
|---|---|
| Frontend | http://wannabe5.gathering.org:3000 |
| Core API | http://wannabe5.gathering.org:8000/api |
| Keycloak | http://keycloak.wannabe5.gathering.org:8080 (never `localhost:8080`) |
| Keycloak admin | admin / `Wannabe5-dev` (from keycloak `.env.example`) |
| Keycloak login | `testbruker` / `testbruker` (same UUID as DB seed — correct account) |
| Core API token (OpenAPI) | uuid `eaf9efc2-…` / token `testbruker` (DB seed) |

## What the Makefile configures

- Docker network `wannabe` (required by backend Keycloak compose)
- Core joins that network (+ `extra_hosts`) so server-side OIDC calls use `http://keycloak.wannabe5.gathering.org:8080`
- Core `.env` merged from `env/local-stack.env`:
  - `KEYCLOAK_BASE_URL=http://keycloak.wannabe5.gathering.org:8080`
  - `KEYCLOAK_REDIRECT_URI=http://wannabe5.gathering.org:8000/api/auth/callback`
  - `KEYCLOAK_POST_LOGOUT_REDIRECT_URI=http://wannabe5.gathering.org:3000/`
  - shared `AUTH_USER_*` JWT keys
- `KEYCLOAK_REALM_PUBLIC_KEY` refreshed from that hostname after Keycloak starts
- Frontend `.env.local` with:
  - `BASE_URL=http://wannabe5.gathering.org:3000`
  - `API_URL=/api` (browser/login links stay on the frontend origin, rewrite to core)
  - `API_INTERNAL_URL` / `API_PROXY_DESTINATION` → `http://127.0.0.1:8000/api` (host loopback only)
  - `AUTH_PUBLIC_KEY` matching core `AUTH_USER_PUBLIC_KEY`

Login flow: `/api/auth/login` → Keycloak → callback `wannabe5.gathering.org:8000` → frontend `wannabe5.gathering.org:3000`.  
Seeded Keycloak user: **`testbruker` / `testbruker`**.

`localhost` / `127.0.0.1` remain only for Docker port binds and the Next→core proxy — not for browser OIDC.

## Auth notes

- Login/register use native `/api/auth/*` OIDC against local Keycloak (`KEYCLOAK_*` in core `.env`).
- OIDC state is stored in a **file session** (`SESSION_DRIVER=file`, `SESSION_DOMAIN=wannabe5.gathering.org`). API routes enable `StartSession` for this. If you see `Invalid OIDC state`, the session cookie was lost (wrong host, `SESSION_DRIVER=array`, or missing session middleware).
- After login, core sets an RS256 `wannabe_jwt` cookie (same keypair the frontend already verifies).
- Core’s `wannabe5` guard accepts:
  1. Basic access tokens
  2. Bearer Keycloak access tokens
  3. `wannabe_jwt` cookie (frontend path)

## Useful targets

```bash
make help
make stack-status
make core-seed          # re-apply DatabaseSeeder + LocalStackSeeder
make e2e                # seed + black-box e2e (OIDC → frontend/API)
make keycloak-down
make core-logs
make stack-down
```

Postgres data uses the `wannabe5_core_pgdata` volume so `docker compose up --force-recreate` no longer wipes seeds. Fresh wipe: `docker compose down -v`.

Override sibling paths if needed:

```bash
make stack-up BACKEND_ROOT=/path/to/wannabe5-backend FRONTEND_ROOT=/path/to/wannabe5-frontend
```

## Manual fallback (no Make)

1. `docker network create wannabe`
2. `cd ../wannabe5-backend/microservices/keycloak && cp -n .env.example .env && docker compose up -d`
3. Copy Keycloak/OIDC + `AUTH_USER_*` values from `env/local-stack.env` into core `.env`
4. `docker compose up` in core
5. Point frontend `BASE_URL` at `http://wannabe5.gathering.org:3000`, `API_URL=/api`, proxy/internal at `http://127.0.0.1:8000/api`, and set `AUTH_PUBLIC_KEY` to the same public key as `AUTH_USER_PUBLIC_KEY`
