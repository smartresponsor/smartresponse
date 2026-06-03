# Viewing W27 — Shipment / Billing / Attachment / Search support surfaces

This wave adds visual depth for commerce support surfaces around orders, payments, files, and search.

## Scope

- Shipment: tracking, rates, labels, delivery, carrier and exception surfaces.
- Billing: billing account, invoice, usage, meter, subscription and payment method surfaces.
- Attachment: document, media, upload, preview and storage surfaces.
- Search: result, commerce, suggestion, facet and autocomplete surfaces.

## Non-goals

- No controller migration.
- No route migration.
- No API changes.
- No JavaScript framework changes.
- No repository-wide cleanup.

## Rendering model

Producer components return structured payloads. `Viewing` selects the candidate template. `Interfacing` renders these noun-surface operation templates and shared partials.
