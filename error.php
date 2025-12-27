<?php
/**
 * CRCY Dispatch System
 * Error Page
 */

require_once 'config.php';

$pageTitle = 'Error - CRCY Dispatch';
$errorCode = $_GET['code'] ?? '500';
$errorMessage = $_GET['message'] ?? 'An unexpected error occurred';

// Sanitize inputs
$errorCode = preg_replace('/[^0-9]/', '', $errorCode);
$errorMessage = sanitizeInput($errorMessage);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-triangle display-1 text-warning"></i>
            </div>
            
            <h1 class="display-4 text-danger mb-3"><?= htmlspecialchars($errorCode) ?></h1>
            
            <?php if ($errorCode === '404'): ?>
                <h2 class="mb-4">Page Not Found</h2>
                <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
            <?php elseif ($errorCode === '403'): ?>
                <h2 class="mb-4">Access Denied</h2>
                <p class="text-muted mb-4">You don't have permission to access this resource.</p>
            <?php elseif ($errorCode === '500'): ?>
                <h2 class="mb-4">Server Error</h2>
                <p class="text-muted mb-4">Something went wrong on our end. Please try again later.</p>
            <?php else: ?>
                <h2 class="mb-4">Error</h2>
                <p class="text-muted mb-4"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Go Home
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Go Back
                </button>
                <?php if (isAdmin()): ?>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($errorCode === '500'): ?>
                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        If this problem persists, please contact the CRCY administrator.
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>