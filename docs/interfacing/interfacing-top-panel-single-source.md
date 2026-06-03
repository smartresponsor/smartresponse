# Interfacing top panel single source

The top panel markup is owned by `template/interfacing/shell/partial/top_panel.html.twig`.

`template/interfacing/shell/base.html.twig` is the shell assembler. It may pass shell variables into the top-panel partial, but it must not own or duplicate top-panel HTML.

Canonical ownership:

- `base.html.twig`: page skeleton, shell variables, slot assembly.
- `partial/top_panel.html.twig`: top-panel brand/search/menu markup.
- `partial/quick_menu.html.twig`: quick-menu markup.
- `provider-baseline.css`: visual styling.

This prevents brand/logo/menu drift when future waves update the top panel.
