#!/usr/bin/env bash
# Starts the checker for local use or behind a website. Listens on this machine only (127.0.0.1),
# so the outside world cannot reach it directly; the website forwards visitors' requests to it.
cd "$(dirname "$0")" || exit 1
exec python3 -m uvicorn app.main:app --host 127.0.0.1 --port "${PORT:-8765}" --env-file .env
