@echo off
title AI Study Assistant - Online Launcher
echo Starting AI Study Assistant Online Tunnel...
cd /d "%~dp0bin\tunnel"
call start-service.bat
pause
