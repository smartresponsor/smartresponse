# App Host Provider Rendering Set

The App host owns the runtime and the `/` dashboard route, but it does not own primary visual rendering.

Canonical flow:

```text
App route / -> App dashboard contract -> Bridging handoff/rendering -> Interfacing provider page -> Ant Design ProComponents primary UI
```

Required runtime/public responsibilities:

- App publishes Interfacing admin-body provider assets under `public/interfacing/admin-body/`.
- App dashboard controller uses the Bridging app-host responder before rendering the provider surface.
- App dashboard contract exposes provider dashboard data: metrics, sections, widgets, and side panels.
- App dashboard route must use the canonical Interfacing provider page shell for visible chrome.
- Top, left, and footer panels are owned by the Interfacing shell base, not by App.

Forbidden as primary App homepage UI:

- `crud-app-shell` markup;
- `data-cruding-shell-contract` shell markup;
- `@Cruding/crud/*.html.twig` final dashboard template;
- handmade Twig CSS as fallback/insurance path.

The App host guard `tools/app-host/app_provider_rendering_set_guard.php` checks the host route, bridge handoff, provider assets, and dashboard renderer markers.
