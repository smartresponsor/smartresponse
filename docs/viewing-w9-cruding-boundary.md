# Viewing W9 — Cruding controller boundary

This wave moves generic human-facing CRUD workbench controllers away from direct Twig rendering.

## Scope

Cruding UI controllers now return `CrudSurfaceContract` objects for non-redirect/non-error render paths. Symfony `kernel.view` is expected to pass those surface objects to Viewing, where they are normalized and rendered centrally.

Changed controller methods:

- `CrudIndexController::__invoke()`
- `CrudShowController::__invoke()`
- `CrudCreateController::__invoke()`
- `CrudEditController::__invoke()`

## Boundary rule

Cruding still owns CRUD page construction, form handling, object lookup, redirects, access checks, mutation guard, and not-found responses.

Viewing owns the final HTML rendering boundary.

## Non-scope

This wave does not change API CRUD controllers, route governance, metrics endpoints, webhooks, or JavaScript/frontend framework behavior.
