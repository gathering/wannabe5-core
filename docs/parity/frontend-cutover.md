# Frontend Cutover Guide (`wannabe5-frontend` → `wannabe5-core`)

## Goal

Run `wannabe5-frontend` against consolidated core native APIs (`/api/{service}/...`) with **no frontend code changes**. Keep `/api/ms/{service}/...` only as a compatibility escape hatch for other consumers.

## Env-only cutover

Preferred (from core repo, with sibling frontend/backend checkouts):

```bash
make stack-up
make frontend-up
```

That merges OIDC + shared JWT keys and writes `wannabe5-frontend/.env.local`. See `docs/development/local-stack.md`.

Manual equivalent in `wannabe5-frontend/.env` / `.env.local`:

```bash
API_URL=http://localhost:8000/api
API_PROXY_DESTINATION=http://localhost:8000/api
BASE_URL=http://localhost:3000
AUTH_PUBLIC_KEY={"key":"<RS256 public key that verifies wannabe_jwt>"}
```

Browser calls to `/api/...` are rewritten by Next.js to core when `API_PROXY_DESTINATION` is set.

## Path map

| Frontend `service` | Path prefix | Core target |
|---|---|---|
| `auth` | `/api/auth/...` | Native auth (OIDC + refresh-session) |
| `access` | `/api/access/...` | Native access |
| `application` | `/api/application/...` | Native application |
| `communication` | `/api/communication/...` | Native communication |
| `crew` | `/api/crew/...` | Native crew |
| `profile` | `/api/profile/...` | Profile microservice surface (+ core UserProfile on numeric/UUID ids) |
| `tasking` | `/api/tasking/...` | Native tasking |

Frontend does **not** call `/api/ms/...`.

## Avatar images

`<img src="/api/profile/profile/{id}/profile-image/image[/thumb|/orginal]">` receives JPEG bytes from core (same contract as the legacy profile service).

## Phases

1. Path smoke in core — **done** (`FrontendPathSmokeTest`).
2. Local dual-run with env pointing at core — **manual**.
3. Browser Playwright e2e in core (`make e2e-playwright`, `e2e/playwright/`) — **PoC + role page maps**.
4. Deprecate legacy mesh / `/api/ms` when unused — **later**.

## Still required at runtime (not frontend code)

- Matching Keycloak realm and JWT verification key between frontend and core.
- OIDC callback / post-logout redirect URIs aimed at the running stack.
- Seeded events/crews/profiles for SSR pages that assume data exists.
- Communication SMS/Slack credentials if those channels must deliver for real.

## Verification

```bash
php artisan test --filter=FrontendPathSmokeTest
```

Manual checklist:

- [ ] Frontend boots with `API_URL` / `API_PROXY_DESTINATION` targeting core
- [ ] `/api/auth/refresh-session` refreshes `wannabe_jwt`
- [ ] Profile show loads `/api/profile/profile/{id}` and avatar `<img>` renders
- [ ] Crew event index loads `/api/crew/events`
- [ ] Application comments load `/api/application/{id}/comments`

### Core security hardening

Existing frontend routes, HTTP methods and envelopes are retained. Image moderation PUT requests require moderator permission; owners cannot self-approve. Session renewal requires the server session created by OIDC login as well as the JWT cookie. SSR reads remain JWT-only compatible. See [security boundaries](../architecture/core-security-boundaries.md). No frontend package or environment change is required for the existing same-host proxy deployment.
