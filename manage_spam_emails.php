<?php
/**
 * Spam Email Management Page
 * Block emails from submitting support requests
 */

require_once 'config.php';
require_once 'spam_email_functions.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = 'info';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $email = trim($_POST['email'] ?? '');
    $action = $_POST['action'];
    
    if ($action == 'mark_spam') {
        $reason = $_POST['reason'] ?? 'Marked by admin';
        $result = markEmailAsSpam($db, $email, $reason, $_SESSION['user_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    }
    elseif ($action == 'mark_genuine') {
        $result = markEmailAsGenuine($db, $email, $_SESSION['user_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    }
    elseif ($action == 'toggle') {
        $reason = $_POST['reason'] ?? 'Toggled by admin';
        $result = toggleEmailSpamStatus($db, $email, $_SESSION['user_id'], $reason);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'danger';
    }
}

// Get data
$view = $_GET['view'] ?? 'top_emails';
$stats = getSpamEmailStats($db);

if ($view == 'spam_emails') {
    $emails = getSpamEmails($db);
    $page_title = 'Blocked Emails';
} elseif ($view == 'genuine_emails') {
    $emails = getGenuineEmails($db);
    $page_title = 'Genuine Emails';
} else {
    $emails = getEmailsByRequestCount($db, 100);
    $page_title = 'Emails by Request Count';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spam Email Management - CRCY Dispatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #8b0000;
            --evsu-gold: #FFD700;
        }
        
        body {
            background: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--evsu-maroon), #a0002a);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--evsu-maroon);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        
        .spam-badge {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .genuine-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .unknown-badge {
            background: #6c757d;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .nav-pills .nav-link {
            color: #6c757d;
            border-radius: 8px;
        }
        
        .nav-pills .nav-link.active {
            background: var(--evsu-maroon);
        }
        
        .toggle-btn {
            transition: all 0.2s;
        }
        
        .search-box {
            max-width: 400px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-envelope-open-text"></i> Spam Email Management
            </a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_unique_emails']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-envelope"></i> Unique Emails
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_requests']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-inbox"></i> Total Requests
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?php echo number_format($stats['spam_emails']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-ban"></i> Blocked Emails
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?php echo number_format($stats['spam_requests']); ?></div>
                    <div class="stat-label">
                        <i class="fas fa-exclamation-triangle"></i> Spam Requests
                    </div>
                </div>
            </div>
        </div>

        <!-- View Tabs -->
        <div class="row">
            <div class="col-md-12">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $view == 'top_emails' ? 'active' : ''; ?>" 
                                   href="?view=top_emails">
                                    <i class="fas fa-list"></i> All Emails
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $view == 'spam_emails' ? 'active' : ''; ?>" 
                                   href="?view=spam_emails">
                                    <i class="fas fa-ban"></i> Blocked Emails
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $view == 'genuine_emails' ? 'active' : ''; ?>" 
                                   href="?view=genuine_emails">
                                    <i class="fas fa-check-circle"></i> Genuine Emails
                                </a>
                            </li>
                        </ul>
                        
                        <div class="search-box">
                            <input type="text" class="form-control" id="searchInput" 
                                   placeholder="Search emails...">
                        </div>
                    </div>

                    <h5 class="mb-3"><?php echo $page_title; ?></h5>

                    <div class="table-responsive">
                        <table class="table table-hover" id="emailsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Email</th>
                                    <?php if ($view == 'top_emails'): ?>
                                    <th>Request Count</th>
                                    <th>First Request</th>
                                    <th>Last Request</th>
                                    <?php elseif ($view == 'spam_emails' || $view == 'genuine_emails'): ?>
                                    <th>Marked At</th>
                                    <th>Marked By</th>
                                    <th>Reason</th>
                                    <?php endif; ?>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emails as $email_data): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($email_data['email'] ?? $email_data['requester_email']); ?></strong>
                                    </td>
                                    
                                    <?php if ($view == 'top_emails'): ?>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $email_data['request_count']; ?> requests
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($email_data['first_request'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($email_data['last_request'])); ?></td>
                                    <?php elseif ($view == 'spam_emails' || $view == 'genuine_emails'): ?>
                                    <td><?php echo date('M j, Y H:i', strtotime($email_data['spam_marked_at'])); ?></td>
                                    <td>Admin ID: <?php echo $email_data['spam_marked_by'] ?? 'N/A'; ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($email_data['spam_reason'] ?? '-'); ?>
                                        </small>
                                    </td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <?php 
                                        $is_spam = $email_data['is_spam'] ?? null;
                                        if ($is_spam === null): ?>
                                            <span class="unknown-badge">Unknown</span>
                                        <?php elseif ($is_spam): ?>
                                            <span class="spam-badge"><i class="fas fa-ban"></i> BLOCKED</span>
                                        <?php else: ?>
                                            <span class="genuine-badge"><i class="fas fa-check"></i> GENUINE</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        $email_address = $email_data['email'] ?? $email_data['requester_email'];
                                        $is_currently_spam = $is_spam ?? false;
                                        ?>
                                        <button class="btn btn-sm toggle-btn <?php echo $is_currently_spam ? 'btn-success' : 'btn-danger'; ?>" 
                                                onclick="showToggleModal('<?php echo htmlspecialchars($email_address, ENT_QUOTES); ?>', <?php echo $is_currently_spam ? 'true' : 'false'; ?>)">
                                            <?php if ($is_currently_spam): ?>
                                                <i class="fas fa-check"></i> Unblock
                                            <?php else: ?>
                                                <i class="fas fa-ban"></i> Block
                                            <?php endif; ?>
                                        </button>
                                        
                                        <a href="view_email_requests.php?email=<?php echo urlencode($email_address); ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           target="_blank">
                                            <i class="fas fa-eye"></i> View Requests
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($emails)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No emails found</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Modal -->
    <div class="modal fade" id="toggleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Block Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="email" id="modalEmail">
                        
                        <p id="modalMessage"></p>
                        
                        <div class="mb-3" id="reasonField">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" rows="2" 
                                      placeholder="Why are you blocking this email?"></textarea>
                            <small class="text-muted">Optional but recommended</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn" id="confirmBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#emailsTable tbody tr');
            
            rows.forEach(row => {
                const email = row.cells[0].textContent.toLowerCase();
                row.style.display = email.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Toggle modal
        function showToggleModal(email, isSpam) {
            document.getElementById('modalEmail').value = email;
            
            const modal = new bootstrap.Modal(document.getElementById('toggleModal'));
            const reasonField = document.getElementById('reasonField');
            const confirmBtn = document.getElementById('confirmBtn');
            const modalMessage = document.getElementById('modalMessage');
            const modalTitle = document.getElementById('modalTitle');
            
            if (isSpam) {
                // Currently blocked, will unblock
                modalTitle.textContent = 'Unblock Email';
                modalMessage.innerHTML = `Are you sure you want to <strong>unblock</strong> <strong>${email}</strong>?<br><small class="text-muted">This email will be able to submit support requests again.</small>`;
                reasonField.style.display = 'none';
                confirmBtn.className = 'btn btn-success';
                confirmBtn.innerHTML = '<i class="fas fa-check"></i> Unblock Email';
            } else {
                // Currently not blocked, will block
                modalTitle.textContent = 'Block Email';
                modalMessage.innerHTML = `Are you sure you want to <strong>block</strong> <strong>${email}</strong>?<br><small class="text-muted">This email will not be able to submit support requests.</small>`;
                reasonField.style.display = 'block';
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.innerHTML = '<i class="fas fa-ban"></i> Block Email';
            }
            
            modal.show();
        }
    </script>
</body>
</html>
