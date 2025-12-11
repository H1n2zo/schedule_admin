<?php
/**
 * EVSU Event Management System
 * Manage Users Page - Updated with External Assets
 * File: manage_users.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Manage Users - EVSU Admin Panel';
$customCSS = ['dashboard'];
$customJS = ['modals'];

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

// Prepare inline scripts
$inlineScripts = "
    function confirmUserAction(userId, action, userName) {
        window.confirmUserAction(userId, action, userName);
    }
";

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

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
                                                onclick="confirmUserAction(<?= $user['id'] ?>, 'demote', '<?= htmlspecialchars($user['full_name']) ?>')">
                                            <i class="fas fa-arrow-down"></i> Demote
                                        </button>
                                    <?php else: ?>
                                        <!-- Promote to admin -->
                                        <button class="btn btn-sm btn-success" 
                                                onclick="confirmUserAction(<?= $user['id'] ?>, 'promote', '<?= htmlspecialchars($user['full_name']) ?>')">
                                            <i class="fas fa-arrow-up"></i> Promote
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Delete user -->
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmUserAction(<?= $user['id'] ?>, 'delete', '<?= htmlspecialchars($user['full_name']) ?>')">
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

<!-- User Action Modal -->
<div class="modal fade" id="userActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" id="userActionId">
                    <input type="hidden" name="action" id="userActionType">
                    <button type="submit" class="btn btn-primary">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-row {
        background: #fffbf0;
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
    
    .action-buttons .btn {
        margin: 2px;
    }
    
    @media (max-width: 768px) {
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .action-buttons .btn {
            width: 100%;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>