# Shared shell grid contract

The top panel and body panel must use the same grid contract and the same layout variables:

- left primary column
- left secondary column
- main/body column
- optional right-context column

Top-panel elements must be mapped into the same sections instead of using a separate brand/search/menu grid. The runtime compatibility CSS supports older `sr-*` markup while the canonical source migrates to the neutral `interface-*` namespace.
