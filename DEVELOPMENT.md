# How to develop on Wannabe5-Core

Doc index: [`docs/README.md`](docs/README.md). Agent rules: [`AGENTS.md`](AGENTS.md).

## Local stack (core + frontend + Keycloak)

Uses the existing Keycloak service from `wannabe5-backend/microservices/keycloak`:

```bash
make stack-up       # Keycloak + core API
make frontend-up    # separate terminal — frontend against core
```

Details: [`docs/development/local-stack.md`](docs/development/local-stack.md).

## Configure pre-commits

```bash
php artisan app:precommit
```

## Linting

PRs must pass Pint. If hooks fail:

```bash
./vendor/bin/pint
```

## Full readiness checks

Run before opening a pull request:

```bash
composer run-script test:lint
composer run-script test:static
composer test
composer run-script test:contracts
composer run-script check:parity
composer run-script check:openapi
```

Frontend path smoke (subset):

```bash
php artisan test --filter=FrontendPathSmokeTest
```

## Generate OpenAPI documentation

```bash
php artisan scramble:export
# or
composer run-script check:openapi
```

## Native domains vs compatibility adapter

- Prefer implementing behavior on native `/api/{service}/...` controllers.
- Keep `/api/ms/{service}/...` contracts green unless retiring an adapter route (update matrix + contract tests).
- Frontend cutover is env-only; see [`docs/parity/frontend-cutover.md`](docs/parity/frontend-cutover.md).

## Creating a new model

Prefer Resources, Requests, and lean Controllers. Put validation in Requests and return Resources.

```bash
php artisan make:request <ModelName>Request
php artisan make:resource <ModelName>Resource
php artisan make:factory <ModelName>Factory
```

Laravel docs: [Resources](https://laravel.com/docs/12.x/eloquent-resources), [Requests](https://laravel.com/docs/12.x/validation#form-request-validation).
