@echo off
rem Starts the fake news checker for the website. Listens on this PC only (127.0.0.1).
cd /d "%~dp0"
if not exist .env copy .env.example .env >nul
python -m uvicorn app.main:app --host 127.0.0.1 --port 8765 --env-file .env
