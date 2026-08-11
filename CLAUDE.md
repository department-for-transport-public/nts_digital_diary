# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

DfT **National Travel Survey (NTS) Digital Diary** — a Symfony 6.4 application (PHP 8.3+) where household members ("diary keepers") record travel journeys, interviewers manage households, and admins run allocation/export. Frontend uses the GOV.UK Design System via the `ghost/govuk-*` bundles; APIs are served with API Platform 4. Deployed to Google App Engine via Cloud Build.

## Commands

Console entrypoint is `bin/console`. Custom app commands use the `nts:` prefix (e.g. `nts:form-wizard:list`, `nts:maintenance-mode:lock`, `nts:dev:add-household`).

```bash
# Tests (PHPUnit 9.5 via symfony/phpunit-bridge)
bin/phpunit                                   # full suite (APP_ENV=test forced by phpunit.xml.dist)
bin/phpunit --testsuite unit                  # unit only
bin/phpunit --testsuite functional            # functional (Panther/browser) only
bin/phpunit tests/Utility/TimeHelperTest.php  # single file
bin/phpunit --filter testMethodName           # single test
# Suppress deprecation noise when it obscures failures:
SYMFONY_DEPRECATIONS_HELPER=disabled bin/phpunit ...

# Static analysis (PHPStan level 1; config phpstan.dist.neon)
vendor/bin/phpstan analyse

# Frontend assets (Webpack Encore, yarn 4)
yarn dev            # dev build
yarn watch          # dev build + watch
yarn build          # production build

# Database
bin/console doctrine:migrations:migrate       # apply migrations (migrations/)
bin/console doctrine:migrations:diff          # generate a migration from entity changes

# Async message workers (Doctrine transport — see Messenger below)
bin/console messenger:consume async async_notify async_property_change async_feedback async_job
```

Test DB is SQLite (`.env.test`: `DATABASE_URL=sqlite:////dev/shm/test2.sqlite`); dev/prod are MySQL/MariaDB. Functional tests boot fixtures via `liip/test-fixtures-bundle` (see `tests/AbstractFixturesTest::bootKernelAndLoadFixtures`).

## Architecture — the parts that span many files

### Two hosts, two worlds (read this first)
The app serves **two logical sites off one codebase**, distinguished by hostname:
- **Frontend** host (`%domain_name%`) — diary keepers, interviewers, onboarding, public API.
- **Admin** host (`admin.<domain>`, separator configurable via `APP_HOSTNAME_SEPARATOR`) — admin-only tools.

This split is enforced in `config/packages/security.yaml` via the `regex.frontend_host` / `regex.admin_host` parameters, and mirrored in routing (`config/routes/annotations.yaml`) where each controller subfolder is bound to `%hostnames.frontend%` or `%hostnames.admin%`. When adding a controller/route, put it under the correct `src/Controller/<Area>/` subfolder — routing, name prefix, URL prefix, and host are all inherited from that subfolder's entry in `annotations.yaml` (no `app_` default prefix).

### Security: five firewalls, several auth mechanisms
Defined in `config/packages/security.yaml`. Each area authenticates differently:
- `main` (frontend) — DB users via `form_login`, `App\Entity\User`. Supports `switch_user` impersonation (`CAN_IMPERSONATE_USER`).
- `onboarding` — one-time-password (OTP) auth via `App\Security\OneTimePassword\*` authenticators + chained OTP user providers.
- `api` (`^/api/`) — stateless HMAC signing, `App\Security\HmacAuth\HmacAuthenticatorV2` (V1 kept only to return a helpful "upgrade your client" error).
- `admin` — Google IAP (`App\Security\GoogleIap\IapAuthenticator`), stateless.
- `dev` — profiler/assets, security off.

Role hierarchy (in the same file) is the source of truth: `ROLE_DIARY_KEEPER`/`ROLE_INTERVIEWER`/`ROLE_ON_BOARDING` → `ROLE_USER`; feedback/interviewer-admin/sample-importer → `ROLE_ADMIN`; `ROLE_SUPER_ADMIN` → `[ROLE_MAINTENANCE, ROLE_ADMIN]`. Access is gated per path in `access_control`.

