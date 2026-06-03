# Route Governance W3 — CRUD Collection Cleanup

Generic collection CRUD routes are owned by `Cruding`, not by producer controllers.

Producer components must not declare standalone collection routes for:

- `GET /api/attachments` / `POST /api/attachments`
- `GET /api/messages` / `POST /api/messages`
- `GET /api/orders` / `POST /api/orders`
- `GET /api/payments` / `POST /api/payments`
- `GET /api/products` / `POST /api/products`
- `GET /api/projects` / `POST /api/projects`
- `GET /api/vendors` / `POST /api/vendors`
- `GET /api/categories` / `POST /api/categories`

Canonical collection paths are singular and pass through the generic Cruding routes:

- `GET /api/{resourcePath}`
- `GET /api/{resourcePath}/`
- `POST /api/{resourcePath}`
- `POST /api/{resourcePath}/`

Business command routes remain producer-owned, for example `pay`, `ship`, `refund`, `invoice`, `contribute`, `upload`, `presign`, `finalize`, `search`, and webhooks.
