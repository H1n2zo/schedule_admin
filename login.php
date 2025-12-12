<?php
/**
 * EVSU Event Management System
 * Admin Login Page - Password Only with Register
 * File: login.php
 */

require_once 'config.php';

// Set page configuration
$pageTitle = 'Admin Login - EVSU Admin Panel';
$bodyClass = 'auth-page';
$customCSS = ['auth'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login logic - password only
    $password = $_POST['password'];
    
    $db = getDB();
    
    // Get all users and check password
    $stmt = $db->query("SELECT id, email, password, full_name, role FROM users");
    $users = $stmt->fetchAll();
    
    $loginSuccess = false;
    foreach ($users as $user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $loginSuccess = true;
            header('Location: dashboard.php');
            exit;
        }
    }
    
    if (!$loginSuccess) {
        $error = 'Invalid password';
    }
}

// Include header
include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-header">
        <h1>🎓 EVSU Admin Panel</h1>
        <p class="text-muted">Administrator Login</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" data-warn-unsaved="false">
        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required autofocus
                   placeholder="Enter your password">
        </div>
        
        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>
    
    <div class="text-center mt-3">
        <p class="text-muted mb-2">Don't have an account?</p>
        <a href="register.php" class="btn btn-outline-primary">
            <i class="fas fa-user-plus"></i> Register New Account
        </a>
    </div>
    
    <div class="back-link">
        <a href="index.php">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>