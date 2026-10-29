# Testing Harness

## Test Layers

- Feature tests for native API behavior under `tests/Feature`.
- Compatibility adapter contract tests:
  - `tests/Feature/CompatContractTest.php`
  - `tests/Feature/BackendCompatProxyTest.php`
  - `tests/Feature/BackendCompatWorkflowDispatchTest.php`
  - `tests/Feature/CompatSurfaceCoverageTest.php`
- Native microservice lift-in tests:
  - `tests/Feature/AccessServiceTest.php`
  - `tests/Feature/AuthServiceTest.php`
  - `tests/Feature/ApplicationServiceTest.php`
  - `tests/Feature/CommunicationServiceTest.php`
  - `tests/Feature/CrewServiceTest.php`
  - `tests/Feature/ProfileServiceTest.php` (includes JPEG avatar streaming)
  - `tests/Feature/TaskingServiceTest.php`
  - `tests/Feature/InternalAccessParityTest.php`
  - `tests/Feature/CrewNestedAccessTest.php`
  - `tests/Feature/CommunicationDeliveryTest.php`
- Frontend path smoke tests:
  - `tests/Feature/FrontendPathSmokeTest.php`
- Focused denial / fanout / OIDC / cookie-auth feature tests:
  - `tests/Feature/PolicyDenialTest.php`
  - `tests/Feature/AuthOidcCallbackTest.php`
  - `tests/Feature/ApplicationStatusFanoutTest.php`
  - `tests/Feature/WannabeJwtCookieAuthTest.php`
- Unit tests under `tests/Unit`:
  - validation rules (`CountrycodeRuleTest`, `PostcodeRuleTest`)
  - `TrustedRedirectTest`, `ProfileImageProcessorTest`, `ServiceAccessAttributeRegistryTest`

Related docs: `docs/testing/e2e.md`, `docs/parity/frontend-cutover.md`.

Local-stack black-box HTTP e2e: `make e2e`. Browser Playwright e2e (Docker): `make e2e-playwright` — see `docs/testing/e2e.md` and `e2e/playwright/`.

**New features that add or change user/admin-visible pages must extend Playwright coverage** (`e2e/playwright/fixtures/pages-map.md` + specs). See `AGENTS.md`.

## Commands

```bash
make e2e
make e2e-playwright
composer test
composer run-script test:contracts
composer run-script test:lint
composer run-script test:static
composer run-script check:parity
composer run-script check:openapi
```

Frontend path smoke only:

```bash
php artisan test --filter=FrontendPathSmokeTest
```

## What Contract Tests Guarantee

- Upstream response shape / status / multi-value header passthrough for `/api/ms/*`.
- Multipart upload forwarding for profile-style compatibility endpoints.
- Workflow dispatch only after successful upstream responses.
- Route-surface forwarding coverage for all known backend operations (157 across access/application/auth/communication/crew/profile/tasking).
- Inventory source: `tests/Fixtures/parity/backend_route_inventory.php`.
