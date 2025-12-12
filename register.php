<?php
/**
 * EVSU Event Management System
 * Registration Page - Standalone
 * File: register.php
 */

require_once 'config.php';

// Set page configuration
$pageTitle = 'Register - EVSU Admin Panel';
$bodyClass = 'auth-page';
$customCSS = ['auth'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration logic
    $fullName = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            $db = getDB();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email address already registered';
            } else {
                // Insert new user as admin
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (email, password, full_name, role, created_at)
                    VALUES (?, ?, ?, 'user', NOW())
                ");
                $stmt->execute([$email, $hashedPassword, $fullName]);
                
                $success = true;
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="login-container">
    <?php if ($success): ?>
        <!-- Success Message -->
        <div class="success-container text-center">
            <div class="success-icon mx-auto mb-4" style="width: 100px; height: 100px; background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(46, 125, 50, 0.3);">
                <i class="fas fa-check" style="font-size: 3rem; color: white;"></i>
            </div>
            <h2 style="color: var(--evsu-maroon); font-weight: 800; margin-bottom: 20px;">
                Registration Successful!
            </h2>
            <p style="font-size: 1.1rem; color: #6c757d; margin-bottom: 30px;">
                Your administrator account has been created successfully.<br>
                You can now login to access the admin panel.
            </p>
            <div class="d-grid gap-2">
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Registration Form -->
        <div class="login-header">
            <h1>🎓 EVSU Admin Panel</h1>
            <p class="text-muted">Create Administrator Account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>Administrator Registration</strong>
            <p class="mb-0 mt-2" style="font-size: 0.9rem;">
                This form creates a new administrator account with full access to the event management system.
            </p>
        </div>
        
        <form method="POST" data-warn-unsaved="false">
            <div class="mb-3">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control" required autofocus 
                       placeholder="Juan Dela Cruz" 
                       value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
                <small class="text-muted">Enter your complete name as it will appear in the system</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required 
                       placeholder="admin@evsu.edu.ph" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                <small class="text-muted">Use your official EVSU email address</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required 
                       placeholder="Enter password" id="password" minlength="6">
                <small class="text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Confirm Password <span class="required">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required 
                       placeholder="Re-enter password" id="confirmPassword" minlength="6">
                <small class="text-muted" id="passwordMatch"></small>
            </div>
            
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                <label class="form-check-label" for="agreeTerms">
                    I agree to use this account responsibly and according to EVSU policies
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create Administrator Account
            </button>
        </form>
        
        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Already have an account? Login here
            </a>
            <br><br>
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.login-container {
    max-width: 500px;
}

.success-container {
    padding: 40px 20px;
}

.back-link {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.back-link a {
    color: var(--evsu-maroon);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
    display: inline-block;
    margin: 5px 0;
}

.back-link a:hover {
    text-decoration: underline;
    color: var(--maroon-dark);
}

.required {
    color: #dc3545;
    font-weight: 700;
}

#passwordMatch {
    display: block;
    margin-top: 5px;
    font-weight: 600;
}

#passwordMatch.match {
    color: #2e7d32;
}

#passwordMatch.no-match {
    color: #c62828;
}

.form-check-input:checked {
    background-color: var(--evsu-maroon);
    border-color: var(--evsu-maroon);
}

.form-check-input:focus {
    border-color: var(--evsu-gold);
    box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordMatch = document.getElementById('passwordMatch');
    const submitBtn = document.getElementById('submitBtn');
    const agreeTerms = document.getElementById('agreeTerms');
    
    function checkPasswordMatch() {
        if (confirmPassword.value === '') {
            passwordMatch.textContent = '';
            passwordMatch.className = '';
            return;
        }
        
        if (password.value === confirmPassword.value) {
            passwordMatch.textContent = '✓ Passwords match';
            passwordMatch.className = 'text-muted match';
        } else {
            passwordMatch.textContent = '✗ Passwords do not match';
            passwordMatch.className = 'text-muted no-match';
        }
    }
    
    function updateSubmitButton() {
        const passwordsMatch = password.value === confirmPassword.value && password.value !== '';
        const termsAgreed = agreeTerms.checked;
        const passwordLongEnough = password.value.length >= 6;
        
        if (passwordsMatch && termsAgreed && passwordLongEnough) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('disabled');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('disabled');
        }
    }
    
    password.addEventListener('input', function() {
        checkPasswordMatch();
        updateSubmitButton();
    });
    
    confirmPassword.addEventListener('input', function() {
        checkPasswordMatch();
        updateSubmitButton();
    });
    
    agreeTerms.addEventListener('change', updateSubmitButton);
    
    // Show password strength
    password.addEventListener('input', function() {
        const length = this.value.length;
        let strength = '';
        
        if (length === 0) {
            strength = '';
        } else if (length < 6) {
            strength = '<span class="text-danger">Too short</span>';
        } else if (length < 8) {
            strength = '<span class="text-warning">Fair</span>';
        } else if (length < 12) {
            strength = '<span class="text-info">Good</span>';
        } else {
            strength = '<span class="text-success">Strong</span>';
        }
        
        const strengthDiv = this.nextElementSibling;
        if (strength) {
            strengthDiv.innerHTML = 'Password strength: ' + strength;
        }
    });
    
    // Initial check
    updateSubmitButton();
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>