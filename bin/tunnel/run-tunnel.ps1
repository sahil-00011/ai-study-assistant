$ErrorActionPreference = "SilentlyContinue"
$tunnelDir = "E:\chotu AI\bin\tunnel"
$logFile = "$tunnelDir\tunnel.log"
$urlFile = "$tunnelDir\live_url.txt"

# 1. Stop existing instances
Get-Process | Where-Object { $_.ProcessName -match "cloudflared" } | Stop-Process -Force -ErrorAction SilentlyContinue

# 2. Check if IPv4 proxy is running; if not, launch detached
$proxyPortListening = Test-NetConnection -ComputerName "127.0.0.1" -Port 8888 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $proxyPortListening) {
    $proxyStartInfo = New-Object System.Diagnostics.ProcessStartInfo
    $proxyStartInfo.FileName = "node"
    $proxyStartInfo.Arguments = "`"$tunnelDir\ipv4-proxy.js`""
    $proxyStartInfo.UseShellExecute = $false
    $proxyStartInfo.CreateNoWindow = $true
    [System.Diagnostics.Process]::Start($proxyStartInfo)
    Start-Sleep -Seconds 2
}

# 3. Clean previous log and url file
Remove-Item $logFile -Force -ErrorAction SilentlyContinue
Remove-Item $urlFile -Force -ErrorAction SilentlyContinue

# 4. Start cloudflared detached with IPv4 proxy
$startInfo = New-Object System.Diagnostics.ProcessStartInfo
$startInfo.FileName = "$tunnelDir\cloudflared.exe"
$startInfo.Arguments = "tunnel --edge-ip-version 4 --url http://127.0.0.1:80 --logfile `"$logFile`""
$startInfo.EnvironmentVariables["HTTPS_PROXY"] = "http://127.0.0.1:8888"
$startInfo.EnvironmentVariables["HTTP_PROXY"] = "http://127.0.0.1:8888"
$startInfo.UseShellExecute = $false
$startInfo.CreateNoWindow = $true

[System.Diagnostics.Process]::Start($startInfo)

# 5. Extract generated URL
$foundUrl = $null
for ($i = 0; $i -lt 30; $i++) {
    Start-Sleep -Seconds 1
    if (Test-Path $logFile) {
        $content = Get-Content $logFile -Raw -ErrorAction SilentlyContinue
        if ($content -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com') {
            $foundUrl = $matches[0]
            Set-Content -Path $urlFile -Value $foundUrl
            Write-Host "TUNNEL_READY:$foundUrl"
            break
        }
    }
}

if (-not $foundUrl) {
    Write-Host "ERROR: Could not establish tunnel. Log output:"
    Get-Content $logFile -ErrorAction SilentlyContinue
}
