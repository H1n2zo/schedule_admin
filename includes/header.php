<?php
/**
 * EVSU Event Management System
 * Common Header Include
 * File: includes/header.php
 */

// Prevent direct access
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

// Get page title from variable or use default
$pageTitle = isset($pageTitle) ? $pageTitle : 'EVSU Event Management System';

// Get custom CSS files array
$customCSS = isset($customCSS) ? $customCSS : [];

// Get custom JS files array
$customJS = isset($customJS) ? $customJS : [];

// Get body class if set
$bodyClass = isset($bodyClass) ? $bodyClass : '';

// Flash message system
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="EVSU Event Management System - Streamline event requests and volunteer coordination">
    <meta name="author" content="EVSU">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" 
          crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Global Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Page-specific CSS -->
    <?php foreach ($customCSS as $css): ?>
        <link rel="stylesheet" href="assets/css/<?= htmlspecialchars($css) ?>.css">
    <?php endforeach; ?>
    
    <!-- Additional head content -->
    <?php if (isset($additionalHead)): ?>
        <?= $additionalHead ?>
    <?php endif; ?>
</head>
<body<?= $bodyClass ? ' class="' . htmlspecialchars($bodyClass) . '"' : '' ?>>

<?php if ($flashMessage): ?>
<div class="container mt-3">
    <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?> alert-dismissible fade show" role="alert">
        <?php
        $icon = '';
        switch ($flashMessage['type']) {
            case 'success':
                $icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
                $icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'warning':
                $icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'info':
                $icon = '<i class="fas fa-info-circle me-2"></i>';
                break;
        }
        echo $icon . htmlspecialchars($flashMessage['message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>

<script>
// Auto-dismiss flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const flashAlert = document.querySelector('.alert-dismissible');
    if (flashAlert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(flashAlert);
            bsAlert.close();
        }, 5000);
    }
});
</script>
<?php endif; ?>