<?php
/**
 * EVSU Event Management System
 * Common Footer Include
 * File: includes/footer.php
 */
?>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" 
            crossorigin="anonymous"></script>
    
    <!-- Global Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Page-specific JS -->
    <?php if (isset($customJS) && is_array($customJS)): ?>
        <?php foreach ($customJS as $js): ?>
            <script src="assets/js/<?= htmlspecialchars($js) ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional footer content -->
    <?php if (isset($additionalFooter)): ?>
        <?= $additionalFooter ?>
    <?php endif; ?>
    
    <!-- Inline scripts if needed -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?= $inlineScripts ?>
        </script>
    <?php endif; ?>

</body>
</html>