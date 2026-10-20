@echo off
REM Quick start without XAMPP's Apache: needs PHP in PATH and MySQL running.
if not exist config\installed.lock (
  echo Opening the setup wizard...
  start "" http://localhost:8000/setup.php
) else (
  start "" http://localhost:8000
)
php -S localhost:8000
