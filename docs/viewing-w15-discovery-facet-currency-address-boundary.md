# Viewing W15 — Discovery / Facet / Currency / Address boundary

This wave moves a small group of remaining human-facing producer controllers from direct Twig rendering into the central `Viewing` runtime boundary.

## Scope

Migrated controllers:

- `Discovering/src/Controller/Discovery/DiscoveryController.php`
- `Faceting/src/Controller/Management/FacetManagementController.php`
- `Currencing/src/Controller/Currency/CurrencyAdminPreviewController.php`
- `Currencing/src/Controller/Currency/CurrencyDemoController.php`
- `Addressing/src/Http/Controller/AddressController.php`

The migrated actions now return neutral `_view` payload arrays with noun surfaces:

- `discovery`
- `facet`
- `currency`
- `address`

`Viewing` owns final response rendering through the configured Interfacing template/fallback chain.

## Explicit non-scope

This wave does not change:

- API JSON endpoints
- route governance
- metrics/text endpoints
- webhooks
- security/firewall flows
- Vue.js/Vite/JavaScript framework state
- repository-wide cleanup

## Template alignment

The touched Interfacing surface templates now use namespaced inheritance through `@Interfacing/...` so host-level Twig namespace registration remains authoritative.
