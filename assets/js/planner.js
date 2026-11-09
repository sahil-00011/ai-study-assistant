/**
 * Study Task Planner Frontend Logic
 * AI Study Assistant
 */

let currentTasksList = [];
let activeStatusFilter = 'all';

document.addEventListener('DOMContentLoaded', () => {
    loadTasks();

    // Status filter buttons
    document.querySelectorAll('.filter-status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-status-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeStatusFilter = btn.getAttribute('data-status');
            loadTasks();
        });
    });

    // Dropdown filters
    const categorySelect = document.getElementById('filterCategory');
    const prioritySelect = document.getElementById('filterPriority');
    const searchInput = document.getElementById('searchTaskInput');

    if (categorySelect) categorySelect.addEventListener('change', () => loadTasks());
    if (prioritySelect) prioritySelect.addEventListener('change', () => loadTasks());

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadTasks(), 300);
        });
    }

    // Add Task Form
    const addForm = document.getElementById('addTaskForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const title = document.getElementById('taskTitle').value.trim();
            const description = document.getElementById('taskDescription').value.trim();
            const category = document.getElementById('taskCategory').value;
            const priority = document.getElementById('taskPriority').value;
            const dueDate = document.getElementById('taskDueDate').value;

            if (!title) {
                showToast('Task title is required.', 'warning');
                return;
            }

            const saveBtn = document.getElementById('saveTaskBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            try {
                const res = await fetch('api/tasks.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title,
                        description,
                        category,
                        priority,
                        due_date: dueDate
                    })
                });

                const data = await res.json();
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Task';

                if (data.success) {
                    showToast('Task created successfully!', 'success');
                    addForm.reset();

                    // Close modal
                    const modalEl = document.getElementById('addTaskModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    loadTasks();
                } else {
                    showToast(data.error || 'Failed to create task.', 'error');
                }
            } catch (err) {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Task';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }

    // Edit Task Form
    const editForm = document.getElementById('editTaskForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const taskId = document.getElementById('editTaskId').value;
            const title = document.getElementById('editTaskTitle').value.trim();
            const description = document.getElementById('editTaskDescription').value.trim();
            const category = document.getElementById('editTaskCategory').value;
            const priority = document.getElementById('editTaskPriority').value;
            const dueDate = document.getElementById('editTaskDueDate').value;

            const updateBtn = document.getElementById('updateTaskBtn');
            updateBtn.disabled = true;
            updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

            try {
                const res = await fetch('api/tasks.php?action=update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: taskId,
                        title,
                        description,
                        category,
                        priority,
                        due_date: dueDate
                    })
                });

                const data = await res.json();
                updateBtn.disabled = false;
                updateBtn.textContent = 'Update Task';

                if (data.success) {
                    showToast('Task updated successfully!', 'success');

                    const modalEl = document.getElementById('editTaskModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) modalInstance.hide();

                    loadTasks();
                } else {
                    showToast(data.error || 'Failed to update task.', 'error');
                }
            } catch (err) {
                updateBtn.disabled = false;
                updateBtn.textContent = 'Update Task';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }
});

/**
 * Fetch tasks from API based on current filter states
 */
async function loadTasks() {
    const container = document.getElementById('tasksListContainer');
    const category = document.getElementById('filterCategory')?.value || 'all';
    const priority = document.getElementById('filterPriority')?.value || 'all';
    const query = document.getElementById('searchTaskInput')?.value.trim() || '';

    const url = `api/tasks.php?action=list&status=${activeStatusFilter}&category=${encodeURIComponent(category)}&priority=${priority}&q=${encodeURIComponent(query)}`;

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            currentTasksList = data.tasks;
            updateStatsCounters(data.stats);
            renderTasksList(data.tasks);
        } else {
            container.innerHTML = `<div class="p-4 text-center text-danger small">${escapeHtml(data.error || 'Failed to load tasks')}</div>`;
        }
    } catch (err) {
        container.innerHTML = `<div class="p-4 text-center text-danger small">Network error: ${escapeHtml(err.message)}</div>`;
    }
}

function updateStatsCounters(stats) {
    if (!stats) return;
    document.getElementById('statPendingCount').textContent = stats.pending || 0;
    document.getElementById('statCompletedCount').textContent = stats.completed || 0;
    document.getElementById('statOverdueCount').textContent = stats.overdue || 0;
    document.getElementById('statTotalCount').textContent = stats.total || 0;
}

