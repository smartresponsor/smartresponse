@echo off
setlocal
cd /d "%~dp0\..\.."
title PostgreSQL Docker Smoke
call composer test:preprod:postgres:docker
echo.
pause
