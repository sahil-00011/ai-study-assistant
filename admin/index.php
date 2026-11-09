<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php', 'Please log in with administrator credentials.', 'warning');
}

if (!isAdmin()) {
    redirect(BASE_URL . '/dashboard.php', 'Access denied. Administrator privileges required.', 'danger');
}

redirect(BASE_URL . '/admin/dashboard.php');

