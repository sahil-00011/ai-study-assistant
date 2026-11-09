<?php
/**
 * CSRF Protection Helper
 * AI Study Assistant
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Get or generate the current session CSRF token
 *
 * @return string
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate a hidden input field containing the CSRF token
 *
 * @return string
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from POST body, JSON payload, or Request Header
 *
 * @param bool $terminate If true, dies with 403 HTTP status on failure
 * @return bool
 */
function validateCsrfToken(bool $terminate = true): bool {
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($sessionToken)) {
        if ($terminate) {
            handleCsrfFailure('CSRF session token missing. Please refresh the page and try again.');
        }
        return false;
    }

    $submittedToken = '';

    // Check POST data
    if (!empty($_POST['csrf_token'])) {
        $submittedToken = $_POST['csrf_token'];
    }
    // Check custom request headers (e.g. for Fetch / AJAX requests)
    elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    // Check raw JSON body if request is application/json
    else {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if (is_array($jsonData) && !empty($jsonData['csrf_token'])) {
                $submittedToken = $jsonData['csrf_token'];
            }
        }
    }

    if (empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
        if ($terminate) {
            handleCsrfFailure('Invalid or expired CSRF token. Please refresh the page.');
        }
        return false;
    }

    return true;
}

/**
 * Handle CSRF verification failure
 *
 * @param string $message
 */
function handleCsrfFailure(string $message): void {
    http_response_code(403);
    
    // If request expects JSON or is API
    $isJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
        || (defined('IS_API') && IS_API);

    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => $message
        ]);
        exit;
    }

    // HTML response
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head><title>403 Forbidden - Security Error</title>
    <style>body{font-family:system-ui,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .box{background:#fff;padding:2rem;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.05);max-width:480px;text-align:center;}
    h1{color:#ef4444;margin-top:0;font-size:1.5rem;} p{color:#64748b;} a{display:inline-block;margin-top:1rem;padding:0.6rem 1.2rem;background:#4f46e5;color:#fff;text-decoration:none;border-radius:6px;}</style></head>
    <body><div class='box'><h1>Security Verification Failed</h1><p>" . htmlspecialchars($message) . "</p><a href='javascript:history.back()'>&larr; Go Back</a></div></body></html>";
    exit;
}

