<?php
/**
 * Authentication and Authorization System
 * AI Study Assistant
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is currently logged in
 *
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Check if current user is an administrator
 *
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current authenticated user details from database
 *
 * @param bool $refresh Force reload from DB
 * @return array|null
 */
function getCurrentUser(bool $refresh = false): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser !== null && !$refresh) {
        return $cachedUser;
    }

    try {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT id, name, email, role, avatar, academic_level, institution, subject_interests, status, created_at, last_login_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            // User record deleted
            logoutUser();
            return null;
        }

        // Check if account was suspended/deactivated
        if ($user['status'] !== 'active') {
            logoutUser();
            redirect(BASE_URL . '/login.php', 'Your account has been deactivated. Please contact support.', 'danger');
            return null;
        }

        $cachedUser = $user;
        return $user;
    } catch (Exception $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Require authentication; redirects to login if not authenticated
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $isApi = (defined('IS_API') && IS_API) || 
                 (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        
        if ($isApi) {
            jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        redirect(BASE_URL . '/login.php', 'Please log in to access this page.', 'warning');
    }

    // Verify user is active
    $user = getCurrentUser();
    if (!$user) {
        redirect(BASE_URL . '/login.php', 'Session expired. Please log in again.', 'warning');
    }
}

/**
 * Require admin privileges; redirects or denies if not an admin
 */
function requireAdmin(): void {
    requireAuth();

    if (!isAdmin()) {
        $isApi = (defined('IS_API') && IS_API) || 
                 (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isApi) {
            jsonResponse(['success' => false, 'error' => 'Unauthorized: Admin privileges required.'], 403);
        }

        redirect(BASE_URL . '/dashboard.php', 'Access denied. You do not have permission to view the Admin panel.', 'danger');
    }
}

/**
 * Log in a user and set up their session
 *
 * @param array $user User database record
 * @param bool $remember Remember me option
 */
function loginUser(array $user, bool $remember = false): void {
    // Regenerate session ID to mitigate session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['LAST_ACTIVITY'] = time();

    // Update last_login_at in database
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // Ensure user_settings row exists
        $stmt = $db->prepare("INSERT INTO user_settings (user_id, theme_preference, email_notifications) VALUES (?, 'light', 1) ON DUPLICATE KEY UPDATE user_id=user_id");
        $stmt->execute([$user['id']]);

        // Load theme preference into session
        $stmt = $db->prepare("SELECT theme_preference FROM user_settings WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $pref = $stmt->fetchColumn();
        $_SESSION['theme'] = $pref ?: 'light';

        logActivity($user['id'], 'login', 'User logged in successfully', $db);
    } catch (Exception $e) {
        error_log("Login post-processing error: " . $e->getMessage());
    }
}

/**
 * Cleanly log out user
 */
function logoutUser(): void {
    if (isLoggedIn()) {
        try {
            logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        } catch (Exception $e) {}
    }

    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

