<?php
$pageTitle = 'Student Profile';
$currentPage = 'profile';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser(true); // reload fresh from DB
$userId = (int)$currentUser['id'];

// Counts for profile overview
$totalNotes = 0;
$totalQuizzes = 0;
$totalTasks = 0;

try {
    $db = getDbConnection();
    $s1 = $db->prepare("SELECT COUNT(*) FROM notes WHERE user_id = ?");
    $s1->execute([$userId]);
    $totalNotes = (int)$s1->fetchColumn();

    $s2 = $db->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ?");
    $s2->execute([$userId]);
    $totalQuizzes = (int)$s2->fetchColumn();

    $s3 = $db->prepare("SELECT COUNT(*) FROM study_tasks WHERE user_id = ? AND status = 'completed'");
    $s3->execute([$userId]);
    $totalTasks = (int)$s3->fetchColumn();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <div class="row g-4 justify-content-center">
        <!-- Profile Summary Card -->
        <div class="col-lg-4">
            <div class="card p-4 shadow-sm text-center">
                <div class="avatar-circle mx-auto mb-3" style="width: 84px; height: 84px; font-size: 2.2rem; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); box-shadow: 0 10px 20px rgba(79, 70, 229, 0.25);">
                    <?php 
                    $initials = '';
                    $parts = explode(' ', $currentUser['name'] ?? 'User');
                    foreach ($parts as $p) {
                        if (!empty($p)) $initials .= strtoupper($p[0]);
                        if (strlen($initials) >= 2) break;
                    }
                    echo htmlspecialchars($initials);
                    ?>
                </div>

                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($currentUser['name']); ?></h5>
                <p class="text-muted small mb-2"><?php echo htmlspecialchars($currentUser['email']); ?></p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge badge-primary-soft"><?php echo htmlspecialchars($currentUser['academic_level']); ?></span>
                    <span class="badge bg-body-secondary text-muted border"><?php echo ucfirst($currentUser['role']); ?></span>
                </div>

                <?php if (!empty($currentUser['institution'])): ?>
                    <p class="small text-muted mb-3"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($currentUser['institution']); ?></p>
                <?php endif; ?>

                <div class="border-top pt-3 text-start small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Member Since:</span>
                        <span class="fw-semibold"><?php echo formatDate($currentUser['created_at'], 'M d, Y'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Last Active:</span>
                        <span class="fw-semibold"><?php echo timeAgo($currentUser['last_login_at'] ?? $currentUser['created_at']); ?></span>
                    </div>
                </div>

                <!-- Academic Stats Summary -->
                <div class="row g-2 text-center mt-3 pt-3 border-top">
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-primary"><?php echo $totalNotes; ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Notes</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-warning"><?php echo $totalQuizzes; ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Quizzes</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-success"><?php echo $totalTasks; ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Done</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="col-lg-8">
            <div class="card p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-person-gear text-primary me-2"></i>Profile Details</h5>
                        <p class="text-muted small mb-0">Update your academic information and subject interests.</p>
                    </div>
                    <a href="settings.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                        <i class="bi bi-sliders"></i> Security Settings
                    </a>
                </div>

                <form id="editProfileForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="profName" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="profName" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="profEmail" class="form-label small fw-semibold">Email Address</label>
                            <input type="email" class="form-control bg-body-tertiary" id="profEmail" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                            <span class="text-muted" style="font-size: 0.72rem;">Email cannot be changed directly for security.</span>
                        </div>

                        <div class="col-md-6">
                            <label for="profLevel" class="form-label small fw-semibold">Academic Level</label>
                            <select class="form-select" id="profLevel">
                                <option value="High School" <?php echo $currentUser['academic_level'] === 'High School' ? 'selected' : ''; ?>>High School</option>
                                <option value="Undergraduate" <?php echo $currentUser['academic_level'] === 'Undergraduate' ? 'selected' : ''; ?>>Undergraduate (B.Tech / B.Sc / BCA)</option>
                                <option value="Postgraduate" <?php echo $currentUser['academic_level'] === 'Postgraduate' ? 'selected' : ''; ?>>Postgraduate (M.Tech / M.Sc / MCA)</option>
                                <option value="PhD / Research" <?php echo $currentUser['academic_level'] === 'PhD / Research' ? 'selected' : ''; ?>>PhD / Research</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="profInstitution" class="form-label small fw-semibold">College / University</label>
                            <input type="text" class="form-control" id="profInstitution" value="<?php echo htmlspecialchars($currentUser['institution'] ?? ''); ?>" placeholder="e.g. State Technical Institute">
                        </div>

                        <div class="col-12">
                            <label for="profInterests" class="form-label small fw-semibold">Subject Interests & Specializations</label>
                            <input type="text" class="form-control" id="profInterests" value="<?php echo htmlspecialchars($currentUser['subject_interests'] ?? ''); ?>" placeholder="e.g. Operating Systems, Data Structures, Web Development, DBMS">
                            <span class="text-muted" style="font-size: 0.75rem;">Comma separated topics you frequently study.</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-2" id="saveProfileBtn">
                            <i class="bi bi-check2"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('editProfileForm');
    const saveBtn = document.getElementById('saveProfileBtn');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const name = document.getElementById('profName').value.trim();
            const academicLevel = document.getElementById('profLevel').value;
            const institution = document.getElementById('profInstitution').value.trim();
            const interests = document.getElementById('profInterests').value.trim();

            if (!name) {
                showToast('Full name is required.', 'warning');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-1\"></span> Saving...';

            try {
                const res = await fetch('api/profile.php?action=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        academic_level: academicLevel,
                        institution,
                        subject_interests: interests
                    })
                });

                const data = await res.json();
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class=\"bi bi-check2\"></i> <span>Save Profile Changes</span>';

                if (data.success) {
                    showToast('Profile updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class=\"bi bi-check2\"></i> <span>Save Profile Changes</span>';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }
});
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>

