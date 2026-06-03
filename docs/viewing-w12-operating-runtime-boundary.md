# Viewing W12 — Operating / Runtiming page boundary

This wave moves repetitive human-facing Operating and Runtiming status/module/responsibility page controllers away from direct Twig rendering.

## Scope

Converted controllers now return neutral Viewing payload arrays with `surface`, `operation`, `component`, `locations`, `data`, and `meta`. Rendering is owned by `Viewing` and Interfacing noun-surface templates.

## Surfaces

- `operating`
- `runtime`

## Operations

- `status`
- `module-overview`
- `responsibility-surface`

## Explicitly not changed

- API routes
- route-governance migrations
- metrics/text endpoints
- webhooks
- JavaScript/Vue/Vite stack
