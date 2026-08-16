$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8000'
$roomId = '00000000-0000-4000-8000-000000010001'
$userId = '00000000-0000-4000-8000-000000000111'
$headers = @{ 'X-User-Id' = $userId }

function Invoke-Smoke([string]$Method, [string]$Path, $Body = $null) {
    try {
        if ($null -ne $Body) {
            $json = $Body | ConvertTo-Json -Compress -Depth 8
            $response = Invoke-WebRequest -UseBasicParsing -Uri ($base + $Path) -Method $Method -Headers $headers -ContentType 'application/json' -Body $json -TimeoutSec 15
        } else {
            $response = Invoke-WebRequest -UseBasicParsing -Uri ($base + $Path) -Method $Method -Headers $headers -TimeoutSec 15
        }
        $payload = $null
        try { $payload = $response.Content | ConvertFrom-Json } catch {}
        Write-Host ("{0} {1} => {2}" -f $Method, $Path, [int]$response.StatusCode)
        return @{ Status = [int]$response.StatusCode; Payload = $payload; Content = $response.Content }
    } catch {
        $status = 0
        if ($_.Exception.Response -and $_.Exception.Response.StatusCode) { $status = [int]$_.Exception.Response.StatusCode }
        Write-Host ("{0} {1} => {2} ERROR {3}" -f $Method, $Path, $status, $_.Exception.Message)
        return @{ Status = $status; Payload = $null; Content = '' }
    }
}

Invoke-Smoke 'GET' '/api/thread' | Out-Null
$created = Invoke-Smoke 'POST' '/api/thread' @{ roomId = $roomId; userId = $userId; title = 'Messaging production-readiness smoke' }
$threadId = if ($created.Payload -and $created.Payload.threadId) { [string]$created.Payload.threadId } else { '00000000-0000-4000-8000-000000020001' }
Invoke-Smoke 'GET' ("/api/thread/{0}" -f $threadId) | Out-Null
Invoke-Smoke 'GET' ("/api/thread/message/{0}" -f $threadId) | Out-Null
$reply = Invoke-Smoke 'POST' ("/api/thread/reply/{0}" -f $threadId) @{ userId = $userId; text = 'Canonical Messaging smoke reply'; links = @() }
$messageId = if ($reply.Payload -and $reply.Payload.messageId) { [string]$reply.Payload.messageId } else { '00000000-0000-4000-8000-000000030001' }
Invoke-Smoke 'GET' ("/api/thread/participant/{0}" -f $threadId) | Out-Null
Invoke-Smoke 'GET' ("/api/thread/role/{0}" -f $threadId) | Out-Null
Invoke-Smoke 'POST' ("/api/thread/role/{0}" -f $threadId) @{ userId = $userId; role = 'owner'; by = $userId } | Out-Null
Invoke-Smoke 'POST' ("/api/thread/read/{0}" -f $threadId) @{ userId = $userId; messageId = $messageId } | Out-Null
Invoke-Smoke 'POST' ("/api/thread/archive/{0}" -f $threadId) @{ userId = $userId; archived = $false } | Out-Null
Invoke-Smoke 'POST' ("/api/thread/ban/{0}" -f $threadId) @{ userId = $userId } | Out-Null
Invoke-Smoke 'POST' ("/api/thread/mute/{0}" -f $threadId) @{ userId = $userId } | Out-Null
exit 0
