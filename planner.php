<?php
$pageTitle = 'Study Planner';
$currentPage = 'planner';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header with Action -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-calendar2-check text-primary me-2"></i>Study Task Planner</h4>
            <p class="text-muted small mb-0">Organize assignments, syllabus milestones, and exam preparation schedules.</p>
        </div>

        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-circle"></i>
            <span>Add Study Task</span>
        </button>
    </div>

    <!-- Stat Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Pending</span>
                    <i class="bi bi-clock-history text-warning fs-5"></i>
                </div>
                <div class="stat-number fs-3 mt-2 text-warning" id="statPendingCount">0</div>
                <div class="small text-muted" style="font-size:0.75rem;">Tasks to be completed</div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Completed</span>
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                </div>
                <div class="stat-number fs-3 mt-2 text-success" id="statCompletedCount">0</div>
                <div class="small text-muted" style="font-size:0.75rem;">Milestones achieved</div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Overdue</span>
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                </div>
                <div class="stat-number fs-3 mt-2 text-danger" id="statOverdueCount">0</div>
                <div class="small text-muted" style="font-size:0.75rem;">Past deadline</div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="card stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Total Planned</span>
                    <i class="bi bi-card-checklist text-primary fs-5"></i>
                </div>
                <div class="stat-number fs-3 mt-2 text-primary" id="statTotalCount">0</div>
                <div class="small text-muted" style="font-size:0.75rem;">All registered tasks</div>
            </div>
        </div>
    </div>

    <!-- Filters & Controls Card -->
    <div class="card p-3 shadow-sm mb-4">
        <div class="row g-2 align-items-center">
            <!-- Status Tabs -->
            <div class="col-lg-5">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary active filter-status-btn" data-status="all">All</button>
                    <button type="button" class="btn btn-sm btn-outline-primary filter-status-btn" data-status="pending">Pending</button>
                    <button type="button" class="btn btn-sm btn-outline-primary filter-status-btn" data-status="completed">Completed</button>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-sm-6 col-lg-2">
                <select class="form-select form-select-sm" id="filterCategory">
                    <option value="all">All Categories</option>
                    <option value="Programming">Programming</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Assignments">Assignments</option>
                    <option value="Exams">Exams</option>
                    <option value="Personal Study">Personal Study</option>
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="col-sm-6 col-lg-2">
                <select class="form-select form-select-sm" id="filterPriority">
                    <option value="all">All Priorities</option>
                    <option value="high">High Priority</option>
                    <option value="medium">Medium Priority</option>
                    <option value="low">Low Priority</option>
                </select>
            </div>

            <!-- Search Field -->
            <div class="col-lg-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchTaskInput" placeholder="Search tasks...">
                </div>
            </div>
        </div>
    </div>

    <!-- Task Items List Card -->
    <div class="card shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div id="tasksListContainer">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    Loading your study tasks...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Task -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="addTaskForm">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addTaskModalLabel"><i class="bi bi-plus-circle text-primary me-2"></i>Add Study Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="taskTitle" class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="taskTitle" placeholder="e.g. Complete DSA Assignment 3" required>
                    </div>

                    <div class="mb-3">
                        <label for="taskDescription" class="form-label small fw-semibold">Description / Notes</label>
                        <textarea class="form-control" id="taskDescription" rows="2" placeholder="Optional details, chapters, or reference links..."></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="taskCategory" class="form-label small fw-semibold">Category</label>
                            <select class="form-select" id="taskCategory">
                                <option value="Computer Science">Computer Science</option>
                                <option value="Programming">Programming</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Assignments" selected>Assignments</option>
                                <option value="Exams">Exams</option>
                                <option value="Personal Study">Personal Study</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="taskPriority" class="form-label small fw-semibold">Priority</label>
                            <select class="form-select" id="taskPriority">
                                <option value="low">Low Priority</option>
                                <option value="medium" selected>Medium Priority</option>
                                <option value="high">High Priority</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="taskDueDate" class="form-label small fw-semibold">Due Date</label>
                        <input type="date" class="form-control" id="taskDueDate">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold" id="saveTaskBtn">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Task -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="editTaskForm">
                <input type="hidden" id="editTaskId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editTaskModalLabel"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Study Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editTaskTitle" class="form-label small fw-semibold">Task Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTaskTitle" required>
                    </div>

                    <div class="mb-3">
                        <label for="editTaskDescription" class="form-label small fw-semibold">Description</label>
                        <textarea class="form-control" id="editTaskDescription" rows="2"></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="editTaskCategory" class="form-label small fw-semibold">Category</label>
                            <select class="form-select" id="editTaskCategory">
                                <option value="Computer Science">Computer Science</option>
                                <option value="Programming">Programming</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Assignments">Assignments</option>
                                <option value="Exams">Exams</option>
                                <option value="Personal Study">Personal Study</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="editTaskPriority" class="form-label small fw-semibold">Priority</label>
                            <select class="form-select" id="editTaskPriority">
                                <option value="low">Low Priority</option>
                                <option value="medium">Medium Priority</option>
                                <option value="high">High Priority</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editTaskDueDate" class="form-label small fw-semibold">Due Date</label>
                        <input type="date" class="form-control" id="editTaskDueDate">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold" id="updateTaskBtn">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "<script src='assets/js/planner.js'></script>";
require_once __DIR__ . '/includes/footer.php';
?>

