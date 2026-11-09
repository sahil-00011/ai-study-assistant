<?php
/**
 * Shared Authenticated Header & Topbar
 * AI Study Assistant
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Authenticate session
requireAuth();
$currentUser = getCurrentUser();

$pageTitle = $pageTitle ?? 'Dashboard';
$currentPage = $currentPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(getCsrfToken()); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo APP_NAME; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Application Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/responsive.css">

    <!-- Chart.js (for analytics) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="sidebar-backdrop"></div>

<div class="app-container">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <main class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="topbar-icon-btn d-lg-none sidebar-toggle-btn" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="topbar-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>

            <div class="topbar-right">
                <!-- Quick AI Search / Ask Trigger -->
                <div class="d-none d-md-flex align-items-center">
                    <a href="<?php echo BASE_URL; ?>/chatbot.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 rounded-pill px-3 py-1">
                        <i class="bi bi-stars text-primary"></i>
                        <span class="small text-muted">Ask AI Tutor anything...</span>
                        <kbd class="bg-body-secondary text-muted border px-1 py-0 small" style="font-size: 0.7rem;">/chat</kbd>
                    </a>
                </div>

                <!-- Theme Toggle Button -->
                <button type="button" class="topbar-icon-btn theme-toggle-btn" title="Toggle Light/Dark Theme">
                    <i class="bi bi-moon-stars-fill theme-toggle-icon"></i>
                </button>

                <!-- User Dropdown Menu -->
                <div class="dropdown">
                    <button class="btn p-0 border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle" style="background: var(--primary-color);">
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
                        <span class="d-none d-md-inline fw-semibold small text-start">
                            <?php echo htmlspecialchars($currentUser['name'] ?? 'Student'); ?>
                            <i class="bi bi-chevron-down ms-1 text-muted" style="font-size: 0.75rem;"></i>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="border-radius: 12px; min-width: 200px;">
                        <li class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($currentUser['name'] ?? 'Student'); ?></div>
                            <div class="small text-muted text-truncate"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
                            <span class="badge badge-primary-soft mt-1"><?php echo htmlspecialchars($currentUser['academic_level'] ?? 'Student'); ?></span>
                        </li>
                        <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/profile.php"><i class="bi bi-person text-primary"></i> My Profile</a></li>
                        <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/settings.php"><i class="bi bi-gear text-secondary"></i> Settings</a></li>
                        <?php if (isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger fw-semibold" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="bi bi-shield-lock-fill"></i> Admin Portal</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash alerts display -->
        <div class="px-3 pt-3">
            <?php echo renderFlashAlerts(); ?>
        </div>

