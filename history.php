<?php
$pageTitle = 'Learning History';
$currentPage = 'history';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header with Actions -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Learning & Activity History</h4>
            <p class="text-muted small mb-0">Review your past study actions, generated notes, quizzes, and AI tutor interactions.</p>
        </div>

        <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" id="clearAllHistoryBtn">
            <i class="bi bi-trash"></i>
            <span>Clear My History</span>
        </button>
    </div>

    <!-- Filters Bar -->
    <div class="card p-3 shadow-sm mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="historySearchInput" placeholder="Search activity description...">
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <select class="form-select form-select-sm" id="historyTypeFilter">
                    <option value="all">All Activity Types</option>
                    <option value="chat">AI Chat & Tutor</option>
                    <option value="notes">Notes Generated</option>
                    <option value="quiz">Quizzes & Tests</option>
                    <option value="task">Study Tasks</option>
                    <option value="pdf">PDF Summaries</option>
                    <option value="login">Account Logins</option>
                </select>
            </div>

            <div class="col-sm-6 col-md-3">
                <select class="form-select form-select-sm" id="historySortOrder">
                    <option value="desc" selected>Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
            </div>
        </div>
    </div>

    <!-- History Table Card -->
    <div class="card shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div id="historyTableContainer">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Loading your learning logs...
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadHistory();

    const searchInput = document.getElementById('historySearchInput');
    const typeFilter = document.getElementById('historyTypeFilter');
    const sortOrder = document.getElementById('historySortOrder');
    const clearBtn = document.getElementById('clearAllHistoryBtn');

    if (typeFilter) typeFilter.addEventListener('change', () => loadHistory());
    if (sortOrder) sortOrder.addEventListener('change', () => loadHistory());

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadHistory(), 300);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', async () => {
            if (!confirm('Are you sure you want to delete your entire learning activity history? This cannot be undone.')) return;

            try {
                const res = await fetch('api/history.php?action=clear_all', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const d = await res.json();
                if (d.success) {
                    showToast('History cleared.', 'info');
                    loadHistory();
                } else {
                    showToast(d.error || 'Failed to clear', 'error');
                }
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            }
        });
    }
});

async function loadHistory() {
    const container = document.getElementById('historyTableContainer');
    const type = document.getElementById('historyTypeFilter')?.value || 'all';
    const sort = document.getElementById('historySortOrder')?.value || 'desc';
    const q = document.getElementById('historySearchInput')?.value.trim() || '';

    const url = `api/history.php?action=list&type=\${encodeURIComponent(type)}&sort=\${sort}&q=\${encodeURIComponent(q)}`;

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            renderHistoryTable(data.logs);
        } else {
            container.innerHTML = `<div class=\"p-4 text-center text-danger small\">\${escapeHtml(data.error || 'Failed to load logs')}</div>`;
        }
    } catch (e) {
        container.innerHTML = `<div class=\"p-4 text-center text-danger small\">Network error: \${escapeHtml(e.message)}</div>`;
    }
}

function renderHistoryTable(logs) {
    const container = document.getElementById('historyTableContainer');
    if (!logs || logs.length === 0) {
        container.innerHTML = `
            <div class=\"text-center py-5 text-muted\">
                <i class=\"bi bi-clock-history fs-1 text-primary opacity-50 d-block mb-2\"></i>
                <h6 class=\"fw-bold\">No activity records found</h6>
                <p class=\"small text-muted mb-0\">Activities will appear automatically as you interact with the AI tutor, generate notes, and take quizzes.</p>
            </div>
        `;
        return;
    }

    let html = `
        <div class=\"table-responsive\">
            <table class=\"table table-hover align-middle mb-0\">
                <thead class=\"table-light small\">
                    <tr>
                        <th style=\"width: 140px;\">Event Type</th>
                        <th>Activity Description</th>
                        <th style=\"width: 180px;\">Timestamp</th>
                        <th style=\"width: 60px; text-align: right;\">Action</th>
                    </tr>
                </thead>
                <tbody>
    `;

    logs.forEach(log => {
        const badgeInfo = getActivityBadge(log.activity_type);
        html += `
            <tr id=\"log-row-\${log.id}\">
                <td>
                    <span class=\"badge \${badgeInfo.class} d-inline-flex align-items-center gap-1\" style=\"font-size:0.75rem;\">
                        <i class=\"bi \${badgeInfo.icon}\"></i> \${escapeHtml(badgeInfo.label)}
                    </span>
                </td>
                <td class=\"small fw-medium\">
                    \${escapeHtml(log.description)}
                </td>
                <td class=\"small text-muted\">
                    \${formatDateString(log.created_at)}
                </td>
                <td class=\"text-end\">
                    <button type=\"button\" class=\"btn btn-sm btn-link text-danger p-0 delete-log-btn\" data-id=\"\${log.id}\" title=\"Delete Log\">
                        <i class=\"bi bi-trash\"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;

    // Attach individual delete event
    document.querySelectorAll('.delete-log-btn').forEach(btn => {
        btn.onclick = async () => {
            const id = btn.getAttribute('data-id');
            if (!confirm('Delete this history item?')) return;

            try {
                const res = await fetch('api/history.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const d = await res.json();
                if (d.success) {
                    const row = document.getElementById(`log-row-\${id}`);
                    if (row) row.remove();
                    showToast('History item removed.', 'info');
                } else {
                    showToast(d.error || 'Failed to delete', 'error');
                }
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            }
        };
    });
}

function getActivityBadge(type) {
    if (!type) return { class: 'bg-secondary', icon: 'bi-info-circle', label: 'Activity' };
    const t = type.toLowerCase();

    if (t.includes('chat')) return { class: 'badge-primary-soft', icon: 'bi-chat-dots-fill', label: 'AI Chat' };
    if (t.includes('note')) return { class: 'badge-success-soft', icon: 'bi-journal-richtext', label: 'AI Notes' };
    if (t.includes('quiz')) return { class: 'badge-warning-soft', icon: 'bi-patch-question-fill', label: 'Quiz' };
    if (t.includes('task')) return { class: 'badge-info-soft', icon: 'bi-check2-circle', label: 'Task' };
    if (t.includes('pdf')) return { class: 'badge-danger-soft', icon: 'bi-file-earmark-pdf-fill', label: 'PDF' };
    if (t.includes('login')) return { class: 'bg-body-secondary text-muted border', icon: 'bi-box-arrow-in-right', label: 'Login' };

    return { class: 'bg-body-secondary text-muted', icon: 'bi-activity', label: 'System' };
}

function formatDateString(str) {
    if (!str) return 'N/A';
    const d = new Date(str.replace(' ', 'T'));
    if (isNaN(d.getTime())) return str;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' +
           d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>

