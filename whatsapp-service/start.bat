@echo off
REM WhatsApp Service Startup Script for Windows

echo ==================================================
echo   WhatsApp Bike Notification Service
echo ==================================================
echo.

REM Check if .env exists
if not exist .env (
    echo [WARNING] .env file not found!
    echo [INFO] Creating .env from template...
    copy env.template .env
    echo [SUCCESS] .env file created. Please configure it before starting the service.
    echo.
    echo Required configuration:
    echo   1. Set PORT (default: 3000^)
    echo   2. Run 'npm start' once to get WhatsApp groups list
    echo   3. Set WHATSAPP_GROUP_ID in .env
    echo   4. Run this script again
    pause
    exit /b 1
)

REM Check if node_modules exists
if not exist node_modules (
    echo [INFO] Installing dependencies...
    call npm install
    echo.
)

REM Create logs directory if it doesn't exist
if not exist logs mkdir logs

REM Check if PM2 is available
where pm2 >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [INFO] Starting with PM2...
    pm2 start ecosystem.config.js
    pm2 save
    echo.
    echo [SUCCESS] Service started with PM2
    echo [INFO] Monitor: pm2 monit
    echo [INFO] Logs: pm2 logs whatsapp-service
    echo [INFO] Restart: pm2 restart whatsapp-service
    echo [INFO] Stop: pm2 stop whatsapp-service
) else (
    echo [INFO] Starting with Node.js...
    echo [WARNING] Consider installing PM2 for production: npm install -g pm2
    echo.
    node server.js
)

echo.
echo ==================================================
pause

