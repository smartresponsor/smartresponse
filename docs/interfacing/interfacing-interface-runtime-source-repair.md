# Interfacing runtime source repair

This repair removes the active shell runtime dependency on legacy `sr-*` classes.

Canonical active runtime markers:

- `interface-provider-body`
- `interface-shell`
- `data-interface-shell`
- `interface-shell-grid`
- `interface-brand`
- `interface-brand__logo`
- `Smart Response`

The top panel uses the same `interface-shell-grid`, `interface-shell-panel`, and `interface-shell-body` primitives as the body layout. The search submit control is icon-only and does not render the word `Search`.

`sr-*` may remain only in historical documentation or migration notes, not in the active shell source.
