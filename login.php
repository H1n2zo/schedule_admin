<?php
/**
 * EVSU Event Management System
 * Admin Login Page - Updated with External Assets
 * File: login.php
 */

require_once 'config.php';

// Set page configuration
$pageTitle = 'Admin Login - EVSU Admin Panel';
$bodyClass = 'auth-page';
$customCSS = ['auth']; // Loads assets/css/auth.css

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login logic
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email, password, full_name, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
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
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required autofocus 
                   placeholder="admin@evsu.edu.ph" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required 
                   placeholder="Enter your password">
        </div>
        
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="rememberMe">
            <label class="form-check-label" for="rememberMe">
                Remember me
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
    </form>
    
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