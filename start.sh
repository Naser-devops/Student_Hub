#!/usr/bin/env bash
# Quick start without Apache: needs PHP in PATH and MySQL running.
cd "$(dirname "$0")"
if [ ! -f config/installed.lock ]; then echo "First run → open http://localhost:8000/setup.php"; fi
php -S localhost:8000
