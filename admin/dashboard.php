<?php
$pageTitle = 'Admin Dashboard';
$currentPage = 'admin_dashboard';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();

$totalStudents = 0;
$activeUsers = 0;
$totalConversations = 0;
$totalNotes = 0;
$totalQuizzes = 0;
$recentActivities = [];

try {
    $db = getDbConnection();

    $s1 = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $totalStudents = (int)$s1->fetchColumn();

    $s2 = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $activeUsers = (int)$s2->fetchColumn();

    $s3 = $db->query("SELECT COUNT(*) FROM chat_conversations");
    $totalConversations = (int)$s3->fetchColumn();

    $s4 = $db->query("SELECT COUNT(*) FROM notes");
    $totalNotes = (int)$s4->fetchColumn();

    $s5 = $db->query("SELECT COUNT(*) FROM quiz_attempts");
    $totalQuizzes = (int)$s5->fetchColumn();

    $actStmt = $db->query("SELECT a.*, u.name as user_name, u.email as user_email 
                           FROM activity_logs a 
                           LEFT JOIN users u ON a.user_id = u.id 
                           ORDER BY a.created_at DESC LIMIT 10");
    $recentActivities = $actStmt->fetchAll();

} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content py-3">
    <!-- Admin Header Banner -->
    <div class="card border-0 mb-4 shadow-sm" style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 100%); color: #FFFFFF; border-radius: 16px;">
        <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="badge bg-danger mb-2 px-3 py-1 fw-semibold">
                    <i class="bi bi-shield-lock-fill me-1"></i> System Administration
                </span>
                <h2 class="fw-bold mb-1 text-white">Platform Control Center</h2>
                <p class="mb-0 text-white-50">
                    Administrator: <?php echo htmlspecialchars($currentUser['name']); ?> (<?php echo htmlspecialchars($currentUser['email']); ?>)
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-light fw-semibold text-primary shadow-sm">
                    <i class="bi bi-people-fill me-1"></i> Manage Users
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/subjects.php" class="btn btn-outline-light fw-semibold">
                    <i class="bi bi-book-half me-1"></i> Manage Subjects
                </a>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-light fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i> Student View
                </a>
            </div>
        </div>
    </div>

    <!-- Admin Stat Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(79, 70, 229, 0.12); color: #4F46E5;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <span class="badge badge-primary-soft">Registered</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalStudents); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(22, 163, 74, 0.12); color: #16A34A;">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <span class="badge badge-success-soft">Active</span>
                </div>
                <div class="stat-number"><?php echo number_format($activeUsers); ?></div>
                <div class="stat-label">Active Accounts</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(6, 182, 212, 0.12); color: #0891B2;">
                        <i class="bi bi-chat-quote-fill"></i>
                    </div>
                    <span class="badge" style="background: rgba(6, 182, 212, 0.15); color: #0891B2;">AI Chats</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalConversations); ?></div>
                <div class="stat-label">Conversations</div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #F59E0B;">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <span class="badge badge-warning-soft">Notes</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalNotes); ?></div>
                <div class="stat-label">Generated Notes</div>
            </div>
        </div>

        <div class="col-sm-12 col-xl-2dot4 col-lg-4">
            <div class="card stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.12); color: #EF4444;">
                        <i class="bi bi-award"></i>
                    </div>
                    <span class="badge badge-danger-soft">Quizzes</span>
                </div>
                <div class="stat-number"><?php echo number_format($totalQuizzes); ?></div>
                <div class="stat-label">Quiz Attempts</div>
            </div>
        </div>
    </div>

    <!-- Quick Admin Action Navigation -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 card-hover border">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle" style="background: var(--primary-light); color: var(--primary-color);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Student User Management</h6>
                        <span class="small text-muted">Activate, suspend, or inspect user accounts</span>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-primary mt-3 w-100">
                    Open Users &rarr;
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 card-hover border">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle" style="background: rgba(16, 185, 129, 0.12); color: #10B981;">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Academic Subjects Catalog</h6>
                        <span class="small text-muted">Configure curricula, course codes, and icons</span>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/subjects.php" class="btn btn-sm btn-outline-success mt-3 w-100">
                    Open Subjects &rarr;
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 card-hover border">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle" style="background: rgba(245, 158, 11, 0.12); color: #F59E0B;">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Audit Logs & Security</h6>
                        <span class="small text-muted">Monitor system activities, logins, and API calls</span>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/activity.php" class="btn btn-sm btn-outline-warning mt-3 w-100">
                    View Audit Logs &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Platform Activity Table -->
    <div class="card p-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0">Recent Platform Operations</h5>
            <a href="<?php echo BASE_URL; ?>/admin/activity.php" class="small text-decoration-none text-primary fw-semibold">View Full Audit Log &rarr;</a>
        </div>

        <?php if (empty($recentActivities)): ?>
            <p class="small text-muted mb-0">No system activities recorded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>User</th>
                            <th>Action Type</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php foreach ($recentActivities as $act): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?php echo htmlspecialchars($act['user_name'] ?? 'System / Anonymous'); ?>
                                    <?php if (!empty($act['user_email'])): ?>
                                        <div class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($act['user_email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-body-secondary text-muted border"><?php echo htmlspecialchars($act['activity_type']); ?></span>
                                </td>
                                <td class="text-truncate" style="max-width: 280px;"><?php echo htmlspecialchars($act['description']); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($act['ip_address'] ?? '127.0.0.1'); ?></td>
                                <td class="text-muted"><?php echo timeAgo($act['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

