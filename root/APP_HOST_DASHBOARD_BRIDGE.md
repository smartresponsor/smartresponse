# App Host Dashboard Bridge

The App host owns `/` and dashboard composition. It exposes an App dashboard
surface payload, sends it through Bridging, and renders the normalized payload
with Interfacing provider documents.

Canonical flow:

```text
App route /
  -> AppDashboardSurfaceContract
  -> Bridging AppHostInterfacing bridge
  -> Interfacing provider surface
  -> Ant Design ProComponents primary UI
```

App must not render the dashboard through handmade Twig CSS or assign the
homepage to a single component such as Cruding, Cataloging, or Vendoring.
