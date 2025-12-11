<?php
/**
 * EVSU Event Management System
 * Common Navigation Bar
 * File: includes/navbar.php
 */

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$fullName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest';

// Get pending actions count if admin
$pendingCount = 0;
if ($isAdmin) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) FROM event_requests WHERE status = 'pending_notification'");
        $pendingCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Handle error silently
        $pendingCount = 0;
    }
}

// Determine current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-dark navbar-expand-lg">
    <div class="container-fluid">
        <!-- Brand -->
        <a href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>" class="navbar-brand">
            🎓 EVSU Event Management System
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($isLoggedIn && $isAdmin): ?>
                    <!-- Dashboard Link -->
                    <li class="nav-item">
                        <a href="dashboard.php" class="btn btn-light btn-sm me-2 <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Pending Actions -->
                    <li class="nav-item">
                        <a href="pending_actions.php" class="btn btn-warning btn-sm me-2 position-relative">
                            <i class="fas fa-bell"></i> Pending Actions
                            <?php if ($pendingCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $pendingCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- User Management -->
                    <li class="nav-item">
                        <a href="manage_users.php" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-users-cog"></i> Manage Users
                        </a>
                    </li>
                    
                    <!-- User Info -->
                    <li class="nav-item">
                        <span class="navbar-text text-white me-2">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($fullName) ?>
                        </span>
                    </li>
                    
                    <!-- Logout -->
                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    
                <?php elseif ($isLoggedIn): ?>
                    <!-- Regular User (if you have non-admin logged in users) -->
                    <li class="nav-item">
                        <span class="navbar-text text-white me-2">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($fullName) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    
                <?php else: ?>
                    <!-- Guest/Public User -->
                    <li class="nav-item">
                        <a href="index.php" class="btn btn-light btn-sm me-2 <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="submit_request.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-calendar-plus"></i> Submit Request
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>