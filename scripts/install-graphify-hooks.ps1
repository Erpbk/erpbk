# Installs graphify git hooks, then patches them to run scripts/graphify-link-views.php
# after every automatic rebuild (post-commit / post-checkout).
#
# Usage: powershell -File scripts/install-graphify-hooks.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Write-Host "Installing stock graphify hooks..."
graphify hook install
if ($LASTEXITCODE -ne 0) {
    Write-Error "graphify hook install failed"
    exit $LASTEXITCODE
}

$linkerBlock = @"
    # erpbk-view-linker-start
    # Laravel view('...') -> blade `"renders`" edges (custom erpbk post-step)
    try:
        import shutil, subprocess
        _php = shutil.which('php')
        _linker = _root / 'scripts' / 'graphify-link-views.php'
        if _php and _linker.is_file():
            print('[graphify hook] linking view() calls to blades...')
            subprocess.run([_php, str(_linker)], cwd=os.getcwd(), check=False)
        else:
            print('[graphify hook] view linker skipped (php or scripts/graphify-link-views.php missing)')
    except Exception as _link_exc:
        print(f'[graphify hook] view linker skipped: {_link_exc}')
    # erpbk-view-linker-end
"@

function Patch-GraphifyHook {
    param([string]$HookPath)

    if (-not (Test-Path $HookPath)) {
        Write-Warning "Hook not found: $HookPath"
        return
    }

    $content = Get-Content -Raw -Path $HookPath

    # Remove previous erpbk patch if re-running
    $content = [regex]::Replace(
        $content,
        '(?ms)^\s*# erpbk-view-linker-start.*?^\s*# erpbk-view-linker-end\r?\n?',
        ''
    )

    $anchors = @(
        '_rebuild_code(_root, changed_paths=changed, force=_force)',
        '_rebuild_code(_root, force=_force)'
    )

    $patched = $false
    foreach ($anchor in $anchors) {
        $idx = $content.IndexOf($anchor)
        if ($idx -lt 0) { continue }

        $insertAt = $idx + $anchor.Length
        # Keep following newline
        if ($insertAt -lt $content.Length -and ($content[$insertAt] -eq "`r" -or $content[$insertAt] -eq "`n")) {
            # move past \r\n or \n
            if ($content[$insertAt] -eq "`r" -and ($insertAt + 1) -lt $content.Length -and $content[$insertAt + 1] -eq "`n") {
                $insertAt += 2
            } else {
                $insertAt += 1
            }
        }

        $content = $content.Substring(0, $insertAt) + $linkerBlock + "`n" + $content.Substring($insertAt)
        $patched = $true
        break
    }

    if (-not $patched) {
        Write-Warning "Could not find _rebuild_code(...) insertion point in $HookPath"
        return
    }

    Set-Content -Path $HookPath -Value $content -NoNewline -Encoding utf8
    Write-Host "Patched: $HookPath"
}

Patch-GraphifyHook -HookPath (Join-Path $root ".git\hooks\post-commit")
Patch-GraphifyHook -HookPath (Join-Path $root ".git\hooks\post-checkout")

Write-Host "Done. Commit/checkout rebuilds will now link view() calls to blades."
