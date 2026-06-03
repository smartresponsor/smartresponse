# Viewing W6 — render pipeline hardening

This wave continues the Symfony Viewing runtime boundary without introducing Vue.js or any JavaScript framework layer.

## Goal

Make the `Viewing -> Interfacing` render path more tolerant of existing producer surface contracts while keeping rendering ownership centralized in `Viewing`.

## Changes

- `ViewTemplateCandidateService` now owns the full candidate chain and does not read producer template path hints.
- Candidate resolution is canonical and namespace-driven from `Viewing` itself.
- `ViewTemplateRenderer` exposes additional top-level context variables for templates: `payload`, `surface`, `operation`, and `component`.
- Remaining e-commerce showcase includes in Interfacing now use the explicit `@Interfacing` namespace.

## Rendering order after W6

1. `@Interfacing/<surface>/<operation>.html.twig`
2. `@Interfacing/<surface>/index.html.twig`
3. `@Interfacing/<surface>/surface.html.twig`
4. `@Interfacing/index.html.twig`
5. Local component fallback rendered by `Viewing`
6. `@Viewing/view/index.html.twig`
7. JSON fallback if HTML is not allowed or no template can render

Controllers still must not call `render()` or direct Interfacing render services for controlled UI routes.
