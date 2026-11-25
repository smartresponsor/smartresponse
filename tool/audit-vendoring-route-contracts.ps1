$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$release='326e99cd5feb031be91ed1fc3163766ea071e638'
$other='ea60599dfeb69feae1c8d12322b0a094115afd13'
$paths=@('config/platform/routes.business.yaml','config/platform/routes.crud.yaml','config/platform/routes.platform.yaml','config/platform/routes/business/vendor.yaml','config/platform/routes/crud/vendor.yaml','config/platform/routes/crud/vendor.attachment.document.yaml','config/platform/routes/crud/vendor.attachment.media.yaml')
Write-Output '=== LEGACY ROUTE FILES ==='
foreach($path in $paths){Write-Output ('FILE '+$path); & git -C $repo show ($other+':'+$path) | Select-Object -First 50}
Write-Output '=== RELEASE ROUTE CONFIG FILES ==='
$targets=@('config/component/routes.yaml','config/platform/routes/business/vendor_business.yaml','config/platform/routes/crud/vendor_crud.yaml','config/platform/routes/crud/vendor_attachment_document.yaml','config/platform/routes/crud/vendor_attachment_media.yaml','config/platform/vendor_routes_business.yaml','config/platform/vendor_routes_crud.yaml')
foreach($path in $targets){Write-Output ('FILE '+$path); & git -C $repo show ($release+':'+$path) | Select-Object -First 50}
