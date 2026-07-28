@echo off
setlocal
cd /d "%~dp0\..\.."
title PostgreSQL Pre-production Smoke
call composer test:preprod:postgres
echo.
pause
