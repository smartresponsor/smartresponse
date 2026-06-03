# Route Governance W4 — AI/Operating API route migration

This wave moves legacy plural/platform API route declarations to canonical runtime/operating/message/integration/component-owned API paths.

## Scope

- Runtime tools and agents are moved under `/api/runtime/...`.
- Operating workflow, trace/evaluation/evidence, platform, MCP, memory and knowledge surfaces are moved under `/api/operating/...`.
- Message upload/export/metrics surfaces are moved under `/api/message/...`.
- Order/vendor/message metrics are moved to component-owned metric routes where applicable.
- Integration adapters are moved under `/api/integration/adapter...`, with component-owned adapter item routes reserved for payment/vendor/order.
- Analytics export jobs are normalized to singular `/api/analytics/export-job`.

## Non-goals

- This wave does not change Viewing/Interfacing rendering behavior.
- This wave does not introduce legacy redirect aliases.
- This wave does not modify unrelated `/observability/api/...` endpoints.
- This wave does not delete business command endpoints; command suffixes under migrated families follow their canonical family prefix.

## Canonical examples

- `/api/runtime/tool` → `/api/runtime/tool`
- `/api/runtime/tool-execution` → `/api/runtime/tool-execution`
- `/api/runtime/agent` → `/api/runtime/agent`
- `/api/operating/workflow` → `/api/operating/workflow`
- `/api/operating/platform/provider` → `/api/operating/platform/provider`
- `/api/operating/mcp/session` → `/api/operating/mcp/session`
- `/api/operating/memory/session` → `/api/operating/memory/session`
- `/api/operating/knowledge/index` → `/api/operating/knowledge/index`
- `/api/message/attachment/upload/presign` → `/api/message/attachment/upload/presign`
- `/api/message/export` → `/api/message/export`
- `/api/order/metric` → `/api/order/metric`
- `/api/vendor/{vendorId}/metric` → `/api/vendor/{vendorId}/metric`
- `/api/operating/metric` → `/api/operating/metric`
- `/api/integration/adapter` → `/api/integration/adapter`
- `/api/analytics/export-job` → `/api/analytics/export-job`
