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
    SELECT * FROM support_requests 
    WHERE id = ?
");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: dashboard.php');
    exit;
}

// Set page configuration
$pageTitle = htmlspecialchars($request['event_name']) . ' - CRCY Admin';
$customCSS = ['forms'];
$customJS = ['modals'];

// Get attachments
$stmt = $db->prepare("SELECT * FROM attachments WHERE request_id = ?");
$stmt->execute([$requestId]);
$attachments = $stmt->fetchAll();



// Handle approve/decline actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Verify admin permissions
    if (!isAdmin()) {
        logSecurityEvent('unauthorized_approval_attempt', [
            'ip' => $clientIP,
            'request_id' => $requestId,
            'user_session' => $_SESSION['user_id'] ?? 'none'
        ]);
        header('Location: error.php?code=403&message=Access denied');
        exit;
    }
    
    // Rate limiting for approval actions
    if (!checkRateLimit($clientIP . '_approval', 10, 300)) { // 10 actions per 5 minutes
        logSecurityEvent('approval_rate_limit_exceeded', ['ip' => $clientIP]);
        $_SESSION['error'] = 'Too many approval actions. Please wait before trying again.';
        header('Location: view_request.php?id=' . $requestId);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        if (isset($_POST['approve'])) {
            // Validate request is still pending
            $stmt = $db->prepare("SELECT status FROM support_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $currentStatus = $stmt->fetchColumn();
            
            if ($currentStatus !== 'pending') {
                throw new Exception('Request has already been processed');
            }
            
            // Check for time conflicts before approving
            $conflicts = checkDateConflict($request['event_date'], $request['event_time'], $request['event_end_time'], $requestId);
            if (!empty($conflicts)) {
                $conflictEvent = $conflicts[0];
                throw new Exception('Cannot approve: Time conflict with "' . $conflictEvent['event_name'] . '" at ' . formatTime($conflictEvent['event_time']));
            }
            
            // Approve the request
            $stmt = $db->prepare("
                UPDATE support_requests 
                SET status = 'approved', updated_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$requestId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to approve request - it may have been already processed');
            }
            
            // Log the approval
            logSecurityEvent('request_approved', [
                'request_id' => $requestId,
                'admin_id' => $_SESSION['user_id'],
                'event_name' => $request['event_name'],
                'event_date' => $request['event_date']
            ]);
            
            $db->commit();
            
            // Send approval email
            require_once 'send_email.php';
            sendStatusUpdateEmail($requestId, $request['requester_email'], $request['requester_name'], 
                                $request['event_name'], $request['event_date'], 'approved');
            
            $_SESSION['success'] = '✅ Support request has been approved successfully!';
            
        } elseif (isset($_POST['decline'])) {
            // Validate decline reason
            $declineReason = sanitizeInput($_POST['decline_reason'] ?? '');
            
            if (empty($declineReason) || strlen($declineReason) < 10) {
                throw new Exception('Decline reason must be at least 10 characters long');
            }
            
            if (strlen($declineReason) > 500) {
                throw new Exception('Decline reason is too long (maximum 500 characters)');
            }
            
            // Validate request is still pending
            $stmt = $db->prepare("SELECT status FROM support_requests WHERE id = ?");
            $stmt->execute([$requestId]);
            $currentStatus = $stmt->fetchColumn();
            
            if ($currentStatus !== 'pending') {
                throw new Exception('Request has already been processed');
            }
            
            // Decline the request
            $stmt = $db->prepare("
                UPDATE support_requests 
                SET status = 'declined', updated_at = NOW(), rejection_reason = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$declineReason, $requestId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to decline request - it may have been already processed');
            }
            
            // Log the decline
            logSecurityEvent('request_declined', [
                'request_id' => $requestId,
                'admin_id' => $_SESSION['user_id'],
                'event_name' => $request['event_name'],
                'reason' => $declineReason
            ]);
            
            $db->commit();
            
            // Send decline email
            require_once 'send_email.php';
            sendStatusUpdateEmail($requestId, $request['requester_email'], $request['requester_name'], 
                                $request['event_name'], $request['event_date'], 'declined', $declineReason);
            
            $_SESSION['success'] = '❌ Support request has been declined.';
            
        } else {
            throw new Exception('Invalid action');
        }
        
        recordRateLimitAttempt($clientIP . '_approval');
        
        // Refresh the request data
        $stmt = $db->prepare("SELECT * FROM support_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
    } catch (Exception $e) {
        $db->rollback();
        logSecurityEvent('approval_error', [
            'request_id' => $requestId,
            'error' => $e->getMessage(),
            'admin_id' => $_SESSION['user_id'] ?? 'unknown'
        ]);
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
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
        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }
    
    function confirmDecline() {
        const modal = new bootstrap.Modal(document.getElementById('declineModal'));
        modal.show();
    }
    
    function submitApproval() {
        document.getElementById('approveForm').submit();
    }
    
    function submitDecline() {
        const reason = document.getElementById('declineReason').value.trim();
        if (reason === '') {
            alert('Please provide a reason for declining this request.');
            return;
        }
        document.getElementById('declineForm').submit();
    }
    

";

// Include header
include 'includes/header.php';
?>

<!-- Modern Admin Layout -->
<div class="modern-admin-layout">
    <!-- Modern Header -->
    <div class="modern-main-header">
        <div class="modern-header-left">
            <div class="modern-welcome-section">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <div class="bg-gmail-blue rounded-circle shadow-gmail-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-file-text text-white fs-5"></i>
                    </div>
                    <div>
                        <h1 class="modern-page-title mb-1"><?= htmlspecialchars($request['event_name']) ?></h1>
                        <p class="modern-page-subtitle mb-0 text-muted">Support Request Details</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modern-header-right">
            <div class="dropdown">
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person me-2"></i>
                    Admin
                    <i class="bi bi-chevron-down ms-2"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Admin Account</h6></li>
                    <li><a class="dropdown-item" href="maintenance.php">
                        <i class="bi bi-tools"></i> Maintenance Mode
                    </a></li>
                    <li><a class="dropdown-item" href="logs/security.log" target="_blank">
                        <i class="bi bi-file-text"></i> View Logs
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="modern-main-content" style="padding: var(--gmail-spacing-lg);">
        <div class="card shadow-gmail-2" style="max-width: 1000px; margin: 0 auto;">
            <!-- Request Header -->
            <div class="card-header bg-white border-bottom">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-gmail-blue text-white px-3 py-2">
                                <i class="bi bi-calendar-event me-1"></i>
                                <?= htmlspecialchars($request['organization']) ?>
                            </span>
                            <?= getStatusBadge($request['status']) ?>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($request['requester_name']) ?>
                            <span class="mx-2">•</span>
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($request['requester_email']) ?>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Request Details -->
            <div class="card-body p-4">
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
                <div class="info-label">Venue:</div>
                <div class="info-value"><?= htmlspecialchars($request['venue']) ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Expected Participants:</div>
                <div class="info-value"><?= $request['expected_participants'] ?> attendees</div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Volunteers Needed:</div>
                <div class="info-value">
                    <strong><?= $request['volunteers_needed'] ?></strong> volunteers
                </div>
            </div>
            
            <?php if ($request['volunteer_roles']): ?>
            <div class="info-row">
                <div class="info-label">Volunteer Roles:</div>
                <div class="info-value"><?= htmlspecialchars($request['volunteer_roles']) ?></div>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <div class="info-label">Requester:</div>
                <div class="info-value">
                    <strong><?= htmlspecialchars($request['requester_name']) ?></strong>
                    <?php if ($request['requester_position']): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($request['requester_position']) ?></small>
                    <?php endif; ?>
                    <br><a href="mailto:<?= htmlspecialchars($request['requester_email']) ?>"><?= htmlspecialchars($request['requester_email']) ?></a>
                    <?php if ($request['contact_number']): ?>
                        <br><small class="text-muted">📞 <?= htmlspecialchars($request['contact_number']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Submitted:</div>
                <div class="info-value">
                    <?= date('F j, Y g:i A', strtotime($request['submitted_at'])) ?>
                </div>
            </div>
            
            <?php if ($request['status'] !== 'pending'): ?>
            <div class="info-row">
                <div class="info-label">Reviewed:</div>
                <div class="info-value">
                    <?php if ($request['updated_at'] && $request['status'] !== 'pending'): ?>
                        CRCY Administrator on <?= date('F j, Y g:i A', strtotime($request['updated_at'])) ?>
                    <?php else: ?>
                        <span class="text-muted">Not yet reviewed</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="description-section">
                <h5 class="mb-3">Event Description</h5>
                <p style="white-space: pre-line;"><?= htmlspecialchars($request['event_description'] ?? 'No description provided') ?></p>
            </div>
            
            <?php if ($request['special_requirements']): ?>
            <div class="description-section">
                <h5 class="mb-3">Special Requirements</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <p class="mb-0" style="white-space: pre-line;"><?= htmlspecialchars($request['special_requirements']) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($attachments)): ?>
            <div class="attachments-section">
                <h6 class="mb-3"><i class="bi bi-paperclip"></i> Attachments (<?= count($attachments) ?>)</h6>
                
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
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </div>
                            <?php else: ?>
                                <!-- Generic File Icon -->
                                <div class="attachment-preview file-preview" onclick="window.open('download.php?id=<?= $file['id'] ?>&action=view', '_blank')">
                                    <i class="bi bi-file-earmark-text"></i>
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
                                    <i class="bi bi-box-arrow-up-right"></i> Open
                                </a>
                                <a href="download.php?id=<?= $file['id'] ?>&action=download" class="btn btn-sm btn-primary">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
            <!-- Action Buttons -->
            <div class="card-footer bg-light border-top p-4">
                <?php if ($request['status'] === 'pending'): ?>
                <div class="d-flex gap-3 justify-content-end">
                    <button type="button" class="btn btn-danger px-4" onclick="confirmDecline()">
                        <i class="bi bi-x-circle me-2"></i>Decline Request
                    </button>
                    <button type="button" class="btn btn-success px-4" onclick="confirmApprove()">
                        <i class="bi bi-check-circle me-2"></i>Approve Request
                    </button>
                </div>
                <?php elseif ($request['status'] === 'approved'): ?>
                <div class="alert alert-success mb-0 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                    <div>
                        <strong>Request Approved</strong>
                        <div class="small text-muted">This support request has been approved and volunteers will be coordinated.</div>
                    </div>
                </div>
                <?php elseif ($request['status'] === 'declined'): ?>
                <div class="alert alert-danger mb-0">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-x-circle-fill me-3 fs-4 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong>Request Declined</strong>
                            <div class="small text-muted mb-2">This support request has been declined.</div>
                            <?php if ($request['rejection_reason']): ?>
                                <div class="mt-2 p-3 bg-white rounded border-start border-danger border-3">
                                    <strong class="text-danger">Reason:</strong>
                                    <div class="mt-1"><?= htmlspecialchars($request['rejection_reason']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Approve Support Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Confirm Approval</strong>
                </div>
                <p>Are you sure you want to approve this CRCY support request?</p>
                <div class="alert alert-warning">
                    <strong><?= htmlspecialchars($request['event_name']) ?></strong><br>
                    <?= htmlspecialchars($request['organization']) ?><br>
                    <?= formatDate($request['event_date']) ?> at <?= formatTime($request['event_time']) ?>
                </div>
                <p><strong>⚠️ Note:</strong> Once approved, this action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="approveForm" method="POST" style="display: inline;">
                    <button type="button" class="btn btn-success" onclick="submitApproval()">
                        <i class="bi bi-check"></i> Approve Request
                    </button>
                    <input type="hidden" name="approve" value="1">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Decline Modal -->
<div class="modal fade" id="declineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle"></i> Decline Support Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>Decline Request</strong>
                </div>
                <p>Please provide a reason for declining this support request:</p>
                <div class="alert alert-warning">
                    <strong><?= htmlspecialchars($request['event_name']) ?></strong><br>
                    <?= htmlspecialchars($request['organization']) ?><br>
                    <?= formatDate($request['event_date']) ?> at <?= formatTime($request['event_time']) ?>
                </div>
                <form id="declineForm" method="POST">
                    <div class="mb-3">
                        <label for="declineReason" class="form-label">Reason for Decline <span class="text-danger">*</span></label>
                        <textarea id="declineReason" name="decline_reason" class="form-control" rows="3" 
                                  placeholder="e.g., Date conflict with existing event, insufficient volunteers available, etc." required></textarea>
                    </div>
                    <input type="hidden" name="decline" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitDecline()">
                    <i class="bi bi-x"></i> Decline Request
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>