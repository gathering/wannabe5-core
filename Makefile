# Local stack: wannabe5-core + wannabe5-frontend + backend Keycloak
# See docs/development/local-stack.md

ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
BACKEND_ROOT ?= $(ROOT)/../wannabe5-backend
FRONTEND_ROOT ?= $(ROOT)/../wannabe5-frontend
KEYCLOAK_DIR := $(BACKEND_ROOT)/microservices/keycloak
COMPOSE := docker compose
# Browser-facing hosts (/etc/hosts). Loopback only for host→container proxy.
CORE_URL ?= http://wannabe5.gathering.org:8000
CORE_INTERNAL_URL ?= http://127.0.0.1:8000
FRONTEND_URL ?= http://wannabe5.gathering.org:3000
KEYCLOAK_URL ?= http://keycloak.wannabe5.gathering.org:8080
E2E_PW_DIR := $(ROOT)/e2e/playwright
E2E_PW_COMPOSE := FRONTEND_ROOT=$(FRONTEND_ROOT) $(COMPOSE) -f $(E2E_PW_DIR)/compose.yml

.PHONY: help network hosts-check stack-env keycloak-env keycloak-up keycloak-down keycloak-sync-realm-key \
	core-env core-up core-down core-logs core-migrate core-seed core-shell \
	frontend-env frontend-install frontend-up \
	stack-up stack-down stack-status test e2e \
	e2e-playwright-env e2e-playwright-up e2e-playwright-down e2e-playwright e2e-playwright-host

help:
	@echo "wannabe5-core local stack"
	@echo ""
	@echo "  make hosts-check          # remind /etc/hosts entries"
	@echo "  make network              # ensure docker network 'wannabe' exists"
	@echo "  make stack-env            # merge env/local-stack.env into core .env + frontend .env.local"
	@echo "  make keycloak-up          # start backend microservices/keycloak"
	@echo "  make keycloak-sync-realm-key"
	@echo "  make core-up              # docker compose up core (api :8000 + postgres)"
	@echo "  make frontend-up          # pnpm install + pnpm dev in frontend (foreground)"
	@echo "  make stack-up             # network + keycloak + stack-env + realm key + core-up"
	@echo "  make stack-down           # stop core + keycloak"
	@echo "  make stack-status         # quick health checks"
	@echo "  make core-seed            # re-run DatabaseSeeder (local fixtures)"
	@echo "  make e2e                  # black-box HTTP e2e vs live local stack"
	@echo "  make e2e-playwright-up    # Docker frontend for Playwright e2e (:3000)"
	@echo "  make e2e-playwright       # seed + Keycloak users + Playwright in Docker"
	@echo "  make e2e-playwright-host  # same tests on host Node (needs browsers)"
	@echo "  make e2e-playwright-down  # stop Playwright e2e frontend compose"
	@echo "  make test                 # composer test"
	@echo ""
	@echo "Override paths: BACKEND_ROOT=... FRONTEND_ROOT=..."

hosts-check:
	@echo "Ensure /etc/hosts contains:"
	@echo "  127.0.0.1 wannabe5.gathering.org keycloak.wannabe5.gathering.org"
	@if grep -Eq 'keycloak\.wannabe5\.gathering\.org' /etc/hosts; then \
		echo "hosts entry OK"; \
	else \
		echo "WARNING: missing hosts entry — OIDC redirects to keycloak.wannabe5.gathering.org will fail until added." >&2; \
	fi

network:
	@docker network inspect wannabe >/dev/null 2>&1 || docker network create wannabe
	@echo "docker network 'wannabe' ready"

stack-env: core-env frontend-env

core-env:
	@test -f $(ROOT)/.env || cp $(ROOT)/.env.example $(ROOT)/.env
	@bash $(ROOT)/scripts/dev/merge-env.sh $(ROOT)/env/local-stack.env $(ROOT)/.env

frontend-env:
	@FRONTEND_ROOT=$(FRONTEND_ROOT) CORE_URL=$(CORE_INTERNAL_URL) FRONTEND_URL=$(FRONTEND_URL) \
		bash $(ROOT)/scripts/dev/sync-frontend-env.sh

keycloak-env:
	@test -d $(KEYCLOAK_DIR) || (echo "Keycloak not found at $(KEYCLOAK_DIR)"; exit 1)
	@test -f $(KEYCLOAK_DIR)/.env || cp $(KEYCLOAK_DIR)/.env.example $(KEYCLOAK_DIR)/.env

keycloak-up: network keycloak-env
	@cd $(KEYCLOAK_DIR) && $(COMPOSE) up -d
	@echo "Waiting for Keycloak at $(KEYCLOAK_URL)..."
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		curl -fsS $(KEYCLOAK_URL)/realms/wannabe >/dev/null 2>&1 && break; \
		sleep 2; \
	done
	@curl -fsS $(KEYCLOAK_URL)/realms/wannabe >/dev/null
	@echo "Keycloak realm wannabe is up at $(KEYCLOAK_URL)"

keycloak-sync-realm-key:
	@KEYCLOAK_BASE_URL=$(KEYCLOAK_URL) bash $(ROOT)/scripts/dev/sync-keycloak-realm-key.sh $(ROOT)/.env

keycloak-down:
	@test -d $(KEYCLOAK_DIR) && cd $(KEYCLOAK_DIR) && $(COMPOSE) down || true

