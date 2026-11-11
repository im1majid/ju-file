@echo off
title Ju File - Local Server

set "PROJECT_DIR=%~dp0"
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

set "SCRATCH=C:\Users\im majid\.gemini\antigravity\brain\1be6cf95-9abc-409e-853e-a7609cb89d31\scratch"
set "PHP_EXE=%SCRATCH%\php\php.exe"
set "MARIADB_BIN=%SCRATCH%\mariadb-11.4.13-winx64\bin"
set "DB_DATA=%SCRATCH%\dbdata"

if not exist "%PHP_EXE%" (
    if exist "C:\xampp\php\php.exe" (
        set "PHP_EXE=C:\xampp\php\php.exe"
    ) else (
        set "PHP_EXE=php"
    )
)

echo ===================================================
echo   Ju File - Starting MySQL and PHP Web Server...
echo ===================================================

netstat -ano | findstr ":3306 " >nul
if errorlevel 1 (
    if exist "%MARIADB_BIN%\mariadbd.exe" (
        if not exist "%DB_DATA%" (
            "%MARIADB_BIN%\mysql_install_db.exe" --datadir="%DB_DATA%" >nul 2>&1
        )
        start "JuFile-MySQL" /min "%MARIADB_BIN%\mariadbd.exe" --datadir="%DB_DATA%" --port=3306
        timeout /t 3 /nobreak >nul
        "%MARIADB_BIN%\mysql.exe" -u root -P 3306 < "%PROJECT_DIR%\database.sql" >nul 2>&1
    )
)

echo.
echo [OK] Server running at: http://localhost:8000
echo Keep this window open while using the site.
echo ===================================================

start "" cmd /c "timeout /t 2 /nobreak >nul & start http://localhost:8000"
"%PHP_EXE%" -d upload_max_filesize=60M -d post_max_size=65M -d memory_limit=512M -S localhost:8000 -t "%PROJECT_DIR%"
pause