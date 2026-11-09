<?php
/**
 * Core Helper Functions
 * AI Study Assistant
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize string input
 *
 * @param mixed $data
 * @return mixed
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Output JSON response and exit
 *
 * @param array $data
 * @param int $statusCode
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Redirect to a specific URL with an optional flash message
 *
 * @param string $url
 * @param string|null $message
 * @param string $type 'success', 'danger', 'warning', 'info'
 */
function redirect(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message !== null) {
        setFlash($type, $message);
    }
    header("Location: " . $url);
    exit;
}

/**
 * Set flash message in session
 *
 * @param string $type
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash messages
 *
 * @return array
 */
function getFlash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as Bootstrap alerts
 *
 * @return string
 */
function renderFlashAlerts(): string {
    $messages = getFlash();
    if (empty($messages)) {
        return '';
    }

    $html = '';
    foreach ($messages as $msg) {
        $type = in_array($msg['type'], ['success', 'danger', 'warning', 'info']) ? $msg['type'] : 'info';
        $icon = match($type) {
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default   => 'bi-info-circle-fill'
        };
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi ' . $icon . ' me-2 fs-5"></i>
                    <div>' . htmlspecialchars($msg['message']) . '</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
    }
    return $html;
}

/**
 * Log user activity into activity_logs table
 *
 * @param int|null $userId
 * @param string $type
 * @param string $description
 * @param PDO|null $pdo
 * @return bool
 */
function logActivity(?int $userId, string $type, string $description, ?PDO $pdo = null): bool {
    try {
        $db = $pdo ?: getDbConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $type, $description, $ip]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Rate Limiter using session counter
 *
 * @param string $actionKey Unique key for the rate limited action
 * @param int $maxRequests Max permitted requests in window
 * @param int $decaySeconds Time window in seconds
 * @return bool True if allowed, False if exceeded
 */
function checkRateLimit(string $actionKey, int $maxRequests = 30, int $decaySeconds = 60): bool {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }

    $now = time();
    $bucket = $_SESSION['rate_limits'][$actionKey] ?? [
        'count'     => 0,
        'reset_at' => $now + $decaySeconds
    ];

    if ($now > $bucket['reset_at']) {
        $bucket = [
            'count'     => 1,
            'reset_at' => $now + $decaySeconds
        ];
    } else {
        $bucket['count']++;
    }

    $_SESSION['rate_limits'][$actionKey] = $bucket;

    return $bucket['count'] <= $maxRequests;
}

/**
 * Format date for friendly display
 *
 * @param string|int|null $date
 * @param string $format
 * @return string
 */
function formatDate($date, string $format = 'M d, Y h:i A'): string {
    if (empty($date)) return 'N/A';
    $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Human-readable relative time (e.g. "5 mins ago", "2 hours ago")
 *
 * @param string|int $datetime
 * @return string
 */
function timeAgo($datetime): string {
    $time = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $time);
    }
}

