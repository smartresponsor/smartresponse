# Interfacing provider CSS runtime compatibility repair

The active source templates use the neutral `interface-*` namespace. Runtime HTML supplied by the host may still contain cached `sr-*` classes until Symfony/browser caches are fully cleared.

This repair keeps the canonical source direction as `interface-*` while adding a temporary compatibility block to `public/interfacing/design/provider-baseline.css` for stale `sr-*` runtime markup.

Rules:

- New templates must use `interface-*`.
- `sr-*` must not be reintroduced in Twig source.
- `sr-*` support is CSS-only runtime compatibility, not the canonical namespace.
- The compatibility block may be removed only after all host/runtime caches and generated markup emit `interface-*`.
