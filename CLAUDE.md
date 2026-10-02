# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Jigawa State Election Situation Room (JESR) — a real-time election monitoring platform for Jigawa State, Nigeria. Three user roles (super_admin, supervisor, ward_agent) interact via web (Livewire), REST API (mobile), and SMS (Africa's Talking) to submit, monitor, and escalate ward-level election reports across 27 LGAs and 287 wards.

## Commands

```bash
# Development server (runs artisan serve + queue + pail + vite concurrently)
composer dev

# Run all tests (MUST clear config cache first)
php artisan optimize:clear && php artisan test

# Run a single test file
php artisan test --filter=AuthTest

# Run a single test method
php artisan test --filter=AuthTest::test_super_admin_login_with_mfa

# Lint (Laravel Pint)
./vendor/bin/pint

# Seed database
php artisan migrate:fresh --seed

# Asset build
npm run build
```

**Critical**: `php artisan optimize` / `config:cache` bakes `.env` values into cache, overriding `phpunit.xml` env vars. Always run `php artisan optimize:clear` before running tests.

## Architecture

### Dual-Interface Pattern

- **Web (Livewire 3)**: Session-based auth via `Auth::login()`. Livewire components in `app/Livewire/` render full pages. Ward agents are redirected to `/reports/create` on login, not `/dashboard`.
- **API (REST + Sanctum)**: Bearer token auth with 8-hour expiry. All endpoints under `/api/v1`. Ward agents are device-bound via `DeviceBinding` middleware (`X-Device-ID` header).
- **SMS**: Africa's Talking webhook at `POST /api/v1/sms/receive`. `SmsParser` validates format `JIG REPORT <WardCode> <Initials> <Party> <Counted> <Valid> <Invalid> <Y/N> [SecCodes]` (minimum 7 tokens).

### Key Services (`app/Services/`)

| Service | Responsibility |
|---|---|
| `ReportService` | Create/update WardReport, handle media uploads, broadcast events, notify on security incidents |
| `EscalationService` | L1/L2/L3 escalation with progressive notification (SMS → email → Log::critical) |
| `SmsService` | Send via Africa's Talking (production) or log (sandbox). Dispatches `SendSmsJob` |
| `SmsParser` | Parse inbound SMS → WardReport |
| `ReportExportService` | PDF (DomPDF) and CSV exports for 7 report types |

### Real-Time Broadcasting

Three `ShouldBroadcastNow` events: `ReportSubmitted`, `ReportEscalated`, `AgentLoggedIn`. Channels: `admin` (super_admin), `lga.{lgaId}` (supervisor), `agent.{userId}`. Frontend subscribes via Laravel Echo + Pusher in `ReportInbox` using dynamic `getListeners()` method (not static `$listeners`).

### Authorization

- `WardReportPolicy` enforces role-based visibility: agents see own reports, supervisors see their LGA, super_admin sees all.
- `CheckRole` middleware (alias `role`) for route-level role gating.
- `WardReportObserver` auto-creates immutable `AuditLog` entries on report create/update.
- 10-minute correction window for ward agents editing their own reports (`isWithinCorrectionWindow()`).

## Conventions and Gotchas

- **Laravel scopes**: Call as `->forUser()` not `->scopeForUser()` (Laravel strips 'scope' prefix via magic `__call`).
- **AuditLog is immutable**: `update()` and `delete()` throw `RuntimeException`.
- **Base Controller** uses `AuthorizesRequests` trait — required for `$this->authorize()` in API controllers.
- **`action_type` enum** on `audit_logs`: `create|update|delete|verify|flag|escalate|login|logout`.
- **`security_types` validation** requires UPPERCASE values: `POLICE`, `ARMY`, `NSCDC`, `VIGILANTE`, `CIVIL_DEFENCE`, `OTHER`.
- **Livewire SPA navigation**: `$this->redirect(..., navigate: true)` uses pushState — Playwright tests need `wait_for_url()`.
- **Broadcast listeners** in Livewire: use `getListeners()` method for dynamic channel names, not static `$listeners` with `{placeholders}`.
- **Non-serializable services** in Livewire: re-bind in `boot()` method (e.g., `ReportInbox`).

## Testing

- PHPUnit 11.5 (not Pest). Tests in `tests/Feature/`. SQLite in-memory DB.
- All feature tests seed `RoleAndUserSeeder` and use `RefreshDatabase`.
- Sanctum migration must be published for token tests (`vendor:publish --tag=sanctum-migrations`).
- Factories exist for: `Lga`, `Ward`, `User`, `WardReport`.
- `BROADCAST_CONNECTION=null` and `QUEUE_CONNECTION=sync` in phpunit.xml.

## Database

- 27 LGAs, 287 wards seeded from INEC official data (integrity.ng).
- Three demo users seeded by `RoleAndUserSeeder`:
  - Super Admin: `08000000001` / `Admin@JESR2024` (MFA enabled)
  - Supervisor (Dutse LGA): `08000000002` / `Super@JESR2024`
  - Ward Agent (Dutse ward): `08000000003` / `Agent@JESR2024`

## Stack

Laravel 12, Livewire 3 (v4.1), Sanctum 4, Spatie Permission 7, DomPDF, Maatwebsite Excel, Pusher (broadcast), Africa's Talking (SMS), Tailwind CSS v4 (via Vite plugin), ApexCharts, PHP 8.2+.

## Note for development
- Whenever you are addressing task use the relevant SKILLS to that if it does not exist then create the SKILL first.
- When executing task, analyze wheather there is the need to launch multiple agents to accomplish or not.
