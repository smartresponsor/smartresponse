# Viewing W5 — Interfacing surface alignment

This wave keeps the platform on the Symfony Viewing boundary. Vue.js is not part of this architecture track.

## Scope

- Added root `@Interfacing/index.html.twig` fallback for normalized Viewing payloads.
- Added a generic payload partial for noun-surface templates.
- Fixed namespaced Twig inheritance for e-commerce/payment/billing/taxation surfaces.
- Added operation-aware candidate lookup in Viewing: `@Interfacing/<surface>/<operation>.html.twig` before `@Interfacing/<surface>/index.html.twig`.
- Moved the taxation admin dashboard from hand-built HTML response to a Viewing payload.

## Render chain

1. Producer controller returns a surface contract or neutral Viewing payload.
2. Viewing resolves Interfacing candidates.
3. Interfacing renders noun-surface templates.
4. Viewing falls back to local component or JSON/diagnostic response if no template exists.

## Explicit non-goals

- No Vue.js, Vite, React, or frontend framework introduction.
- No CRUD route cleanup.
- No producer business-command route migration.
- No repository-wide cleanup.
