# ERP Document Scanner Bridge
# Exposes local Windows WIA scanners to the browser on http://127.0.0.1:39201
# Keep this window open while using Document Scanner in the ERP.

$ErrorActionPreference = 'Stop'
$Port = 39201
$Prefix = "http://127.0.0.1:$Port/"

function Write-CorsHeaders {
    param([Parameter(Mandatory = $true)] $Response)
    $Response.Headers['Access-Control-Allow-Origin'] = '*'
    $Response.Headers['Access-Control-Allow-Methods'] = 'GET, POST, OPTIONS'
    $Response.Headers['Access-Control-Allow-Headers'] = 'Content-Type'
    $Response.Headers['Access-Control-Allow-Private-Network'] = 'true'
}

function Write-JsonResponse {
    param(
        [Parameter(Mandatory = $true)] $Context,
        [Parameter(Mandatory = $true)] $Object,
        [int] $StatusCode = 200
    )
    $json = $Object | ConvertTo-Json -Depth 6 -Compress
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
    $Context.Response.StatusCode = $StatusCode
    $Context.Response.ContentType = 'application/json; charset=utf-8'
    Write-CorsHeaders -Response $Context.Response
    $Context.Response.ContentLength64 = $bytes.Length
    $Context.Response.OutputStream.Write($bytes, 0, $bytes.Length)
    $Context.Response.Close()
}

