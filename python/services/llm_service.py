# python/services/llm_service.py
# Builds the prompt and calls Google Gemini (FREE) to generate resume HTML.

import google.generativeai as genai
import os
import json
from dotenv import load_dotenv

# Load API key from .env file
load_dotenv()

API_KEY = os.getenv("GEMINI_API_KEY")
if not API_KEY:
    raise ValueError(
        "GEMINI_API_KEY not found in .env file.\n"
        "Get a free key at https://aistudio.google.com/apikey\n"
        "Then put it in python/.env as: GEMINI_API_KEY=AIza..."
    )

# Configure Gemini
genai.configure(api_key=API_KEY)

# Use gemini-2.0-flash — fast, free, excellent at HTML generation
model = genai.GenerativeModel('gemini-2.0-flash')


def generate_resume_html(resume_data: dict, revision: str, history: list) -> str:
    """
    Main function. Builds prompt from data + history, calls Gemini, returns clean HTML.
    """
    prompt = _build_prompt(resume_data, revision, history)

    # Call Gemini
    response = model.generate_content(prompt)
    html = response.text.strip()

    # Gemini sometimes wraps output in ```html ... ``` markdown blocks.
    # Strip those if present.
    if html.startswith("```html"):
        html = html[7:]
    elif html.startswith("```"):
        html = html[3:]
    if html.endswith("```"):
        html = html[:-3]
    html = html.strip()

    return html


def _build_prompt(resume_data: dict, revision: str, history: list) -> str:
    """
    Build the full prompt with conversation history for context.
    """

    # ── Format resume data as readable text ──
    def fmt_value(v):
        """Format a single value for the prompt."""
        if isinstance(v, list):
            parts = []
            for item in v:
                if isinstance(item, dict) and "name" in item:
                    # Technical skill with rating
                    rating = item.get('rating', 0)
                    parts.append(f"  - {item['name']} ({rating}/10)")
                elif isinstance(item, dict) and "key" in item:
                    # Custom field
                    parts.append(f"  - {item['key']}: {item['value']}")
                else:
                    parts.append(f"  - {item}")
            return "\n".join(parts)
        return str(v)

    def fmt_section(data, indent=0):
        """Recursively format a section of resume data."""
        lines = []
        prefix = "  " * indent
        if isinstance(data, dict):
            for k, v in data.items():
                if k == "custom_fields":
                    if v:
                        lines.append(prefix + "Additional:")
                        lines.append(fmt_value(v))
                else:
                    lines.append(f"{prefix}{k}: {fmt_value(v)}")
        return "\n".join(lines)

    data_text = """JOB DESCRIPTION:
{jd}

SKILLS USER HAS FROM JD:
{skills}

WHAT USER IS NOW:
{now}

WHAT USER WANTS TO BE:
{future}""".format(
        jd=resume_data.get('job_description', 'N/A'),
        skills=resume_data.get('skills_i_have_from_jd', 'N/A'),
        now=fmt_section(resume_data.get('what_are_u_now', {}), 1),
        future=fmt_section(resume_data.get('what_u_want_to_be', {}), 1)
    )

    # ── Build conversation history section ──
    history_text = ""
    if len(history) >= 2:
        history_text = "\n## PREVIOUS CONVERSATION IN THIS PROJECT\n"
        # Show last 6 turns max (3 user + 3 assistant) to save tokens
        recent = history[-6:]
        for turn in recent:
            if turn["role"] == "user":
                label = "USER'S PREVIOUS INPUT"
                content = turn["content"][:800]
            else:
                label = "YOUR PREVIOUS RESUME OUTPUT"
                content = turn["content"][:500] + "..." if len(turn["content"]) > 500 else turn["content"]
            history_text += f"### {label}:\n{content}\n\n"

        history_text += (
            "IMPORTANT: This is a revision. Look at the user's new input above and "
            "modify your previous resume accordingly. Keep the same style and structure. "
            "Only change what the user asked to change.\n\n"
        )

    # ── Build revision section ──
    revision_text = ""
    if revision:
        revision_text = f"\n## USER'S REVISION REQUEST\n\"{revision}\"\n\nApply these changes to your previous output.\n"

    # ── Full prompt ──
    prompt = """You are an expert resume writer who creates ATS-optimized, professional HTML resumes.

{history}{revision}
## USER'S CURRENT RESUME DATA
{data}

## OUTPUT RULES — FOLLOW EXACTLY
1. Return ONLY valid HTML starting with <!DOCTYPE html><html> ending with </html>
2. NO markdown code blocks (no ```html). NO explanation before or after the HTML.
3. Use <style> in <head> for all CSS. No external CSS, no CDN links, no @import.
4. Use system fonts: -apple-system, 'Segoe UI', Roboto, sans-serif.
5. Must fit ONE page. Use compact spacing, small padding (24-28px), tight line-height (1.3-1.4).
6. Color scheme: dark text (#1a1a1a) with ONE accent color (#b07830 warm amber) for section headers and skill bars.
7. Section headers: small (11px), uppercase, letter-spacing (1.5px), with a thin bottom border.
8. Technical skills: show proficiency visually using colored dots or small bars based on the rating out of 10.
9. Personal/soft skills: show as subtle tag badges.
10. Write a 2-3 line professional summary at the top that weaves together the user's current skills and future vision. This is the MOST IMPORTANT part — make it compelling and specific to the job description.
11. Tailor the summary and skill emphasis to match the job description keywords.
12. Keep everything concise — no fluff, no wasted words. Every line should earn its place.
13. For experience/projects, use the key:value pairs provided. Format them as bullet points with the key as a bold label.
14. Include contact info (email, phone, linkedin, github) in a compact header row.
15. If data is sparse, still make it look complete and professional — don't leave empty sections visible.""".format(
        history=history_text,
        revision=revision_text,
        data=data_text
    )

    return prompt