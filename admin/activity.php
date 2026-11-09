<?php
$pageTitle = 'Platform Audit Logs';
$currentPage = 'admin_activity';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$db = getDbConnection();

$search = trim($_GET['q'] ?? '');
$typeFilter = trim($_GET['type'] ?? 'all');

$sql = "SELECT a.*, u.name as user_name, u.email as user_email, u.role as user_role 
        FROM activity_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (a.description LIKE ? OR a.ip_address LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($typeFilter !== 'all') {
    $sql .= " AND a.activity_type LIKE ?";
    $params[] = "%{$typeFilter}%";
}

$sql .= " ORDER BY a.created_at DESC LIMIT 150";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-shield-shaded text-primary me-2"></i>Platform Audit Logs</h4>
            <p class="text-muted small mb-0">System-wide monitoring of AI chats, notes generation, quiz attempts, and user security events.</p>
        </div>

        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card p-3 shadow-sm mb-4">
        <form method="GET" action="activity.php" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search user name, email, IP, or action...">
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <select class="form-select form-select-sm" name="type" onchange="this.form.submit()">
                    <option value="all">All Event Types</option>
                    <option value="chat" <?php echo $typeFilter === 'chat' ? 'selected' : ''; ?>>AI Chat Sessions</option>
                    <option value="notes" <?php echo $typeFilter === 'notes' ? 'selected' : ''; ?>>Notes Generated</option>
                    <option value="quiz" <?php echo $typeFilter === 'quiz' ? 'selected' : ''; ?>>Quiz Attempts</option>
                    <option value="pdf" <?php echo $typeFilter === 'pdf' ? 'selected' : ''; ?>>PDF Documents</option>
                    <option value="login" <?php echo $typeFilter === 'login' ? 'selected' : ''; ?>>Logins & Security</option>
                </select>
            </div>

            <div class="col-sm-6 col-md-2 text-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="card shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 170px;">Timestamp</th>
                        <th style="width: 180px;">User</th>
                        <th style="width: 140px;">Event Type</th>
                        <th>Action Details</th>
                        <th style="width: 120px;">IP Address</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No audit activity records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="text-muted">
                                    <?php echo formatDate($l['created_at']); ?>
                                </td>
                                <td>
                                    <?php if (!empty($l['user_name'])): ?>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($l['user_name']); ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($l['user_email']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Anonymous / System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-body-secondary text-muted border">
                                        <?php echo htmlspecialchars($l['activity_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($l['description']); ?>
                                </td>
                                <td class="text-muted">
                                    <code><?php echo htmlspecialchars($l['ip_address'] ?? '127.0.0.1'); ?></code>
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

