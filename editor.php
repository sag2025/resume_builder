<?php
// editor.php
// The main resume editor page.
// Left side: form with all fields.
// Right side: live preview in an iframe.
// Top: generate button, accept button, download PDF.

session_start();
require_once __DIR__ . '/services/ProjectService.php';

// Ensure user exists
 $_SESSION['user_id'] = (new ProjectService())->ensureUser(session_id());

// Get project ID from URL
 $projectId = (int) ($_GET['project_id'] ?? 0);
if ($projectId <= 0) {
    header('Location: index.php');
    exit;
}

// Verify this project belongs to the current user
 $svc = new ProjectService();
 $project = $svc->getById($projectId);
if (!$project || $project['user_id'] !== $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

// Pass project name to HTML safely
 $projectName = htmlspecialchars($project['project_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ResumeForge — <?= $projectName ?></title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg: #f4f1eb; --surface: #fff; --surface2: #f9f7f3; --surface3: #f0ede6;
    --border: #ddd9d0; --border2: #c8c3b8;
    --accent: #1a6b3c; --accent-lt: #e8f5ee; --accent2: #145530;
    --ink: #1c1a16; --ink2: #4a4740; --ink3: #8a8680;
    --red: #c0392b; --amber: #b7600a;
    --radius: 10px; --radius-sm: 7px;
    --mono: 'JetBrains Mono', monospace;
    --serif: 'Lora', Georgia, serif;
    --display: 'Syne', sans-serif;
}

html, body { height: 100%; overflow: hidden; background: var(--bg); color: var(--ink); font-family: var(--mono); font-size: 13px; }

/* ── Top bar ── */
.topbar {
    height: 54px; background: var(--ink); display: flex; align-items: center;
    padding: 0 20px; gap: 14px; position: fixed; top: 0; left: 0; right: 0; z-index: 200;
}
.logo { font-family: var(--display); font-weight: 800; font-size: 17px; color: #fff; letter-spacing: -0.3px; flex-shrink: 0; }
.logo em { color: #7ef5a8; font-style: normal; }
.project-chip {
    display: flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
    border-radius: 6px; padding: 4px 12px;
}
.project-chip .dot { width: 6px; height: 6px; border-radius: 50%; background: #7ef5a8; }
.project-chip span { font-family: var(--display); font-weight: 600; font-size: 12px; color: #fff; }
.topbar-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
.tbtn {
    background: none; border: 1px solid rgba(255,255,255,0.18); color: rgba(255,255,255,0.75);
    font-family: var(--mono); font-size: 11px; padding: 5px 13px; border-radius: 5px;
    cursor: pointer; transition: all 0.15s;
}
.tbtn:hover { border-color: rgba(255,255,255,0.4); color: #fff; }
.tbtn-green {
    background: #7ef5a8; border: none; color: #0d2b1a;
    font-family: var(--display); font-weight: 700; font-size: 11px; padding: 6px 16px;
    border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.15s;
}
.tbtn-green:hover { background: #5de08e; }
.tbtn-accept {
    background: #fff; border: none; color: var(--accent);
    font-family: var(--display); font-weight: 700; font-size: 11px; padding: 6px 16px;
    border-radius: 5px; cursor: pointer; display: none; align-items: center; gap: 5px; transition: all 0.15s;
}
.tbtn-accept:hover { background: #e8f5ee; }
.tbtn-accept.visible { display: flex; }

/* ── Main layout: two columns ── */
.main { display: grid; grid-template-columns: 1fr 1fr; height: calc(100vh - 54px); margin-top: 54px; }

/* ── Left column (scrollable form) ── */
.left-col { overflow-y: auto; background: var(--bg); border-right: 1px solid var(--border); }
.left-col::-webkit-scrollbar { width: 5px; }
.left-col::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }
.left-inner { padding: 22px 22px 48px; }

/* ── Section labels ── */
.sec-label {
    font-family: var(--mono); font-size: 9px; font-weight: 500; letter-spacing: 2.5px;
    text-transform: uppercase; color: var(--ink3); margin-bottom: 10px;
    display: flex; align-items: center; gap: 8px;
}
.sec-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

/* ── Field boxes (job description, skills) ── */
.field-box {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
    margin-bottom: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: border-color 0.15s, box-shadow 0.15s;
}
.field-box:focus-within { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,107,60,0.08); }
.field-header {
    display: flex; align-items: center; gap: 10px; padding: 9px 13px;
    background: var(--surface2); border-bottom: 1px solid var(--border);
}
.field-key { font-family: var(--mono); font-size: 10px; font-weight: 500; color: var(--accent); letter-spacing: 0.3px; }
.field-tag {
    margin-left: auto; font-size: 8px; padding: 2px 7px; border-radius: 3px;
    background: #fff8f0; color: var(--amber); letter-spacing: 1px; text-transform: uppercase;
    font-weight: 500; border: 1px solid #ffd9a0;
}
.field-body textarea {
    width: 100%; background: transparent; border: none; outline: none; color: var(--ink);
    font-family: var(--mono); font-size: 11.5px; line-height: 1.75; padding: 12px 13px; resize: none;
}
.field-body textarea::placeholder { color: var(--ink3); }

/* ── Philosophy card ── */
.philosophy {
    margin: 22px 0; background: var(--ink); border-radius: var(--radius);
    padding: 20px; position: relative; overflow: hidden;
}
.philo-title { font-family: var(--display); font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 2px; }
.philo-sub { font-size: 10px; color: rgba(255,255,255,0.4); margin-bottom: 14px; }
.philo-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.philo-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-sm); padding: 12px; }
.philo-num { font-family: var(--serif); font-style: italic; font-size: 32px; color: #7ef5a8; line-height: 1; margin-bottom: 5px; }
.philo-card-title { font-family: var(--display); font-size: 11px; font-weight: 700; color: #fff; margin-bottom: 3px; }
.philo-card-desc { font-size: 10px; color: rgba(255,255,255,0.5); line-height: 1.5; }

/* ── Phase headers ── */
.phase-hdr {
    display: flex; align-items: center; gap: 12px; margin: 26px 0 12px; padding: 12px 14px;
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.phase-num { font-family: var(--serif); font-style: italic; font-size: 34px; color: var(--accent); line-height: 1; flex-shrink: 0; }
.phase-title { font-family: var(--display); font-weight: 700; font-size: 14px; color: var(--ink); }
.phase-desc { font-size: 10px; color: var(--ink3); margin-top: 2px; line-height: 1.4; }

/* ── Collapsible sections ── */
.sec-block {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
    margin-bottom: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.sec-block-hdr {
    display: flex; align-items: center; gap: 10px; padding: 10px 13px;
    background: var(--surface2); border-bottom: 1px solid var(--border);
    cursor: pointer; user-select: none; transition: background 0.15s;
}
.sec-block-hdr:hover { background: var(--surface3); }
.sb-icon {
    width: 28px; height: 28px; border-radius: 6px; background: var(--accent-lt);
    border: 1px solid rgba(26,107,60,0.15); display: flex; align-items: center;
    justify-content: center; font-size: 14px; flex-shrink: 0;
}
.sb-title { font-family: var(--display); font-weight: 700; font-size: 12px; color: var(--ink); }
.sb-sub { font-size: 9.5px; color: var(--ink3); margin-top: 1px; }
.sb-chev { margin-left: auto; font-size: 11px; color: var(--ink3); transition: transform 0.2s; }
.sec-block.closed .sb-chev { transform: rotate(-90deg); }
.sec-block.closed .sb-body { display: none; }
.sb-body { padding: 12px; display: flex; flex-direction: column; gap: 7px; }

/* ── Mini fields (key: value) ── */
.mini-field {
    display: grid; grid-template-columns: 130px 1fr;
    border: 1px solid var(--border); border-radius: var(--radius-sm);
    overflow: hidden; transition: border-color 0.15s, box-shadow 0.15s; background: var(--surface);
}
.mini-field:focus-within { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(26,107,60,0.1); }
.mini-k {
    padding: 8px 11px; font-family: var(--mono); font-size: 9.5px; color: var(--accent);
    background: var(--surface2); border-right: 1px solid var(--border);
    display: flex; align-items: center; font-weight: 500; letter-spacing: 0.2px; white-space: nowrap;
}
.mini-v {
    padding: 8px 11px; background: transparent; border: none; outline: none;
    color: var(--ink); font-family: var(--mono); font-size: 11px; width: 100%;
}
.mini-v::placeholder { color: var(--ink3); }

/* ── Skill rows ── */
.skill-row { display: grid; grid-template-columns: 1fr 116px 22px; gap: 7px; align-items: center; }
.skill-input {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 8px 11px; font-family: var(--mono); font-size: 11px; color: var(--ink);
    outline: none; transition: border-color 0.15s;
}
.skill-input:focus { border-color: var(--accent); }
.skill-input::placeholder { color: var(--ink3); }
.rating-bar { display: flex; gap: 3px; align-items: center; }
.rdot {
    width: 9px; height: 9px; border-radius: 50%; background: var(--surface3);
    border: 1px solid var(--border2); cursor: pointer; transition: all 0.1s; flex-shrink: 0;
}
.rdot.on { background: var(--accent); border-color: var(--accent); }
.rdot:hover { border-color: var(--accent); transform: scale(1.15); }

/* ── Remove button ── */
.btn-rm {
    width: 22px; height: 22px; border-radius: 50%; border: 1px solid var(--border);
    background: none; color: var(--ink3); font-size: 14px; line-height: 1;
    cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; flex-shrink: 0;
}
.btn-rm:hover { border-color: var(--red); color: var(--red); }

/* ── Add button ── */
.btn-add {
    background: none; border: 1px dashed var(--border2); border-radius: var(--radius-sm);
    color: var(--ink3); font-family: var(--mono); font-size: 10.5px; padding: 7px 12px;
    cursor: pointer; width: 100%; text-align: left; display: flex; align-items: center;
    gap: 7px; transition: all 0.15s;
}
.btn-add:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }

/* ── Custom field rows (key: value) ── */
.cf-row { display: grid; grid-template-columns: 1fr 1.4fr 22px; gap: 7px; align-items: center; }
.cf-input {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 8px 11px; font-family: var(--mono); font-size: 11px; color: var(--ink);
    outline: none; width: 100%; transition: border-color 0.15s;
}
.cf-input:focus { border-color: var(--accent); }
.cf-input::placeholder { color: var(--ink3); }
.cf-input.key-field { color: var(--accent); font-weight: 500; }

/* ── Submit area ── */
.submit-area {
    margin-top: 22px; background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.submit-title { font-family: var(--display); font-weight: 700; font-size: 14px; color: var(--ink); margin-bottom: 5px; }
.submit-hint { font-size: 10px; color: var(--ink3); line-height: 1.6; margin-bottom: 12px; }
.revision-box {
    width: 100%; background: var(--surface2); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 10px 12px; color: var(--ink);
    font-family: var(--mono); font-size: 11px; line-height: 1.7; resize: none;
    outline: none; margin-bottom: 12px; transition: border-color 0.15s;
}
.revision-box:focus { border-color: var(--accent); }
.revision-box::placeholder { color: var(--ink3); }

.gen-btn {
    width: 100%; background: var(--ink); border: none; border-radius: var(--radius-sm);
    color: #fff; font-family: var(--display); font-weight: 700; font-size: 13px;
    letter-spacing: 0.3px; padding: 13px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 9px; transition: background 0.15s;
}
.gen-btn:hover { background: #2c2a26; }
.gen-btn.loading { opacity: 0.75; pointer-events: none; }
.spinner {
    width: 15px; height: 15px; border: 2px solid rgba(255,255,255,0.2);
    border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; display: none;
}
.gen-btn.loading .spinner { display: block; }
.gen-btn.loading .btn-label { display: none; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Right column (preview) ── */
.right-col { display: flex; flex-direction: column; background: var(--surface3); overflow: hidden; }
.right-toolbar {
    display: flex; align-items: center; gap: 10px; padding: 11px 18px;
    background: var(--surface); border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.rt-title { font-family: var(--display); font-weight: 700; font-size: 12px; color: var(--ink); }
.rt-hint { font-size: 9.5px; color: var(--ink3); }

/* ── Status badge ── */
.status-badge {
    margin-left: auto; display: flex; align-items: center; gap: 5px;
    font-family: var(--mono); font-size: 9.5px; padding: 3px 10px; border-radius: 20px;
    background: var(--surface2); border: 1px solid var(--border); color: var(--ink3);
}
.sdot { width: 5px; height: 5px; border-radius: 50%; background: var(--ink3); flex-shrink: 0; }
.status-badge.ready { color: var(--accent); border-color: rgba(26,107,60,0.25); background: var(--accent-lt); }
.status-badge.ready .sdot { background: var(--accent); }
.status-badge.working { color: var(--amber); border-color: rgba(183,96,10,0.25); background: #fff8f0; }
.status-badge.working .sdot { background: var(--amber); animation: pulse 1s ease-in-out infinite; }
@keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.3; } }

/* ── Preview area ── */
.preview-wrap {
    flex: 1; overflow: hidden; position: relative;
    background: #e4e0d8;
    background-image: radial-gradient(circle at 1px 1px, rgba(0,0,0,0.08) 1px, transparent 0);
    background-size: 22px 22px;
}
.empty-state {
    position: absolute; inset: 0; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 12px; padding: 40px; text-align: center;
}
.empty-glyph {
    font-family: var(--serif); font-style: italic; font-size: 72px;
    color: rgba(0,0,0,0.12); line-height: 1; user-select: none;
}
.empty-title { font-family: var(--display); font-weight: 700; font-size: 17px; color: var(--ink2); }
.empty-desc { font-size: 11px; color: var(--ink3); max-width: 260px; line-height: 1.7; }
.empty-steps {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 14px 16px; display: flex; flex-direction: column; gap: 8px;
    max-width: 280px; width: 100%; text-align: left; margin-top: 4px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.estep { display: flex; align-items: center; gap: 10px; font-size: 11px; color: var(--ink2); }
.estep-num {
    width: 20px; height: 20px; border-radius: 50%; background: var(--accent); color: #fff;
    font-family: var(--display); font-weight: 700; font-size: 9px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ── Iframe ── */
#resume-frame {
    position: absolute; inset: 20px; border: none; border-radius: var(--radius);
    display: none; box-shadow: 0 4px 24px rgba(0,0,0,0.12), 0 1px 4px rgba(0,0,0,0.08);
}

/* ── Bottom bar (zoom) ── */
.dl-bar {
    display: flex; align-items: center; gap: 8px; padding: 10px 18px;
    background: var(--surface); border-top: 1px solid var(--border); flex-shrink: 0;
}
.dl-info { font-size: 10px; color: var(--ink3); }
.dl-info b { color: var(--ink2); }
.zoom-btn {
    background: var(--surface2); border: 1px solid var(--border); color: var(--ink2);
    font-family: var(--mono); font-size: 12px; width: 26px; height: 26px;
    border-radius: 5px; cursor: pointer; display: flex; align-items: center;
    justify-content: center; transition: all 0.15s;
}
.zoom-btn:hover { border-color: var(--border2); color: var(--ink); }
.zoom-val { font-family: var(--mono); font-size: 10px; color: var(--ink3); min-width: 34px; text-align: center; }
</style>
</head>
<body>

<!-- ── Top bar ── -->
<div class="topbar">
    <div class="logo">Resume<em>Forge</em></div>
    <div class="project-chip">
        <div class="dot"></div>
        <span><?= $projectName ?></span>
    </div>
    <div class="topbar-right">
        <button class="tbtn" onclick="window.location.href='index.php'">&#8592; Projects</button>
        <button class="tbtn-accept" id="accept-btn" onclick="acceptResume()">&#10003; Accept</button>
        <button class="tbtn-green" onclick="downloadResume()">
            <svg width="11" height="11" viewBox="0 0 12 12" fill="none"><path d="M6 1v7M3 5.5l3 2.5 3-2.5M1.5 10.5h9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Download PDF
        </button>
    </div>
</div>

<!-- ── Main two-column layout ── -->
<div class="main">

    <!-- LEFT: Form -->
    <div class="left-col">
        <div class="left-inner">

            <!-- Job context -->
            <div class="sec-label">Job context</div>

            <div class="field-box">
                <div class="field-header">
                    <span class="field-key">job_description</span>
                    <span class="field-tag">required</span>
                </div>
                <div class="field-body">
                    <textarea id="inp-jd" rows="5" placeholder="Paste the full job description here — the AI will tailor every word of your resume to match exactly what the company wants..."></textarea>
                </div>
            </div>

            <div class="field-box">
                <div class="field-header">
                    <span class="field-key">skills_i_have_from_jd</span>
                    <span class="field-tag">required</span>
                </div>
                <div class="field-body">
                    <textarea id="inp-skills" rows="3" placeholder="List only the skills you genuinely have from the JD — e.g. Python, React, REST APIs, Docker, system design..."></textarea>
                </div>
            </div>

            <!-- Philosophy card -->
            <div class="philosophy">
                <div class="philo-title">Our resume-building philosophy</div>
                <div class="philo-sub">Two lenses that make your resume unforgettable</div>
                <div class="philo-cols">
                    <div class="philo-card">
                        <div class="philo-num">01</div>
                        <div class="philo-card-title">What you are now</div>
                        <div class="philo-card-desc">Your current reality — skills, education, experience, who you genuinely are.</div>
                    </div>
                    <div class="philo-card">
                        <div class="philo-num">02</div>
                        <div class="philo-card-title">What you want to be</div>
                        <div class="philo-card-desc">Your ambition and vision — where you are going and why this company matters.</div>
                    </div>
                </div>
            </div>

            <!-- PHASE 01: What you are now -->
            <div class="phase-hdr">
                <div class="phase-num">01</div>
                <div>
                    <div class="phase-title">What you are now</div>
                    <div class="phase-desc">Your current reality — fill in as much detail as you can</div>
                </div>
            </div>

            <!-- Personal details -->
            <div class="sec-block" id="sb-personal">
                <div class="sec-block-hdr" onclick="toggleSec('sb-personal')">
                    <div class="sb-icon">&#128100;</div>
                    <div><div class="sb-title">Personal details</div><div class="sb-sub">name &middot; contact &middot; tagline</div></div>
                    <span class="sb-chev">&#9662;</span>
                </div>
                <div class="sb-body">
                    <div class="mini-field"><span class="mini-k">full_name</span><input class="mini-v" id="mf-full_name" type="text" placeholder="e.g. Arjun Sharma"></div>
                    <div class="mini-field"><span class="mini-k">email</span><input class="mini-v" id="mf-email" type="email" placeholder="e.g. arjun@gmail.com"></div>
                    <div class="mini-field"><span class="mini-k">phone</span><input class="mini-v" id="mf-phone" type="text" placeholder="e.g. +91 98765 43210"></div>
                    <div class="mini-field"><span class="mini-k">tagword</span><input class="mini-v" id="mf-tagword" type="text" placeholder="e.g. Full-stack dev obsessed with AI"></div>
                    <div class="mini-field"><span class="mini-k">linkedin</span><input class="mini-v" id="mf-linkedin" type="text" placeholder="linkedin.com/in/your-profile"></div>
                    <div class="mini-field"><span class="mini-k">github</span><input class="mini-v" id="mf-github" type="text" placeholder="github.com/your-username"></div>
                    <div id="personal-extras"></div>
                    <button class="btn-add" onclick="addCF('personal-extras')">+ add personal field</button>
                </div>
            </div>

            <!-- Education -->
            <div class="sec-block" id="sb-edu">
                <div class="sec-block-hdr" onclick="toggleSec('sb-edu')">
                    <div class="sb-icon">&#127891;</div>
                    <div><div class="sb-title">Educational qualification</div><div class="sb-sub">10th &middot; 12th &middot; graduation</div></div>
                    <span class="sb-chev">&#9662;</span>
                </div>
                <div class="sb-body">
                    <div class="mini-field"><span class="mini-k">10th_marks</span><input class="mini-v" id="mf-10th_marks" type="text" placeholder="e.g. 92% — CBSE 2019"></div>
                    <div class="mini-field"><span class="mini-k">12th_marks</span><input class="mini-v" id="mf-12th_marks" type="text" placeholder="e.g. 88% — CBSE 2021"></div>
                    <div class="mini-field"><span class="mini-k">graduation_cgpa</span><input class="mini-v" id="mf-graduation_cgpa" type="text" placeholder="e.g. 8.7/10 — B.Tech CS, VIT 2025"></div>
                    <div id="edu-extras"></div>
                    <button class="btn-add" onclick="addCF('edu-extras')">+ add certification or course</button>
                </div>
            </div>

            <!-- Technical skills -->
            <div class="sec-block" id="sb-tech">
                <div class="sec-block-hdr" onclick="toggleSec('sb-tech')">
                    <div class="sb-icon">&#9881;</div>
                    <div><div class="sb-title">Technical skills</div><div class="sb-sub">add skill + rate it out of 10</div></div>
                    <span class="sb-chev">&#9662;</span>
                </div>
                <div class="sb-body">
                    <div id="tech-list"></div>
                    <button class="btn-add" onclick="addSkill()">+ add technical skill</button>
                </div>
            </div>

            <!-- Soft skills -->
            <div class="sec-block" id="sb-soft">
                <div class="sec-block-hdr" onclick="toggleSec('sb-soft')">
                    <div class="sb-icon">&#127793;</div>
                    <div><div class="sb-title">Personal / soft skills</div><div class="sb-sub">leadership &middot; communication &middot; adaptability</div></div>
                    <span class="sb-chev">&#9662;</span>
                </div>
                <div class="sb-body">
                    <div id="soft-extras"></div>
                    <button class="btn-add" onclick="addCF('soft-extras')">+ add soft skill</button>
                </div>
            </div>

            <!-- Experience -->
            <div class="sec-block" id="sb-exp">
                <div class="sec-block-hdr" onclick="toggleSec('sb-exp')">
                    <div class="sb-icon">&#128188;</div>
                    <div><div class="sb-title">Work experience &amp; projects</div><div class="sb-sub">internships &middot; jobs &middot; personal projects</div></div>
                    <span class="sb-chev">&#9662;</span>
                </div>
                <div class="sb-body">
                    <div id="exp-extras"></div>
                    <button class="btn-add" onclick="addCF('exp-extras')">+ add experience or project</button>
                </div>
            </div>

            <!-- PHASE 02: What you want to be -->
            <div class="phase-hdr" style="margin-top:28px">
                <div class="phase-num">02</div>
                <div>
                    <div class="phase-title">What you want to be</div>
                    <div class="phase-desc">Your ambition — this shapes how the AI narrates your story</div>
                </div>
            </div>

            <div class="sec-block" id="sb-future">
                <div class="sec-block-hdr" onclick="toggleSec('sb-future')">
                    <div class="sb-icon">&#128640;</div>
                    <div><div class="sb-title">Vision &amp; motivation</div><div class="sb-sub">passion &middot; goal &middot; reason to join</div></div>
                    <span class="sb-chev">&#9662;</span>
                </div>
                <div class="sb-body">
                    <div class="mini-field"><span class="mini-k">passion</span><input class="mini-v" id="mf-passion" type="text" placeholder="e.g. building AI tools that democratise knowledge"></div>
                    <div class="mini-field"><span class="mini-k">vision</span><input class="mini-v" id="mf-vision" type="text" placeholder="e.g. become a founding engineer at an AI-first startup"></div>
                    <div class="mini-field"><span class="mini-k">reason_to_join</span><input class="mini-v" id="mf-reason_to_join" type="text" placeholder="e.g. Google's scale lets me impact billions"></div>
                    <div class="mini-field"><span class="mini-k">five_year_goal</span><input class="mini-v" id="mf-five_year_goal" type="text" placeholder="e.g. lead a team building real-time ML systems"></div>
                    <div id="future-extras"></div>
                    <button class="btn-add" onclick="addCF('future-extras')">+ add ambition field</button>
                </div>
            </div>

            <!-- Generate area -->
            <div class="submit-area">
                <div class="submit-title">Generate my resume</div>
                <div class="submit-hint">Already generated once? Write a revision request below, or edit fields above and hit generate again — the AI remembers your past sessions.</div>
                <textarea id="revision-box" class="revision-box" rows="3" placeholder="Revision request (optional) — e.g. make the summary punchier, move education to top, add a project called X..."></textarea>
                <button class="gen-btn" id="gen-btn" onclick="submitResume()">
                    <div class="spinner"></div>
                    <span class="btn-label">Generate resume</span>
                </button>
            </div>

            <div style="height:40px"></div>
        </div>
    </div>

    <!-- RIGHT: Preview -->
    <div class="right-col">
        <div class="right-toolbar">
            <div class="rt-title">Resume preview</div>
            <span class="rt-hint">— live render</span>
            <div id="status-badge" class="status-badge">
                <div class="sdot"></div> waiting for input
            </div>
        </div>

        <div class="preview-wrap" id="preview-wrap">
            <div class="empty-state" id="empty-state">
                <div class="empty-glyph">&#10022;</div>
                <div class="empty-title">Your resume will appear here</div>
                <div class="empty-desc">Fill in your details on the left and hit "Generate resume" — the AI will craft a tailored resume in seconds.</div>
                <div class="empty-steps">
                    <div class="estep"><div class="estep-num">1</div> Paste the job description</div>
                    <div class="estep"><div class="estep-num">2</div> Fill your current profile (01)</div>
                    <div class="estep"><div class="estep-num">3</div> Add your vision (02)</div>
                    <div class="estep"><div class="estep-num">4</div> Hit generate &#8594;</div>
                </div>
            </div>
            <iframe id="resume-frame"></iframe>
        </div>

        <div class="dl-bar">
            <div class="dl-info"><b>PDF export</b> — click Download PDF above after generating</div>
            <button class="zoom-btn" onclick="changeZoom(-0.1)">&#8722;</button>
            <span class="zoom-val" id="zoom-val">100%</span>
            <button class="zoom-btn" onclick="changeZoom(0.1)">+</button>
        </div>
    </div>

</div>

<script>
// ══════════════════════════════════════════════
// PROJECT ID from PHP
// ══════════════════════════════════════════════
var PID = <?= $projectId ?>;
var lastGeneratedHtml = '';

// ══════════════════════════════════════════════
// UI HELPERS
// ══════════════════════════════════════════════

// Toggle collapsible sections
function toggleSec(id) {
    document.getElementById(id).classList.toggle('closed');
}

// Escape HTML to prevent XSS when inserting user data
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ══════════════════════════════════════════════
// DYNAMIC FIELD ADDERS
// ══════════════════════════════════════════════

var skillCounter = 0;

// Add a technical skill row with rating dots
function addSkill(name, rating) {
    name = name || '';
    rating = rating || 0;
    var list = document.getElementById('tech-list');
    var id = 'sk-' + skillCounter++;
    var el = document.createElement('div');
    el.className = 'skill-row';
    el.id = id;

    // Build 10 rating dots
    var dots = '';
    for (var i = 0; i < 10; i++) {
        dots += '<div class="rdot' + (i < rating ? ' on' : '') + '" onclick="setRating(\'' + id + '\',' + (i + 1) + ')"></div>';
    }

    el.innerHTML =
        '<input class="skill-input" type="text" placeholder="e.g. Python" value="' + escapeHtml(name) + '" data-role="name">' +
        '<div class="rating-bar" data-val="' + rating + '">' + dots + '</div>' +
        '<button class="btn-rm" onclick="document.getElementById(\'' + id + '\').remove()">&times;</button>';

    list.appendChild(el);
}

// Set rating dots for a skill
function setRating(id, value) {
    var el = document.getElementById(id);
    var bar = el.querySelector('.rating-bar');
    bar.dataset.val = value;
    var dots = bar.querySelectorAll('.rdot');
    for (var i = 0; i < dots.length; i++) {
        dots[i].classList.toggle('on', i < value);
    }
}

var cfCounter = 0;

// Add a custom key:value field row
function addCF(containerId, key, val) {
    key = key || '';
    val = val || '';
    var container = document.getElementById(containerId);
    var id = 'cf-' + cfCounter++;
    var el = document.createElement('div');
    el.className = 'cf-row';
    el.id = id;

    el.innerHTML =
        '<input class="cf-input key-field" type="text" placeholder="field name" value="' + escapeHtml(key) + '" data-role="key">' +
        '<input class="cf-input" type="text" placeholder="value" value="' + escapeHtml(val) + '" data-role="val">' +
        '<button class="btn-rm" onclick="document.getElementById(\'' + id + '\').remove()">&times;</button>';

    container.appendChild(el);
}

// ══════════════════════════════════════════════
// COLLECT: Read all form fields → build nested JSON
// This is the EXACT structure that gets sent to Python.
// ══════════════════════════════════════════════

function collect() {
    var data = {
        job_description: document.getElementById('inp-jd').value.trim(),
        skills_i_have_from_jd: document.getElementById('inp-skills').value.trim(),
        what_are_u_now: {
            personal_details: {},
            education: {},
            technical_skills: [],
            soft_skills: [],
            experience_projects: []
        },
        what_u_want_to_be: {}
    };

    // ── Fixed personal fields ──
    var personalKeys = ['full_name', 'email', 'phone', 'tagword', 'linkedin', 'github'];
    for (var i = 0; i < personalKeys.length; i++) {
        var k = personalKeys[i];
        var el = document.getElementById('mf-' + k);
        if (el && el.value.trim()) {
            data.what_are_u_now.personal_details[k] = el.value.trim();
        }
    }

    // ── Custom personal fields ──
    var personalExtras = [];
    document.querySelectorAll('#personal-extras .cf-row').forEach(function(row) {
        var ck = row.querySelector('[data-role="key"]').value.trim();
        var cv = row.querySelector('[data-role="val"]').value.trim();
        if (ck && cv) personalExtras.push({ key: ck, value: cv });
    });
    data.what_are_u_now.personal_details.custom_fields = personalExtras;

    // ── Fixed education fields ──
    var eduKeys = ['10th_marks', '12th_marks', 'graduation_cgpa'];
    for (var i = 0; i < eduKeys.length; i++) {
        var k = eduKeys[i];
        var el = document.getElementById('mf-' + k);
        if (el && el.value.trim()) {
            data.what_are_u_now.education[k] = el.value.trim();
        }
    }

    // ── Custom education fields ──
    var eduExtras = [];
    document.querySelectorAll('#edu-extras .cf-row').forEach(function(row) {
        var ck = row.querySelector('[data-role="key"]').value.trim();
        var cv = row.querySelector('[data-role="val"]').value.trim();
        if (ck && cv) eduExtras.push({ key: ck, value: cv });
    });
    data.what_are_u_now.education.custom_fields = eduExtras;

    // ── Technical skills (with ratings) ──
    document.querySelectorAll('#tech-list .skill-row').forEach(function(row) {
        var name = row.querySelector('[data-role="name"]').value.trim();
        var rating = parseInt(row.querySelector('.rating-bar').dataset.val || '0');
        if (name) data.what_are_u_now.technical_skills.push({ name: name, rating: rating });
    });

    // ── Soft skills ──
    var softSkills = [];
    document.querySelectorAll('#soft-extras .cf-row').forEach(function(row) {
        var ck = row.querySelector('[data-role="key"]').value.trim();
        var cv = row.querySelector('[data-role="val"]').value.trim();
        if (ck && cv) softSkills.push({ key: ck, value: cv });
    });
    data.what_are_u_now.soft_skills = softSkills;

    // ── Experience / projects ──
    var experiences = [];
    document.querySelectorAll('#exp-extras .cf-row').forEach(function(row) {
        var ck = row.querySelector('[data-role="key"]').value.trim();
        var cv = row.querySelector('[data-role="val"]').value.trim();
        if (ck && cv) experiences.push({ key: ck, value: cv });
    });
    data.what_are_u_now.experience_projects = experiences;

    // ── Fixed future fields ──
    var futureKeys = ['passion', 'vision', 'reason_to_join', 'five_year_goal'];
    for (var i = 0; i < futureKeys.length; i++) {
        var k = futureKeys[i];
        var el = document.getElementById('mf-' + k);
        if (el && el.value.trim()) {
            data.what_u_want_to_be[k] = el.value.trim();
        }
    }

    // ── Custom future fields ──
    var futureExtras = [];
    document.querySelectorAll('#future-extras .cf-row').forEach(function(row) {
        var ck = row.querySelector('[data-role="key"]').value.trim();
        var cv = row.querySelector('[data-role="val"]').value.trim();
        if (ck && cv) futureExtras.push({ key: ck, value: cv });
    });
    data.what_u_want_to_be.custom_fields = futureExtras;

    return data;
}

// ══════════════════════════════════════════════
// POPULATE: Restore form from saved nested JSON
// Called when loading an existing project.
// ══════════════════════════════════════════════

function populateForm(data) {
    if (!data) return;

    // Top-level fields
    document.getElementById('inp-jd').value = data.job_description || '';
    document.getElementById('inp-skills').value = data.skills_i_have_from_jd || '';

    var now = data.what_are_u_now || {};
    var pd = now.personal_details || {};

    // Fixed personal fields
    var personalKeys = ['full_name', 'email', 'phone', 'tagword', 'linkedin', 'github'];
    for (var i = 0; i < personalKeys.length; i++) {
        var k = personalKeys[i];
        var el = document.getElementById('mf-' + k);
        if (el && pd[k]) el.value = pd[k];
    }
    // Custom personal fields
    if (pd.custom_fields) {
        pd.custom_fields.forEach(function(f) { addCF('personal-extras', f.key, f.value); });
    }

    // Education
    var edu = now.education || {};
    var eduKeys = ['10th_marks', '12th_marks', 'graduation_cgpa'];
    for (var i = 0; i < eduKeys.length; i++) {
        var k = eduKeys[i];
        var el = document.getElementById('mf-' + k);
        if (el && edu[k]) el.value = edu[k];
    }
    if (edu.custom_fields) {
        edu.custom_fields.forEach(function(f) { addCF('edu-extras', f.key, f.value); });
    }

    // Technical skills
    if (now.technical_skills) {
        now.technical_skills.forEach(function(s) { addSkill(s.name, s.rating); });
    }

    // Soft skills
    if (now.soft_skills) {
        now.soft_skills.forEach(function(s) { addCF('soft-extras', s.key, s.value); });
    }

    // Experience
    if (now.experience_projects) {
        now.experience_projects.forEach(function(e) { addCF('exp-extras', e.key, e.value); });
    }

    // Future
    var fut = data.what_u_want_to_be || {};
    var futureKeys = ['passion', 'vision', 'reason_to_join', 'five_year_goal'];
    for (var i = 0; i < futureKeys.length; i++) {
        var k = futureKeys[i];
        var el = document.getElementById('mf-' + k);
        if (el && fut[k]) el.value = fut[k];
    }
    if (fut.custom_fields) {
        fut.custom_fields.forEach(function(f) { addCF('future-extras', f.key, f.value); });
    }
}

// ══════════════════════════════════════════════
// SUBMIT: Send nested JSON to PHP → Python → get HTML back
// ══════════════════════════════════════════════

async function submitResume() {
    var btn = document.getElementById('gen-btn');
    var badge = document.getElementById('status-badge');
    var frame = document.getElementById('resume-frame');
    var empty = document.getElementById('empty-state');

    // Collect all form data
    var data = collect();
    var revision = document.getElementById('revision-box').value.trim();

    // Basic validation
    if (!data.job_description) {
        badge.className = 'status-badge';
        badge.innerHTML = '<div class="sdot" style="background:var(--red)"></div> need job description';
        return;
    }
    if (!data.what_are_u_now.personal_details.full_name) {
        badge.className = 'status-badge';
        badge.innerHTML = '<div class="sdot" style="background:var(--red)"></div> need your name';
        return;
    }

    // Show loading state
    btn.classList.add('loading');
    badge.className = 'status-badge working';
    badge.innerHTML = '<div class="sdot"></div> generating...';

    try {
        // Send to PHP, which forwards to Python
        var res = await fetch('api/generate_resume.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: PID,
                resume_data: data,
                revision: revision
            })
        });

        var result = await res.json();

        if (result.success) {
            // Store the HTML for later save/download
            lastGeneratedHtml = result.html;

            // Show in iframe
            frame.srcdoc = result.html;
            frame.style.display = 'block';
            empty.style.display = 'none';

            // Update status
            badge.className = 'status-badge ready';
            badge.innerHTML = '<div class="sdot"></div> ready';

            // Show accept button
            document.getElementById('accept-btn').classList.add('visible');
        } else {
            badge.className = 'status-badge';
            badge.innerHTML = '<div class="sdot" style="background:var(--red)"></div> error';
            alert('Generation failed: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        badge.className = 'status-badge';
        badge.innerHTML = '<div class="sdot" style="background:var(--red)"></div> network error';
        alert('Could not reach the server. Make sure:\n1. PHP server is running\n2. Python FastAPI is running on port 8000');
    }

    btn.classList.remove('loading');
}

// ══════════════════════════════════════════════
// ACCEPT: Save resume_json + resume_html to MySQL
// ══════════════════════════════════════════════

async function acceptResume() {
    if (!lastGeneratedHtml) {
        alert('Generate a resume first');
        return;
    }

    var data = collect();

    try {
        var res = await fetch('api/save_resume.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                project_id: PID,
                resume_json: data,        // <-- FIXED: was "resume_data" in original
                resume_html: lastGeneratedHtml,
                is_accepted: true
            })
        });

        var result = await res.json();
        if (result.success) {
            document.getElementById('accept-btn').style.background = '#d4edda';
            document.getElementById('accept-btn').style.color = '#155724';
        } else {
            alert('Save failed: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Network error while saving');
    }
}

// ══════════════════════════════════════════════
// AUTO-SAVE on window close (best effort)
// Uses sendBeacon so it fires even during page unload.
// ══════════════════════════════════════════════

window.addEventListener('beforeunload', function(e) {
    var data = collect();
    var html = lastGeneratedHtml || '';

    // Don't save if form is basically empty
    if (!data.job_description && !data.what_are_u_now.personal_details.full_name) return;

    var payload = JSON.stringify({
        project_id: PID,
        resume_json: data,        // <-- FIXED: was "resume_data" in original
        resume_html: html,
        is_accepted: false
    });

    var blob = new Blob([payload], { type: 'application/json' });
    navigator.sendBeacon('api/save_resume.php', blob);
});

// ══════════════════════════════════════════════
// LOAD: Fetch saved data when page opens
// If project was previously saved, populate the form + show preview.
// ══════════════════════════════════════════════

async function loadProject() {
    try {
        var res = await fetch('api/load_project.php?project_id=' + PID);
        var data = await res.json();

        if (!data.success) return;

        // Restore form fields from saved JSON
        if (data.resume_json) {
            populateForm(data.resume_json);
        }

        // Restore preview if HTML was saved
        if (data.resume_html) {
            lastGeneratedHtml = data.resume_html;
            document.getElementById('resume-frame').srcdoc = data.resume_html;
            document.getElementById('resume-frame').style.display = 'block';
            document.getElementById('empty-state').style.display = 'none';
            document.getElementById('status-badge').className = 'status-badge ready';
            document.getElementById('status-badge').innerHTML = '<div class="sdot"></div> loaded';
            document.getElementById('accept-btn').classList.add('visible');
        }
    } catch (err) {
        // Silent fail — user just sees empty form
        console.error('Failed to load project:', err);
    }
}

// ══════════════════════════════════════════════
// ZOOM controls for the preview iframe
// ══════════════════════════════════════════════

var zoomLevel = 1;

function changeZoom(delta) {
    zoomLevel = Math.max(0.3, Math.min(1.6, zoomLevel + delta));
    applyZoom();
}

function applyZoom() {
    var frame = document.getElementById('resume-frame');
    frame.style.transform = 'scale(' + zoomLevel + ')';
    frame.style.transformOrigin = 'top left';
    frame.style.width = (100 / zoomLevel) + '%';
    frame.style.height = (100 / zoomLevel) + '%';
    document.getElementById('zoom-val').textContent = Math.round(zoomLevel * 100) + '%';
}

// ══════════════════════════════════════════════
// DOWNLOAD: Open resume in new tab and trigger print (PDF)
// ══════════════════════════════════════════════

function downloadResume() {
    var frame = document.getElementById('resume-frame');
    if (!frame.srcdoc) {
        alert('Generate a resume first!');
        return;
    }
    var w = window.open('', '_blank');
    w.document.write(frame.srcdoc);
    w.document.close();
    w.focus();
    setTimeout(function() { w.print(); }, 400);
}

// ══════════════════════════════════════════════
// INIT: Run when page loads
// ══════════════════════════════════════════════

loadProject();
</script>
</body>
</html>