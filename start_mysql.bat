@echo off
echo Starting MySQL for XAMPP...
echo.

REM Change to MySQL directory
cd /d C:\xampp\mysql\bin

REM Check if MySQL is already running
netstat -an | findstr :3306 > nul
if %errorlevel%==0 (
    echo MySQL is already running on port 3306
    goto :end
)

echo Starting MySQL server...
start /B mysqld --console

REM Wait a moment for MySQL to start
timeout /t 3 /nobreak > nul

REM Check if it started successfully
netstat -an | findstr :3306 > nul
if %errorlevel%==0 (
    echo MySQL started successfully!
    echo You can now access your Gold Nail application.
) else (
    echo Failed to start MySQL. Please check XAMPP installation.
    echo You may need to run XAMPP Control Panel as Administrator.
)

:end
echo.
echo Press any key to continue...
pause > nul