function renderTasksList(tasks) {
    const container = document.getElementById('tasksListContainer');
    if (!tasks || tasks.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar2-check fs-1 text-primary opacity-50 d-block mb-2"></i>
                <h6 class="fw-bold">No tasks match your criteria</h6>
                <p class="small text-muted mb-0">Click "+ Add Study Task" to organize a new study milestone.</p>
            </div>
        `;
        return;
    }

    const todayStr = new Date().toISOString().split('T')[0];
    let html = '<div class="list-group list-group-flush">';

    tasks.forEach(t => {
        const isCompleted = (t.status === 'completed');
        const isOverdue = (!isCompleted && t.due_date && t.due_date < todayStr);

        const priorityClass = matchPriorityBadge(t.priority);

        html += `
            <div class="task-item ${isCompleted ? 'completed' : ''}" id="task-row-${t.id}">
                <div class="d-flex align-items-start gap-3 flex-grow-1 me-3">
                    <input class="form-check-input mt-1 toggle-task-checkbox" type="checkbox" style="cursor: pointer; width: 20px; height: 20px;" data-id="${t.id}" ${isCompleted ? 'checked' : ''}>
                    
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold task-title">${escapeHtml(t.title)}</span>
                            <span class="badge ${priorityClass}" style="font-size:0.7rem;">${escapeHtml(t.priority.toUpperCase())}</span>
                            <span class="badge bg-body-secondary text-muted border" style="font-size:0.7rem;">${escapeHtml(t.category)}</span>
                        </div>
                        
                        ${t.description ? `<p class="small text-muted mb-1 mt-1">${escapeHtml(t.description)}</p>` : ''}
                        
                        <div class="d-flex align-items-center gap-3 mt-1 small" style="font-size:0.75rem;">
                            ${t.due_date ? `
                                <span class="${isOverdue ? 'text-danger fw-bold' : 'text-muted'}">
                                    <i class="bi bi-calendar-event me-1"></i> Due: ${formatDateString(t.due_date)}
                                    ${isOverdue ? '<span class="badge bg-danger ms-1">OVERDUE</span>' : ''}
                                </span>
                            ` : '<span class="text-muted"><i class="bi bi-calendar-minus me-1"></i> No deadline</span>'}
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-task-btn" data-id="${t.id}" title="Edit Task">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-task-btn" data-id="${t.id}" title="Delete Task" style="font-size: 1rem;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;

    attachTaskRowEvents();
}

function attachTaskRowEvents() {
    // Checkbox Toggle
    document.querySelectorAll('.toggle-task-checkbox').forEach(cb => {
        cb.onchange = async () => {
            const taskId = cb.getAttribute('data-id');
            try {
                const res = await fetch('api/tasks.php?action=toggle_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: taskId })
                });
                const data = await res.json();
                if (data.success) {
                    loadTasks();
                } else {
                    cb.checked = !cb.checked;
                    showToast(data.error || 'Failed to toggle status', 'error');
                }
            } catch (err) {
                cb.checked = !cb.checked;
                showToast('Error: ' + err.message, 'error');
            }
        };
    });

    // Edit button
    document.querySelectorAll('.edit-task-btn').forEach(btn => {
        btn.onclick = () => {
            const taskId = btn.getAttribute('data-id');
            const task = currentTasksList.find(t => t.id == taskId);
            if (!task) return;

            document.getElementById('editTaskId').value = task.id;
            document.getElementById('editTaskTitle').value = task.title;
            document.getElementById('editTaskDescription').value = task.description || '';
            document.getElementById('editTaskCategory').value = task.category || 'General';
            document.getElementById('editTaskPriority').value = task.priority || 'medium';
            document.getElementById('editTaskDueDate').value = task.due_date || '';

            const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            modal.show();
        };
    });

    // Delete button
    document.querySelectorAll('.delete-task-btn').forEach(btn => {
        btn.onclick = async () => {
            const taskId = btn.getAttribute('data-id');
            if (!confirm('Are you sure you want to delete this study task?')) return;

            try {
                const res = await fetch('api/tasks.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: taskId })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Task deleted.', 'info');
                    loadTasks();
                } else {
                    showToast(data.error || 'Failed to delete task', 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        };
    });
}

function matchPriorityBadge(priority) {
    switch (priority) {
        case 'high': return 'badge-danger-soft';
        case 'medium': return 'badge-warning-soft';
        default: return 'badge-primary-soft';
    }
}

function formatDateString(str) {
    if (!str) return '';
    const parts = str.split('-');
    if (parts.length === 3) {
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    return str;
}

