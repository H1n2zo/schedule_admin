<?php
/**
 * EVSU Event Management System
 * View Request Page - FIXED to show event end time
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
                    <strong><?= formatTime($request['event_time']) ?></strong>
                    <?php if ($request['event_end_time']): ?>
                        to <strong><?= formatTime($request['event_end_time']) ?></strong>
                        <small class="text-muted ms-2">
                            <?php 
                            $start = strtotime($request['event_time']);
                            $end = strtotime($request['event_end_time']);
                            $duration = ($end - $start) / 3600; // hours
                            echo "(" . number_format($duration, 1) . " hours)";
                            ?>
                        </small>
                    <?php endif; ?>
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
                
                <div class="attachments-grid">
                    <?php foreach ($attachments as $file): 
                        $isImage = strpos($file['file_type'], 'image') !== false;
                        $isPDF = strpos($file['file_type'], 'pdf') !== false;
                        $fileExtension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
                    ?>
                        <div class="attachment-card">
                            <?php if ($isImage): ?>
                                <!-- Image Preview -->
                                <div class="attachment-preview image-preview" onclick="window.open('download.php?id=<?= $file['id'] ?>&action=view', '_blank')">
                                    <img src="download.php?id=<?= $file['id'] ?>&action=view" alt="<?= htmlspecialchars($file['file_name']) ?>">
                                </div>
                            <?php elseif ($isPDF): ?>
                                <!-- PDF Icon -->
                                <div class="attachment-preview pdf-preview" onclick="window.open('download.php?id=<?= $file['id'] ?>&action=view', '_blank')">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                            <?php else: ?>
                                <!-- Generic File Icon -->
                                <div class="attachment-preview file-preview" onclick="window.open('download.php?id=<?= $file['id'] ?>&action=view', '_blank')">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="attachment-info">
                                <div class="attachment-name" title="<?= htmlspecialchars($file['file_name']) ?>">
                                    <?= htmlspecialchars($file['file_name']) ?>
                                </div>
                                <div class="attachment-size">
                                    <?= number_format($file['file_size'] / 1024, 2) ?> KB
                                </div>
                            </div>
                            
                            <div class="attachment-actions">
                                <a href="download.php?id=<?= $file['id'] ?>&action=view" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt"></i> Open
                                </a>
                                <a href="download.php?id=<?= $file['id'] ?>&action=download" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
    
    .attachments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .attachment-card {
        background: white;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .attachment-card:hover {
        border-color: var(--evsu-gold);
        box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
        transform: translateY(-3px);
    }
    
    .attachment-preview {
        width: 100%;
        height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        overflow: hidden;
        cursor: pointer;
    }
    
    .attachment-preview.image-preview {
        position: relative;
    }
    
    .attachment-preview.image-preview:hover::after {
        content: 'Click to Open';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 1.2rem;
        font-weight: 600;
        color: white;
        text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        background: rgba(128, 0, 0, 0.9);
        padding: 10px 20px;
        border-radius: 8px;
    }
    
    .attachment-preview.image-preview:hover {
        background: rgba(0,0,0,0.7);
    }
    
    .attachment-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.3s ease;
    }
    
    .attachment-preview.image-preview:hover img {
        opacity: 0.3;
    }
    
    .attachment-preview.pdf-preview {
        background: #dc3545;
        transition: all 0.3s ease;
    }
    
    .attachment-preview.pdf-preview:hover {
        background: #c82333;
        transform: scale(1.05);
    }
    
    .attachment-preview.pdf-preview i {
        font-size: 4rem;
        color: white;
        transition: all 0.3s ease;
    }
    
    .attachment-preview.pdf-preview:hover i {
        transform: scale(1.1);
    }
    
    .attachment-preview.file-preview {
        background: #6c757d;
        transition: all 0.3s ease;
    }
    
    .attachment-preview.file-preview:hover {
        background: #5a6268;
        transform: scale(1.05);
    }
    
    .attachment-preview.file-preview i {
        font-size: 4rem;
        color: white;
        transition: all 0.3s ease;
    }
    
    .attachment-preview.file-preview:hover i {
        transform: scale(1.1);
    }
    
    .attachment-info {
        padding: 15px;
        flex-grow: 1;
    }
    
    .attachment-name {
        font-weight: 600;
        color: var(--evsu-maroon);
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .attachment-size {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .attachment-actions {
        padding: 10px 15px;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 8px;
    }
    
    .attachment-actions .btn {
        flex: 1;
        font-size: 0.85rem;
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
        
        .attachments-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>