@echo off
echo Starting CPMS Development Server...
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP from https://www.php.net/downloads.php
    pause
    exit /b 1
)

REM Check if MySQL is running
sc query mysql >nul 2>&1
if %errorlevel% neq 0 (
    echo WARNING: MySQL service may not be running
    echo Please ensure MySQL is installed and running
    echo.
)

REM Set current directory to project root
cd /d "%~dp0cipms"

REM Start PHP built-in web server
echo Starting PHP server on http://localhost:8000
echo Press Ctrl+C to stop the server
echo.
php -S localhost:8000 -t .

pause
