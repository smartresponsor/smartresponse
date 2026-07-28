@echo off
setlocal
cd /d "%~dp0\..\.."
title App Host - 127.0.0.1:8080
php -S 127.0.0.1:8080 -t public public/index.php
