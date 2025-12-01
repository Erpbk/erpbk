@echo off
cd /d "%~dp0"
echo.
echo ========================================
echo Starting WhatsApp Service with QR Code
echo ========================================
echo.
echo A Chrome window will open with WhatsApp Web
echo The QR code will appear in this console
echo.
echo Scan the QR code with your WhatsApp:
echo 1. Open WhatsApp on your phone
echo 2. Go to Settings ^> Linked Devices ^> Link a Device
echo 3. Scan the QR code
echo.
echo ========================================
echo.
npm start
pause

