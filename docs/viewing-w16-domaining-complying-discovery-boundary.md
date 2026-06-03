# Viewing W16 — Domaining / Complying / Discovery management boundary

This wave moves another group of human-facing producer controllers behind the central Viewing runtime boundary.

## Scope

Converted controllers:

- `Domaining/src/Controller/DomainSurfaceController.php`
- `Complying/src/Http/Compliance/CaseUiController.php`
- `Complying/src/Http/Compliance/Admin/ComplianceConfigAdminController.php`
- `Complying/src/Http/Compliance/Admin/CompliancePolicyAdminController.php`
- `Discovering/src/Controller/Management/AbstractDirectoryBackedFamilyManagementController.php`
- `Discovering/src/Controller/Management/DiscoveryBriefingManagementController.php`
- `Discovering/src/Controller/Management/DiscoveryPlaybookManagementController.php`
- `Discovering/src/Controller/Management/DiscoveryLibsourceLogManagementController.php`
- `Discovering/src/Controller/Management/DiscoveryLibsourceManagementController.php`
- `Discovering/src/Controller/Management/DiscoveryOverviewManagementController.php`

## Rule

Producer controllers must not own final Twig rendering. They return either normal Symfony control responses for redirects/JSON/errors, or a normalized `_view` payload for human-facing pages.

## Not in scope

- API JSON endpoints.
- Metrics/text endpoints.
- Security login/logout/firewall flows.
- Route-governance migrations.
- Interfacing presentation controllers.
- JavaScript/Vue/Vite/frontend stack changes.
