# Viewing W22 — Catalog / Product visual depth

This wave strengthens Interfacing template quality for catalog, category, and product surfaces.

Scope:

- No controller changes.
- No route changes.
- No JavaScript framework changes.
- Twig-only visual depth for catalog/product commerce pages.

Added shared partials:

- `partial/filter_bar.html.twig`
- `partial/product_card.html.twig`
- `partial/category_card.html.twig`
- `partial/product_grid.html.twig`
- `partial/category_grid.html.twig`
- `partial/product_gallery.html.twig`
- `partial/pagination_bar.html.twig`
- `partial/price_panel.html.twig`

Strengthened surfaces:

- `catalog/browse`, `catalog/search`, `catalog/facet`, `catalog/collection`, `catalog/category-list`
- `category/detail`, `category/tree`, `category/product-list`
- `product/list`, `product/detail`, `product/gallery`, `product/pricing`, `product/inventory`, `product/variant`, `product/review`, `product/recommendation`

Template ownership remains:

- Producer components own payload preparation.
- Viewing owns response selection and fallback.
- Interfacing owns Twig composition and reusable partials.
