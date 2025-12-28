<?php
/**
 * CRCY Dispatch System
 * Navigation Bar with Profile Dropdown
 * File: includes/navbar.php
 */

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$fullName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest';

// Determine current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-dark navbar-expand-lg">
    <div class="container-fluid">
        <!-- Brand -->
        <a href="<?= $isLoggedIn ? 'dashboard.php' : 'index.php' ?>" class="navbar-brand">
            <i class="fas fa-hands-helping me-2"></i>
            CRCY Dispatch
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($isLoggedIn && $isAdmin): ?>   
                    <!-- Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" 
                           id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
                            <span><?= htmlspecialchars($fullName) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="fas fa-shield-alt text-primary"></i>
                                    CRCY Administrator
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fas fa-key me-2"></i>
                                    Change Password
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="manage_spam_emails.php" data-bs-toggle="modal" data-bs-target="#manageSpammerModal">
                                    <i class="fas fa-ban me-2"></i>
                                    Manage Spammer
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#maintenanceModal">
                                    <i class="fas fa-tools me-2"></i>
                                    Maintenance Mode
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Change Password Modal -->
<?php if ($isLoggedIn && $isAdmin): ?>
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePasswordForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">
                            <i class="fas fa-lock me-2 text-muted"></i>Current Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="currentPassword" 
                               name="current_password" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">
                            <i class="fas fa-key me-2 text-muted"></i>New Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="newPassword" 
                               name="new_password" 
                               minlength="8" 
                               required>
                        <div class="form-text">Minimum 8 characters required</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">
                            <i class="fas fa-check-circle me-2 text-muted"></i>Confirm New Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="confirmPassword" 
                               name="confirm_password" 
                               minlength="8" 
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New passwords do not match!');
        return false;
    }
});

// Clear form when modal is closed
document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('changePasswordForm').reset();
});
</script>

<!-- Maintenance Mode Modal -->
<div class="modal fade" id="maintenanceModal" tabindex="-1" aria-labelledby="maintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="maintenanceModalLabel">
                    <i class="fas fa-tools me-2"></i>Maintenance Mode
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="maintenanceStatus" class="mb-3">
                    <!-- Status will be loaded here -->
                </div>
                
                <form id="maintenanceForm" method="POST">
                    <input type="hidden" name="action" value="toggle_maintenance">
                    
                    <div class="mb-3">
                        <label for="maintenanceMessage" class="form-label">
                            <i class="fas fa-comment me-2 text-muted"></i>Maintenance Message
                        </label>
                        <textarea class="form-control" 
                                  id="maintenanceMessage" 
                                  name="message" 
                                  rows="3" 
                                  placeholder="System is under maintenance. Please try again later.">System is under maintenance for updates. Please try again in 30 minutes.</textarea>
                        <div class="form-text">This message will be shown to users during maintenance.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" id="toggleMaintenanceBtn" class="btn btn-warning">
                    <i class="fas fa-tools me-2"></i><span id="maintenanceBtnText">Enable Maintenance</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Load maintenance status when modal opens
document.getElementById('maintenanceModal').addEventListener('show.bs.modal', function () {
    loadMaintenanceStatus();
});

// Toggle maintenance mode
document.getElementById('toggleMaintenanceBtn').addEventListener('click', function() {
    const form = document.getElementById('maintenanceForm');
    const formData = new FormData(form);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadMaintenanceStatus();
            // Show success message
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error updating maintenance mode', 'danger');
    });
});

function loadMaintenanceStatus() {
    fetch('?check_maintenance=1')
    .then(response => response.json())
    .then(data => {
        const statusDiv = document.getElementById('maintenanceStatus');
        const btn = document.getElementById('toggleMaintenanceBtn');
        const btnText = document.getElementById('maintenanceBtnText');
        
        if (data.enabled) {
            statusDiv.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Maintenance Mode is ACTIVE</strong><br>
                    <small>Users cannot access the system</small>
                </div>
            `;
            btn.className = 'btn btn-success';
            btnText.textContent = 'Disable Maintenance';
            document.getElementById('maintenanceMessage').value = data.message || '';
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>System is ONLINE</strong><br>
                    <small>Users can access the system normally</small>
                </div>
            `;
            btn.className = 'btn btn-warning';
            btnText.textContent = 'Enable Maintenance';
        }
    });
}

function showAlert(message, type) {
    // Create and show alert in the main page
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show dashboard-alert`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the page
    const container = document.querySelector('.gmail-calendar-container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('maintenanceModal')).hide();
}
</script>
<?php endif; ?>