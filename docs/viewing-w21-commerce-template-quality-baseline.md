# Viewing W21 — Commerce template quality baseline

This wave stabilizes Interfacing commerce templates after the controller-to-Viewing migration.

## Scope

- Keep `Interfacing/template/partial/` as the shared Twig partial layer.
- Add reusable empty/value rendering partials.
- Add stable `data-ui-zone` hooks for future Ant Design, ProComponents, and PrimeReact enrichment.
- Add `_contract.yaml` files for major commerce/business surfaces.
- Improve generated operation-template wording without changing routes, controllers, or API behavior.

## Ownership

- Producer components own payload data.
- Viewing owns response/template selection.
- Interfacing owns noun-surface Twig layout.
- `partial/` owns reusable visual fragments only.

## No frontend framework change

This wave does not add Vue.js, React, Vite, or frontend runtime code.
