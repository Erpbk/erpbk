@echo off
color 0A
echo.
echo ========================================
echo  WhatsApp Integration Verification
echo ========================================
echo.

echo [Step 1/8] Checking Laravel Files...
echo.

set "missing=0"

if exist "app\Events\BikeAssignedEvent.php" (
    echo [OK] BikeAssignedEvent.php exists
) else (
    echo [FAIL] BikeAssignedEvent.php NOT FOUND
    set "missing=1"
)

if exist "app\Listeners\SendBikeAssignmentNotification.php" (
    echo [OK] SendBikeAssignmentNotification.php exists
) else (
    echo [FAIL] SendBikeAssignmentNotification.php NOT FOUND
    set "missing=1"
)

if exist "app\Jobs\SendWhatsAppNotificationJob.php" (
    echo [OK] SendWhatsAppNotificationJob.php exists
) else (
    echo [FAIL] SendWhatsAppNotificationJob.php NOT FOUND
    set "missing=1"
)

if exist "app\Services\WhatsAppService.php" (
    echo [OK] WhatsAppService.php exists
) else (
    echo [FAIL] WhatsAppService.php NOT FOUND
    set "missing=1"
)

echo.
echo [Step 2/8] Checking Node.js Service Files...
echo.

if exist "whatsapp-service\server.js" (
    echo [OK] server.js exists
) else (
    echo [FAIL] server.js NOT FOUND
    set "missing=1"
)

if exist "whatsapp-service\whatsapp-bot.js" (
    echo [OK] whatsapp-bot.js exists
) else (
    echo [FAIL] whatsapp-bot.js NOT FOUND
    set "missing=1"
)

if exist "whatsapp-service\logger.js" (
    echo [OK] logger.js exists
) else (
    echo [FAIL] logger.js NOT FOUND
    set "missing=1"
)

if exist "whatsapp-service\package.json" (
    echo [OK] package.json exists
) else (
    echo [FAIL] package.json NOT FOUND
    set "missing=1"
)

echo.
echo [Step 3/8] Checking Laravel .env Configuration...
echo.

findstr /C:"WHATSAPP_NOTIFICATIONS_ENABLED" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] WHATSAPP_NOTIFICATIONS_ENABLED found
) else (
    echo [FAIL] WHATSAPP_NOTIFICATIONS_ENABLED NOT FOUND
    echo     Add this to .env: WHATSAPP_NOTIFICATIONS_ENABLED=true
)

findstr /C:"WHATSAPP_NODE_SERVICE_URL" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] WHATSAPP_NODE_SERVICE_URL found
) else (
    echo [FAIL] WHATSAPP_NODE_SERVICE_URL NOT FOUND
    echo     Add this to .env: WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
)

findstr /C:"QUEUE_CONNECTION=database" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] QUEUE_CONNECTION=database configured
) else (
    echo [WARN] QUEUE_CONNECTION not set to database
    echo      Add this to .env: QUEUE_CONNECTION=database
)

echo.
echo [Step 4/8] Checking Node.js .env Configuration...
echo.

if exist "whatsapp-service\.env" (
    echo [OK] whatsapp-service\.env exists
    findstr /C:"WHATSAPP_GROUP_ID" whatsapp-service\.env >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] WHATSAPP_GROUP_ID configured
    ) else (
        echo [WARN] WHATSAPP_GROUP_ID not set
        echo      You need to run the service once to get group ID
    )
) else (
    echo [FAIL] whatsapp-service\.env NOT FOUND
    echo     Run: cd whatsapp-service ^&^& copy env.template .env
)

echo.
echo [Step 5/8] Checking Event Registration...
echo.

findstr /C:"BikeAssignedEvent" app\Providers\EventServiceProvider.php >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] BikeAssignedEvent registered in EventServiceProvider
) else (
    echo [FAIL] BikeAssignedEvent NOT registered
    echo     Check app\Providers\EventServiceProvider.php
)

echo.
echo [Step 6/8] Checking BikesController Integration...
echo.

findstr /C:"BikeAssignedEvent" app\Http\Controllers\BikesController.php >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] BikeAssignedEvent called in BikesController
) else (
    echo [FAIL] BikeAssignedEvent NOT found in BikesController
    echo     Check app\Http\Controllers\BikesController.php
)

echo.
echo [Step 7/8] Testing Node.js API Connection...
echo.

curl -s http://localhost:3000/api/health >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Node.js service is RESPONDING
    echo.
    echo API Response:
    curl -s http://localhost:3000/api/health
) else (
    echo [FAIL] Node.js service is NOT RESPONDING
    echo.
    echo To start the service:
    echo   cd whatsapp-service
    echo   npm install
    echo   npm start
)

echo.
echo [Step 8/8] Checking Queue Tables...
echo.

php artisan tinker --execute="echo \DB::table('jobs')->count() >= 0 ? '[OK] jobs table exists' : '[FAIL] jobs table missing';" 2>nul
if %errorlevel% neq 0 (
    echo [FAIL] Could not connect to database
    echo     Check your database configuration
    echo     Run: php artisan queue:table
    echo     Run: php artisan migrate
)

echo.
echo ========================================
echo  Verification Summary
echo ========================================
echo.

if %missing% equ 1 (
    echo [RESULT] Some files are MISSING
    echo.
    echo Please check the failed items above and:
    echo 1. Make sure all files were created
    echo 2. Check file paths and names
    echo 3. Review WHATSAPP_INTEGRATION_GUIDE.md
) else (
    echo [RESULT] All files EXIST!
)

echo.
echo ========================================
echo  Quick Start Commands
echo ========================================
echo.
echo 1. Clear Laravel cache:
echo    php artisan config:clear
echo.
echo 2. Start Node.js service (in new terminal):
echo    cd whatsapp-service
echo    npm install
echo    npm start
echo.
echo 3. Start queue worker (in new terminal):
echo    php artisan queue:work
echo.
echo 4. Test by assigning a bike!
echo.
echo ========================================
echo.

pause

