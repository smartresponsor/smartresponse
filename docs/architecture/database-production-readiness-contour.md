# Database Production Readiness Contour

Every persisted platform component must satisfy the same production database contract.

## Required sequence

1. Entity model is the current design source of truth.
2. Objecting embeddables/traits are explicitly included in the physical schema.
3. Doctrine mapping is mounted in the Host production entity manager.
4. The component owns versioned Doctrine migrations for its schema.
5. Existing databases are adopted only after compatibility checks; incompatible drift aborts.
6. Empty-database bootstrap must be reproducible from versioned migrations.
7. Business invariants that protect data integrity are duplicated at the database level where practical.
8. FK, UNIQUE, CHECK and query-critical indexes are explicit and named.
9. Baseline rollback must not silently drop production data; irreversible baselines are preferred.
10. Re-running preparation/migration tooling must be idempotent.
11. Production deployment runs migrations before schema validation.
12. Destructive schema-update/reset operations are forbidden in production.
13. Backup/restore and failed-migration recovery must be documented and testable.
14. Cross-component table ownership must be unambiguous.

## Operator-safety checks

- No default production credentials in tracked config.
- No automatic database drop/reset in production tooling.
- Exact migration execution is available for staged adoption.
- Migration failure stops deployment before HTTP traffic is considered healthy.
- Existing-table baseline adoption validates required columns before accepting the schema.
- Destructive rollback requires an explicit replacement migration or manual recovery procedure.

## Component matrix

| Component | Repository state | Migration posture | Current action |
|---|---|---|---|
| Domaining | New local Git repository; remote not yet available | Baseline migration present and applied | Finish versioned remote artifact, DNS verification, production deploy |
| Retailing | Small unrelated dirty set | Baseline migration present and applied | Validate constraints and clean-bootstrap contract |
| Vendoring | Readiness hardening in progress | Full entity-first PostgreSQL projection and component-owned Doctrine baseline present; 47/47 tables physically verified | Resolve remaining Objecting/business-status duplication debt and retain post-migration physical verification gate |
| Carting | Clean | No component-owned Doctrine baseline found | Audit four entities and create adopt-or-create baseline |
| Cataloging | Moderate active entity changes | No component-owned Doctrine baseline found | Reconcile current catalog entity canon before baseline |
| Ordering | Very large active worktree rewrite | Legacy SQL/migration artifacts; no safe current Doctrine baseline | Read-only reconcile first; do not write over active rewrite |
| Shipping | Very large active rewrite | Historical PostgreSQL migrations currently deleted in worktree | Reconcile deleted migrations against new Shipment entity canon |
| Paying | Large active rewrite and branch divergence | Historical migrations currently modified/deleted in worktree | Reconcile migration history against current Payment entities |
| Paging | Active RC worktree changes | Doctrine migrations present | Audit coverage, rollback and bootstrap reproducibility |
| Walleting | Large active ledger implementation | Extensive Doctrine migration history present | Audit invariants, recovery, concurrency and migration continuity |

## Platform defects discovered by this contour

### Doctrine schema ownership collision

`doctrine:schema:update --dump-sql` currently cannot build a global PostgreSQL diff because metadata processing reports that `public.outbox_message` already exists. `doctrine:schema:validate --skip-sync` remains green, so ordinary mapping validation does not detect this ownership collision.

This must be resolved before global schema-drift output can become a production gate.

### Clean-bootstrap migration coverage

The Host must not remove the pre-production `schema:update --force` reset fallback until every production-mapped component has complete migration coverage. Production deployment itself must use migrations only.

## Current production consumers

- `smartresponsor.com`: platform-origin production declaration.
- `1tasker.com`: shared-runtime production consumer; declaration and claim exist, DNS verification is pending.
