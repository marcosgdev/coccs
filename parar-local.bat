@echo off
setlocal
cd /d "%~dp0"

echo Parando servidor local do GestContratos...

powershell -NoProfile -ExecutionPolicy Bypass -Command "$root=(Resolve-Path '.').Path; $procs=Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" | Where-Object { $_.CommandLine -like '*public/router.php*' -and $_.CommandLine -like '*127.0.0.1:8080*' }; if(-not $procs){ Write-Host 'Nenhum servidor local encontrado.'; exit 0 }; foreach($p in $procs){ Stop-Process -Id $p.ProcessId -Force; Write-Host ('Processo encerrado: ' + $p.ProcessId) }"

pause
