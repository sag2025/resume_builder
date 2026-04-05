<?php
// index.php
// The home page — shows all projects and lets you create new ones.
// This is a regular PHP page that outputs HTML.

session_start();
require_once __DIR__ . '/services/ProjectService.php';

// Ensure user exists (creates anonymous user from session if needed)
 $_SESSION['user_id'] = (new ProjectService())->ensureUser(session_id());
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ResumeForge</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg: #f4f1eb;
    --surface: #ffffff;
    --surface2: #f9f7f3;
    --border: #ddd9d0;
    --border2: #c8c3b8;
    --accent: #1a6b3c;
    --accent-lt: #e8f5ee;
    --ink: #1c1a16;
    --ink2: #4a4740;
    --ink3: #8a8680;
    --red: #c0392b;
    --radius: 10px;
    --radius-sm: 7px;
    --mono: 'JetBrains Mono', monospace;
    --display: 'Syne', sans-serif;
}

html, body { height: 100%; background: var(--bg); color: var(--ink); font-family: var(--mono); font-size: 13px; }

/* ── Top bar ── */
.topbar {
    height: 54px;
    background: var(--ink);
    display: flex;
    align-items: center;
    padding: 0 20px;
    gap: 14px;
}
.logo { font-family: var(--display); font-weight: 800; font-size: 17px; color: #fff; letter-spacing: -0.3px; }
.logo em { color: #7ef5a8; font-style: normal; }

/* ── Main container ── */
.container { max-width: 820px; margin: 0 auto; padding: 48px 24px 80px; }

.hero { text-align: center; margin-bottom: 44px; }
.hero h1 { font-family: var(--display); font-size: 34px; font-weight: 800; color: var(--ink); margin-bottom: 8px; letter-spacing: -0.5px; }
.hero p { font-size: 13px; color: var(--ink3); line-height: 1.6; max-width: 460px; margin: 0 auto; }

/* ── Action cards ── */
.actions { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 36px; }
.action-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 26px 22px;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
}
.action-card:hover { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,107,60,0.08); transform: translateY(-2px); }
.action-card .ic {
    width: 46px; height: 46px; border-radius: 12px;
    margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.action-card:first-child .ic { background: var(--accent-lt); color: var(--accent); }
.action-card:last-child .ic { background: #fff8f0; color: #b7600a; border: 1px solid #ffd9a0; }
.action-card h3 { font-family: var(--display); font-size: 14px; font-weight: 700; color: var(--ink); margin-bottom: 3px; }
.action-card p { font-size: 11px; color: var(--ink3); }

/* ── Section heading ── */
.sec-heading {
    font-family: var(--mono); font-size: 9px; font-weight: 500;
    letter-spacing: 2.5px; text-transform: uppercase; color: var(--ink3);
    margin-bottom: 14px; display: flex; align-items: center; gap: 8px;
}
.sec-heading::after { content: ''; flex: 1; height: 1px; background: var(--border); }

/* ── Project list ── */
.project-list { display: flex; flex-direction: column; gap: 7px; }
.project-item {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;
    transition: border-color 0.15s;
}
.project-item:hover { border-color: var(--border2); }
.project-item h4 { font-family: var(--display); font-size: 13px; font-weight: 700; color: var(--ink); }
.project-item .meta { font-size: 10px; color: var(--ink3); margin-top: 2px; }
.project-item .meta .accepted { color: var(--accent); font-weight: 500; }
.project-actions { display: flex; gap: 5px; }

/* ── Buttons ── */
.btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: var(--radius-sm);
    font-family: var(--mono); font-size: 11px; font-weight: 500;
    border: none; cursor: pointer; transition: all 0.15s;
}
.btn-sm { padding: 5px 10px; font-size: 10px; }
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: #145530; }
.btn-ghost { background: transparent; color: var(--ink3); border: 1px solid var(--border); }
.btn-ghost:hover { color: var(--ink2); border-color: var(--border2); }
.btn-danger { background: transparent; color: var(--red); border: 1px solid rgba(192,57,43,0.2); }
.btn-danger:hover { background: rgba(192,57,43,0.06); }

/* ── Empty state ── */
.empty-state { text-align: center; padding: 44px 20px; color: var(--ink3); }
.empty-state .glyph { font-size: 34px; margin-bottom: 10px; opacity: 0.25; }
.empty-state p { font-size: 12px; }

/* ── Modal ── */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(4px);
    z-index: 500; display: none; align-items: center; justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    padding: 26px; width: 380px; animation: modalIn 0.25s ease-out;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(8px); }
    to { opacity: 1; transform: none; }
}
.modal-box h3 { font-family: var(--display); font-size: 15px; font-weight: 700; margin-bottom: 14px; color: var(--ink); }
.modal-box input {
    width: 100%; background: var(--surface2); border: 1px solid var(--border);
    border-radius: var(--radius-sm); padding: 10px 12px;
    font-family: var(--mono); font-size: 13px; color: var(--ink);
    outline: none; transition: border-color 0.15s;
}
.modal-box input:focus { border-color: var(--accent); }
.modal-actions { display: flex; gap: 7px; justify-content: flex-end; margin-top: 16px; }

/* ── Toast notifications ── */
.toast-container { position: fixed; top: 66px; right: 20px; z-index: 2000; display: flex; flex-direction: column; gap: 5px; }
.toast {
    padding: 9px 15px; border-radius: var(--radius-sm); font-size: 11.5px; font-weight: 500;
    display: flex; align-items: center; gap: 7px;
    animation: toastIn 0.3s ease-out; box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}
