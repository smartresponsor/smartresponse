# Viewing W7 — Messaging admin controller boundary

This wave moves selected Messaging human-facing admin controllers from direct Twig rendering to the centralized Viewing runtime boundary.

## Scope

Converted controllers:

- `MessageAdminDashboardController::index()`
- `MessageAdminMediaListController::__invoke()`
- `MessageAdminMediaShowController::__invoke()`
- `MessageAdminModerationController::moderation()`
- `MessageAdminStorageController::storage()`
- `MessageAdminSystemController::system()`

Each converted action returns a neutral Viewing payload with:

- `surface: message`
- `component: Messaging`
- operation-specific names such as `dashboard`, `media-list`, `media-show`, `moderation`, `storage`, and `system`

Viewing owns final response rendering and may render Interfacing noun-surface templates or fall back centrally.

## Explicit non-scope

The wave intentionally does not alter:

- login/security rendering
- CSV/PDF/ZIP export responses
- redirect-only actions
- API JSON endpoints
- metrics/text endpoints
- route governance cleanup
- Vue.js, Vite, or any JS framework layer

## Interfacing

The `message` noun-surface templates now use namespaced Twig inheritance. Operation templates are present for the converted Messaging admin surfaces and delegate to a shared message default template.