### FormWizard — Symfony Workflow-driven multi-step forms
This is the core UI pattern; understand it before touching any `Controller/.../Wizards/*` or `config/form_wizards/*`. Full reference: `docs/FormWizard.md` and `docs/FormWizard/Configuration.md`.
- **Workflow definitions** live in `config/form_wizards/**/*.php` (imported by `config/services.yaml`). Places = wizard pages; transitions = paths between them. Configure via place/transition `metadata` (template, form_class, guard, persist, redirect_route, etc.).
- **State objects** in `src/FormWizard/**` hold the data being edited (a "subject" entity plus constants). `App\FormWizard\LocatorService` **selects which workflow to run based on the State object's type** (matched against each workflow's `supports` option).
- **Controllers** extend `AbstractSessionStateForWizardController`, override `getState()` (which merges the session-held edited entity over the DB entity using `$this->propertyMerger`), build a `Place`, and call `$this->doWorkflow($request, $place)`.
- Wizards are grouped: `OnBoarding` (household/diary-keeper/direct onboarding), `TravelDiary` (journey/stage/repeat/return/split/share + the **CombinedReflow** wizard), `SatisfactionSurvey`. Render wizard graphs with `bin/dump-form-wizard-graphs.sh` (needs graphviz `dot`).

### Domain model (`src/Entity/`)
Core aggregate: **Household** → **DiaryKeeper** (a person; also a `User`) → **DiaryDay** → **Journey** (`Journey/Journey.php`) → **Stage** + **Method**. Also **Vehicle**, **Interviewer** (+ `InterviewerTrainingRecord`), **AreaPeriod** (survey area/year allocation), **SatisfactionSurvey**, **Feedback/** (interviewer↔admin messaging), **Metrics/MetricsLog** (passive usage metrics). Embeddables: `Address`, `Distance`, `CostOrNil`. Users implement a small hierarchy (`User`, `ApiUser`, `OtpUser`, plus `UserPersonInterface`/`PreferenceUserInterface`).

Entities implementing `PropertyChangeLoggableInterface` get change-tracked into `PropertyChangeLog` (audit trail), processed asynchronously — see `docs/Travel Diary/PropertyChangeLog.md`.

### Doctrine specifics
Config in `config/packages/doctrine.yaml`.
- **Two entity managers**: `default` (all of `src/Entity` except metrics) and `metrics` (`src/Entity/Metrics`). Route repositories/queries to the right one.
- **Custom DBAL types**: `decimal_brick` (`Brick\Math\BigDecimal`; scale conversion is handled in Forms, not the type — see `docs/Doctrine.md`) and `datetimemicrosecond`.
- `schema_filter` hides `lock_keys` and `vw_*` (views) from schema tools.

### Messenger (async)
Doctrine-backed transports (`config/packages/messenger.yaml`), one queue per concern: `async_notify` (GOV.UK Notify email/SMS/letter, long retry backoff), `async_property_change` (audit log), `async_feedback`, `async_job` (data-export/download jobs in `src/Messenger/Job`, no retries). Message base classes route by namespace; e.g. anything under `App\Messenger\AlphagovNotify\AbstractMessage` → `async_notify`.

### API Platform
Public API under `/api/v1` (versioned via the `version` route requirement in `config/routes/annotations.yaml`). Custom controllers in `src/Controller/Api`, normalizers/denormalizers in `src/Serializer/ApiPlatform` and `src/Serializer/ExportApi`. Endpoint docs live in `docs/API/*`.

### Cron
`src/Controller/Cron/*` routes are gated by request condition (`X-Appengine-Cron` / `X-Cloudscheduler` headers) — they only fire from App Engine's scheduler, not public traffic. Schedules in `config/gcloud-build/all/cron.yaml`.

### Feature flags
Toggled through the `APP_FEATURES` env var (comma/space list) and the existence of certain env vars, resolved via `src/Features.php`. See `docs/Features.md` for the full list (`check-letter`, `demo-fixtures`, `reveal-invite-links`, `form-error-log`, `smartlook-session-recording`, etc.).

## Conventions & gotchas
- New controllers must go in an existing `src/Controller/<Area>/` subfolder so they inherit host + route config; do not add controllers directly at the `src/Controller/` root.
- The frontend/admin host distinction is real at runtime — a route on the wrong host 404s or bypasses the wrong firewall. Check `hostnames.frontend` vs `hostnames.admin`.
- Translations use a custom `TranslatableParameterTranslator` (decorates `translator`) and property-based parameters — see `docs/TranslationParameterPropertiesExtension.md`.
- Architecture Decision Records live in `docs/ADRs/`; consult them before changing onboarding/account-activation/link-expiry or request sanitization behaviour.
