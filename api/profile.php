<?php
/**
 * User Profile & Settings API Endpoint
 * AI Study Assistant
 */

define('IS_API', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
}

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$db = getDbConnection();

$action = $_GET['action'] ?? '';
$rawInput = file_get_contents('php://input');
$inputData = !empty($rawInput) ? json_decode($rawInput, true) : [];
if (!empty($inputData['action'])) {
    $action = $inputData['action'];
}

switch ($action) {
    case 'update_profile':
        validateCsrfToken(true);

        $name = trim($inputData['name'] ?? ($_POST['name'] ?? ''));
        $academicLevel = trim($inputData['academic_level'] ?? ($_POST['academic_level'] ?? 'Undergraduate'));
        $institution = trim($inputData['institution'] ?? ($_POST['institution'] ?? ''));
        $subjectInterests = trim($inputData['subject_interests'] ?? ($_POST['subject_interests'] ?? ''));

        if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
            jsonResponse(['success' => false, 'error' => 'Full name must be between 2 and 100 characters.'], 400);
        }

        try {
            $stmt = $db->prepare("UPDATE users SET name = ?, academic_level = ?, institution = ?, subject_interests = ? WHERE id = ?");
            $stmt->execute([$name, $academicLevel, $institution, $subjectInterests, $userId]);

            $_SESSION['user_name'] = $name;
            logActivity($userId, 'profile_update', 'Updated personal profile details', $db);

            jsonResponse(['success' => true, 'message' => 'Profile updated successfully!']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'change_password':
        validateCsrfToken(true);

        $currentPassword = $inputData['current_password'] ?? ($_POST['current_password'] ?? '');
        $newPassword = $inputData['new_password'] ?? ($_POST['new_password'] ?? '');
        $confirmPassword = $inputData['confirm_password'] ?? ($_POST['confirm_password'] ?? '');

        if (empty($currentPassword) || empty($newPassword)) {
            jsonResponse(['success' => false, 'error' => 'All password fields are required.'], 400);
        }

        if (strlen($newPassword) < 8) {
            jsonResponse(['success' => false, 'error' => 'New password must be at least 8 characters long.'], 400);
        }

        if ($newPassword !== $confirmPassword) {
            jsonResponse(['success' => false, 'error' => 'New password and confirmation do not match.'], 400);
        }

        try {
            // Verify current password from database
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($currentPassword, $hash)) {
                jsonResponse(['success' => false, 'error' => 'Current password is incorrect.'], 400);
            }

            // Hash and update
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $updStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updStmt->execute([$newHash, $userId]);

            logActivity($userId, 'password_change', 'Updated account password', $db);

            jsonResponse(['success' => true, 'message' => 'Password changed successfully!']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'update_theme':
        $theme = in_array($inputData['theme'] ?? '', ['light', 'dark']) ? $inputData['theme'] : 'light';
        try {
            $stmt = $db->prepare("UPDATE user_settings SET theme_preference = ? WHERE user_id = ?");
            $stmt->execute([$theme, $userId]);
            $_SESSION['theme'] = $theme;
            jsonResponse(['success' => true, 'theme' => $theme]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
        break;

    case 'update_notifications':
        validateCsrfToken(true);
        $enabled = !empty($inputData['email_notifications']) ? 1 : 0;
        try {
            $stmt = $db->prepare("UPDATE user_settings SET email_notifications = ? WHERE user_id = ?");
            $stmt->execute([$enabled, $userId]);
            jsonResponse(['success' => true, 'message' => 'Notification preferences updated.']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
        break;

    case 'save_openai_key':
        validateCsrfToken(true);
        if (!isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Administrator privileges required to update API keys.'], 403);
        }

        $key = trim($inputData['api_key'] ?? ($_POST['api_key'] ?? ''));
        $model = trim($inputData['model'] ?? ($_POST['model'] ?? 'gpt-4o-mini'));

        if (empty($key)) {
            jsonResponse(['success' => false, 'error' => 'OpenAI API key cannot be empty.'], 400);
        }

        $localConfigFile = dirname(__DIR__) . '/config/openai.local.php';
        $phpCode = "<?php\n// Local OpenAI Configuration (Saved via Settings)\ndefine('OPENAI_API_KEY', " . var_export($key, true) . ");\ndefine('OPENAI_MODEL', " . var_export($model, true) . ");\n";

        if (file_put_contents($localConfigFile, $phpCode) === false) {
            jsonResponse(['success' => false, 'error' => 'Failed to write config/openai.local.php. Please check file permissions.'], 500);
        }

        logActivity($userId, 'admin_openai_key', 'Configured OpenAI API key', $db);
        jsonResponse(['success' => true, 'message' => 'OpenAI API key saved successfully! You can now use all AI features.']);
        break;

    case 'delete_account':
        validateCsrfToken(true);
        $password = $inputData['password'] ?? ($_POST['password'] ?? '');

        if (empty($password)) {
            jsonResponse(['success' => false, 'error' => 'Password is required to confirm account deletion.'], 400);
        }

        try {
            $stmt = $db->prepare("SELECT password, role FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                jsonResponse(['success' => false, 'error' => 'Password verification failed.'], 400);
            }

            // Prevent deleting the main system admin if ID 1
            if ($user['role'] === 'admin' && $userId === 1) {
                jsonResponse(['success' => false, 'error' => 'The default primary administrator account cannot be deleted.'], 403);
            }

            // Delete user - ON DELETE CASCADE cleans up conversations, notes, quizzes, tasks
            $delStmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $delStmt->execute([$userId]);

            logoutUser();
            jsonResponse(['success' => true, 'redirect' => BASE_URL . '/login.php']);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action.'], 400);
        break;
}

