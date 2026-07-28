@echo off
setlocal
cd /d "%~dp0\..\.."
title Doctrine PostgreSQL Schema Validation
php bin/console doctrine:schema:validate --em=postgres --no-interaction
echo.
pause