.toast.success { background: var(--accent-lt); color: var(--accent); border: 1px solid rgba(26,107,60,0.2); }
.toast.error { background: #fdecea; color: var(--red); border: 1px solid rgba(192,57,43,0.2); }
@keyframes toastIn {
    from { opacity: 0; transform: translateX(25px); }
    to { opacity: 1; transform: none; }
}

/* ── Delete confirmation ── */
.confirm-row { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--red); font-weight: 600; }
</style>
</head>
<body>

<header class="topbar">
    <div class="logo">Resume<em>Forge</em></div>
</header>

<div class="container">
    <div class="hero">
        <h1>Build Resumes That Get Interviews</h1>
        <p>AI-powered, tailored to each job description. Your past sessions make future resumes better.</p>
    </div>

    <div class="actions">
        <div class="action-card" onclick="openModal()">
            <div class="ic">+</div>
            <h3>Create New Resume</h3>
            <p>Start fresh for a new application</p>
        </div>
        <div class="action-card" onclick="document.getElementById('proj-sec').scrollIntoView({behavior:'smooth'})">
            <div class="ic">&#128194;</div>
            <h3>Continue Existing</h3>
            <p>Pick up where you left off</p>
        </div>
    </div>

    <div id="proj-sec">
        <div class="sec-heading">Your Projects</div>
        <div id="project-list" class="project-list">
            <div class="empty-state" id="empty-proj">
                <div class="glyph">&#128194;</div>
                <p>No projects yet. Create one to get started.</p>
            </div>
        </div>
    </div>
</div>

<!-- New project modal -->
<div class="modal-overlay" id="modal">
    <div class="modal-box">
        <h3>New Resume Project</h3>
        <input type="text" id="new-name" placeholder="e.g. Google SWE 2025" maxlength="200">
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="createProject()">Create</button>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toasts"></div>

<script>
// ── Toast notification ──
function toast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toasts');
    var el = document.createElement('div');
    el.className = 'toast ' + type;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(function() {
        el.style.opacity = '0';
        el.style.transform = 'translateX(25px)';
        el.style.transition = '0.3s';
        setTimeout(function() { el.remove(); }, 300);
    }, 3000);
}

// ── Modal controls ──
function openModal() {
    document.getElementById('modal').classList.add('active');
    var input = document.getElementById('new-name');
    input.value = '';
    setTimeout(function() { input.focus(); }, 100);
}

function closeModal() {
    document.getElementById('modal').classList.remove('active');
}

// Close modal when clicking backdrop
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Enter to create, Escape to close
document.getElementById('new-name').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') createProject();
    if (e.key === 'Escape') closeModal();
});

// ── Create project ──
async function createProject() {
    var name = document.getElementById('new-name').value.trim();
    if (!name) { toast('Enter a project name', 'error'); return; }

    try {
        var res = await fetch('api/create_project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_name: name })
        });
        var data = await res.json();

        if (data.success) {
            // Redirect to editor with the new project ID
            window.location.href = 'editor.php?project_id=' + data.project_id;
        } else {
            toast(data.error || 'Failed to create project', 'error');
        }
    } catch (err) {
        toast('Network error — check that PHP server is running', 'error');
    }
}

// ── Load projects list ──
async function loadProjects() {
    try {
        var res = await fetch('api/list_projects.php');
        var data = await res.json();
        renderProjects(data.projects || []);
    } catch (err) {
        toast('Failed to load projects', 'error');
    }
}

// ── Render project list ──
function renderProjects(projects) {
    var list = document.getElementById('project-list');
    var empty = document.getElementById('empty-proj');

    if (!projects.length) {
        list.innerHTML = '';
        list.appendChild(empty);
        empty.style.display = 'block';
        return;
    }

    empty.style.display = 'none';
    list.innerHTML = projects.map(function(p) {
        var statusHtml = p.is_accepted ? ' &middot; <span class="accepted">accepted</span>' : '';
        return '<div class="project-item" id="pi-' + p.id + '">' +
            '<div>' +
                '<h4>' + escapeHtml(p.project_name) + '</h4>' +
                '<div class="meta">' + formatDate(p.updated_at) + statusHtml + '</div>' +
            '</div>' +
            '<div class="project-actions" id="pa-' + p.id + '">' +
                '<button class="btn btn-sm btn-primary" onclick="window.location.href=\'editor.php?project_id=' + p.id + '\'">Open</button>' +
                '<button class="btn btn-sm btn-danger" onclick="confirmDelete(' + p.id + ')">&times;</button>' +
            '</div>' +
        '</div>';
    }).join('');
}

// ── Delete project (two-step confirmation) ──
function confirmDelete(id) {
    var el = document.getElementById('pa-' + id);
    el.innerHTML = '<div class="confirm-row">Delete? ' +
        '<button class="btn btn-sm btn-danger" onclick="deleteProject(' + id + ')">Yes</button>' +
        '<button class="btn btn-sm btn-ghost" onclick="loadProjects()">No</button>' +
    '</div>';
}

async function deleteProject(id) {
    try {
        var res = await fetch('api/delete_project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: id })
        });
        var data = await res.json();

        if (data.success) {
            toast('Project deleted');
            loadProjects();
        } else {
            toast(data.error || 'Delete failed', 'error');
        }
    } catch (err) {
        toast('Network error', 'error');
    }
}

// ── Helpers ──
function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatDate(dateStr) {
    var d = new Date(dateStr);
    var now = Date.now();
    var diff = now - d.getTime();
    if (diff < 60000) return 'just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return d.toLocaleDateString('en-US', {
        month: 'short', day: 'numeric',
        year: d.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
    });
}

// ── On page load ──
loadProjects();
</script>
</body>
</html>