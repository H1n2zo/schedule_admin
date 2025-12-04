<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EVSU Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --evsu-white: #FFFFFF;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
        }
        body {
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(128,0,0,0.3);
            max-width: 400px;
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 EVSU Admin Panel</h1>
            <p class="text-muted">Scheduling & Request Management</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
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
        
        <div class="mt-4 text-center">
            <small class="text-muted">
                <!-- <strong>Test Accounts:</strong><br>
                Admin: admin@evsu.edu.ph / admin123<br>
                Coordinator: coordinator@evsu.edu.ph / admin123 -->
            </small>
        </div>
    </div>
</body>
</html>