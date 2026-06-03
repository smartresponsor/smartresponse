# Interfacing interface namespace and aligned top grid

This wave removes brand-derived `sr-*` class and data-attribute naming from the active Interfacing frontend path. Interfacing-owned shell, access, storefront, messaging, and provider-baseline templates now use the neutral `interface-*` namespace.

## Canon

- CSS class names must describe the owning layer, not the brand.
- Interfacing-owned frontend classes use `interface-*`.
- Interfacing-owned data attributes use `data-interface-*`.
- Provider design variables use `--interface-provider-*`.
- Brand text and logo are content, not CSS namespace.

## Top panel grid

The top panel now follows the same column contract as the body shell:

1. primary-left column
2. secondary-left column
3. main/body column
4. right-context column

The top panel is assembled through `template/interfacing/shell/partial/top_panel.html.twig` and is not owned directly by `base.html.twig`.

## Runtime guards

- `tools/interfacing/native-provider-style-guard.php`
- `tools/interfacing/provider-baseline-application-guard.php`
- `tools/interfacing/top-panel-brand-guard.php`
- `tools/interfacing/interface-namespace-guard.php`

The namespace guard prevents new `sr-*`, `data-sr-*`, and `--sr-provider-*` markers from returning to active templates and provider CSS.
