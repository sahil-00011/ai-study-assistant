<?php
/**
 * Shared Authenticated Sidebar
 * AI Study Assistant
 */
$currentPage = $currentPage ?? 'dashboard';
?>
<aside class="app-sidebar">
    <!-- Brand / Logo -->
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="sidebar-brand">
        <div class="sidebar-logo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div class="sidebar-brand-text">
            Study<span style="color: #818CF8;">Assistant</span>
        </div>
    </a>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">Academic AI Tools</div>

        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="nav-item-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/chatbot.php" class="nav-item-link <?php echo $currentPage === 'chatbot' ? 'active' : ''; ?>">
            <i class="bi bi-robot"></i>
            <span>AI Chatbot</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/notes.php" class="nav-item-link <?php echo $currentPage === 'notes' ? 'active' : ''; ?>">
            <i class="bi bi-journal-richtext"></i>
            <span>AI Notes</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/quiz.php" class="nav-item-link <?php echo $currentPage === 'quiz' ? 'active' : ''; ?>">
            <i class="bi bi-patch-question"></i>
            <span>Quiz Generator</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/pdf-summarizer.php" class="nav-item-link <?php echo $currentPage === 'pdf-summarizer' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-pdf"></i>
            <span>PDF Summarizer</span>
        </a>

        <div class="nav-section-title">Student Productivity</div>

        <a href="<?php echo BASE_URL; ?>/planner.php" class="nav-item-link <?php echo $currentPage === 'planner' ? 'active' : ''; ?>">
            <i class="bi bi-calendar2-check"></i>
            <span>Study Planner</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/history.php" class="nav-item-link <?php echo $currentPage === 'history' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            <span>Learning History</span>
        </a>

        <div class="nav-section-title">Account</div>

        <a href="<?php echo BASE_URL; ?>/profile.php" class="nav-item-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i>
            <span>Profile</span>
        </a>

        <a href="<?php echo BASE_URL; ?>/settings.php" class="nav-item-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
            <i class="bi bi-sliders"></i>
            <span>Settings</span>
        </a>

        <?php if (isAdmin()): ?>
            <div class="nav-section-title text-danger">Administration</div>
            <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="nav-item-link text-warning">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Admin Panel</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>/logout.php" class="nav-item-link text-danger mb-2">
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Out</span>
        </a>
    </div>
</aside>