core-up:
	@test -f $(ROOT)/.env || $(MAKE) core-env
	@cd $(ROOT) && $(COMPOSE) up -d
	@echo "Core API should be on $(CORE_URL) (docker compose)"

core-down:
	@cd $(ROOT) && $(COMPOSE) down

core-logs:
	@cd $(ROOT) && $(COMPOSE) logs -f --tail=100

core-migrate:
	@cd $(ROOT) && $(COMPOSE) exec -T devcontainer php artisan migrate --force

core-seed:
	@cd $(ROOT) && $(COMPOSE) exec -T devcontainer php artisan db:seed --class=DatabaseSeeder --force

core-shell:
	@cd $(ROOT) && $(COMPOSE) exec devcontainer bash

frontend-install:
	@cd $(FRONTEND_ROOT) && (command -v pnpm >/dev/null || npm install -g pnpm)
	@cd $(FRONTEND_ROOT) && pnpm install

frontend-up: frontend-env frontend-install
	@cd $(FRONTEND_ROOT) && pnpm dev

stack-up: hosts-check network keycloak-up stack-env keycloak-sync-realm-key core-up
	@echo ""
	@echo "Stack ready:"
	@echo "  Keycloak:  http://keycloak.wannabe5.gathering.org:8080  (admin/Wannabe5-dev)"
	@echo "  Core API:  $(CORE_URL)/api"
	@echo "  Frontend:  run \`make frontend-up\` (separate terminal) → $(FRONTEND_URL)"
	@echo "  Login:     testbruker / testbruker (imported Keycloak realm)"
	@echo ""
	@$(MAKE) stack-status

stack-down: core-down keycloak-down
	@echo "Core and Keycloak stopped."

stack-status:
	@echo -n "Keycloak realm: "; curl -fsS $(KEYCLOAK_URL)/realms/wannabe >/dev/null && echo OK || echo DOWN
	@echo -n "Core /api:      "; curl -fsS $(CORE_INTERNAL_URL)/api >/dev/null && echo OK || echo DOWN
	@echo -n "Core auth health:"; curl -fsS $(CORE_INTERNAL_URL)/api/auth/health >/dev/null && echo OK || echo DOWN

test:
	@cd $(ROOT) && composer test

e2e:
	@cd $(ROOT) && $(COMPOSE) exec -T devcontainer php artisan db:seed --class=Database\\Seeders\\LocalStackSeeder --force
	@python3 $(ROOT)/scripts/e2e/local_stack_e2e.py

e2e-playwright-env:
	@bash $(E2E_PW_DIR)/scripts/write-frontend-auth-env.sh $(E2E_PW_DIR)/.env.frontend

e2e-playwright-up: network e2e-playwright-env
	@test -d $(FRONTEND_ROOT) || (echo "Frontend not found at $(FRONTEND_ROOT)"; exit 1)
	@cd $(E2E_PW_DIR) && FRONTEND_ROOT=$(FRONTEND_ROOT) $(COMPOSE) -f compose.yml up -d frontend
	@echo "Waiting for e2e frontend at $(FRONTEND_URL)…"
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24; do \
		curl -fsS $(FRONTEND_URL)/liveness >/dev/null 2>&1 && break; \
		sleep 5; \
	done
	@curl -fsS $(FRONTEND_URL)/liveness >/dev/null
	@echo "Playwright e2e frontend is up at $(FRONTEND_URL)"

e2e-playwright-down:
	@cd $(E2E_PW_DIR) && FRONTEND_ROOT=$(FRONTEND_ROOT) $(COMPOSE) -f compose.yml --profile run down --remove-orphans || true

e2e-playwright: hosts-check
	@curl -fsS $(KEYCLOAK_URL)/realms/wannabe >/dev/null || (echo "Keycloak down — run make stack-up"; exit 1)
	@curl -fsS $(CORE_INTERNAL_URL)/api >/dev/null || (echo "Core down — run make stack-up"; exit 1)
	@curl -fsS $(FRONTEND_URL)/liveness >/dev/null 2>&1 || $(MAKE) e2e-playwright-up
	@cd $(ROOT) && $(COMPOSE) exec -T devcontainer php artisan db:seed --class=Database\\Seeders\\LocalStackSeeder --force
	@KEYCLOAK_URL=$(KEYCLOAK_URL) bash $(E2E_PW_DIR)/scripts/ensure-e2e-users.sh
	@cd $(E2E_PW_DIR) && FRONTEND_ROOT=$(FRONTEND_ROOT) $(COMPOSE) -f compose.yml --profile run run --rm --build playwright

e2e-playwright-host: hosts-check
	@curl -fsS $(KEYCLOAK_URL)/realms/wannabe >/dev/null || (echo "Keycloak down — run make stack-up"; exit 1)
	@curl -fsS $(CORE_INTERNAL_URL)/api >/dev/null || (echo "Core down — run make stack-up"; exit 1)
	@curl -fsS $(FRONTEND_URL)/liveness >/dev/null || (echo "Frontend down — run make frontend-up or make e2e-playwright-up"; exit 1)
	@cd $(ROOT) && $(COMPOSE) exec -T devcontainer php artisan db:seed --class=Database\\Seeders\\LocalStackSeeder --force
	@KEYCLOAK_URL=$(KEYCLOAK_URL) bash $(E2E_PW_DIR)/scripts/ensure-e2e-users.sh
	@cd $(E2E_PW_DIR) && npm install && npx playwright install chromium && npx playwright test
