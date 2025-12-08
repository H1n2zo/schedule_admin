<?php
require_once 'config.php';
requireAdmin();

$db = getDB();
$message = '';
$messageType = '';

// Handle role changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = (int)$_POST['user_id'];
        
        // Prevent admin from removing their own admin role
        if ($userId === $_SESSION['user_id'] && $_POST['action'] === 'demote') {
            $message = 'You cannot remove your own admin privileges.';
            $messageType = 'danger';
        } else {
            try {
                if ($_POST['action'] === 'promote') {
                    // Promote to admin
                    $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    $message = htmlspecialchars($user['full_name']) . ' has been promoted to admin.';
                    $messageType = 'success';
                    
                } elseif ($_POST['action'] === 'demote') {
                    // Demote to regular user
                    $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    $message = htmlspecialchars($user['full_name']) . ' has been demoted to regular user.';
                    $messageType = 'warning';
                    
                } elseif ($_POST['action'] === 'delete') {
                    // Delete user
                    $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $message = 'User ' . htmlspecialchars($user['full_name']) . ' has been deleted.';
                    $messageType = 'info';
                }
            } catch (PDOException $e) {
                $message = 'Error updating user: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Get all users
$stmt = $db->query("
    SELECT id, email, full_name, role, created_at 
    FROM users 
    ORDER BY 
        CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
        full_name ASC
");
$users = $stmt->fetchAll();

// Count admins
$adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EVSU Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
            --maroon-light: #fff5f5;
        }
        body { background: #f5f7fa; }
        .navbar { background: var(--evsu-maroon) !important; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px; }
        .card-header { 
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            color: white; 
            border-radius: 8px 8px 0 0 !important;
            font-weight: 600;
        }
        .table thead { background: var(--maroon-light); }
        .badge-role {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .admin-row {
            background: #fffbf0;
        }
        .btn-primary { 
            background-color: var(--evsu-maroon); 
            border-color: var(--evsu-maroon); 
        }
        .btn-primary:hover { 
            background-color: var(--maroon-dark); 
            border-color: var(--maroon-dark); 
        }
        .btn-warning { 
            background-color: var(--evsu-gold); 
            border-color: var(--gold-dark); 
            color: var(--maroon-dark); 
            font-weight: 600; 
        }
        .btn-warning:hover { 
            background-color: var(--gold-dark); 
            border-color: var(--gold-dark); 
            color: white; 
        }
        .action-buttons .btn {
            margin: 2px;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-top: 3px solid var(--evsu-gold);
            margin-bottom: 20px;
        }
        .you-indicator {
            background: var(--evsu-maroon);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand">🎓 EVSU Admin Panel</span>
            <div class="d-flex align-items-center">
                <a href="dashboard.php" class="btn btn-light btn-sm me-3">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <span class="text-white me-3"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h6 class="text-muted">Total Users</h6>
                    <h2 class="text-primary"><?= count($users) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h6 class="text-muted">Administrators</h6>
                    <h2 class="text-warning"><?= $adminCount ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h6 class="text-muted">Regular Users</h6>
                    <h2 class="text-info"><?= count($users) - $adminCount ?></h2>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users"></i> User Management
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $isCurrentUser = ($user['id'] === $_SESSION['user_id']);
                                $rowClass = ($user['role'] === 'admin') ? 'admin-row' : '';
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="you-indicator">YOU</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge badge-role bg-warning text-dark">
                                            <i class="fas fa-crown"></i> Administrator
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-role bg-secondary">
                                            <i class="fas fa-user"></i> User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= formatDate($user['created_at']) ?>
                                    </small>
                                </td>
                                <td class="text-end action-buttons">
                                    <?php if (!$isCurrentUser): ?>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <!-- Demote from admin -->
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="confirmAction(<?= $user['id'] ?>, 'demote', '<?= htmlspecialchars($user['full_name']) ?>')">
                                                <i class="fas fa-arrow-down"></i> Demote
                                            </button>
                                        <?php else: ?>
                                            <!-- Promote to admin -->
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="confirmAction(<?= $user['id'] ?>, 'promote', '<?= htmlspecialchars($user['full_name']) ?>')">
                                                <i class="fas fa-arrow-up"></i> Promote
                                            </button>
                                        <?php endif; ?>
                                        
                                        <!-- Delete user -->
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="confirmAction(<?= $user['id'] ?>, 'delete', '<?= htmlspecialchars($user['full_name']) ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <small><i class="fas fa-lock"></i> Cannot modify own account</small>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info mt-4">
            <h6><i class="fas fa-info-circle"></i> User Management Information</h6>
            <ul class="mb-0">
                <li><strong>Promote:</strong> Grant administrator privileges to a regular user</li>
                <li><strong>Demote:</strong> Remove administrator privileges (convert to regular user)</li>
                <li><strong>Delete:</strong> Permanently remove a user from the system</li>
                <li><strong>Note:</strong> You cannot modify your own account for security reasons</li>
                <li><strong>Warning:</strong> At least one administrator must remain in the system</li>
            </ul>
        </div>
    </div>

    <!-- Hidden Form for Actions -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="actionUserId">
        <input type="hidden" name="action" id="actionType">
    </form>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentUserId, currentAction;
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

        function confirmAction(userId, action, userName) {
            currentUserId = userId;
            currentAction = action;
            
            let title, message, btnClass;
            
            switch(action) {
                case 'promote':
                    title = 'Promote to Administrator';
                    message = `Are you sure you want to promote <strong>${userName}</strong> to administrator? They will have full access to all admin features.`;
                    btnClass = 'btn-success';
                    break;
                case 'demote':
                    title = 'Demote from Administrator';
                    message = `Are you sure you want to demote <strong>${userName}</strong> to regular user? They will lose all administrative privileges.`;
                    btnClass = 'btn-warning';
                    break;
                case 'delete':
                    title = 'Delete User';
                    message = `Are you sure you want to permanently delete <strong>${userName}</strong>? This action cannot be undone.`;
                    btnClass = 'btn-danger';
                    break;
            }
            
            document.getElementById('confirmModalTitle').textContent = title;
            document.getElementById('confirmModalBody').innerHTML = message;
            
            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.className = 'btn ' + btnClass;
            confirmBtn.textContent = 'Confirm';
            
            confirmModal.show();
        }

        document.getElementById('confirmBtn').addEventListener('click', function() {
            document.getElementById('actionUserId').value = currentUserId;
            document.getElementById('actionType').value = currentAction;
            document.getElementById('actionForm').submit();
        });
    </script>
</body>
</html>