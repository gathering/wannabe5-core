# Native API Surfaces

Preferred client paths (used by `wannabe5-frontend`):

```text
/api/auth/*
/api/access/*
/api/application/*
/api/communication/*
/api/crew/*
/api/profile/*
/api/tasking/*
```

Compatibility escape hatch (legacy mesh forwarder):

```text
/api/ms/{access|application|auth|communication|crew|profile|tasking}/*
```

## Profile dual mount

| Path | Purpose |
|---|---|
| `GET/PUT /api/profile/{numeric\|uuid}` | Core `UserProfile` resource |
| `/api/profile/search`, `/api/profile/profile/...`, `/api/profile/image/...`, `/api/profile/updatefromauth`, … | Legacy profile microservice surface for frontend |
| `/api/profileservice/...` | Same microservice surface, explicit alias |

Avatar binary streaming (legacy-compatible):

| Path | Behavior |
|---|---|
| `GET .../profile-image/image` | Cropped/fitted JPEG (~500×750) |
| `GET .../profile-image/image/thumb` | Cropped/fitted JPEG (~100×150) |
| `GET .../profile-image/image/orginal` | Original stored JPEG (`orginal` spelling retained) |

Implementation: `App\Domain\Profile\Services\ProfileImageProcessor`.

## Auth session cookie

Frontend session refresh uses:

```text
POST|GET /api/auth/refresh-session
```

with `credentials: include` so `wannabe_jwt` can be refreshed. Keycloak OIDC settings live in `.env` (`KEYCLOAK_*`).

## OpenAPI

Regenerate and gate with:

```bash
composer run-script check:openapi
```

See `docs/api/openapi-workflow.md`.
