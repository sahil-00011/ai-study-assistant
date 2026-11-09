<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } else {
        redirect(BASE_URL . '/dashboard.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    validateCsrfToken(true);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email and password.';
    } else {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account has been deactivated. Please contact support.';
                } else {
                    loginUser($user, $remember);

                    if ($user['role'] === 'admin') {
                        redirect(BASE_URL . '/admin/dashboard.php', 'Welcome back, Administrator!', 'success');
                    } else {
                        redirect(BASE_URL . '/dashboard.php', 'Welcome back, ' . htmlspecialchars($user['name']) . '!', 'success');
                    }
                }
            } else {
                $error = 'Invalid email address or password.';
                // Log failed attempt
                logActivity(null, 'failed_login', "Failed login attempt for email: {$email}", $db);
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'A system error occurred. Please check your database connection.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>

    <div class="auth-wrapper">
        <div class="card auth-card p-4 p-md-5">
            <div class="text-center mb-4">
                <a href="index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
                    <div class="sidebar-logo">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <span class="fs-4 fw-bold text-primary">AI Study Assistant</span>
                </a>
                <h4 class="fw-bold mb-1">Student & Faculty Login</h4>
                <p class="text-muted small">Sign in to resume your studies, notes, and AI chats</p>
            </div>

            <?php echo renderFlashAlerts(); ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger shadow-sm py-2 d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div class="small"><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" novalidate>
                <?php echo csrfField(); ?>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="name@university.edu" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label small fw-semibold mb-0">Password <span class="text-danger">*</span></label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small text-muted" for="remember">
                            Remember me
                        </label>
                    </div>
                    <span class="small text-muted">Protected with CSRF</span>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Log In
                </button>
            </form>

            <div class="text-center mt-4 pt-2 border-top">
                <span class="text-muted small">Don't have an account?</span>
                <a href="register.php" class="text-primary fw-semibold text-decoration-none small ms-1">Create free account</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>

