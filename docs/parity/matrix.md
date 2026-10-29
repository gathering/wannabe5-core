# Wannabe5 Backend -> Core Parity Matrix

This matrix is the implementation source of truth for parity work from `../wannabe5-backend` into `wannabe5-core`.

Status values:
- `done`: implemented + tested in core.
- `in_progress`: partially implemented or implemented without full tests.
- `todo`: not implemented in core yet.
- `intentional_delta`: behavior intentionally differs and is documented.

## Coverage Table

| Domain | Backend source | Capability cluster | Core target module | API/behavior status | Test status | Notes |
|---|---|---|---|---|---|---|
| Auth | `microservices/auth` | Session + service auth flows (`login`, `refresh-session`, `services/login`, `logout/profile`) | `app/Domain/Auth` | done | done | Native `/api/auth/*` with Keycloak OIDC redirects/callback/token refresh when configured, trusted redirect sanitization, wannabe_jwt cookies, and service-login scope/upstream client-credentials support. |
| Access | `microservices/access` | Users/attributes/attribute-types + `_internal/access` | `app/Domain/Access` | in_progress | in_progress | Native CRUD/user-check + `_internal/access`/`liveness`; attribute policy baseline added. |
| Application | `microservices/application` | Applications, questions, answers, comments, status transitions | `app/Domain/Application` | in_progress | in_progress | Native CRUD/status/Q&A/comments + status-transition side effects fanout to communication; ownership policies + per-event/crew limits added. |
| Communication | `microservices/communication` | Messages/templates/notifications/lists + preview/schema + batch-send | `app/Domain/Communication` | done | done | Native CRUD + prepare/send pipeline with mail/SMS/Slack delivery providers (mail via Laravel Mail; SMS/Slack webhook/log hooks). |
| Crew | `microservices/crew` | Events/crews/users/teams/pages + public/private visibility rules | `app/Domain/Crew` | done | done | Native events/crews/users/teams/pages + nested child-crew manager/owner attribute expansion + team attributes in `_internal/access`. Membership listings eager-load `crew`/`role`/`team`/profiles (with `{data}` envelope for member search), expand organizer child crews for sidenav, and `?pretty` crew show includes users/teams/roles. |
| Profile | `microservices/profile` | Profile CRUD/search, crew history, profile images, moderation states | `app/Domain/Profile` | done | done | Native `/api/profile/*` (frontend path) + `/api/profileservice/*` alias; resized uploads, approve/decline moderation, approved-image overwrite protection, crop metadata, and binary JPEG streaming for default/`thumb`/`orginal` avatar URLs. Core UserProfile resource remains on `/api/profile/{numeric\|uuid}`. |
| Frontend cutover | `wannabe5-frontend` | `doApiRequest` `/api/{service}/...` path parity + e2e plan | docs + `FrontendPathSmokeTest` | done | in_progress | Env-only cutover documented; path smoke + avatar streaming done in core. Browser Playwright e2e still pending in frontend repo. |
| Tasking | `microservices/tasking` | Event/crew/user task views, responses, approvals | `app/Domain/Tasking` | in_progress | in_progress | Native listings/responses/approvals + `_internal/access`; task save accepts frontend `questions` payload and always returns 200 (incl. first create) for FE success checks; `skipped` persisted. Advanced recipient filters still pending. |
| Observability | multiple services | `/_internal/access`, `/_internal/metrics`, liveness | cross-domain | done | done | Native `_internal/access`, `_internal/metrics` (where applicable), and `liveness` wired per domain. |
| OpenAPI | per-service `openapi.json` + swagger build scripts | Deterministic API contract artifacts | `openapi.json` + `docs/api` harness | done | done | `openapi.json` regenerated and CI openapi diff check is clean for the expanded native surface. |
| Event flows | event subscribers/listeners in backend | Cross-domain side effects and eventual consistency | `app/Domain/*/Events` and listeners | in_progress | in_progress | Auth->profile sync, application status->communication fanout, and communication prepare/send jobs are wired. |

## Endpoint Group Checklist

### Auth
- [x] Login/session refresh/logout/profile flow behavior mirrored.
- [x] Service login behavior mirrored.
- [x] Cookie/JWT side effects documented and tested.
- [x] Keycloak OIDC redirect/callback/token-refresh when configured.

### Access
- [x] Users endpoint cluster.
- [x] Attributes CRUD.
- [x] Attribute types CRUD.
- [x] `_internal/access` and liveness behavior.

### Application
- [x] Application CRUD.
- [x] Status transition endpoints and rules.
- [x] Questions and answers lifecycle.
- [x] Application comments behavior.

### Communication
- [x] Messages CRUD + retry/schema/preview + batch send.
- [x] Templates CRUD + schema/preview.
- [x] Notifications CRUD + schema.
- [x] Lists, list owners, list recipients CRUD.

### Crew
- [x] Events CRUD + visibility constraints.
- [x] Crew CRUD in event scope.
- [x] Crew user assignment + chief/organizer logic.
- [x] Teams endpoints.
- [x] Page endpoints (`frontpage` and event pages).

### Profile
- [x] Profile create/update/delete/search parity.
- [x] Crew history endpoint parity.
- [x] Profile image CRUD + resize/crop + moderation state list.
- [x] Binary avatar streaming for default / `thumb` / `orginal` (frontend `<img>` contract).
- [x] Frontend path mount under `/api/profile/*` (+ `/api/profileservice` alias).
- [x] Update-from-auth behavior.

### Frontend cutover
- [x] Native `/api/{service}` paths match `wannabe5-frontend` `doApiRequest` shapes.
- [x] Path smoke tests in core (`FrontendPathSmokeTest`).
- [ ] Browser Playwright e2e in `wannabe5-frontend` (tracked separately).

### Tasking
- [x] Event task listings.
- [x] User/crew scoped task retrieval.
- [x] Response submission and approval workflow.

## Cross-Cutting Readiness Gates

- [x] Route/controller parity complete (no registered action without implementation).
- [x] Policy/authorization matrix parity.
- [x] Validation and error-shape parity for critical workflows.
- [x] OpenAPI generated and diff-clean in CI.
- [x] Feature tests for each domain capability cluster.
- [x] Event workflow integration tests for async side effects.
- [x] Frontend path smoke + avatar binary streaming for cutover without frontend code changes.

## Backend Route Inventory

The compatibility surface in `tests/Fixtures/parity/backend_route_inventory.php` currently tracks:

- Access: 19 operations
- Application: 25 operations
- Auth: 11 operations
- Communication: 41 operations
- Crew: 29 operations
- Profile: 20 operations
- Tasking: 12 operations

Total: 157 operations validated through `/api/ms/{service}/...` in `CompatSurfaceCoverageTest`.


## Native security hardening

Native authorization is enforced for access administration, application decisions and related content, profile aliases and image moderation, communication resources, crew writes, task responses, and core pages. See [security boundaries](../architecture/core-security-boundaries.md) for the permission rules and remaining coordinated work. The earlier policy-parity checklist is a capability inventory, not proof of exhaustive legacy equivalence. Security restrictions intentionally reject requests previously accepted from unrelated authenticated users.
