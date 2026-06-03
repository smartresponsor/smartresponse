# Viewing W4 current-slice boundary retry

This patch is based only on `www-clean-20260530-171737.zip` as the active current slice.

## Scope

- Moves `Taxating/src/Controller/Taxation/TaxationAdminDashboardController.php` away from manual HTML string assembly and `new Response($html)`.
- Keeps `Taxating` admin dashboard as a Symfony UI route, but returns a neutral Viewing payload array.
- Updates `Taxating/src/ControllerInterface/Taxation/TaxationAdminControllerInterface.php` to allow `Response|array`, matching the controller boundary.

## Non-goals

- No Vue.js.
- No frontend framework layer.
- No API route cleanup.
- No metrics endpoint changes.
- No repository-wide cleanup or overwrite.

## Expected runtime flow

`TaxationAdminDashboardController::dashboard()` returns a neutral array payload. `Viewing` catches it at `kernel.view`, tries Interfacing surface templates first, then the generic Interfacing index, then local component fallback, then the Viewing diagnostic fallback or JSON according to request policy.
