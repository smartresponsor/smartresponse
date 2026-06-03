# W18 Producer Route Source Evacuation

This wave removes producer route ingestion from the host route map.

Canonical direction:

- Cruding owns generic CRUD and catch-all route grammar.
- Producer components do not register CRUD/surface routes directly in the host app.
- Viewing handles payload results at `kernel.view`.
- Interfacing owns templates and layout surfaces.
- Producer components keep business services, providers, entities, policies, forms, and templates where appropriate.

This wave intentionally does not delete producer route files yet. It makes them inactive for the host app by removing explicit producer route imports and guarding sibling route discovery. Physical deletion of producer controllers and stale route files should follow in targeted waves after the active router confirms that the host is using Cruding routes only.
