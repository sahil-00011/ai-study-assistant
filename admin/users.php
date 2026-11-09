<?php
$pageTitle = 'Manage Users';
$currentPage = 'admin_users';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$db = getDbConnection();

// Handle status or role changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken(true);

    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $operation = $_POST['operation'] ?? '';

    if ($targetUserId > 0) {
        if ($operation === 'toggle_status') {
            // Cannot deactivate oneself or primary admin 1
            if ($targetUserId === (int)$currentUser['id']) {
                setFlash('danger', 'You cannot deactivate your own administrative account.');
            } else {
                $stmt = $db->prepare("SELECT status, name FROM users WHERE id = ?");
                $stmt->execute([$targetUserId]);
                $target = $stmt->fetch();
                if ($target) {
                    $newStatus = ($target['status'] === 'active') ? 'inactive' : 'active';
                    $upd = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $upd->execute([$newStatus, $targetUserId]);
                    logActivity($currentUser['id'], 'admin_user_status', "Changed status of {$target['name']} to {$newStatus}", $db);
                    setFlash('success', "User account status updated to {$newStatus}.");
                }
            }
        } elseif ($operation === 'toggle_role') {
            if ($targetUserId === 1) {
                setFlash('danger', 'The primary system administrator role cannot be altered.');
            } else {
                $stmt = $db->prepare("SELECT role, name FROM users WHERE id = ?");
                $stmt->execute([$targetUserId]);
                $target = $stmt->fetch();
                if ($target) {
                    $newRole = ($target['role'] === 'admin') ? 'student' : 'admin';
                    $upd = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $upd->execute([$newRole, $targetUserId]);
                    logActivity($currentUser['id'], 'admin_role_change', "Changed role of {$target['name']} to {$newRole}", $db);
                    setFlash('success', "User role updated to {$newRole}.");
                }
            }
        }
    }
    redirect(BASE_URL . '/admin/users.php');
}

// Search & filters
$search = trim($_GET['q'] ?? '');
$roleFilter = trim($_GET['role'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM notes WHERE user_id = u.id) as notes_count,
        (SELECT COUNT(*) FROM quiz_attempts WHERE user_id = u.id) as quizzes_count,
        (SELECT COUNT(*) FROM chat_conversations WHERE user_id = u.id) as chats_count
        FROM users u WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.institution LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($roleFilter === 'student' || $roleFilter === 'admin') {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $sql .= " AND u.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY u.created_at DESC LIMIT 100";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-people text-primary me-2"></i>User Management</h4>
            <p class="text-muted small mb-0">View student accounts, toggle active/inactive statuses, and manage administrative privileges.</p>
        </div>

        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="card p-3 shadow-sm mb-4">
        <form method="GET" action="users.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, or college...">
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <select class="form-select form-select-sm" name="role" onchange="this.form.submit()">
                    <option value="all">All Roles</option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Students</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                </select>
            </div>

            <div class="col-sm-6 col-md-3">
                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive / Suspended</option>
                </select>
            </div>

            <div class="col-md-1 text-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th>User</th>
                        <th>Academic Info</th>
                        <th>Activity Summary</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 opacity-50 d-block mb-2"></i>
                                No users match your filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="width: 34px; height: 34px; font-size: 0.85rem; background: var(--primary-color);">
                                            <?php 
                                            $in = '';
                                            foreach (explode(' ', $u['name']) as $part) {
                                                if (!empty($part)) $in .= strtoupper($part[0]);
                                                if (strlen($in) >= 2) break;
                                            }
                                            echo htmlspecialchars($in);
                                            ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($u['name']); ?></div>
                                            <div class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($u['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($u['academic_level']); ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($u['institution'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <span class="badge badge-primary-soft" title="Notes"><?php echo $u['notes_count']; ?> Notes</span>
                                    <span class="badge badge-warning-soft" title="Quizzes"><?php echo $u['quizzes_count']; ?> Quizzes</span>
                                    <span class="badge bg-body-secondary text-muted border" title="Chats"><?php echo $u['chats_count']; ?> Chats</span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['status'] === 'active' ? 'badge-success-soft' : 'badge-danger-soft'; ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td class="text-muted">
                                    <?php echo formatDate($u['created_at'], 'M d, Y'); ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                                            <!-- Toggle Status Form -->
                                            <li>
                                                <form method="POST" action="users.php" onsubmit="return confirm('Change user status?');">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="operation" value="toggle_status">
                                                    <button type="submit" class="dropdown-item py-1">
                                                        <i class="bi bi-power me-2 text-warning"></i>
                                                        <?php echo $u['status'] === 'active' ? 'Deactivate Account' : 'Activate Account'; ?>
                                                    </button>
                                                </form>
                                            </li>

                                            <!-- Toggle Role Form (Protected) -->
                                            <?php if ($u['id'] != 1): ?>
                                                <li>
                                                    <form method="POST" action="users.php" onsubmit="return confirm('Change administrative role?');">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="target_user_id" value="<?php echo $u['id']; ?>">
                                                        <input type="hidden" name="operation" value="toggle_role">
                                                        <button type="submit" class="dropdown-item py-1">
                                                            <i class="bi bi-shield-lock me-2 text-primary"></i>
                                                            <?php echo $u['role'] === 'admin' ? 'Demote to Student' : 'Promote to Admin'; ?>
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

