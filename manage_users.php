<?php
/**
 * CRCY Dispatch - Admin User Management
 * Manage admin accounts (super_admin only)
 */

require_once 'config.php';
requireAdmin();

// Only super_admin can manage users
if ($_SESSION['role'] !== 'super_admin') {
    $_SESSION['error'] = 'Only super administrators can manage users.';
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Manage Admin Users - CRCY Dispatch';
$customCSS = ['dashboard'];

$db = getDB();

// Handle actions (deactivate, activate, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($userId && $userId !== $_SESSION['user_id']) { // Can't modify own account
        try {
            switch ($action) {
                case 'deactivate':
                    $stmt = $db->prepare("UPDATE admin_users SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $stmt = $db->prepare("
                        INSERT INTO admin_activity_log (admin_id, action, description, ip_address)
                        VALUES (?, 'user_deactivated', ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        "Deactivated user ID: $userId",
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    $_SESSION['success'] = 'Admin account has been deactivated.';
                    break;
                    
                case 'activate':
                    $stmt = $db->prepare("UPDATE admin_users SET status = 'active' WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $stmt = $db->prepare("
                        INSERT INTO admin_activity_log (admin_id, action, description, ip_address)
                        VALUES (?, 'user_activated', ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        "Activated user ID: $userId",
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    $_SESSION['success'] = 'Admin account has been activated.';
                    break;
                    
                case 'delete':
                    // Get user info before deletion
                    $stmt = $db->prepare("SELECT username FROM admin_users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $userInfo = $stmt->fetch();
                    
                    $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    $stmt = $db->prepare("
                        INSERT INTO admin_activity_log (admin_id, action, description, ip_address)
                        VALUES (?, 'user_deleted', ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        "Deleted user: " . ($userInfo['username'] ?? $userId),
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    $_SESSION['success'] = 'Admin account has been permanently deleted.';
                    break;
            }
            
            logSecurityEvent('user_management', [
                'action' => $action,
                'target_user_id' => $userId,
                'admin_id' => $_SESSION['user_id']
            ]);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error performing action: ' . $e->getMessage();
        }
    } elseif ($userId === $_SESSION['user_id']) {
        $_SESSION['error'] = 'You cannot modify your own account.';
    }
    
    header('Location: manage_users.php');
    exit;
}

// Get all admin users
$stmt = $db->query("
    SELECT 
        u.*,
        creator.username as created_by_username,
        (SELECT COUNT(*) FROM admin_activity_log WHERE admin_id = u.id) as activity_count
    FROM admin_users u
    LEFT JOIN admin_users creator ON u.created_by = creator.id
    ORDER BY 
        CASE 
            WHEN u.role = 'super_admin' THEN 1
            WHEN u.role = 'admin' THEN 2
            WHEN u.role = 'moderator' THEN 3
        END,
        u.created_at DESC
");
$users = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) as super_admins,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'moderator' THEN 1 ELSE 0 END) as moderators
    FROM admin_users
");
$stats = $stmt->fetch();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3><i class="fas fa-users-cog"></i> Admin User Management</h3>
            <p class="text-muted mb-0">Manage administrator accounts and permissions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="register.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Register New Admin
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Admins</h6>
                            <h3 class="mb-0"><?= $stats['total'] ?></h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Active</h6>
                            <h3 class="mb-0 text-success"><?= $stats['active'] ?></h3>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Super Admins</h6>
                            <h3 class="mb-0 text-danger"><?= $stats['super_admins'] ?></h3>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="fas fa-crown"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Moderators</h6>
                            <h3 class="mb-0 text-info"><?= $stats['moderators'] ?></h3>
                        </div>
                        <div class="text-info" style="font-size: 2rem;">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-table"></i> Administrator Accounts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                                <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                    <?= htmlspecialchars($user['email']) ?>
                                </a>
                            </td>
                            <td>
                                <?php
                                $roleClass = [
                                    'super_admin' => 'danger',
                                    'admin' => 'primary',
                                    'moderator' => 'info'
                                ][$user['role']] ?? 'secondary';
                                $roleIcon = [
                                    'super_admin' => 'fa-crown',
                                    'admin' => 'fa-user-shield',
                                    'moderator' => 'fa-user-check'
                                ][$user['role']] ?? 'fa-user';
                                ?>
                                <span class="badge bg-<?= $roleClass ?>">
                                    <i class="fas <?= $roleIcon ?>"></i> 
                                    <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($user['status'] === 'inactive'): ?>
                                    <span class="badge bg-warning text-dark">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <small><?= date('M j, Y g:i A', strtotime($user['last_login'])) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                    <?php if ($user['created_by_username']): ?>
                                        <br>by <?= htmlspecialchars($user['created_by_username']) ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role'] !== 'super_admin'): ?>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <button class="btn btn-outline-warning" 
                                                    onclick="confirmAction('deactivate', <?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-success" 
                                                    onclick="confirmAction('activate', <?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-danger" 
                                                onclick="confirmAction('delete', <?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                <?php elseif ($user['role'] === 'super_admin'): ?>
                                    <span class="badge bg-secondary">Protected</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Action Confirmation Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="actionForm" style="display: inline;">
                    <input type="hidden" name="action" id="actionInput">
                    <input type="hidden" name="user_id" id="userIdInput">
                    <button type="submit" class="btn" id="confirmBtn"></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmAction(action, userId, username) {
    const modal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
    const modalHeader = document.getElementById('modalHeader');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const confirmBtn = document.getElementById('confirmBtn');
    const actionInput = document.getElementById('actionInput');
    const userIdInput = document.getElementById('userIdInput');
    
    actionInput.value = action;
    userIdInput.value = userId;
    
    if (action === 'deactivate') {
        modalHeader.className = 'modal-header bg-warning text-dark';
        modalTitle.innerHTML = '<i class="fas fa-pause-circle"></i> Deactivate Admin Account';
        modalBody.innerHTML = `
            <p>Are you sure you want to deactivate the account for <strong>${username}</strong>?</p>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                The user will not be able to log in until the account is reactivated.
            </div>
        `;
        confirmBtn.className = 'btn btn-warning';
        confirmBtn.innerHTML = '<i class="fas fa-pause"></i> Deactivate Account';
    } else if (action === 'activate') {
        modalHeader.className = 'modal-header bg-success text-white';
        modalTitle.innerHTML = '<i class="fas fa-check-circle"></i> Activate Admin Account';
        modalBody.innerHTML = `
            <p>Are you sure you want to activate the account for <strong>${username}</strong>?</p>
            <div class="alert alert-success">
                <i class="fas fa-info-circle"></i>
                The user will be able to log in again.
            </div>
        `;
        confirmBtn.className = 'btn btn-success';
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Activate Account';
    } else if (action === 'delete') {
        modalHeader.className = 'modal-header bg-danger text-white';
        modalTitle.innerHTML = '<i class="fas fa-trash"></i> Delete Admin Account';
        modalBody.innerHTML = `
            <p>Are you sure you want to permanently delete the account for <strong>${username}</strong>?</p>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This action cannot be undone. All associated activity logs will also be deleted.
            </div>
        `;
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Delete Permanently';
    }
    
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>