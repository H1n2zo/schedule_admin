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

// Determine current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-dark navbar-expand-lg">
    <div class="container-fluid">
        <!-- Brand -->
        <a href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>" class="navbar-brand">
              EVSU Event Management System
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
                    <!-- User Management -->
                    <li class="nav-item">
                        <a href="dashboard.php" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-users-cog"></i> Dashboard
                        </a>
                    </li>      

                    <!-- History Link -->
                    <li class="nav-item">
                        <a href="history.php" class="btn btn-info btn-sm me-2">
                            <i class="fas fa-history"></i> History
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