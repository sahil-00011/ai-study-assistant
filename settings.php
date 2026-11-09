<?php
$pageTitle = 'Account Settings';
$currentPage = 'settings';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Fetch user settings
$userSettings = ['theme_preference' => 'light', 'email_notifications' => 1];
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT theme_preference, email_notifications FROM user_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $userSettings = $row;
    }
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <?php if (isAdmin()): ?>
                <!-- Admin OpenAI API Key Configuration -->
                <div class="card p-4 shadow-sm mb-4 border-primary">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-cpu text-primary me-2"></i>OpenAI API Configuration (Admin)</h5>
                            <p class="text-muted small mb-0">Set or update the OpenAI API key used across AI Tutor, Notes, Quizzes, and PDF Summarizer.</p>
                        </div>
                        <span class="badge <?php echo (!empty(OPENAI_API_KEY) && OPENAI_API_KEY !== 'YOUR_API_KEY_HERE') ? 'badge-success-soft' : 'badge-danger-soft'; ?>">
                            <?php echo (!empty(OPENAI_API_KEY) && OPENAI_API_KEY !== 'YOUR_API_KEY_HERE') ? '<i class="bi bi-check-circle-fill"></i> API Key Active' : '<i class="bi bi-exclamation-circle-fill"></i> Key Not Configured'; ?>
                        </span>
                    </div>

                    <form id="openaiConfigForm">
                        <div class="mb-3">
                            <label for="openaiKeyInput" class="form-label small fw-semibold">OpenAI API Key <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="password" class="form-control font-monospace" id="openaiKeyInput" placeholder="sk-proj-..." required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleKeyVisibility"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="small text-muted mt-1" style="font-size:0.75rem;">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">OpenAI Developer Platform</a>. Key is securely stored on the server.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="openaiModelSelect" class="form-label small fw-semibold">AI Model</label>
                                <select class="form-select form-select-sm" id="openaiModelSelect">
                                    <option value="gemini-3-flash-preview" <?php echo OPENAI_MODEL === 'gemini-3-flash-preview' ? 'selected' : ''; ?>>Google Gemini 3.8 Flash (Active & Free)</option>
                                    <option value="gpt-4o-mini" <?php echo OPENAI_MODEL === 'gpt-4o-mini' ? 'selected' : ''; ?>>OpenAI gpt-4o-mini</option>
                                    <option value="gpt-4o" <?php echo OPENAI_MODEL === 'gpt-4o' ? 'selected' : ''; ?>>OpenAI gpt-4o</option>
                                    <option value="gpt-3.5-turbo" <?php echo OPENAI_MODEL === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>OpenAI gpt-3.5-turbo</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm fw-semibold" id="saveOpenaiBtn">
                            <i class="bi bi-save me-1"></i> Save API Key
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- 1. Change Password Card -->
            <div class="card p-4 shadow-sm mb-4">
                <div class="pb-3 mb-3 border-bottom">
                    <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>Security & Password</h5>
                    <p class="text-muted small mb-0">Ensure your student account remains secure with a strong password.</p>
                </div>

                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label small fw-semibold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>

                    <div class="mb-3">
                        <label for="newPassword" class="form-label small fw-semibold">New Password (min 8 characters) <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="newPassword" required minlength="8">
                        <div class="password-meter mt-2">
                            <div id="settings-password-bar" class="password-meter-bar"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmNewPassword" class="form-label small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirmNewPassword" required>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 fw-semibold" id="changePasswordBtn">
                        <i class="bi bi-key-fill me-1"></i> Update Password
                    </button>
                </form>
            </div>

            <!-- 2. Preferences Card (Theme & Notifications) -->
            <div class="card p-4 shadow-sm mb-4">
                <div class="pb-3 mb-3 border-bottom">
                    <h5 class="fw-bold mb-1"><i class="bi bi-palette text-primary me-2"></i>Preferences & Interface</h5>
                    <p class="text-muted small mb-0">Customize your visual interface and communication preferences.</p>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold mb-2">Display Theme</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input theme-radio-opt" type="radio" name="themeRadio" id="themeLight" value="light" <?php echo $userSettings['theme_preference'] === 'light' ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="themeLight">
                                <i class="bi bi-sun-fill text-warning me-1"></i> Light Theme
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input theme-radio-opt" type="radio" name="themeRadio" id="themeDark" value="dark" <?php echo $userSettings['theme_preference'] === 'dark' ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="themeDark">
                                <i class="bi bi-moon-stars-fill text-primary me-1"></i> Dark Theme
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label small fw-semibold mb-2">Study Notifications</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="notifSwitch" <?php echo $userSettings['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="notifSwitch">
                            Enable email reminders for upcoming study task deadlines and quiz reviews
                        </label>
                    </div>
                </div>
            </div>

            <!-- 3. Danger Zone Card -->
            <div class="card p-4 shadow-sm border-danger-subtle bg-danger-subtle text-danger">
                <h5 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h5>
                <p class="small mb-3">Deleting your account permanently wipes all your saved notes, chat conversations, and quiz attempts.</p>
                <button type="button" class="btn btn-outline-danger btn-sm align-self-start" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    Delete Student Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirm Delete Account -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="deleteAccountForm">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-shield-exclamation me-2"></i>Confirm Account Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-danger fw-semibold">
                        This action is irreversible. All your study notes, conversations, and quiz records will be permanently removed.
                    </p>
                    <div class="mb-3">
                        <label for="deletePasswordConfirm" class="form-label small fw-semibold">Please enter your password to confirm:</label>
                        <input type="password" class="form-control" id="deletePasswordConfirm" placeholder="Account Password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold" id="confirmDeleteBtn">Delete My Account Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 0. OpenAI Config Form (for Admins)
    const openaiForm = document.getElementById('openaiConfigForm');
    const toggleKeyBtn = document.getElementById('toggleKeyVisibility');
    const keyInput = document.getElementById('openaiKeyInput');

    if (toggleKeyBtn && keyInput) {
        toggleKeyBtn.addEventListener('click', () => {
            const isPass = keyInput.type === 'password';
            keyInput.type = isPass ? 'text' : 'password';
            toggleKeyBtn.innerHTML = isPass ? '<i class=\"bi bi-eye-slash\"></i>' : '<i class=\"bi bi-eye\"></i>';
        });
    }

    if (openaiForm) {
        openaiForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const key = keyInput.value.trim();
            const model = document.getElementById('openaiModelSelect').value;
            const btn = document.getElementById('saveOpenaiBtn');

            btn.disabled = true;
            btn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-1\"></span> Saving...';

            try {
                const res = await fetch('api/profile.php?action=save_openai_key', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ api_key: key, model: model })
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<i class=\"bi bi-save me-1\"></i> Save API Key';

                if (data.success) {
                    showToast('OpenAI API key saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.error || 'Failed to save API key.', 'error');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class=\"bi bi-save me-1\"></i> Save API Key';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }

    // 1. Password strength meter on settings page
    const newPassInput = document.getElementById('newPassword');
    const bar = document.getElementById('settings-password-bar');
    if (newPassInput && bar) {
        newPassInput.addEventListener('input', () => {
            const v = newPassInput.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;

            const widths = ['0%', '25%', '50%', '75%', '100%'];
            const colors = ['#E2E8F0', '#EF4444', '#F59E0B', '#3B82F6', '#16A34A'];
            bar.style.width = widths[score];
            bar.style.backgroundColor = colors[score];
        });
    }

    // 2. Change Password Form
    const passForm = document.getElementById('changePasswordForm');
    if (passForm) {
        passForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const current = document.getElementById('currentPassword').value;
            const newP = document.getElementById('newPassword').value;
            const confirmP = document.getElementById('confirmNewPassword').value;

            if (newP !== confirmP) {
                showToast('New passwords do not match.', 'warning');
                return;
            }

            const btn = document.getElementById('changePasswordBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-1\"></span> Updating...';

            try {
                const res = await fetch('api/profile.php?action=change_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: current,
                        new_password: newP,
                        confirm_password: confirmP
                    })
                });

                const d = await res.json();
                btn.disabled = false;
                btn.innerHTML = '<i class=\"bi bi-key-fill me-1\"></i> Update Password';

                if (d.success) {
                    showToast('Password updated successfully!', 'success');
                    passForm.reset();
                    if (bar) bar.style.width = '0%';
                } else {
                    showToast(d.error || 'Failed to update password.', 'error');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class=\"bi bi-key-fill me-1\"></i> Update Password';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }

    // 3. Theme radio change
    document.querySelectorAll('.theme-radio-opt').forEach(radio => {
        radio.addEventListener('change', async () => {
            const selected = radio.value;
            applyTheme(selected);
            localStorage.setItem('app_theme', selected);

            try {
                await fetch('api/profile.php?action=update_theme', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: selected })
                });
                showToast(`Applied \${selected} theme.`, 'info');
            } catch (e) {}
        });
    });

    // 4. Notifications toggle
    const notifSwitch = document.getElementById('notifSwitch');
    if (notifSwitch) {
        notifSwitch.addEventListener('change', async () => {
            const enabled = notifSwitch.checked ? 1 : 0;
            try {
                const res = await fetch('api/profile.php?action=update_notifications', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email_notifications: enabled })
                });
                const d = await res.json();
                if (d.success) {
                    showToast('Notification preference saved.', 'info');
                }
            } catch (e) {
                showToast('Failed to save preference.', 'error');
            }
        });
    }

    // 5. Delete Account Form
    const delForm = document.getElementById('deleteAccountForm');
    if (delForm) {
        delForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pwd = document.getElementById('deletePasswordConfirm').value;
            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-1\"></span> Processing...';

            try {
                const res = await fetch('api/profile.php?action=delete_account', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: pwd })
                });
                const d = await res.json();
                if (d.success && d.redirect) {
                    window.location.href = d.redirect;
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Delete My Account Permanently';
                    showToast(d.error || 'Failed to delete account', 'error');
                }
            } catch (err) {
                btn.disabled = false;
                btn.textContent = 'Delete My Account Permanently';
                showToast('Error: ' + err.message, 'error');
            }
        });
    }
});
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>

