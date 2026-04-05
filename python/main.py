# python/main.py
# This is the FastAPI server entry point.
# PHP talks to THIS file via cURL.
#
# HOW TO RUN:
#   cd python
#   uvicorn main:app --port 8000 --reload
#
# Then it listens at http://127.0.0.1:8000

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional
import os

from services.conversation_service import get_history, add_turn
from services.llm_service import generate_resume_html

app = FastAPI(title="ResumeForge AI Backend")


# ── Request model ──
# This must match what PHP sends (from GenerateController.php)
class GenerateRequest(BaseModel):
    user_id: int
    project_id: int
    resume_data: dict
    revision: Optional[str] = ""


# ── Health check ──
# Visit http://127.0.0.1:8000/health to verify Python is running
@app.get("/health")
async def health():
    return {"status": "ok", "message": "ResumeForge AI is running"}


# ── Main endpoint ──
# PHP calls this: POST /generate-resume
@app.post("/generate-resume")
async def generate_resume(req: GenerateRequest):
    project_id = str(req.project_id)

    # Step 1: Save user's message to conversation history
    user_message = "Resume data:\n" + str(req.resume_data)
    if req.revision:
        user_message += "\n\nRevision request: " + req.revision
    add_turn(project_id, "user", user_message)

    # Step 2: Get ALL past conversation turns for this project
    history = get_history(project_id)

    # Step 3: Call Gemini AI to generate HTML
    try:
        html = generate_resume_html(req.resume_data, req.revision, history)
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"AI generation failed: {str(e)}")

    # Step 4: Save AI's response to conversation history
    add_turn(project_id, "assistant", html)

    # Step 5: Return HTML to PHP
    return {"html": html}