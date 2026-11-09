<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/dashboard.php');
}

$errors = [];
$name = '';
$email = '';
$academicLevel = 'Undergraduate';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    validateCsrfToken(true);

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $academicLevel = trim($_POST['academic_level'] ?? 'Undergraduate');
    $institution = trim($_POST['institution'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Check duplicate email
    if (empty($errors)) {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please log in.';
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user
                $insertStmt = $db->prepare("INSERT INTO users (name, email, password, role, academic_level, institution, status) VALUES (?, ?, ?, 'student', ?, ?, 'active')");
                $insertStmt->execute([$name, $email, $hashedPassword, $academicLevel, $institution]);
                $userId = (int)$db->lastInsertId();

                // Create initial settings
                $setStmt = $db->prepare("INSERT INTO user_settings (user_id, theme_preference, email_notifications) VALUES (?, 'light', 1)");
                $setStmt->execute([$userId]);

                // Fetch new user and log in automatically
                $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $userStmt->execute([$userId]);
                $newUser = $userStmt->fetch();

                logActivity($userId, 'registration', 'New student account created', $db);
                loginUser($newUser);

                redirect(BASE_URL . '/dashboard.php', 'Welcome to AI Study Assistant! Your account was created successfully.', 'success');
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'A database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - <?php echo APP_NAME; ?></title>
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
                <h4 class="fw-bold mb-1">Create Student Account</h4>
                <p class="text-muted small">Sign up to unlock personalized AI notes, quizzes, and tutoring</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm py-2">
                    <ul class="mb-0 ps-3 small">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" novalidate>
                <?php echo csrfField(); ?>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="e.g. Sahil Kumar" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="student@university.edu" required>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label for="academic_level" class="form-label small fw-semibold">Academic Level</label>
                        <select class="form-select" id="academic_level" name="academic_level">
                            <option value="High School">High School</option>
                            <option value="Undergraduate" <?php echo $academicLevel === 'Undergraduate' ? 'selected' : ''; ?>>Undergraduate (B.Tech / B.Sc)</option>
                            <option value="Postgraduate" <?php echo $academicLevel === 'Postgraduate' ? 'selected' : ''; ?>>Postgraduate (M.Tech / M.Sc)</option>
                            <option value="PhD / Research" <?php echo $academicLevel === 'PhD / Research' ? 'selected' : ''; ?>>PhD / Research</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="institution" class="form-label small fw-semibold">College / University</label>
                        <input type="text" class="form-control" id="institution" name="institution" placeholder="e.g. National Tech University">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Password (min 8 characters) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Create a strong password" required minlength="8">
                    </div>
                    <div class="password-meter mt-2">
                        <div id="password-strength-bar" class="password-meter-bar"></div>
                    </div>
                    <div id="password-strength-text" class="small mt-1" style="font-size:0.75rem;"></div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-person-plus-fill me-2"></i> Register Account
                </button>
            </form>

            <div class="text-center mt-4 pt-2 border-top">
                <span class="text-muted small">Already have an account?</span>
                <a href="login.php" class="text-primary fw-semibold text-decoration-none small ms-1">Log in here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>

