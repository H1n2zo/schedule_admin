<?php
require_once 'config.php';

$error = '';
$success = '';
$mode = isset($_GET['mode']) && $_GET['mode'] === 'register' ? 'register' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Registration
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        $role = 'coordinator'; // Force coordinator role for public registration
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@evsu.edu.ph')) {
            $error = 'Only EVSU email addresses are allowed';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $stmt = $db->prepare("INSERT INTO users (email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $fullName, $role]);
                $success = 'Registration successful! You can now login.';
                $mode = 'login';
            }
        }
    } else {
        // Login
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode === 'register' ? 'Register' : 'Login' ?> - EVSU Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
        }
        body {
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(128,0,0,0.3);
            max-width: 450px;
            width: 100%;
            border-top: 5px solid var(--evsu-gold);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: var(--evsu-maroon);
            font-size: 24px;
            margin-bottom: 10px;
        }
        .btn-primary {
            background: var(--evsu-maroon);
            border: none;
        }
        .btn-primary:hover {
            background: var(--maroon-dark);
        }
        .form-control:focus {
            border-color: var(--evsu-gold);
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }
        .toggle-mode {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .toggle-mode a {
            color: var(--evsu-maroon);
            font-weight: 600;
            text-decoration: none;
        }
        .info-box {
            background: #fffbf0;
            border: 1px solid var(--gold-dark);
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 EVSU Admin Panel</h1>
            <p class="text-muted"><?= $mode === 'register' ? 'Create Account' : 'Scheduling & Request Management' ?></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if ($mode === 'login'): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            
            <div class="toggle-mode">
                Don't have an account? <a href="?mode=register">Register here</a>
            </div>
        <?php else: ?>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> <strong>Note:</strong> New accounts are registered as <strong>Coordinators</strong>. Only admins can promote users to Administrator role.
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">EVSU Email</label>
                    <input type="email" name="email" class="form-control" placeholder="yourname@evsu.edu.ph" pattern=".*@evsu\.edu\.ph$" required>
                    <small class="text-muted">Must be @evsu.edu.ph</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary w-100">Create Account</button>
            </form>
            
            <div class="toggle-mode">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>