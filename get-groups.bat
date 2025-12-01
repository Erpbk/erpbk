@echo off
echo.
echo Waiting for WhatsApp to connect...
timeout /t 3 /nobreak >nul
echo.
echo Fetching WhatsApp Groups...
echo.
powershell -NoProfile -ExecutionPolicy Bypass -Command "$response = Invoke-RestMethod -Uri 'http://localhost:3001/api/groups' -Method Get; Write-Host '============================================================' -ForegroundColor Cyan; Write-Host 'AVAILABLE WHATSAPP GROUPS:' -ForegroundColor Cyan; Write-Host '============================================================' -ForegroundColor Cyan; Write-Host ''; foreach($group in $response.groups) { Write-Host 'Group Name: ' -NoNewline; Write-Host $group.name -ForegroundColor Green; Write-Host 'Group ID: ' -NoNewline; Write-Host $group.id -ForegroundColor Yellow; Write-Host 'Participants: ' -NoNewline; Write-Host $group.participants; Write-Host '------------------------------------------------------------'; Write-Host '' }; Write-Host ''; Write-Host 'Copy the Group ID (yellow text) and add it to:' -ForegroundColor Cyan; Write-Host 'whatsapp-service\.env as WHATSAPP_GROUP_ID=your-id-here' -ForegroundColor White; Write-Host '============================================================' -ForegroundColor Cyan"
echo.
pause
