# Frontend page map (Playwright e2e)

Source of truth for which routes roles should open. Derived from `wannabe5-frontend/pages/**` and sidenav `NAV_ITEMS` in `src/_partials/navigation.js`.

Fixture event slug: **`tg26`** (LocalStackSeeder).

## Guest (logged out)

| Path | Notes |
|---|---|
| `/` | Landing + login/register links |
| `/events` | Public events list |
| `/goodbye` | Logged-out / session end |
| `/liveness` | Health |

## Normal user (`normalbruker`)

Seeded with self `user:owner` + `profile:owner` only (no `event:owner` / `access:owner`).

| Path | Notes |
|---|---|
| `/home` | Post-login home |
| `/welcome`, `/welcome/profile-creation`, `/welcome/complete` | Onboarding |
| `/profile`, `/profile/show/{id}`, `/profile/edit/{id}`, `/profile/profile-picture/{id}` | Own profile |
| `/tg26` | Event frontpage |
| `/tg26/application` | Apply (visible when not crew member) |
| `/tg26/tasks` | Own tasks |
| `/tg26/profile`, `/tg26/profile/show/{id}` | Event-scoped profile |

Admin routes are **hidden from sidenav** for this role (FE does not hard-block deep links; API enforces privileges). Playwright asserts nav labels are absent.

## Admin user (`testbruker`)

Full local-stack attribute set (`event:owner`, `access:owner`, …).

Includes all normal-user paths (as admin) plus:

| Path | Nav / purpose |
|---|---|
| `/access/user`, `/access/user/edit/{id}` | Access → Users |
| `/access/attribute-type`, `/…/create` | Access → Attribute types |
| `/debug/access` | Debug → Access |
| `/communication/notification` | Communication |
| `/communication/template` | Communication |
| `/communication/message` | Communication |
| `/tg26/edit`, `/tg26/edit/questions` | Event → Configure |
| `/tg26/pages/manage` | Event → Pages |
| `/tg26/profile-images` | Event → Profile images |
| `/tg26/tasks/manage`, `/tg26/tasks/manage/create` | Event → Tasks |
| `/tg26/crew/manage`, `/tg26/crew/create`, `/tg26/crew` | Crew admin |
| `/tg26/lists/manage` | Lists |
| `/tg26/notify` | Notify |
| `/tg26/member` | Member search |

Dynamic detail routes (`/tg26/crew/{cid}/…`, `/tg26/tasks/{tid}`, `/tg26/pages/{pid}`, `/tg26/lists/{lid}`, communication edit/preview) are covered when seeds expose IDs (see `tests/admin-detail-pages.spec.ts`).

## Rule for new features

Any new user-facing page or admin surface must:

1. Be listed here (and in `helpers/pages.ts`), and
2. Get a Playwright coverage assertion in `e2e/playwright/tests/`.
