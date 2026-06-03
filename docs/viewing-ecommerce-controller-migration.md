# Viewing e-commerce controller migration wave

This wave moves the first e-commerce storefront/operator surfaces away from direct controller rendering and into the central `Viewing` runtime boundary.

## Runtime rule

Business UI controllers should not call `InterfacingRendererInterface` directly. They should return one of the following:

- a neutral array payload with `_view`, `locations`, `data`, and `meta`;
- a surface contract that can be normalized by `Viewing` without producer-owned template lookup rules.

`Viewing` owns the Symfony `kernel.view` decision and renders through the configured fallback chain:

1. `@Interfacing/<surface>/index.html.twig`
2. `@Interfacing/<surface>/surface.html.twig`
3. `@Interfacing/index.html.twig`
4. `@<Component>/index.html.twig`
5. `@Viewing/view/index.html.twig`
6. structured JSON fallback

## Migrated surfaces

- `Cataloging/src/Controller/Catalog/CatalogCategoryStorefrontController.php`
- `Carting/src/Controller/CartController.php`
- `Ordering/src/Controller/OrderManagementController.php`
- `Paying/src/Controller/PaymentConsoleController.php`

## Viewing compatibility added

`Viewing/src/Service/View/ViewPayloadNormalizer.php` now accepts existing producer surface objects by structural contract rather than by a hard dependency on Interfacing.

`Viewing/src/Service/View/ViewTemplateRenderer.php` now exposes producer payload `data` as top-level Twig context while keeping Viewing reserved keys authoritative. This keeps current surface templates working during migration.

## Deliberate scope boundary

API mutation routes still return JSON responses directly. This wave targets business UI GET/operator surfaces only. Mutation/API routes should be evaluated separately when the API response contract is stabilized.
