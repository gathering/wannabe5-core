# Playwright browser e2e

Docker-based Chromium tests against the local Wannabe5 stack (Keycloak + core + frontend).

See `docs/testing/e2e.md` and `fixtures/pages-map.md`.

```bash
make stack-up
make e2e-playwright        # or: make frontend-up && make e2e-playwright
```
