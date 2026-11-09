<?php
/**
 * Shared Authenticated Footer
 * AI Study Assistant
 */
?>
        <footer class="mt-auto py-3 px-4 border-top bg-body-tertiary">
            <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted">
                <div>
                    <strong><?php echo APP_NAME; ?></strong> &copy; <?php echo date('Y'); ?>. Designed for College & University Students.
                </div>
                <div class="d-flex gap-3">
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="text-decoration-none text-muted">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>/chatbot.php" class="text-decoration-none text-muted">AI Tutor</a>
                    <a href="<?php echo BASE_URL; ?>/settings.php" class="text-decoration-none text-muted">Support & Settings</a>
                </div>
            </div>
        </footer>
    </main>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>

<?php if (!empty($extraScripts)): ?>
    <?php echo $extraScripts; ?>
<?php endif; ?>

</body>
</html>

