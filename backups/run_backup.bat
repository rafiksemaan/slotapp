@echo off
set BACKUP_DIR=C:\wamp64\backups
set DATE=%date:~-4,4%-%date:~-10,2%-%date:~-7,2%
set PHP_PATH=C:\wamp64\bin\php\php8.0.26\php.exe
set SCRIPT_PATH=C:\wamp64\www\slotapp\backups\backup.php

%PHP_PATH% %SCRIPT_PATH%