# Route Governance W3 — CRUD index cleanup

This wave removes producer-owned exact plural API index route declarations for generic CRUD collection reads.

Cruding owns generic API index resolution through the dynamic route:

```text
GET /api/{resourcePath}
GET /api/{resourcePath}/
```

Canonical resource paths are singular:

```text
/api/attachment
/api/message
/api/order
/api/payment
/api/product
/api/project
/api/vendor
/api/category
```

Producer components must keep only business-specific commands, nested operations, searches, uploads, webhooks, and orchestration endpoints.

This wave intentionally does not rewrite business command routes such as payment creation/refund, order pay/ship/refund/invoice, project contribution, product search, message send/reaction/read, or attachment upload/download/preview flows.
