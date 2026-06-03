# Viewing W13 — Projecting / Paging boundary

This wave continues the controller migration to the central `Viewing` runtime boundary.

## Scope

The wave moves selected human-facing `Projecting` and `Paging` controllers away from direct Twig rendering:

- `ProjectEditorController::new()`
- `ProjectEditorController::edit()`
- `ProjectingOpsDashboardController::index()`
- `ProjectModerationController::index()`
- `PageViewController::index()`
- `PageViewController::__invoke()`

Controllers now return neutral `_view` payload arrays. `Viewing` owns the final response decision and renders via `Interfacing` noun-surface templates.

## Surface ownership

- `Projecting` emits `surface: project`.
- `Paging` emits `surface: page`.
- `Interfacing` owns passive templates under `Interfacing/template/project` and `Interfacing/template/page`.

## Guardrail

This wave does not change API routes, route governance, metrics, webhooks, or JavaScript/frontend framework behavior.
