# FreeScout Sentry Module

Forward FreeScout errors (unhandled exceptions and `error`-level logs, with stack traces) to Sentry
(self-hosted or sentry.io / GlitchTip). Errors only — no performance tracing, low noise.

## How it works
Ships the raw `sentry/sentry-php` SDK in its own vendor (no Laravel package, no core changes). On boot,
if a DSN is configured it calls `\Sentry\init()` and registers `Log::listen` to capture `error`/`critical`/
`alert`/`emergency` records. Exceptions are sent via `captureException` (full stack trace). If no DSN is
set, the module is completely inert.

### Why `guzzlehttp/psr7` is pinned to `^3.0`
`start.php` loads this module's own autoloader, so the classes in its `vendor/` win over FreeScout's
for the whole request. `GuzzleHttp\Psr7\Rfc3986` is therefore resolved here, not in the core. Shipping
psr7 2.x while the core runs Guzzle 8 breaks every outgoing HTTP call with
`Call to undefined method GuzzleHttp\Psr7\Rfc3986::isValidHost()` — module license checks, webhooks and
any module that talks to an API. Keep this constraint aligned with the core's psr7 major version.

## Requirements
FreeScout ≥ 1.8.0, PHP ≥ 8.1. No core changes.

## Installation
```bash
cd Modules
git clone https://github.com/mnicole-dev/freescout-sentry-module Sentry
cd Sentry && composer install --no-dev --optimize-autoloader
```
Activate **Sentry** in **Manage → Modules**.

## Configuration
Set the DSN as an environment variable read by FreeScout. With the official Docker image, set
`FREESCOUT_SENTRY_DSN=https://…@host/projectId` (the image writes `SENTRY_DSN` into FreeScout's `.env`).
Optional: `SENTRY_ENVIRONMENT` (default `production`). Without a DSN the module does nothing.

> If installing via CLI in a container, run `artisan` as the web user, not root.

## License
AGPL-3.0 — see [LICENSE](LICENSE).
