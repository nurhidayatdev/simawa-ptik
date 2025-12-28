@echo off
REM Batch file to run Telegram Reminder Script
REM This file can be scheduled in Windows Task Scheduler

REM Change to the XAMPP PHP directory
cd /d C:\xampp\php

REM Run the PHP script
php.exe "C:\xampp\htdocs\ptikc24\backend\cron\telegram_reminder.php"

REM Optional: pause to see the output (remove this line for scheduled tasks)
REM pause
