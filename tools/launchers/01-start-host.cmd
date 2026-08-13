@echo off
setlocal
cd /d "%~dp0\..\.."
title App Host - 127.0.0.1:8000
set APP_BASE_URI=http://127.0.0.1:8000
php -S 127.0.0.1:8000 -t public public/index.php
