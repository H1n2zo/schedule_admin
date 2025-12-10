<?php
require_once 'config.php';
requireAdmin();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Actions - EVSU Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
        }
        body { background: #f5f7fa; }
        .navbar { background: var(--evsu-maroon) !important; }
        .action-card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 15px; border-left: 4px solid var(--gold-dark); transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .action-card:hover { box-shadow: 0 4px 12px rgba(128,0,0,0.15); }
        .action-card.approve { border-left-color: #2e7d32; }
        .action-card.disapprove { border-left-color: #c62828; }
        .action-type-badge { padding: 8px 16px; border-radius: 20px; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        .action-type-badge.approve { background: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; }
        .action-type-badge.disapprove { background: #ffebee; color: #c62828; border: 1px solid #c62828; }
        .btn-primary { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-primary:hover { background-color: var(--maroon-dark); border-color: var(--maroon-dark); }
        .btn-outline-light { border-color: white; color: white; }
        .btn-outline-light:hover { background-color: rgba(255,255,255,0.2); border-color: white; color: white; }
        .alert-info { background-color: #fffbf0; border-color: var(--gold-dark); color: var(--maroon-dark); }
        .btn-outline-secondary { color: #6c757d; border-color: #6c757d; }
        .btn-outline-secondary:hover { background-color: #6c757d; border-color: #6c757d; color: white; }
        .action-buttons { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand">🎓 EVSU Admin Panel</span>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><?= $_SESSION['full_name'] ?></span>
                <a href="dashboard.php" class="btn btn-light btn-sm me-2">Dashboard</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancel(actionId, requestId, eventName) {
            document.getElementById('cancelActionId').value = actionId;
            document.getElementById('cancelRequestId').value = requestId;
            document.getElementById('cancelEventInfo').innerHTML = '<strong>' + eventName + '</strong>';
            
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();
        }
    </script>
</body>
</html>