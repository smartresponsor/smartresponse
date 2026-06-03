# Viewing W3 — controller boundary, no Vue.js layer

This wave continues the runtime rendering boundary without introducing Vue.js.
The word "Viewing" means the Symfony `kernel.view` component boundary, not a JavaScript frontend framework.

## Rule

Producer controllers must not render Twig directly for controlled human/UI routes.
They return one of these runtime values instead:

- an existing producer surface contract object;
- a neutral array containing `_view`, `locations`, `data`, and `meta`.

`Viewing` receives that result at `kernel.view`, chooses HTML or JSON, resolves templates through Interfacing first, and only then falls back according to the configured chain.

## Scope of this wave

This wave removes the remaining high-value direct rendering calls from the connected e-commerce/tax/billing UI boundary:

- `Billing` home/dashboard surface no longer calls `InterfacingRendererInterface` directly.
- `Cataloging` admin/merchant category UI controllers return neutral Viewing payload arrays.
- `Taxating` admin/console forms return neutral Viewing payload arrays.

API endpoints, webhooks, metrics text endpoints, and custom business commands remain Response/JsonResponse driven.
They are outside this human/UI rendering boundary wave.

## Explicit non-goals

- No Vue.js.
- No frontend hotfix layer.
- No repository-wide cleanup.
- No API CRUD route ownership expansion in this wave.
- No destructive application of cumulative snapshots.

## Next visual wave

After runtime is stable, Interfacing templates should be improved by surface noun folders only:

- `catalog`
- `category`
- `cart`
- `order`
- `payment`
- `billing`
- `taxation`
- `shipment`
- `vendor`

