@echo off
echo === STARTING FASTAPI SERVICE ===

cd /d D:\xampp\htdocs\traffic\ai_service

call D:\xampp\htdocs\traffic\venv\Scripts\activate.bat

uvicorn query_laws:app --host 0.0.0.0 --port 8000 --reload
