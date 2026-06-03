# Viewing W25 — Vendor / Payout / Merchant Visual Depth

This wave deepens the Interfacing template layer for vendor, merchant, payout, settlement, and commerce operations.

## Scope

Changed only Twig templates, Interfacing surface contracts, and reusable partials. No controllers, routes, API declarations, JavaScript framework, or Viewing runtime logic were changed.

## Added reusable partials

- `partial/merchant_profile_panel.html.twig`
- `partial/payout_account_panel.html.twig`
- `partial/settlement_schedule.html.twig`
- `partial/merchant_risk_panel.html.twig`
- `partial/merchant_action_rail.html.twig`
- `partial/adapter_status_panel.html.twig`

## Vendor operations

The vendor surface now has operation-level templates for merchant dashboard, profile, onboarding, payout methods, payout schedule, settlement, risk, commission, catalog syndication, media readiness, and statement export.

## Rule

Producer components prepare vendor/merchant/payout payloads. Viewing selects the response and template. Interfacing renders passive noun-surface templates through shared `partial/` fragments.
