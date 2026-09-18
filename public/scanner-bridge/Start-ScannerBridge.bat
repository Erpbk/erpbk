@echo off
title ERP Document Scanner Bridge
cd /d "%~dp0"
echo Starting ERP Document Scanner Bridge...
echo Keep this window open while using Document Scanner in the ERP.
echo.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0ScannerBridge.ps1"
echo.
pause
