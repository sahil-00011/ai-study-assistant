<?php
$pageTitle = 'Manage Subjects';
$currentPage = 'admin_subjects';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$currentUser = getCurrentUser();
$db = getDbConnection();

// Handle Subject Mutations (Add / Edit / Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken(true);

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'bi-book');

        if (empty($name) || empty($code)) {
            setFlash('danger', 'Subject name and course code are required.');
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO subjects (name, code, description, icon, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$name, $code, $description, $icon]);
                logActivity($currentUser['id'], 'admin_add_subject', "Added subject: {$name} ({$code})", $db);
                setFlash('success', "Subject '{$name}' created successfully!");
            } catch (Exception $e) {
                setFlash('danger', 'Error adding subject (Code or Name might already exist): ' . $e->getMessage());
            }
        }
    } elseif ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $db->prepare("UPDATE subjects SET is_active = (1 - is_active) WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('info', 'Subject status toggled.');
            } catch (Exception $e) {
                setFlash('danger', 'Database error: ' . $e->getMessage());
            }
        }
    }

    redirect(BASE_URL . '/admin/subjects.php');
}

// Fetch all subjects
$subjects = [];
try {
    $stmt = $db->query("SELECT * FROM subjects ORDER BY name ASC");
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-book-half text-primary me-2"></i>Curriculum Subjects</h4>
            <p class="text-muted small mb-0">Manage course catalogs, syllabus modules, and icon representations for notes and quizzes.</p>
        </div>

        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-circle"></i>
            <span>Add New Subject</span>
        </button>
    </div>

    <!-- Subjects Grid Table -->
    <div class="card shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th style="width: 50px;">Icon</th>
                        <th>Subject Name</th>
                        <th>Course Code</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No subjects found in the database.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td>
                                    <div class="avatar-circle" style="width: 32px; height: 32px; font-size: 1rem; background: var(--primary-light); color: var(--primary-color);">
                                        <i class="bi <?php echo htmlspecialchars($s['icon'] ?? 'bi-book'); ?>"></i>
                                    </div>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($s['code']); ?></code></td>
                                <td class="text-muted text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($s['description'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $s['is_active'] ? 'badge-success-soft' : 'badge-danger-soft'; ?>">
                                        <?php echo $s['is_active'] ? 'Active' : 'Disabled'; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="subjects.php" class="d-inline" onsubmit="return confirm('Toggle subject visibility?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $s['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>" style="font-size:0.75rem;">
                                            <?php echo $s['is_active'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Subject -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="subjects.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add Course Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subName" class="form-label small fw-semibold">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subName" name="name" placeholder="e.g. Artificial Intelligence" required>
                    </div>

                    <div class="mb-3">
                        <label for="subCode" class="form-label small fw-semibold">Course Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="subCode" name="code" placeholder="e.g. CS401" required>
                    </div>

                    <div class="mb-3">
                        <label for="subIcon" class="form-label small fw-semibold">Bootstrap Icon Class</label>
                        <input type="text" class="form-control" id="subIcon" name="icon" value="bi-book" placeholder="e.g. bi-cpu, bi-robot, bi-database">
                    </div>

                    <div class="mb-3">
                        <label for="subDesc" class="form-label small fw-semibold">Description</label>
                        <textarea class="form-control" id="subDesc" name="description" rows="2" placeholder="Brief syllabus or overview..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold">Save Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

