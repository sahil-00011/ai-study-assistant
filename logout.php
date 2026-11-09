<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
redirect(BASE_URL . '/login.php', 'You have been logged out successfully.', 'info');

