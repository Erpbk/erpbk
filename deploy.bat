@echo off
REM =========================================================================
REM Laravel Deployment Script for Windows
REM This script automates the deployment process
REM =========================================================================

echo ===============================================================
echo          Laravel Deployment Script - Asset Fix
echo ===============================================================
echo.

REM Check PHP version
echo Checking PHP version...
php -r "if (version_compare(PHP_VERSION, '8.2.0', '<')) { echo '[ERROR] PHP version ' . PHP_VERSION . ' is too old!' . PHP_EOL; echo 'This project requires PHP 8.2.0 or higher' . PHP_EOL; echo 'Please read PHP_VERSION_FIX.md for upgrade instructions.' . PHP_EOL; exit(1); } else { echo '[OK] PHP version ' . PHP_VERSION . PHP_EOL; }"
if errorlevel 1 (
    pause
    exit /b 1
)
echo.

REM Check if .env exists
if not exist .env (
    echo [ERROR] .env file not found!
    if exist .env.example (
        echo Creating .env from .env.example...
        copy .env.example .env
        echo [SUCCESS] .env file created
        echo Please edit .env and set APP_URL, database credentials, etc.
        echo Then run this script again.
        pause
        exit /b 1
    ) else (
        echo [ERROR] .env.example not found either!
        pause
        exit /b 1
    )
)

echo [OK] .env file found

REM Check if APP_KEY is set
findstr /C:"APP_KEY=base64:" .env >nul
if errorlevel 1 (
    echo Generating application key...
    php artisan key:generate
    echo [SUCCESS] Application key generated
) else (
    echo [OK] Application key already set
)

REM Install Composer dependencies
echo.
echo Installing Composer dependencies...
where composer >nul 2>nul
if %errorlevel% equ 0 (
    composer install --optimize-autoloader --no-dev
    echo [SUCCESS] Composer dependencies installed
) else (
    echo [WARNING] Composer not found. Please install dependencies manually.
)

REM Create storage link
echo.
echo Creating storage symlink...
php artisan storage:link 2>nul
echo [SUCCESS] Storage symlink created (or already exists)

REM Clear caches
echo.
echo Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo [SUCCESS] Caches cleared

REM Ask about production caching
echo.
set /p CACHE="Do you want to cache config/routes for production? (y/n): "
if /i "%CACHE%"=="y" (
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo [SUCCESS] Production caches created
)

REM Build assets
echo.
set /p BUILD="Do you want to build assets with npm? (y/n): "
if /i "%BUILD%"=="y" (
    where npm >nul 2>nul
    if %errorlevel% equ 0 (
        echo Installing npm dependencies...
        call npm install
        echo Building assets for production...
        call npm run production
        echo [SUCCESS] Assets built
    ) else (
        echo [WARNING] npm not found. Skipping asset build.
    )
)

echo.
echo ===============================================================
echo                  Deployment Complete!
echo ===============================================================
echo.
echo Next steps:
echo 1. Verify APP_URL in .env matches your domain
echo 2. Ensure document root points to /public folder
echo 3. Visit your website and check browser console for errors
echo 4. Clear your browser cache (Ctrl + F5)
echo.
echo For troubleshooting, see DEPLOYMENT_GUIDE.md
echo.
pause

