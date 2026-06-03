# Minimal production Composer contour

This contour is intentionally narrow. It allows only the runtime host plus three local ecosystem components resolved from Git over SSH:

- `accessing/access` from `git@github.com:smartresponsor/accessing.git`
- `interfacing/interface` from `git@github.com:smartresponsor/interfacing.git`
- `viewing/view` from `git@github.com:smartresponsor/viewing.git`

The normal development `composer.json` is not replaced. Production install should use:

```powershell
$env:COMPOSER = 'composer.prod.json'
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
Remove-Item Env:\COMPOSER
```

The host also needs the minimal bundle allowlist from `config/bundles.prod-minimal.php`; otherwise `config/bundles.php` will still boot disabled ecosystem bundles.

Recommended helper:

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy\prod\install-minimal-prod-composer.ps1 -RootPath D:\PhpstormProjects\www\App
```

If the GitHub owner or repository names differ, update only the `repositories[*].url` values in `composer.prod.json`.
