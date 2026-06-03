# Viewing W18 — Navigation boundary

This wave moves the Navigating public surface from component-owned rendering to the centralized Viewing boundary.

## Scope

- `Navigating/src/Controller/NavigationController.php` returns a neutral `_view` payload for `/navigation`.
- `Navigating/src/Service/Navigation/NavigationSurfaceResponseProvider.php` builds the payload from the existing navigation template data provider.
- `Interfacing/template/navigation/index.html.twig` uses the namespaced Interfacing inheritance path.

## Ownership

Navigating still owns navigation data, locations, active state, and provider contract payloads. Viewing owns final response rendering. Interfacing owns the `navigation` noun-surface templates.

## Non-goals

This wave does not touch API routes, route governance, firewall/security flows, metrics, webhooks, or JavaScript/frontend framework decisions.
