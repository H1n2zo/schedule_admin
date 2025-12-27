<?php
/**
 * CRCY Dispatch - Admin Registration
 * Only super_admin can register new admins
 */

require_once 'config.php';
requireAdmin();

// Only super_admin can register new admins
if ($_SESSION['role'] !== 'super_admin') {
    $_SESSION['error'] = 'Only super administrators can register new admin users.';
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Register New Admin - CRCY Dispatch';
$bodyClass = 'auth-page';
$customCSS = ['auth'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'admin';

    // Validation
    $validationErrors = [];

    // Username validation
    if (empty($username) || strlen($username) < 3) {
        $validationErrors[] = 'Username must be at least 3 characters long';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $validationErrors[] = 'Username can only contain letters, numbers, and underscores';
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors[] = 'Please provide a valid email address';
    }

    // Full name validation
    if (empty($fullName) || strlen($fullName) < 3) {
        $validationErrors[] = 'Full name must be at least 3 characters long';
    }

    // Password validation
    if (strlen($password) < 8) {
        $validationErrors[] = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $validationErrors[] = 'Passwords do not match';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $validationErrors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
    }

    // Role validation
    if (!in_array($role, ['admin', 'moderator'])) {
        $validationErrors[] = 'Invalid role selected';
    }

    if (!empty($validationErrors)) {
        $error = implode('. ', $validationErrors);
    } else {
        try {
            $db = getDB();

            // Check if username already exists
            $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                // Check if email already exists
                $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already exists. Please use a different email address.';
                } else {
                    // Hash password
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new admin
                    $stmt = $db->prepare("
                        INSERT INTO admin_users 
                        (username, email, password_hash, full_name, role, status, created_by)
                        VALUES (?, ?, ?, ?, ?, 'active', ?)
                    ");
                    $stmt->execute([
                        $username,
                        $email,
                        $passwordHash,
                        $fullName,
                        $role,
                        $_SESSION['user_id']
                    ]);

                    $newAdminId = $db->lastInsertId();

                    // Log the admin creation
                    $stmt = $db->prepare("
                        INSERT INTO admin_activity_log (admin_id, action, description, ip_address, user_agent)
                        VALUES (?, 'admin_created', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        "Created new admin: $username ($role)",
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);

                    logSecurityEvent('admin_registered', [
                        'new_admin_id' => $newAdminId,
                        'username' => $username,
                        'email' => $email,
                        'role' => $role,
                        'created_by' => $_SESSION['user_id']
                    ]);

                    $_SESSION['registration_success'] = "New admin '$username' has been registered successfully!";
                    header('Location: manage_users.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            logSecurityEvent('registration_error', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            $error = 'Registration error. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="modern-login-container">
    <!-- Logo and Branding -->
    <div class="login-brand">
        <div class="brand-icon">
            <i class="bi bi-person-plus"></i>
        </div>
        <h1>REGISTER NEW ADMIN</h1>
        <p class="brand-subtitle">Create a new administrator account</p>
        <div class="brand-divider"></div>
    </div>

    <!-- Registration Form -->
    <div class="login-form-wrapper">
        <?php if ($error): ?>
            <div class="alert alert-danger modern-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" data-warn-unsaved="false" class="modern-form">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user"></i>
                    Username <span class="text-danger">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="text" name="username" class="form-control modern-input" required
                        placeholder="Choose a unique username" 
                        pattern="[a-zA-Z0-9_]{3,}" 
                        title="Username must be at least 3 characters (letters, numbers, underscores only)"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <div class="input-focus-line"></div>
                </div>
                <small class="text-muted">Letters, numbers, and underscores only</small>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-envelope"></i>
                    Email Address <span class="text-danger">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="email" name="email" class="form-control modern-input" required
                        placeholder="admin@example.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="input-focus-line"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-id-card"></i>
                    Full Name <span class="text-danger">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="text" name="full_name" class="form-control modern-input" required
                        placeholder="Administrator full name"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    <div class="input-focus-line"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user-tag"></i>
                    Role <span class="text-danger">*</span>
                </label>
                <div class="input-wrapper">
                    <select name="role" class="form-control modern-input" required>
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                            Admin - Full access to manage requests
                        </option>
                        <option value="moderator" <?= ($_POST['role'] ?? '') === 'moderator' ? 'selected' : '' ?>>
                            Moderator - View and review requests only
                        </option>
                    </select>
                    <div class="input-focus-line"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-lock"></i>
                    Password <span class="text-danger">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="password" name="password" class="form-control modern-input" required
                        placeholder="Create a strong password" minlength="8">
                    <div class="input-focus-line"></div>
                </div>
                <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, and number</small>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-lock"></i>
                    Confirm Password <span class="text-danger">*</span>
                </label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" class="form-control modern-input" required
                        placeholder="Re-enter password" minlength="8">
                    <div class="input-focus-line"></div>
                </div>
            </div>

            <button type="submit" class="btn modern-btn-primary">
                <span class="btn-content">
                    <i class="fas fa-user-plus"></i>
                    Register Admin Account
                </span>
                <div class="btn-ripple"></div>
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="login-footer">
        <a href="manage_users.php" class="back-home-link">
            <i class="fas fa-arrow-left me-1"></i> Back to User Management
        </a>
    </div>
</div>

<style>
    /* Use the same modern styles as login.php */
    .modern-login-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(139, 0, 0, 0.15), 0 8px 25px rgba(0, 0, 0, 0.1);
        max-width: 520px;
        width: 100%;
        overflow: hidden;
        animation: slideInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideInUp {
        from { opacity: 0; transform: translateY(40px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .login-brand {
        background: linear-gradient(135deg, var(--evsu-maroon) 0%, #6B0000 100%);
        color: white;
        padding: 40px 30px 30px;
        text-align: center;
        position: relative;
    }

    .login-brand::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dots" width="60" height="60" patternUnits="userSpaceOnUse"><circle cx="30" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23dots)"/></svg>');
        opacity: 0.3;
    }

    .brand-icon {
        width: 80px; height: 80px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        z-index: 1;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .brand-icon i { font-size: 2.2rem; color: white; }
    .login-brand h1 { font-size: 1.8rem; font-weight: 900; letter-spacing: 2px; margin-bottom: 8px; position: relative; z-index: 1; }
    .brand-subtitle { font-size: 1rem; opacity: 0.9; margin-bottom: 20px; font-weight: 500; position: relative; z-index: 1; }
    .brand-divider { width: 60px; height: 3px; background: rgba(255, 255, 255, 0.8); margin: 0 auto; border-radius: 2px; position: relative; z-index: 1; }
    
    .login-form-wrapper { padding: 40px 30px 30px; }
    
    .modern-alert {
        background: #fee;
        border: 1px solid #fcc;
        color: #c33;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
    }
    
    .form-group { margin-bottom: 25px; }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--evsu-maroon);
        font-weight: 600;
        margin-bottom: 12px;
        font-size: 0.95rem;
    }
    
    .input-wrapper { position: relative; }
    
    .modern-input {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        background: #f8f9fa;
    }
    
    .modern-input:focus {
        outline: none;
        border-color: var(--evsu-gold);
        background: white;
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .input-focus-line {
        position: absolute;
        bottom: 0; left: 0;
        width: 0; height: 3px;
        background: var(--evsu-gold);
        border-radius: 2px;
        transition: width 0.3s ease;
    }
    
    .modern-input:focus + .input-focus-line { width: 100%; }
    
    .modern-btn-primary {
        width: 100%;
        background: linear-gradient(135deg, var(--evsu-maroon) 0%, #6B0000 100%);
        border: none;
        border-radius: 12px;
        padding: 16px 24px;
        color: white;
        font-weight: 700;
        font-size: 1.1rem;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        margin-top: 10px;
    }
    
    .modern-btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(139, 0, 0, 0.3);
    }
    
    .btn-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        position: relative;
        z-index: 1;
    }
    
    .login-footer {
        padding: 20px 30px;
        background: #f8f9fa;
        text-align: center;
        border-top: 1px solid #e9ecef;
    }
    
    .back-home-link {
        color: var(--evsu-maroon);
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    
    .back-home-link:hover {
        color: #6B0000;
        transform: translateX(-3px);
    }
    
    @media (max-width: 576px) {
        .modern-login-container { margin: 20px; border-radius: 15px; }
        .login-brand { padding: 30px 20px 25px; }
        .login-brand h1 { font-size: 1.5rem; }
        .login-form-wrapper { padding: 30px 20px 25px; }
    }
</style>

<script>
// Password strength indicator
document.addEventListener('DOMContentLoaded', function() {
    const password = document.querySelector('input[name="password"]');
    const confirm = document.querySelector('input[name="confirm_password"]');
    
    if (password && confirm) {
        confirm.addEventListener('input', function() {
            if (this.value && this.value !== password.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        password.addEventListener('input', function() {
            if (confirm.value && confirm.value !== this.value) {
                confirm.setCustomValidity('Passwords do not match');
            } else {
                confirm.setCustomValidity('');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>