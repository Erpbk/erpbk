# Watches the project for file changes and runs deploy-auto.bat (debounced).
# Usage: powershell -ExecutionPolicy Bypass -File scripts\watch-deploy.ps1

param(
    [int]$DebounceSeconds = 30
)

$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$deployScript = Join-Path $projectRoot "deploy-auto.bat"
$ignorePatterns = @(
    '\\vendor\\',
    '\\node_modules\\',
    '\\storage\\logs\\',
    '\\storage\\framework\\cache\\',
    '\\storage\\framework\\sessions\\',
    '\\storage\\framework\\views\\',
    '\\\.git\\',
    '\\bootstrap\\cache\\'
)

$state = @{
    Timer = $null
}

function Start-DeployTimer {
    param([int]$Seconds)

    if ($state.Timer) {
        $state.Timer.Stop()
        $state.Timer.Dispose()
        $state.Timer = $null
    }

    $timer = New-Object System.Timers.Timer
    $timer.Interval = $Seconds * 1000
    $timer.AutoReset = $false
    $timer.Add_Elapsed({
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Running deployment..." -ForegroundColor Cyan
        & $using:deployScript
    })
    $timer.Start()
    $state.Timer = $timer
}

$onChange = {
    param($source, $eventArgs)

    $fullPath = $eventArgs.FullPath
    foreach ($pattern in $using:ignorePatterns) {
        if ($fullPath -match $pattern) {
            return
        }
    }

    Start-DeployTimer -Seconds $using:DebounceSeconds
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Change detected: $fullPath" -ForegroundColor Yellow
}

$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $projectRoot
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true

Register-ObjectEvent -InputObject $watcher -EventName Changed -Action $onChange | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName Created -Action $onChange | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName Renamed -Action $onChange | Out-Null

Write-Host "Watching $projectRoot (debounce: ${DebounceSeconds}s). Press Ctrl+C to stop." -ForegroundColor Green

try {
    while ($true) {
        Start-Sleep -Seconds 1
    }
} finally {
    $watcher.EnableRaisingEvents = $false
    $watcher.Dispose()
}
