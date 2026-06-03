# Viewing W8 — Accessing page boundary

This patch moves the Accessing page rendering path behind the central Viewing runtime boundary.

## Scope

- Accessing controllers no longer call `InterfacingRendererInterface::renderSurface()` directly for the home surface.
- `PageResponderInterface` now returns the Accessing surface contract instead of rendering it through Interfacing.
- Accessing controller actions that use `PageResponderInterface` are allowed to return `SurfaceRenderableInterface` so Symfony `kernel.view` can hand the result to Viewing.
- Accessing surface contracts now expose the `access` surface noun explicitly.
- Accessing page template paths now point to existing `Interfacing/template/access/*` templates.
- Accessing Interfacing templates now extend `@Interfacing/access/base.html.twig` with a namespaced path.

## Non-scope

- No API routes.
- No CRUD route governance.
- No Vue.js or JavaScript framework layer.
- No login/logout semantics changes.
- No repository-wide cleanup.
