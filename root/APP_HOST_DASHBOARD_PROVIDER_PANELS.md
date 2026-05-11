# App host dashboard provider panels

App owns the dashboard route and composition contract. Bridging owns the handoff
boundary. Interfacing owns the provider renderer and Twig provider templates.
App publishes provider assets under `public/interfacing/admin-body`, but the
visible shell chrome comes from the canonical Interfacing provider page.

Wave 08 keeps the old commerce shell ideas as provider schema/dashboard panels:
metrics, widgets, sections, side panels, quick links, and future customer
shortcuts. These are data contracts. The actual visible top, left, and footer
panels now come from the Interfacing shell base.

Forbidden primary UI: `crud-app-shell`, `@Cruding/crud`, Bootstrap/EasyAdmin, or
handmade Twig CSS. Panels must be emitted as schema and rendered by the canonical
Ant Design ProComponents provider inside the Interfacing shell page, with
PrimeReact only as secondary/rich-facade.
