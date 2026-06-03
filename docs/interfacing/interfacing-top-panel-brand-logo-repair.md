# Top panel brand logo repair

The top panel brand must render the vector logo from `public/mandala.svg` and the visible brand text `Smart Response`.

This repair covers both top-panel rendering paths:

- `template/interfacing/shell/base.html.twig`
- `template/interfacing/shell/partial/top_panel.html.twig`

The implementation is native provider styling: Twig renders semantic markup and `public/interfacing/design/provider-baseline.css` owns visual rules.

Guard:

```bash
php tools/interfacing/top-panel-brand-guard.php
```
