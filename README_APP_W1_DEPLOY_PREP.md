# App W1 deploy-prep patch report

Base archive: `App(11).zip`

## Scope

Focused deploy-prep fixes for naming convention, YAML validity, route-token canon, and the primary SOLID hotspot found in `ShellChromeProvider`.

## Changed

### Config

- Fixed invalid YAML in `config/packages/security.yaml` by quoting the `accessing` auth regex containing `(?:...)`.
- Removed duplicate `^/api/applicating` access-control row.
- Migrated category action access-control patterns to static-operation-first / terminal-id shape:
  - `^/api/catalog/category/publish/.+$`
  - `^/api/catalog/category/move/.+$`
- Split composite API tokens in `config/packages/interfacing.yaml`:
  - `/api/billing-meter` -> `/api/billing/meter`
  - `/api/order-summary` -> `/api/order/summary`
- Replaced stale universal dynamic OpenAPI paths in `config/packages/nelmio_api_doc.yaml` with stable owner-root examples.

### Naming convention

All PHP files under `src/Service` now use class/file names ending with `Service`.

Renamed or replaced:

- `AppDashboardSurfaceBuilder` -> `AppDashboardSurfaceBuilderService`
- `EcosystemCrudResourceContribution` -> `EcosystemCrudResourceContributionService`
- `DemoProjectShowcaseProvider` -> `DemoProjectShowcaseProviderService`
- `EcommerceScreenCatalogProvider` -> `EcommerceScreenCatalogProviderService`
- `ShellLayoutPreviewProvider` -> `ShellLayoutPreviewProviderService`
- `ShellScreenCatalogProvider` -> `ShellScreenCatalogProviderService`
- `InterfacingNavigationSurfaceRenderer` -> `InterfacingNavigationSurfaceRendererService`

Removed duplicate unused controller-shaped service file:

- `src/Service/Diagnostics/AppRouteAuditController.php`

### SOLID / SRP split

`ShellChromeProvider` was split into smaller Symfony-style services:

- `ShellChromeProviderService`
- `ShellChromeUrlResolverService`
- `ShellTopLinkRegistryService`
- `ShellFooterRegistryService`
- `ShellQuickMenuRegistryService`
- `ShellRightPanelRegistryService`
- `ShellApplicationDashboardService`

The public contract remains through `ShellChromeProviderInterface`.

## Validation performed

- PHP syntax lint over `src`, `config`, `migrations`, `tests`: passed.
- YAML parse over `config/**/*.yaml`: passed.
- `src/Service/**/*.php` service suffix scan: passed, 0 non-`Service` class files remain.
- Class/file-name alignment under `src/Service`: passed.
- Composite `/api/foo-bar` path scan in `config` and `src`: passed, 0 matches.

## Not executed

Runtime/container checks were not executed because this archive has no `vendor/` directory:

- `bin/console lint:container`
- `bin/console debug:router`
- `bin/console doctrine:schema:validate`
- `vendor/bin/phpunit`

Run these inside the real repository after applying the touched files and exact deletes.
