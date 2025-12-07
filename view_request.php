<?php
require_once 'config.php';
requireAdmin();

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$db = getDB();
$stmt = $db->prepare("
    SELECT er.*, u.full_name as reviewed_by_name
    FROM event_requests er
    LEFT JOIN users u ON er.reviewed_by = u.id
    WHERE er.id = ?
");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: dashboard.php');
    exit;
}

// Get attachments
$stmt = $db->prepare("SELECT * FROM attachments WHERE request_id = ?");
$stmt->execute([$requestId]);
$attachments = $stmt->fetchAll();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    // Delete attachments from filesystem
    foreach ($attachments as $file) {
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
    }
    
    // Delete from database (cascade should handle related records)
    $stmt = $db->prepare("DELETE FROM attachments WHERE request_id = ?");
    $stmt->execute([$requestId]);
    
    $stmt = $db->prepare("DELETE FROM pending_actions WHERE request_id = ?");
    $stmt->execute([$requestId]);
    
    $stmt = $db->prepare("DELETE FROM audit_log WHERE request_id = ?");
    $stmt->execute([$requestId]);
    
    $stmt = $db->prepare("DELETE FROM event_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    
    header('Location: dashboard.php?deleted=1');
    exit;
}

// Handle approval/disapproval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'disapprove'])) {
        // Update request status to pending_notification
        $stmt = $db->prepare("
            UPDATE event_requests 
            SET status = 'pending_notification', 
                reviewed_by = ?, 
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $requestId]);
        
        // Add to pending actions queue
        $stmt = $db->prepare("
            INSERT INTO pending_actions (request_id, action_type, admin_id) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$requestId, $action, $_SESSION['user_id']]);
        
        // Log audit
        $stmt = $db->prepare("
            INSERT INTO audit_log (request_id, admin_id, action) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$requestId, $_SESSION['user_id'], $action === 'approve' ? 'approved' : 'disapproved']);
        
        header('Location: pending_actions.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($request['event_name']) ?> - EVSU Admin</title>
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
        .gmail-container { max-width: 900px; margin: 30px auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(128,0,0,0.15); border-top: 4px solid var(--evsu-gold); }
        .gmail-header { padding: 20px 30px; border-bottom: 1px solid #e9ecef; }
        .gmail-subject { font-size: 24px; font-weight: 600; margin-bottom: 15px; color: var(--evsu-maroon); }
        .gmail-meta { display: flex; align-items: center; gap: 15px; color: #6c757d; font-size: 14px; }
        .gmail-body { padding: 30px; }
        .info-row { display: flex; margin-bottom: 15px; }
        .info-label { width: 180px; font-weight: 600; color: var(--evsu-maroon); }
        .info-value { flex: 1; color: #6c757d; }
        .description-section { margin-top: 30px; padding-top: 30px; border-top: 1px solid #e9ecef; }
        .attachments-section { margin-top: 30px; padding: 20px; background: #fffbf0; border-radius: 8px; border: 1px solid var(--gold-dark); }
        .attachment-item { display: inline-block; padding: 10px 15px; background: white; border: 1px solid var(--gold-dark); border-radius: 6px; margin: 5px; }
        .action-buttons { padding: 20px 30px; border-top: 1px solid #e9ecef; display: flex; gap: 10px; justify-content: space-between; background: #f8f9fa; }
        .btn-back { margin-bottom: 20px; }
        .btn-success { background-color: #2e7d32; border-color: #2e7d32; }
        .btn-success:hover { background-color: #1b5e20; border-color: #1b5e20; }
        .btn-danger { background-color: #c62828; border-color: #c62828; }
        .btn-danger:hover { background-color: #8e0000; border-color: #8e0000; }
        .btn-outline-secondary { color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-outline-secondary:hover { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); color: white; }
        .status-badge-large { padding: 8px 16px; font-size: 14px; }
        .modal-header { background-color: var(--evsu-maroon); color: white; }
        .modal-header .btn-close { filter: invert(1); }
        .btn-primary { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-primary:hover { background-color: var(--maroon-dark); border-color: var(--maroon-dark); }
        .modal-header.delete-modal { background-color: #c62828; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="gmail-container">
            <!-- Header -->
            <div class="gmail-header">
                <div class="gmail-subject">
                    <?= htmlspecialchars($request['event_name']) ?>
                </div>
                <div class="gmail-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($request['requester_name']) ?></span>
                    <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($request['requester_email']) ?></span>
                    <span class="ms-auto">
                        <?= getStatusBadge($request['status']) ?>
                    </span>
                </div>
            </div>
            
            <!-- Body -->
            <div class="gmail-body">
                <div class="info-row">
                    <div class="info-label">Organization:</div>
                    <div class="info-value"><?= htmlspecialchars($request['organization']) ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Event Date:</div>
                    <div class="info-value">
                        <?= formatDate($request['event_date']) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Event Time:</div>
                    <div class="info-value">
                        <?= formatTime($request['event_time']) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Volunteers Needed:</div>
                    <div class="info-value">
                        <strong><?= $request['volunteers_needed'] ?></strong> volunteers
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Submitted:</div>
                    <div class="info-value">
                        <?= date('F j, Y g:i A', strtotime($request['created_at'])) ?>
                    </div>
                </div>
                
                <?php if ($request['reviewed_by']): ?>
                <div class="info-row">
                    <div class="info-label">Reviewed By:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($request['reviewed_by_name']) ?> on 
                        <?= date('F j, Y g:i A', strtotime($request['reviewed_at'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="description-section">
                    <h5 class="mb-3">Event Description</h5>
                    <p style="white-space: pre-line;"><?= htmlspecialchars($request['description']) ?></p>
                </div>
                
                <?php if (!empty($attachments)): ?>
                <div class="attachments-section">
                    <h6 class="mb-3"><i class="fas fa-paperclip"></i> Attachments (<?= count($attachments) ?>)</h6>
                    <?php foreach ($attachments as $file): ?>
                        <div class="attachment-item">
                            <i class="fas fa-file"></i>
                            <a href="<?= $file['file_path'] ?>" target="_blank">
                                <?= htmlspecialchars($file['file_name']) ?>
                            </a>
                            <small class="text-muted">
                                (<?= number_format($file['file_size'] / 1024, 2) ?> KB)
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Delete Request
                </button>
                
                <?php if ($request['status'] === 'pending'): ?>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-danger" onclick="confirmAction('disapprove')">
                        <i class="fas fa-times"></i> Disapprove
                    </button>
                    <button type="button" class="btn btn-success" onclick="confirmAction('approve')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header delete-modal">
                    <h5 class="modal-title">Delete Event Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning!</strong> This action cannot be undone.
                    </div>
                    <p>Are you sure you want to permanently delete this request?</p>
                    <div class="alert alert-warning">
                        <strong><?= htmlspecialchars($request['event_name']) ?></strong><br>
                        <?= htmlspecialchars($request['organization']) ?><br>
                        <?= formatDate($request['event_date']) ?>
                    </div>
                    <p>All associated data including attachments, audit logs, and pending actions will be permanently removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="delete" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Permanently
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" id="actionType">
                        <button type="submit" class="btn btn-primary" id="confirmBtn">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete() {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        
        function confirmAction(action) {
            const eventName = <?= json_encode($request['event_name']) ?>;
            const organization = <?= json_encode($request['organization']) ?>;
            const eventDate = <?= json_encode(formatDate($request['event_date'])) ?>;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('actionType').value = action;
            
            if (action === 'approve') {
                document.getElementById('confirmTitle').textContent = 'Approve Event Request';
                document.getElementById('confirmMessage').innerHTML = `
                    <p>Are you sure you want to <strong class="text-success">approve</strong> this request?</p>
                    <div class="alert alert-info">
                        <strong>${eventName}</strong><br>
                        ${organization}<br>
                        ${eventDate}
                    </div>
                    <p>This will move the request to the Pending Actions queue where you can send the approval notification.</p>
                `;
                document.getElementById('confirmBtn').className = 'btn btn-success';
            } else {
                document.getElementById('confirmTitle').textContent = 'Disapprove Event Request';
                document.getElementById('confirmMessage').innerHTML = `
                    <p>Are you sure you want to <strong class="text-danger">disapprove</strong> this request?</p>
                    <div class="alert alert-warning">
                        <strong>${eventName}</strong><br>
                        ${organization}<br>
                        ${eventDate}
                    </div>
                    <p>This will move the request to the Pending Actions queue where you can send the disapproval notification.</p>
                `;
                document.getElementById('confirmBtn').className = 'btn btn-danger';
            }
            
            modal.show();
        }
    </script>
</body>
</html>