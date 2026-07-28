@echo off
setlocal
cd /d "%~dp0\..\.."
if not exist "var\log" mkdir "var\log"
start "" explorer.exe "%CD%\var\log"
