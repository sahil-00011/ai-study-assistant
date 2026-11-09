@echo off
cd /d "E:\chotu AI\bin\tunnel"
set HTTPS_PROXY=http://127.0.0.1:8888
set HTTP_PROXY=http://127.0.0.1:8888

taskkill /f /im cloudflared.exe >nul 2>&1
timeout /t 1 /nobreak >nul

start "" /b node "E:\chotu AI\bin\tunnel\ipv4-proxy.js"
timeout /t 2 /nobreak >nul

if exist "E:\chotu AI\bin\tunnel\tunnel.log" del /f /q "E:\chotu AI\bin\tunnel\tunnel.log"

:loop
"E:\chotu AI\bin\tunnel\cloudflared.exe" tunnel --protocol http2 --edge-ip-version 4 --url http://127.0.0.1:80 --logfile "E:\chotu AI\bin\tunnel\tunnel.log"
timeout /t 3 /nobreak >nul
goto loop
