</div>
</main>
<footer class="text-center py-4 text-gray-600">
    <p>&copy; <?php echo date('Y'); ?> <?php echo getenv('COMPANY_NAME'); ?>. All rights reserved.</p>
</footer>
<?php
// Render page-specific scripts if they exist
if (isset($pageScripts) && is_array($pageScripts)): ?>
    <?php foreach ($pageScripts as $script): ?>
        <?php if (is_array($script)): ?>
            <?php if ($script['type'] === 'url'): ?>
                <script src="<?php echo htmlspecialchars($script['content']); ?>" <?php echo isset($script['defer']) ? ' defer' : ''; ?><?php echo isset($script['async']) ? ' async' : ''; ?>></script>
            <?php elseif ($script['type'] === 'inline'): ?>
                <script>
                    <?php echo $script['content']; ?>
                </script>
            <?php endif; ?>
        <?php else: ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
