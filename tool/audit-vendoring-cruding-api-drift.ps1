$ErrorActionPreference='Stop'
$repo=(Resolve-Path (Join-Path $PSScriptRoot '../../Vendoring')).Path
$cr=(Resolve-Path (Join-Path $PSScriptRoot '../../Cruding')).Path
$m='c6a39143fc4a5e46d6fb8872383a01a284b0fabe'
$r='1279300419cc90f4cd53576dbf9f66e0dbd05a8d'
Write-Output '=== MASTER CRUDING REFERENCES ==='
& git -C $repo grep -n -E 'App\\Cruding\\(Dto\\Crud|Value\\Resource|Service\\Crud|ServiceInterface\\Crud)' $m -- src | Select-Object -First 30
Write-Output '=== RELEASE CRUDING REFERENCES ==='
& git -C $repo grep -n -E 'App\\Cruding\\(Dto\\Crud|Value\\Resource|Service\\Crud|ServiceInterface\\Crud)' $r -- src | Select-Object -First 30
Write-Output '=== CRUDING HEAD CANONICAL FILES ==='
& git -C $cr ls-tree -r --name-only master -- src | Select-String 'CrudAbstractService|CrudServiceContextDTO|CrudResourceContract'
Write-Output '=== CRUDING LEGACY FILES AT MASTER ==='
& git -C $cr ls-tree -r --name-only master -- src | Select-String 'AbstractCrudService|CrudServiceContext\.php'
