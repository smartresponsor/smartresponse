# Viewing W14 — Applicating administration boundary

This wave moves `Applicating` administration UI actions behind the central `Viewing` runtime boundary.

## Scope

- `ApplicationAdminController` no longer renders Twig directly for index/new/report/show/edit pages.
- The controller now returns neutral `_view` payload arrays with `surface: application`.
- `Interfacing/template/application` now has namespaced templates for the relevant operations.

## Out of scope

- Symfony security login/logout remains a special firewall flow.
- API routes, route-governance, metrics, and webhooks are not changed.
- No JavaScript framework is introduced.
