<?php
/**
 * Application Configuration
 * AI Study Assistant
 */

// Prevent multiple definitions
if (defined('APP_INIT')) {
    return;
}
define('APP_INIT', true);

// Environment: 'development' or 'production'
define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Application Information
define('APP_NAME', 'AI Study Assistant');
define('APP_TAGLINE', 'Your Personal AI-Powered Academic Companion');
define('APP_VERSION', '1.0.0');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('DATABASE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'database');

// Load environment variables from .env file if present
$envFile = ROOT_PATH . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Determine Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Normalize base URL so it points to /ai-study-assistant
if (strpos($scriptDir, '/admin') !== false) {
    $scriptDir = preg_replace('/\/admin.*$/', '', $scriptDir);
}
if (strpos($scriptDir, '/api') !== false) {
    $scriptDir = preg_replace('/\/api.*$/', '', $scriptDir);
}
$basePath = rtrim($scriptDir, '/');
if (empty($basePath)) {
    $basePath = '/ai-study-assistant';
}
define('BASE_URL', $protocol . $host . $basePath);

// Session Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Session Timeout (30 minutes of inactivity)
define('SESSION_LIFETIME', 1800);
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
    // Last request was more than 30 minutes ago
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['LAST_ACTIVITY'] = time();

