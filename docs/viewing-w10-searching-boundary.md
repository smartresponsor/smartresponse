# Viewing W10 — Searching boundary

## Scope

This wave continues the Symfony Viewing runtime boundary migration. It does not touch route-governance, API CRUD ownership, JavaScript, Vue.js, or frontend build tooling.

## Changes

- `Searching/src/Controller/Web/SearchResultController.php` no longer injects `InterfacingRendererInterface`.
- The search result controller no longer calls `renderSurface()`.
- The controller returns a neutral Viewing payload array with `surface=search`, `operation=result`, and `component=Searching`.
- Search rate-limit/deferred responses preserve their HTTP status through `meta.status_code`.
- `Viewing/src/Service/View/ViewTemplateRenderer.php` now honors `meta.status_code` for HTML responses.
- `Viewing/src/Service/View/ViewJsonResponseFactory.php` now honors `meta.status_code` for JSON fallback responses.

## Runtime boundary

```text
SearchResultController
  -> search surface contract
  -> neutral Viewing payload
  -> kernel.view
  -> Viewing normalizer/decision/template candidate service
  -> Interfacing search templates or fallback
```

## Canon

Producer controllers may build business/search surfaces, but they must not render through Interfacing services directly. Viewing remains the only runtime response boundary for UI/surface routes.
