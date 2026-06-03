# Top panel logo integration

The shell top panel uses the repository-owned SVG asset `public/mandala.svg` as the brand mark.

The logo is rendered by `template/interfacing/shell/base.html.twig` through the Symfony `asset()` helper and styled only through the native provider baseline CSS. No inline CSS is used.

## Contract

- Asset owner: Interfacing public asset tree.
- Runtime path: `/mandala.svg`.
- Shell owner: `template/interfacing/shell/base.html.twig`.
- Style owner: `public/interfacing/design/provider-baseline.css`.
- CSS marker: `.sr-shell-brand__logo`.

The top panel brand must remain a structural link with a visual logo plus text label. The logo must not be duplicated into the template as raw SVG markup.
