# Viewing W23 — Cart / Checkout visual depth

This wave strengthens Interfacing cart and checkout templates after the Viewing boundary migration.

## Scope

- Cart summary, line items, promotion, shipping estimate, empty state, checkout handoff.
- Checkout cart, shipping, payment, review, and success operations.
- Shared Twig partials for stepper, cart lines, monetary summary, promotions, shipping, and payment methods.

## Non-scope

- No controller migration.
- No route-governance changes.
- No API changes.
- No JS/Vue/React/Vite layer.

## Canon

The reusable fragments live under `Interfacing/template/partial/`. Cart and checkout surfaces remain noun folders.
