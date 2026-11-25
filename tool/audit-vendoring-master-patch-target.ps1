$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$master='c6a39143fc4a5e46d6fb8872383a01a284b0fabe'
$release='1279300419cc90f4cd53576dbf9f66e0dbd05a8d'
Write-Output '=== MASTER VENDOR UPDATE CANDIDATES ==='
& git -C $repo ls-tree -r --name-only $master -- src | Select-String 'Vendor(Update|AbstractCrud|Crud).*Service\.php|VendorRouteServicesTest'
Write-Output '=== RELEASE UPDATE CANDIDATES ==='
& git -C $repo ls-tree -r --name-only $release -- src | Select-String 'Vendor(Update|AbstractCrud|Crud).*Service\.php'
Write-Output '=== MASTER BRAND UPDATE REFERENCES ==='
& git -C $repo grep -n -F 'brand_name_required' $master -- src tests | Select-Object -First 25
Write-Output '=== MASTER VS RELEASE ==='
& git -C $repo rev-list --left-right --count ($master+'...'+$release)
Write-Output '=== MASTER ROUTE TESTS ==='
& git -C $repo ls-tree -r --name-only $master -- tests | Select-String 'VendorRoute|VendorCore|VendorCrud'
