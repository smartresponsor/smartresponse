# Viewing runtime integration

This host application now registers the `viewing/view` sibling component as the central Symfony runtime-view boundary.

## Purpose

Producer controllers should stop owning Twig rendering decisions for business UI routes. They should return a neutral view payload or structured array. The `Viewing` component handles the `kernel.view` boundary and resolves the response through this fallback chain:

1. `@Interfacing/<surface>/index.html.twig`
2. `@Interfacing/index.html.twig`
3. `@<Component>/index.html.twig`
4. `@Viewing/view/index.html.twig`
5. structured JSON fallback

## Host wiring

The host application wires:

- `App\Viewing\ViewingBundle`
- `viewing/view` Composer path package
- `Viewing` in `app.connected_components`
- `@Interfacing` Twig namespace mapped to `../Interfacing/template`
- `@Viewing` Twig namespace mapped to `../Viewing/templates`
- component-local Twig namespaces for controlled fallback rendering

## Controller migration rule

Business UI controllers should return payload data instead of calling `render()`, searching templates, or deciding between HTML and JSON directly.

API, admin, profiler, asset, health, and metrics paths remain excluded from Viewing interception by default.
