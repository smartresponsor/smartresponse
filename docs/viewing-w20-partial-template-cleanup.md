# Viewing W20 — Partial template cleanup

This wave removes the unclear `provider/ecommerce` Twig wrapper introduced by the e-commerce template surface kit.

Canonical shared Twig partials now live directly under:

```text
Interfacing/template/partial/
```

Moved files:

```text
Interfacing/template/provider/ecommerce/surface_header.html.twig -> Interfacing/template/partial/surface_header.html.twig
Interfacing/template/provider/ecommerce/metric_grid.html.twig -> Interfacing/template/partial/metric_grid.html.twig
Interfacing/template/provider/ecommerce/record_grid.html.twig -> Interfacing/template/partial/record_grid.html.twig
Interfacing/template/provider/ecommerce/detail_panel.html.twig -> Interfacing/template/partial/detail_panel.html.twig
Interfacing/template/provider/ecommerce/workbench.html.twig -> Interfacing/template/partial/workbench.html.twig
```

All commerce surface includes now reference `@Interfacing/partial/...`.

No controller, route-governance, API, JavaScript, Vue, Vite, or runtime Viewing logic was changed.
