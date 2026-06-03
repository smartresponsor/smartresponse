# Top panel shared grid classes

The top panel must use the same shell section classes as the body grid.

Canonical pattern:

- `interface-shell-grid` for the top grid container.
- `interface-shell-panel interface-shell-panel--primary` for the brand column.
- `interface-shell-panel interface-shell-panel--secondary` for the context column.
- `interface-shell-body interface-shell-body--top` for the main/search column.
- `interface-shell-panel interface-shell-panel--right` for the right/menu column when a right context column exists.

The old `interface-top-cell*` pattern is legacy drift and must not be used for canonical runtime markup. The temporary `sr-*` support in CSS exists only for old compiled/runtime markup and must not be treated as the source of truth.