function Write-BytesResponse {
    param(
        [Parameter(Mandatory = $true)] $Context,
        [Parameter(Mandatory = $true)] [byte[]] $Bytes,
        [string] $ContentType = 'application/octet-stream',
        [string] $FileName = 'scan.bin',
        [int] $StatusCode = 200
    )
    $Context.Response.StatusCode = $StatusCode
    $Context.Response.ContentType = $ContentType
    Write-CorsHeaders -Response $Context.Response
    $Context.Response.Headers['Content-Disposition'] = "attachment; filename=`"$FileName`""
    $Context.Response.ContentLength64 = $Bytes.Length
    $Context.Response.OutputStream.Write($Bytes, 0, $Bytes.Length)
    $Context.Response.Close()
}

function Write-OptionsResponse {
    param([Parameter(Mandatory = $true)] $Context)
    $Context.Response.StatusCode = 204
    Write-CorsHeaders -Response $Context.Response
    $Context.Response.Close()
}

function Get-WiaPropertyValue {
    param($Props, [string] $Name)
    try {
        return $Props.Item($Name).Value
    } catch {
        return $null
    }
}

function Get-WiaScanners {
    $dm = New-Object -ComObject WIA.DeviceManager
    $list = @()
    for ($i = 1; $i -le $dm.DeviceInfos.Count; $i++) {
        $info = $dm.DeviceInfos.Item($i)
        $type = [int]$info.Type
        $name = Get-WiaPropertyValue $info.Properties 'Name'
        if (-not $name) { $name = "Device $i" }

        # 1 = Scanner. Also keep multi-function devices that advertise scan capability.
        $isScanner = ($type -eq 1) -or ($name -match 'scan|scanner|canon|epson|brother|hp|pixma|fujitsu|kodak')
        if (-not $isScanner) { continue }

        $list += [pscustomobject]@{
            id    = [string]$i
            name  = [string]$name
            label = [string]$name
            type  = 'scanner'
        }
    }

    # Always offer the native Windows acquire dialog as a reliable fallback
    # (works for many devices that Windows Scan can see but WIA listing misses).
    $list += [pscustomobject]@{
        id    = 'wia-dialog'
        name  = 'Windows Scan dialog…'
        label = 'Windows Scan dialog…'
        type  = 'dialog'
    }

    return $list
}

function Invoke-WiaScanDialog {
    param([string] $Format = 'jpeg')

    $format = $Format.ToLowerInvariant()
    $formatGuid = '{B96B3CAE-0728-11D3-9D7E-0000F81EF32E}' # JPEG
    $ext = 'jpg'
    $mime = 'image/jpeg'
    if ($format -eq 'png') {
        $formatGuid = '{B96B3CAF-0728-11D3-9D7E-0000F81EF32E}'
        $ext = 'png'
        $mime = 'image/png'
    }

    $dialog = New-Object -ComObject WIA.CommonDialog
    # ShowAcquireImage opens the native Windows scanner UI (device picker + scan)
    $image = $dialog.ShowAcquireImage(
        1,          # ScannerDeviceType
        0,          # Intent (unspecified)
        $formatGuid,
        $false,     # AlwaysSelectDevice
        $true,      # UseCommonUI
        $false      # CancelError
    )

    if (-not $image) {
        throw 'Scan was cancelled or no image was returned.'
    }

    $temp = Join-Path ([System.IO.Path]::GetTempPath()) ("erp-scan-" + [guid]::NewGuid().ToString('N') + ".$ext")
    try {
        $image.SaveFile($temp)
        $bytes = [System.IO.File]::ReadAllBytes($temp)
        return @{
            Bytes       = $bytes
            ContentType = $mime
            FileName    = ("scanner-document." + $ext)
        }
    } finally {
        if (Test-Path $temp) { Remove-Item -Force $temp -ErrorAction SilentlyContinue }
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($image) | Out-Null } catch {}
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($dialog) | Out-Null } catch {}
    }
}

function Set-WiaItemProperty {
    param($Item, [int] $PropertyId, $Value)
    try {
        foreach ($p in $Item.Properties) {
            if ([int]$p.PropertyID -eq $PropertyId) {
                $p.Value = $Value
                return $true
            }
        }
    } catch {}
    return $false
}

function Invoke-WiaScan {
    param(
        [string] $DeviceId,
        [string] $Format = 'jpeg',
        [string] $Source = 'flatbed'
    )

    if ($DeviceId -eq 'wia-dialog' -or [string]::IsNullOrWhiteSpace($DeviceId)) {
        return Invoke-WiaScanDialog -Format $Format
    }

    $format = $Format.ToLowerInvariant()
    $formatGuid = '{B96B3CAE-0728-11D3-9D7E-0000F81EF32E}' # JPEG
    $ext = 'jpg'
    $mime = 'image/jpeg'
    if ($format -eq 'png') {
        $formatGuid = '{B96B3CAF-0728-11D3-9D7E-0000F81EF32E}'
        $ext = 'png'
        $mime = 'image/png'
    } elseif ($format -eq 'bmp') {
        $formatGuid = '{B96B3CAB-0728-11D3-9D7E-0000F81EF32E}'
        $ext = 'bmp'
        $mime = 'image/bmp'
    }

    $dm = New-Object -ComObject WIA.DeviceManager
    $index = [int]$DeviceId
    if ($index -lt 1 -or $index -gt $dm.DeviceInfos.Count) {
        # Fall back to native dialog if the id is stale
        return Invoke-WiaScanDialog -Format $Format
    }

    $info = $dm.DeviceInfos.Item($index)
    $device = $info.Connect()
    if (-not $device -or $device.Items.Count -lt 1) {
        return Invoke-WiaScanDialog -Format $Format
    }

    $item = $device.Items.Item(1)

    if ($Source -eq 'adf') {
        Set-WiaItemProperty -Item $device -PropertyId 3088 -Value 1 | Out-Null
    } else {
        Set-WiaItemProperty -Item $device -PropertyId 3088 -Value 2 | Out-Null
    }

    Set-WiaItemProperty -Item $item -PropertyId 6146 -Value 2 | Out-Null
    Set-WiaItemProperty -Item $item -PropertyId 6147 -Value 200 | Out-Null
    Set-WiaItemProperty -Item $item -PropertyId 6148 -Value 200 | Out-Null

    $image = $null
    try {
        $image = $item.Transfer($formatGuid)
    } catch {
        try {
            $dialog = New-Object -ComObject WIA.CommonDialog
            $image = $dialog.ShowTransfer($item, $formatGuid, $false)
        } catch {
            return Invoke-WiaScanDialog -Format $Format
        }
    }

    if (-not $image) {
        return Invoke-WiaScanDialog -Format $Format
    }

    $temp = Join-Path ([System.IO.Path]::GetTempPath()) ("erp-scan-" + [guid]::NewGuid().ToString('N') + ".$ext")
    try {
        $image.SaveFile($temp)
        $bytes = [System.IO.File]::ReadAllBytes($temp)
        return @{
            Bytes       = $bytes
            ContentType = $mime
            FileName    = ("scanner-document." + $ext)
        }
    } finally {
        if (Test-Path $temp) { Remove-Item -Force $temp -ErrorAction SilentlyContinue }
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($image) | Out-Null } catch {}
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($item) | Out-Null } catch {}
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($device) | Out-Null } catch {}
        try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($dm) | Out-Null } catch {}
    }
}

function Read-JsonBody {
    param($Request)
    $reader = New-Object System.IO.StreamReader($Request.InputStream, $Request.ContentEncoding)
    try {
        $text = $reader.ReadToEnd()
        if ([string]::IsNullOrWhiteSpace($text)) { return @{} }
        return $text | ConvertFrom-Json
    } finally {
        $reader.Close()
    }
}

Write-Host ''
Write-Host '============================================' -ForegroundColor Cyan
Write-Host ' ERP Document Scanner Bridge' -ForegroundColor Cyan
Write-Host " Listening on $Prefix" -ForegroundColor Cyan
Write-Host ' Keep this window open while scanning.' -ForegroundColor Yellow
Write-Host ' Press Ctrl+C to stop.' -ForegroundColor Yellow
Write-Host '============================================' -ForegroundColor Cyan
Write-Host ''

try {
    $scanners = Get-WiaScanners
    if ($scanners.Count -eq 0) {
        Write-Host 'No WIA scanners detected yet. Connect/power on the scanner.' -ForegroundColor Yellow
    } else {
        Write-Host 'Detected scanners:' -ForegroundColor Green
        $scanners | ForEach-Object { Write-Host ("  - [{0}] {1}" -f $_.id, $_.name) }
    }
} catch {
    Write-Host ("WIA check warning: " + $_.Exception.Message) -ForegroundColor Yellow
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add($Prefix)

try {
    $listener.Start()
} catch {
    Write-Host ''
    Write-Host 'Failed to start listener. Is another Scanner Bridge already running?' -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ''
    Read-Host 'Press Enter to exit'
    exit 1
}

Write-Host 'Bridge is ready.' -ForegroundColor Green

while ($listener.IsListening) {
    $context = $null
    try {
        $context = $listener.GetContext()
        $request = $context.Request
        $path = $request.Url.AbsolutePath.TrimEnd('/').ToLowerInvariant()
        if ([string]::IsNullOrWhiteSpace($path)) { $path = '/' }

        if ($request.HttpMethod -eq 'OPTIONS') {
            Write-OptionsResponse -Context $context
            continue
        }

        if ($request.HttpMethod -eq 'GET' -and ($path -eq '/' -or $path -eq '/health' -or $path -eq '/api/health')) {
            Write-JsonResponse -Context $context -Object @{
                ok      = $true
                service = 'erp-scanner-bridge'
                version = '1.0.0'
                port    = $Port
            }
            continue
        }

        if ($request.HttpMethod -eq 'GET' -and ($path -eq '/api/scanners' -or $path -eq '/scanners')) {
            try {
                $list = @(Get-WiaScanners)
                Write-JsonResponse -Context $context -Object @{
                    ok       = $true
                    scanners = $list
                    count    = $list.Count
                }
            } catch {
                Write-JsonResponse -Context $context -StatusCode 500 -Object @{
                    ok      = $false
                    message = $_.Exception.Message
                    scanners = @()
                }
            }
            continue
        }

        if ($request.HttpMethod -eq 'POST' -and ($path -eq '/api/scan' -or $path -eq '/scan')) {
            try {
                $body = Read-JsonBody -Request $request
                $deviceId = [string]($body.deviceId)
                $format = [string]($body.format)
                $source = [string]($body.source)
                if (-not $format) { $format = 'jpeg' }
                if (-not $source) { $source = 'flatbed' }
                # PDF is assembled in the browser; scan as JPEG here.
                if ($format -eq 'pdf') { $format = 'jpeg' }

                $result = Invoke-WiaScan -DeviceId $deviceId -Format $format -Source $source
                Write-BytesResponse -Context $context -Bytes $result.Bytes -ContentType $result.ContentType -FileName $result.FileName
            } catch {
                Write-JsonResponse -Context $context -StatusCode 500 -Object @{
                    ok      = $false
                    message = $_.Exception.Message
                }
            }
            continue
        }

        Write-JsonResponse -Context $context -StatusCode 404 -Object @{
            ok      = $false
            message = 'Not found'
        }
    } catch {
        if ($context) {
            try {
                Write-JsonResponse -Context $context -StatusCode 500 -Object @{
                    ok      = $false
                    message = $_.Exception.Message
                }
            } catch {}
        }
        Write-Host ("Request error: " + $_.Exception.Message) -ForegroundColor Red
    }
}
