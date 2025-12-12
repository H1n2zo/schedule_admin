<?php
/**
 * EVSU Event Management System
 * View Request Page - Direct Approve/Decline with Notification
 * File: view_request.php
 */

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

// Set page configuration
$pageTitle = htmlspecialchars($request['event_name']) . ' - EVSU Admin';
$customCSS = ['forms'];
$customJS = ['modals'];

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
    
    $stmt = $db->prepare("DELETE FROM audit_log WHERE request_id = ?");
    $stmt->execute([$requestId]);
    
    $stmt = $db->prepare("DELETE FROM event_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    
    header('Location: dashboard.php?deleted=1');
    exit;
}

// Prepare event data for JavaScript
$eventData = [
    'eventName' => $request['event_name'],
    'organization' => $request['organization'],
    'eventDate' => formatDate($request['event_date'])
];

$inlineScripts = "
    const eventData = " . json_encode($eventData) . ";
    
    function confirmApprove() {
        window.location.href = 'send_notification.php?id={$requestId}&action=approve';
    }
    
    function confirmDecline() {
        window.location.href = 'send_notification.php?id={$requestId}&action=decline';
    }
    
    function confirmDelete() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
";

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container">
    <a href="history.php" class="btn btn-outline-secondary btn-back mt-3 mb-3">
        Back to History
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
                <button type="button" class="btn btn-danger btn-lg" onclick="confirmDecline()">
                    <i class="fas fa-times"></i> Decline & Notify
                </button>
                <button type="button" class="btn btn-success btn-lg" onclick="confirmApprove()">
                    <i class="fas fa-check"></i> Approve & Notify
                </button>
            </div>
            <?php elseif ($request['status'] === 'approved'): ?>
            <div class="alert alert-success mb-0">
                <i class="fas fa-check-circle"></i> This request has been approved
            </div>
            <?php elseif ($request['status'] === 'declined'): ?>
            <div class="alert alert-danger mb-0">
                <i class="fas fa-times-circle"></i> This request has been declined
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
                <p>All associated data including attachments, audit logs will be permanently removed.</p>
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

<style>
    .gmail-container {
        max-width: 900px;
        margin: 30px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(128,0,0,0.15);
        border-top: 4px solid var(--evsu-gold);
    }
    
    .gmail-header {
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .gmail-subject {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 15px;
        color: var(--evsu-maroon);
    }
    
    .gmail-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        color: #6c757d;
        font-size: 14px;
        flex-wrap: wrap;
    }
    
    .gmail-body {
        padding: 30px;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 15px;
    }
    
    .info-label {
        width: 180px;
        font-weight: 600;
        color: var(--evsu-maroon);
        flex-shrink: 0;
    }
    
    .info-value {
        flex: 1;
        color: #6c757d;
    }
    
    .description-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e9ecef;
    }
    
    .attachments-section {
        margin-top: 30px;
        padding: 20px;
        background: #fffbf0;
        border-radius: 8px;
        border: 1px solid var(--gold-dark);
    }
    
    .attachment-item {
        display: inline-block;
        padding: 10px 15px;
        background: white;
        border: 1px solid var(--gold-dark);
        border-radius: 6px;
        margin: 5px;
    }
    
    .action-buttons {
        padding: 20px 30px;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        background: #f8f9fa;
        flex-wrap: wrap;
    }
    
    @media (max-width: 768px) {
        .info-row {
            flex-direction: column;
        }
        
        .info-label {
            width: 100%;
            margin-bottom: 5px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .action-buttons .d-flex {
            width: 100%;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>