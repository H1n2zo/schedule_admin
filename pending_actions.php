<?php
/**
 * EVSU Event Management System
 * Pending Actions Page - Updated with External Assets
 * File: pending_actions.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Pending Actions - EVSU Admin';
$customCSS = ['dashboard'];
$customJS = ['modals'];

$db = getDB();

// Handle cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_action'])) {
    $actionId = (int)$_POST['action_id'];
    $requestId = (int)$_POST['request_id'];
    
    // Get the action details first
    $stmt = $db->prepare("SELECT * FROM pending_actions WHERE id = ?");
    $stmt->execute([$actionId]);
    $action = $stmt->fetch();
    
    if ($action) {
        // Return request to pending status
        $stmt = $db->prepare("UPDATE event_requests SET status = 'pending', reviewed_by = NULL, reviewed_at = NULL WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Delete the pending action
        $stmt = $db->prepare("DELETE FROM pending_actions WHERE id = ?");
        $stmt->execute([$actionId]);
        
        // Log the cancellation in audit log
        $stmt = $db->prepare("
            INSERT INTO audit_log (request_id, admin_id, action, notes) 
            VALUES (?, ?, 'notification_sent', ?)
        ");
        $cancelNote = "Action cancelled - returned to pending status";
        $stmt->execute([$requestId, $_SESSION['user_id'], $cancelNote]);
        
        header('Location: pending_actions.php?cancelled=1');
        exit;
    }
}

// Get pending actions
$stmt = $db->query("
    SELECT 
        pa.id as action_id,
        pa.action_type,
        pa.created_at as action_date,
        er.*,
        u.full_name as admin_name
    FROM pending_actions pa
    JOIN event_requests er ON pa.request_id = er.id
    JOIN users u ON pa.admin_id = u.id
    WHERE er.status = 'pending_notification'
    ORDER BY pa.created_at DESC
");
$pendingActions = $stmt->fetchAll();

$success = isset($_GET['success']) ? true : false;
$cancelled = isset($_GET['cancelled']) ? true : false;

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container mt-4">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Request has been marked for notification!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($cancelled): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-undo"></i> Action cancelled! Request has been returned to pending status.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="fas fa-bell"></i> Pending Actions (<?= count($pendingActions) ?>)</h3>
    </div>

    <?php if (empty($pendingActions)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No pending actions at the moment. All notifications have been sent!
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-12">
                <?php foreach ($pendingActions as $action): ?>
                    <div class="action-card <?= $action['action_type'] ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-2">
                                    <span class="action-type-badge <?= $action['action_type'] ?>">
                                        <?= $action['action_type'] === 'approve' ? '✓ Approve' : '✗ Disapprove' ?>
                                    </span>
                                </div>
                                
                                <h5 class="mb-2"><?= htmlspecialchars($action['event_name']) ?></h5>
                                
                                <div class="text-muted mb-2">
                                    <i class="fas fa-building"></i> <?= htmlspecialchars($action['organization']) ?><br>
                                    <i class="fas fa-calendar"></i> <?= formatDate($action['event_date']) ?> at <?= formatTime($action['event_time']) ?><br>
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($action['requester_name']) ?> (<?= htmlspecialchars($action['requester_email']) ?>)<br>
                                    <i class="fas fa-users"></i> <?= $action['volunteers_needed'] ?> volunteers needed
                                </div>
                                
                                <small class="text-muted">
                                    Marked by <?= htmlspecialchars($action['admin_name']) ?> on 
                                    <?= date('F j, Y g:i A', strtotime($action['action_date'])) ?>
                                </small>
                            </div>
                            
                            <div class="col-md-4 d-flex align-items-center justify-content-end">
                                <div class="action-buttons">
                                    <button type="button" 
                                            class="btn btn-outline-secondary" 
                                            onclick="confirmCancel(<?= $action['action_id'] ?>, <?= $action['id'] ?>, '<?= htmlspecialchars($action['event_name']) ?>')">
                                        <i class="fas fa-undo"></i> Cancel
                                    </button>
                                    <a href="send_notification.php?action_id=<?= $action['action_id'] ?>" 
                                       class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane"></i> Send Notification
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f57c00; color: white;">
                <h5 class="modal-title">Cancel Pending Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Are you sure?</strong>
                </div>
                <p>This will return the request back to <strong>Pending</strong> status.</p>
                <div class="alert alert-info" id="cancelEventInfo">
                    <!-- Dynamic content -->
                </div>
                <p>You'll be able to review and approve/disapprove the request again from the dashboard.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                <form method="POST" id="cancelForm">
                    <input type="hidden" name="action_id" id="cancelActionId">
                    <input type="hidden" name="request_id" id="cancelRequestId">
                    <button type="submit" name="cancel_action" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Yes, Cancel Action
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .action-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
        border-left: 4px solid var(--gold-dark);
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .action-card:hover {
        box-shadow: 0 4px 12px rgba(128,0,0,0.15);
    }
    
    .action-card.approve {
        border-left-color: #2e7d32;
    }
    
    .action-card.disapprove {
        border-left-color: #c62828;
    }
    
    .action-type-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
    }
    
    .action-type-badge.approve {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #2e7d32;
    }
    
    .action-type-badge.disapprove {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #c62828;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .action-card .row > div:last-child {
            margin-top: 15px;
        }
        
        .action-buttons {
            width: 100%;
        }
        
        .action-buttons .btn {
            flex: 1;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>