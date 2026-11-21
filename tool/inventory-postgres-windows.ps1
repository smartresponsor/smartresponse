$ErrorActionPreference='Stop'
Write-Output 'WINDOWS_POSTGRES_SERVICES'
Get-CimInstance Win32_Service | Where-Object { $_.Name -match 'postgres' -or $_.DisplayName -match 'PostgreSQL' } | Select-Object Name,State,StartMode,PathName | Format-Table -Wrap | Out-String | Write-Output
Write-Output 'WINDOWS_POSTGRES_LISTENERS'
Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -in @(5432,5433) } | Select-Object LocalAddress,LocalPort,OwningProcess | Format-Table | Out-String | Write-Output
Write-Output 'POSTGRES_EXECUTABLES'
Get-Command psql,pg_ctl,postgres -ErrorAction SilentlyContinue | Select-Object Name,Source | Format-Table | Out-String | Write-Output
