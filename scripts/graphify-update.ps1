# Graphify update + Laravel view→blade linker
# Usage: powershell -File scripts/graphify-update.ps1 [path] [-Force]
param(
    [string]$Path = ".",
    [switch]$Force
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$forceArg = @()
if ($Force) {
    $forceArg = @("--force")
}

Write-Host "graphify update $Path $($forceArg -join ' ') ..."
& graphify update $Path @forceArg
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Write-Host "linking view() calls to blades ..."
php "$PSScriptRoot\graphify-link-views.php"
exit $LASTEXITCODE
