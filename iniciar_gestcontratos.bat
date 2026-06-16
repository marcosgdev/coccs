@echo off
cd /d "%~dp0"
echo Iniciando GestContratos em http://localhost:8080/login
echo.
echo Mantenha esta janela aberta enquanto estiver usando o sistema.
echo Para parar o servidor, pressione Ctrl+C e confirme com S.
echo.
php -S 127.0.0.1:8080 -t public public/router.php
