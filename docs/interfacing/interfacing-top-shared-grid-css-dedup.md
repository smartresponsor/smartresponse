# Interfacing Top Panel Shared Grid CSS Dedup

The top panel must not maintain an independent width model. It must be assembled with the same structural shell classes used by the body layout:

- `interfacing-shell-grid`
- `interfacing-shell-panel interfacing-shell-panel--primary`
- `interfacing-shell-panel interfacing-shell-panel--secondary`
- `interfacing-shell-body`
- `interfacing-shell-panel interfacing-shell-panel--right`

The legacy `interfacing-shell-top__inner` layout is removed from the active runtime source and provider baseline CSS. The top panel may use modifier classes for vertical alignment only, not a separate grid contract.

Canonical brand text is `Smart Response`; the SVG logo is served from `public/mandala.svg` through Symfony `asset('mandala.svg')`.
