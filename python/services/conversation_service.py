# python/services/conversation_service.py
# Stores conversation history as simple JSON files.
# One file per project: python/data/conversations/project_123.json
#
# WHY NOT ChromaDB?
#   - ChromaDB needs embeddings (costs money with OpenAI)
#   - We don't need semantic search for resumes — we just need ALL past turns
#   - JSON files are free, instant, and human-readable
#   - If you have 50 revisions for one project, that's maybe 200KB. Trivial.

import json
import os
from datetime import datetime

# Store conversations in python/data/conversations/
DATA_DIR = os.path.join(os.path.dirname(__file__), '..', 'data', 'conversations')


def _ensure_dir():
    """Create the data directory if it doesn't exist."""
    os.makedirs(DATA_DIR, exist_ok=True)


def _file_path(project_id: str) -> str:
    """Get the JSON file path for a project."""
    return os.path.join(DATA_DIR, f"project_{project_id}.json")


def get_history(project_id: str) -> list:
    """
    Get ALL conversation turns for a project.
    Returns: [{"role": "user"|"assistant", "content": "...", "timestamp": "..."}]
    """
    _ensure_dir()
    path = _file_path(project_id)

    if not os.path.exists(path):
        return []

    try:
        with open(path, 'r', encoding='utf-8') as f:
            return json.load(f)
    except (json.JSONDecodeError, IOError):
        return []


def add_turn(project_id: str, role: str, content: str) -> None:
    """
    Append a conversation turn to the project's history file.
    role: "user" or "assistant"
    content: the full text (resume data JSON string, or HTML output)
    """
    _ensure_dir()
    history = get_history(project_id)

    history.append({
        "role": role,
        "content": content,
        "timestamp": datetime.now().isoformat()
    })

    with open(_file_path(project_id), 'w', encoding='utf-8') as f:
        json.dump(history, f, ensure_ascii=False, indent=2)