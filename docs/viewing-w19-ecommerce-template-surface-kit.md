# Viewing W19 — E-commerce template surface kit

This wave pauses controller migration and expands Interfacing noun-surface templates for commerce workflows.

## Scope

- Cart: summary, line items, checkout, empty state, promotion, shipping estimate.
- Catalog/category/product: browsing, search, facet, tree, detail, inventory, pricing, variants, reviews, recommendations.
- Order/payment: history, detail, payment, shipment, invoice, refund, method, adapter, settlement.
- Vendor/project/billing/checkout/shipment/merchandise/commercial: transaction, payout, contribution, usage, delivery and marketplace surfaces.

## Provider direction

The templates use Interfacing-owned Twig only. They are structured so future Ant Design, ProComponents and PrimeReact provider fragments can mount into stable zones without moving business ownership into templates.

## Boundary

Producer components still own payload and business logic. Interfacing owns visual noun-surface rendering. Viewing owns response selection and fallback.
