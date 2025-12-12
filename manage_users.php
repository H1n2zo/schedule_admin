<?php
/**
 * EVSU Event Management System
 * Manage Users Page - FIXED
 * File: manage_users.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Manage Users - EVSU Admin Panel';
$customCSS = ['dashboard'];
$customJS = [];

$db = getDB();
$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    // Prevent admin from modifying their own account
    if ($userId === $_SESSION['user_id']) {
        $message = 'You cannot modify your own account.';
        $messageType = 'danger';
    } else {
        try {
            // Get user info before action
            $stmt = $db->prepare("SELECT email, full_name, role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $message = 'User not found.';
                $messageType = 'danger';
            } else {
                if ($action === 'promote') {
                    // Promote to admin
                    $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $message = htmlspecialchars($user['full_name']) . ' has been promoted to administrator.';
                    $messageType = 'success';
                    
                } elseif ($action === 'demote') {
                    // Check if this is the last admin
                    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                    $adminCount = $stmt->fetchColumn();
                    
                    if ($adminCount <= 1) {
                        $message = 'Cannot demote the last administrator. At least one admin must remain.';
                        $messageType = 'danger';
                    } else {
                        // Demote to regular user
                        $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        $message = htmlspecialchars($user['full_name']) . ' has been demoted to regular user.';
                        $messageType = 'warning';
                    }
                    
                } elseif ($action === 'delete') {
                    // Check if this is the last admin
                    if ($user['role'] === 'admin') {
                        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                        $adminCount = $stmt->fetchColumn();
                        
                        if ($adminCount <= 1) {
                            $message = 'Cannot delete the last administrator. At least one admin must remain.';
                            $messageType = 'danger';
                        } else {
                            // Delete user
                            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$userId]);
                            
                            $message = 'User ' . htmlspecialchars($user['full_name']) . ' has been deleted.';
                            $messageType = 'info';
                        }
                    } else {
                        // Delete user
                        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        $message = 'User ' . htmlspecialchars($user['full_name']) . ' has been deleted.';
                        $messageType = 'info';
                    }
                }
            }
        } catch (PDOException $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
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
                                                onclick="showConfirmModal(<?= $user['id'] ?>, 'demote', '<?= htmlspecialchars($user['full_name']) ?>')">
                                            <i class="fas fa-arrow-down"></i> Demote
                                        </button>
                                    <?php else: ?>
                                        <!-- Promote to admin -->
                                        <button class="btn btn-sm btn-success" 
                                                onclick="showConfirmModal(<?= $user['id'] ?>, 'promote', '<?= htmlspecialchars($user['full_name']) ?>')">
                                            <i class="fas fa-arrow-up"></i> Promote
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Delete user -->
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="showConfirmModal(<?= $user['id'] ?>, 'delete', '<?= htmlspecialchars($user['full_name']) ?>')">
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

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="confirmForm" style="display: inline;">
                    <input type="hidden" name="user_id" id="confirmUserId">
                    <input type="hidden" name="action" id="confirmAction">
                    <button type="submit" class="btn btn-primary" id="confirmButton">Confirm</button>
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

<script>
function showConfirmModal(userId, action, userName) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const modalHeader = document.getElementById('modalHeader');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const confirmButton = document.getElementById('confirmButton');
    const confirmUserId = document.getElementById('confirmUserId');
    const confirmAction = document.getElementById('confirmAction');
    
    // Set form values
    confirmUserId.value = userId;
    confirmAction.value = action;
    
    // Set modal content based on action
    let titleText, bodyHTML, btnClass, btnText, headerClass;
    
    switch(action) {
        case 'promote':
            titleText = 'Promote to Administrator';
            bodyHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-arrow-up"></i> 
                    Are you sure you want to promote <strong>${userName}</strong> to administrator?
                </div>
                <p>They will have full access to all admin features including:</p>
                <ul>
                    <li>Approving/declining event requests</li>
                    <li>Managing users</li>
                    <li>Sending notifications</li>
                    <li>Viewing all audit logs</li>
                </ul>
            `;
            btnClass = 'btn-success';
            btnText = '<i class="fas fa-arrow-up"></i> Confirm Promotion';
            headerClass = 'bg-success text-white';
            break;
            
        case 'demote':
            titleText = 'Demote from Administrator';
            bodyHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-arrow-down"></i> 
                    Are you sure you want to demote <strong>${userName}</strong> to regular user?
                </div>
                <p>They will lose all administrative privileges including:</p>
                <ul>
                    <li>Access to the admin dashboard</li>
                    <li>Ability to approve/decline requests</li>
                    <li>User management capabilities</li>
                </ul>
            `;
            btnClass = 'btn-warning';
            btnText = '<i class="fas fa-arrow-down"></i> Confirm Demotion';
            headerClass = 'bg-warning text-dark';
            break;
            
        case 'delete':
            titleText = 'Delete User';
            bodyHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-trash"></i> 
                    Are you sure you want to permanently delete <strong>${userName}</strong>?
                </div>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone!</p>
                <p>All data associated with this user will be permanently removed from the system.</p>
            `;
            btnClass = 'btn-danger';
            btnText = '<i class="fas fa-trash"></i> Confirm Deletion';
            headerClass = 'bg-danger text-white';
            break;
    }
    
    // Update modal
    modalTitle.textContent = titleText;
    modalBody.innerHTML = bodyHTML;
    confirmButton.className = 'btn ' + btnClass;
    confirmButton.innerHTML = btnText;
    modalHeader.className = 'modal-header ' + headerClass;
    
    // Show modal
    modal.show();
}
</script>

<?php
// Include footer
include 'includes/footer.php';
?>