<?php
/**
 * CRCY Dispatch - Simple Admin Login
 * Hardcoded admin credentials for simplicity
 */

require_once 'config.php';

$pageTitle = 'CRCY Admin Login - CRCY Dispatch';
$bodyClass = 'auth-page';
$customCSS = ['auth'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $password = trim($_POST['password'] ?? '');

    // Rate limiting for login attempts
    if (!checkRateLimit($clientIP . '_login', 5, 900)) { // 5 attempts per 15 minutes
        $error = 'Too many failed login attempts. Please try again in 15 minutes.';
        logSecurityEvent('login_rate_limit_exceeded', ['ip' => $clientIP]);
    } else {
        try {
            $db = getDB();

            // Get the single admin user from database
            $stmt = $db->prepare("
                SELECT id, password_hash, full_name, 
                       failed_login_attempts, locked_until 
                FROM admin_users 
                WHERE id = 1
            ");
            $stmt->execute();
            $admin = $stmt->fetch();

            if ($admin) {
                // Check if account is locked
                if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                    $lockoutMinutes = ceil((strtotime($admin['locked_until']) - time()) / 60);
                    $error = "Account is locked. Try again in $lockoutMinutes minutes.";
                    logSecurityEvent('login_account_locked', [
                        'ip' => $clientIP,
                        'locked_until' => $admin['locked_until']
                    ]);
                } else {
                    // Verify password
                    if (password_verify($password, $admin['password_hash'])) {
                        // Reset failed attempts and unlock account
                        $stmt = $db->prepare("
                            UPDATE admin_users 
                            SET failed_login_attempts = 0, 
                                locked_until = NULL, 
                                last_login = NOW() 
                            WHERE id = 1
                        ");
                        $stmt->execute();

                        // Reset rate limit on successful login
                        $rateLimitFile = sys_get_temp_dir() . '/crcy_rate_limit_' . md5($clientIP . '_login');
                        if (file_exists($rateLimitFile)) {
                            unlink($rateLimitFile);
                        }

                        // Set session for admin
                        $_SESSION['user_id'] = 1;
                        $_SESSION['email'] = 'admin@evsu.edu.ph';
                        $_SESSION['full_name'] = $admin['full_name'];
                        $_SESSION['role'] = 'admin';
                        $_SESSION['login_time'] = time();

                        // Log successful login
                        logSecurityEvent('admin_login_success', [
                            'ip' => $clientIP,
                            'admin_id' => 1
                        ]);

                        header('Location: dashboard.php');
                        exit;
                    } else {
                        // Increment failed attempts
                        $newFailedAttempts = $admin['failed_login_attempts'] + 1;
                        $lockoutTime = null;

                        // Lock account after 5 failed attempts
                        if ($newFailedAttempts >= 5) {
                            $lockoutTime = date('Y-m-d H:i:s', time() + 900); // 15 minutes
                        }

                        $stmt = $db->prepare("
                            UPDATE admin_users 
                            SET failed_login_attempts = ?, locked_until = ? 
                            WHERE id = 1
                        ");
                        $stmt->execute([$newFailedAttempts, $lockoutTime]);

                        // Record failed attempt for rate limiting
                        recordRateLimitAttempt($clientIP . '_login');

                        // Log failed login
                        logSecurityEvent('admin_login_failed', [
                            'ip' => $clientIP,
                            'failed_attempts' => $newFailedAttempts,
                            'account_locked' => $lockoutTime ? true : false
                        ]);

                        if ($lockoutTime) {
                            $error = 'Too many failed attempts. Account locked for 15 minutes.';
                        } else {
                            $remaining = 5 - $newFailedAttempts;
                            $error = "Invalid password. $remaining attempts remaining.";
                        }
                    }
                }
            } else {
                // Record failed attempt for rate limiting
                recordRateLimitAttempt($clientIP . '_login');

                // Log failed login attempt
                logSecurityEvent('admin_login_failed', [
                    'ip' => $clientIP,
                    'reason' => 'admin_not_found'
                ]);

                $error = 'Admin account not found. Please check database setup.';
            }
        } catch (Exception $e) {
            logSecurityEvent('login_error', [
                'ip' => $clientIP,
                'error' => $e->getMessage()
            ]);
            $error = 'Login system error. Please try again.';
        }
    }
}

include 'includes/header.php';
?>

<div class="modern-login-container">
    <!-- Logo and Branding -->
    <div class="login-brand">
        <div class="brand-icon">
            <i class="bi bi-person-gear"></i>
        </div>
        <h1>CRCY DISPATCH</h1>
        <p class="brand-subtitle">Administrator Portal</p>
        <div class="brand-divider"></div>
    </div>

    <!-- Login Form -->
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
                    <i class="fas fa-lock"></i>
                    Administrator Password
                </label>
                <div class="input-wrapper">
                    <input type="password" name="password" class="form-control modern-input" required autofocus
                        placeholder="Enter admin password">
                    <div class="input-focus-line"></div>
                </div>
            </div>

            <button type="submit" class="btn modern-btn-primary">
                <span class="btn-content">
                    <i class="fas fa-sign-in-alt"></i>
                    Access Admin Dashboard
                </span>
                <div class="btn-ripple"></div>
            </button>
        </form>


    </div>

    <!-- Footer -->
    <div class="login-footer">
        <a href="index.php" class="back-home-link">
            Return to Public Portal
        </a>
    </div>
</div>

<style>
    /* Modern Login Design */
    .modern-login-container {
        background: white;
        border-radius: 20px;
        box-shadow:
            0 20px 60px rgba(139, 0, 0, 0.15),
            0 8px 25px rgba(0, 0, 0, 0.1);
        max-width: 480px;
        width: 100%;
        overflow: hidden;
        position: relative;
        animation: slideInUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Brand Section */
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
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="dots" width="60" height="60" patternUnits="userSpaceOnUse"><circle cx="30" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23dots)"/></svg>');
        opacity: 0.3;
    }

    .brand-icon {
        width: 80px;
        height: 80px;
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

    .brand-icon i {
        font-size: 2.2rem;
        color: white;
    }

    .brand-text {
        font-size: 1.8rem;
        font-weight: 900;
        color: white;
        letter-spacing: 2px;
    }

    .login-brand h1 {
        font-size: 1.8rem;
        font-weight: 900;
        letter-spacing: 2px;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .brand-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin-bottom: 20px;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }

    .brand-divider {
        width: 60px;
        height: 3px;
        background: rgba(255, 255, 255, 0.8);
        margin: 0 auto;
        border-radius: 2px;
        position: relative;
        z-index: 1;
    }

    /* Form Section */
    .login-form-wrapper {
        padding: 40px 30px 30px;
    }

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

    .modern-alert i {
        font-size: 1.1rem;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--evsu-maroon);
        font-weight: 600;
        margin-bottom: 12px;
        font-size: 0.95rem;
    }

    .input-wrapper {
        position: relative;
    }

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
        bottom: 0;
        left: 0;
        width: 0;
        height: 3px;
        background: var(--evsu-gold);
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    .modern-input:focus+.input-focus-line {
        width: 100%;
    }

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

    .modern-btn-primary:hover .btn-content {
        color: white;
    }

    .btn-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        position: relative;
        z-index: 1;
    }

    .btn-ripple {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
        transform: scale(0);
        transition: transform 0.6s ease;
    }

    .modern-btn-primary:active .btn-ripple {
        transform: scale(1);
    }

    /* Security Notice */
    .security-notice {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 10px;
        margin-top: 25px;
        font-size: 0.85rem;
        color: #6c757d;
        border-left: 4px solid var(--evsu-gold);
    }

    /* Footer */
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

    /* Responsive */
    @media (max-width: 576px) {
        .modern-login-container {
            margin: 20px;
            border-radius: 15px;
        }

        .login-brand {
            padding: 30px 20px 25px;
        }

        .login-brand h1 {
            font-size: 1.5rem;
        }

        .login-form-wrapper {
            padding: 30px 20px 25px;
        }

        .login-footer {
            padding: 15px 20px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>