= App host Interfacing dashboard provider assets

The App host is the runtime and public webroot owner. Interfacing owns the source renderer/assets. The host publishes the current Interfacing admin-body provider assets under `public/interfacing/admin-body/` so dashboard and resource pages can load the canonical Ant Design ProComponents provider from the host runtime. This is not a visual ownership transfer: App exposes public files and composes dashboard contracts; Bridging performs handoff; Interfacing renders.
