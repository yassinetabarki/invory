@echo off
rem Runs Laravel Sail inside WSL2 from Windows (PowerShell or cmd), from any folder of the repo.
rem   .\sail up -d            start the Docker stack (app, PostgreSQL, Mailpit)
rem   .\sail artisan migrate  run artisan inside the app container
rem   .\sail test             run the Pest suite inside the container
rem   .\sail down             stop the stack
rem Requires Docker Desktop running with WSL integration enabled for your distro (docs/SETUP.md).
setlocal
pushd "%~dp0"
if not exist "vendor\bin\sail" (
    echo vendor\bin\sail not found. Run scripts\bootstrap.ps1 first. 1>&2
    popd
    exit /b 1
)
wsl.exe -- ./vendor/bin/sail %*
set "SAIL_EXIT=%ERRORLEVEL%"
popd
exit /b %SAIL_EXIT%
