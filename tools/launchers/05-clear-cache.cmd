@echo off
setlocal
cd /d "%~dp0\..\.."
title Symfony Cache Clear
php bin/console cache:clear --no-interaction
echo.
pause
