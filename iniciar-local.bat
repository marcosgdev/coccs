@echo off
setlocal
cd /d "%~dp0"

set "APP_URL=http://localhost:8080/"

echo ===============================================
echo GestContratos - ambiente local
echo ===============================================
echo.

where php >nul 2>nul
if errorlevel 1 (
  echo PHP nao encontrado no PATH.
  echo Instale/configure o PHP ou use o terminal onde o comando php funciona.
  pause
  exit /b 1
)

if not exist "vendor\autoload.php" (
  echo Dependencias nao encontradas. Executando composer install...
  composer install
  if errorlevel 1 (
    echo Falha ao instalar dependencias.
    pause
    exit /b 1
  )
)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$mariaExe='C:\Users\vinicius.encarnacao\AppData\Local\MariaDB\mariadb-11.4.7-winx64\bin\mysqld.exe';" ^
  "$dataDir='C:\Users\vinicius.encarnacao\AppData\Local\MariaDB\data';" ^
  "$running=Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue;" ^
  "if(-not $running){" ^
  "  Write-Host 'Iniciando MariaDB...';" ^
  "  Start-Process -FilePath $mariaExe -ArgumentList '--console','--datadir=' + $dataDir,'--port=3306' -WindowStyle Hidden;" ^
  "  Start-Sleep -Seconds 3; Write-Host 'MariaDB iniciado.'" ^
  "} else { Write-Host 'MariaDB ja esta rodando na porta 3306.' }"

powershell -NoProfile -ExecutionPolicy Bypass -Command "$p=Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue; if($p){ exit 2 }"
if errorlevel 2 (
  echo.
  echo Ja existe um servico escutando na porta 8080.
  echo Abrindo o GestContratos no navegador.
  start "" "%APP_URL%"
  pause
  exit /b 0
)

echo.
echo Iniciando em %APP_URL%
echo Mantenha esta janela aberta enquanto estiver usando o sistema.
echo Para parar, pressione Ctrl+C ou execute parar-local.bat.
echo.

start "" powershell -WindowStyle Hidden -NoProfile -ExecutionPolicy Bypass -Command "Start-Sleep -Seconds 2; Start-Process '%APP_URL%'"
php -d upload_max_filesize=50M -d post_max_size=60M -d memory_limit=512M -S 127.0.0.1:8080 -t public public/router.php

echo.
echo Servidor local encerrado.
pause
