# Runtime SR grid final override

The canonical Interfacing shell is moving to `interface-*` classes, but host/runtime pages may still emit compiled `sr-*` markup. The provider CSS must therefore keep a compatibility section that maps the old header markup onto the exact same shell-grid geometry as the body.

Rules:

- Do not introduce a separate top-panel grid model.
- `.sr-shell-top__inner` and `.sr-shell-grid` must share the same width variables and column template.
- In three-column mode, the menu is positioned at the right edge of the main/body column.
- In four-column mode, the menu occupies the right-context column.
- The search button is icon-only and remains attached to the input group.

This is a runtime compatibility layer, not a new canonical namespace. New template source should use `interface-*` classes.
