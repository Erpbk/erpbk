# Get WhatsApp Groups Script
Write-Host "`n============================================================" -ForegroundColor Cyan
Write-Host "FETCHING YOUR WHATSAPP GROUPS..." -ForegroundColor Cyan
Write-Host "============================================================`n" -ForegroundColor Cyan

Start-Sleep -Seconds 2

try {
    $response = Invoke-RestMethod -Uri 'http://localhost:3001/api/groups' -Method Get -ErrorAction Stop
    
    if ($response.success) {
        Write-Host "Found $($response.count) groups:`n" -ForegroundColor Green
        
        foreach ($group in $response.groups) {
            Write-Host "Name: " -NoNewline -ForegroundColor White
            Write-Host $group.name -ForegroundColor Green
            Write-Host "ID: " -NoNewline -ForegroundColor White
            Write-Host $group.id -ForegroundColor Yellow
            Write-Host "Participants: $($group.participants)" -ForegroundColor Gray
            Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray
            Write-Host ""
        }
        
        Write-Host "`nCOPY THE YELLOW ID and add to whatsapp-service\.env:" -ForegroundColor Cyan
        Write-Host "WHATSAPP_GROUP_ID=<paste-id-here>" -ForegroundColor White
        Write-Host "============================================================`n" -ForegroundColor Cyan
    }
    else {
        Write-Host "Error: $($response.message)" -ForegroundColor Red
    }
}
catch {
    Write-Host "ERROR: Could not connect to WhatsApp service" -ForegroundColor Red
    Write-Host "Make sure the service is running and WhatsApp is connected.`n" -ForegroundColor Yellow
    Write-Host "Details: $($_.Exception.Message)" -ForegroundColor Gray
}

Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

