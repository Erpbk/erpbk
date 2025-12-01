@echo off
color 0E
echo.
echo ========================================
echo   WhatsApp Quick Diagnosis
echo ========================================
echo.

set "errors=0"

echo [Test 1/6] Checking Node.js Service...
curl -s http://localhost:3000/api/health >nul 2>&1
if %errorlevel% equ 0 (
    echo [PASS] Node.js service is responding
    curl -s http://localhost:3000/api/health
) else (
    echo [FAIL] Node.js service is NOT responding
    echo.
    echo FIX: Open a terminal and run:
    echo      cd whatsapp-service
    echo      npm start
    echo.
    set "errors=1"
)

echo.
echo [Test 2/6] Checking WhatsApp Connection...
curl -s http://localhost:3000/api/status >nul 2>&1
if %errorlevel% equ 0 (
    echo [PASS] WhatsApp API responding
    curl -s http://localhost:3000/api/status
) else (
    echo [FAIL] Cannot check WhatsApp status
    set "errors=1"
)

echo.
echo [Test 3/6] Checking Laravel Configuration...
findstr /C:"WHATSAPP_NOTIFICATIONS_ENABLED=true" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo [PASS] WHATSAPP_NOTIFICATIONS_ENABLED=true
) else (
    echo [FAIL] WHATSAPP_NOTIFICATIONS_ENABLED not set to true
    echo.
    echo FIX: Add this to .env:
    echo      WHATSAPP_NOTIFICATIONS_ENABLED=true
    echo.
    set "errors=1"
)

findstr /C:"WHATSAPP_NODE_SERVICE_URL" .env >nul 2>&1
if %errorlevel% equ 0 (
    echo [PASS] WHATSAPP_NODE_SERVICE_URL is configured
) else (
    echo [FAIL] WHATSAPP_NODE_SERVICE_URL not found
    echo.
    echo FIX: Add this to .env:
    echo      WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
    echo.
    set "errors=1"
)

echo.
echo [Test 4/6] Checking Queue Worker...
tasklist | findstr "php.exe" >nul 2>&1
if %errorlevel% equ 0 (
    echo [PASS] PHP processes are running
    tasklist | findstr "php.exe"
) else (
    echo [FAIL] No PHP queue worker detected
    echo.
    echo FIX: Open a terminal and run:
    echo      php artisan queue:work
    echo.
    set "errors=1"
)

echo.
echo [Test 5/6] Checking Failed Jobs...
php artisan queue:failed --json > temp_failed.txt 2>&1
find /C "[]" temp_failed.txt >nul
if %errorlevel% equ 0 (
    echo [PASS] No failed jobs
) else (
    echo [WARN] There are failed jobs
    echo.
    type temp_failed.txt
    echo.
    echo To retry: php artisan queue:retry all
    echo.
)
del temp_failed.txt >nul 2>&1

echo.
echo [Test 6/6] Testing WhatsApp Message...
echo Sending test message...
curl -X POST http://localhost:3000/api/send-message -H "Content-Type: application/json" -d "{\"type\":\"test\",\"message\":\"Diagnostic test at %time%\"}" 2>nul
if %errorlevel% equ 0 (
    echo.
    echo [PASS] Test message sent
    echo.
    echo CHECK YOUR WHATSAPP GROUP NOW!
    echo Did you receive the test message?
) else (
    echo [FAIL] Could not send test message
    set "errors=1"
)

echo.
echo ========================================
echo   Diagnosis Summary
echo ========================================
echo.

if %errors% equ 0 (
    echo [RESULT] All tests PASSED!
    echo.
    echo If messages still don't send:
    echo 1. Check WhatsApp group for test message
    echo 2. Try assigning a bike again
    echo 3. Check Laravel logs: storage\logs\laravel.log
    echo 4. Check Node.js logs: whatsapp-service\logs\combined.log
) else (
    echo [RESULT] Some tests FAILED!
    echo.
    echo Follow the FIX instructions above for each failed test.
    echo.
    echo After fixing, run this script again to verify.
)

echo.
echo ========================================
echo   Quick Fixes
echo ========================================
echo.
echo 1. Clear Laravel cache:
echo    php artisan config:clear
echo.
echo 2. Start Node.js service (Terminal 1):
echo    cd whatsapp-service
echo    npm start
echo.
echo 3. Start Queue Worker (Terminal 2):
echo    php artisan queue:work
echo.
echo 4. Check logs:
echo    type storage\logs\laravel.log
echo    type whatsapp-service\logs\error.log
echo.
echo ========================================
echo.

pause

