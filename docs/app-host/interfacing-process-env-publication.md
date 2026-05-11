# App host Interfacing process/env publication

The App host publishes the Interfacing browser process/env shim from `public/interfacing/admin-body/process-env.js`.

The shim is a host-public integration artifact. Interfacing owns the provider renderer source; App owns runtime publication from the active webroot.

The shim must load before `canonical-providers.js` and must provide `globalThis.process.env.NODE_ENV` for browser ESM bundles that include React/JSX runtime checks.
