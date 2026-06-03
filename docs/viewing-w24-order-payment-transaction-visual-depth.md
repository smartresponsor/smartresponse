# Viewing W24 — Order / Payment / Transaction visual depth

This wave deepens Interfacing templates for post-checkout commerce flows. It does not change controllers, routes, APIs, Viewing runtime, or any JavaScript framework.

## Scope

- Order history/detail/payment/invoice/refund/timeline screens.
- Payment history/detail/refund/settlement screens.
- Vendor transaction, transaction detail, payout account and payout statement screens.
- Shared Twig partials for timelines, payment status, invoices, refunds, ledgers and payout statements.

## Ownership

- Producer components own data preparation and business commands.
- Viewing owns response/template selection and fallback.
- Interfacing owns visual layout, partial composition and surface contracts.

## Template lookup

The templates are designed for the existing Viewing candidate chain:

1. `@Interfacing/<surface>/<operation>.html.twig`
2. `@Interfacing/<surface>/index.html.twig`
3. `@Interfacing/<surface>/surface.html.twig`
4. `@Interfacing/index.html.twig`

## Notes

No React, Vue, Vite, API route governance, or controller migration is included in this wave.
