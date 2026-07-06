@echo off
REM Non-interactive deployment — run after uploading or overwriting project files.
cd /d "%~dp0"

php artisan app:deploy --force --optimize
if errorlevel 1 (
    echo [ERROR] Deployment failed.
    exit /b 1
)

echo [SUCCESS] Deployment complete.
