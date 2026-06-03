# Route Governance W5 — static-first, identifier-last migration

This wave migrates selected API routes to the canonical route-shape rule:

- The first token after `/api` is the stable business/system owner.
- Static semantic tokens come before identifiers.
- Dynamic identifiers such as `{id}`, `{vendorId}`, and slugs stay at the end of the route, or at the end of the deepest resource/action segment when multiple identifiers are required.
- No Vue.js, Viewing, or Interfacing runtime files are changed by this route-governance wave.

## Canonical target examples

- Order payment command target: `/api/order/pay/{id}`.
- Payment refund command target: `/api/payment/refund/{id}`.
- Vendor payout statement export target: `/api/vendor/{vendorId}/payout/statement/export`.
- Vendor transaction status target: `/api/vendor/transaction/status/{vendorId}/{id}`.
- API documentation targets: `/api/doc` and `/api/doc/json`.

The historical legacy-to-canonical mapping is stored only in the route-governance CSV manifest. That manifest is an audit artifact and is intentionally excluded from legacy-route source scans.

External documentation URLs that merely contain an API documentation path as part of an external vendor URL are intentionally excluded from migration.
