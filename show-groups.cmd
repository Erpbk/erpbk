@echo off
echo.
echo Fetching your WhatsApp groups...
echo.
curl -s http://localhost:3001/api/groups
echo.
echo.
echo Copy the "id" value from your target group above
echo and paste it in whatsapp-service\.env as WHATSAPP_GROUP_ID
echo.
pause

