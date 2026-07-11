# Call the deploy webhook after an FTP/SFTP sync completes.
# Usage:
#   powershell -File scripts\trigger-deploy-webhook.ps1 -Url https://example.com -Secret your-secret

param(
    [Parameter(Mandatory = $true)]
    [string]$Url,

    [Parameter(Mandatory = $true)]
    [string]$Secret
)

$endpoint = $Url.TrimEnd('/') + '/api/deploy/webhook'

$response = Invoke-RestMethod -Method Post -Uri $endpoint -Headers @{
    'X-Deploy-Secret' = $Secret
    'Accept' = 'application/json'
}

$response | ConvertTo-Json -Depth 5
