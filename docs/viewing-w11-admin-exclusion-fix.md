# Viewing W11 — admin exclusion fix

This wave fixes the runtime boundary conflict discovered after the CodexCLI-assisted migration.

## Problem

`App/config/packages/viewing.yaml` excluded every `/admin` path from `Viewing`:

```text
#^/admin(?:/|$)#
```

That was safe before admin/workbench routes started returning `_view` payloads. After the migration, many admin controllers return neutral Viewing payload arrays or surface contracts. A broad `/admin` path exclusion prevents `ViewKernelViewSubscriber` from converting those controller results into real responses.

## Decision

`/admin` is no longer globally excluded. Admin/workbench routes that return `_view` payloads are now allowed through the central Viewing boundary.

The subscriber is also payload-aware: route exclusions continue to protect non-Viewing framework/API/static responses, but an explicit Viewing payload or surface contract is treated as an opt-in signal.

## Still excluded

The following remain excluded by path or route pattern:

- profiler and web debug toolbar
- assets and build artifacts
- favicon, robots, sitemap
- health and metrics endpoints
- generic `/api` routes unless they explicitly return a Viewing payload

## Scope

Changed files:

- `App/config/packages/viewing.yaml`
- `Viewing/src/Subscriber/View/ViewKernelViewSubscriber.php`

This wave does not change controller routes, templates, API route governance, or frontend stack.
