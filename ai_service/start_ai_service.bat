@echo off
cd /d C:/xampp/htdocs/traffic
call .venv/Scripts/activate
uvicorn ai_service.embeddings.query_laws:app --host 127.0.0.1 --port 8000
