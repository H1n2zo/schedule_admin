<?php
/**
 * EVSU Event Management System
 * Send Notification - Admin can attach NEW files to send
 * File: send_notification.php
 */

require_once 'config.php';
requireAdmin();

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!in_array($action, ['approve', 'decline'])) {
    header('Location: dashboard.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT * FROM event_requests WHERE id = ? AND status = 'pending'
");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: dashboard.php?error=request_not_found');
    exit;
}

// Set page configuration
$pageTitle = ($action === 'approve' ? 'Approve' : 'Decline') . ' Event Request - EVSU Admin';
$customCSS = ['forms'];
$customJS = ['forms'];

// Pre-fill email templates with end time
$timeDisplay = formatTime($request['event_time']);
if ($request['event_end_time']) {
    $timeDisplay .= " - " . formatTime($request['event_end_time']);
}

if ($action === 'approve') {
    $defaultSubject = "✓ Event Request APPROVED - " . $request['event_name'];
    $defaultBody = "Dear " . $request['requester_name'] . ",\n\n";
    $defaultBody .= "Great news! Your event request has been APPROVED.\n\n";
    $defaultBody .= "Event Details:\n";
    $defaultBody .= "- Event Name: " . $request['event_name'] . "\n";
    $defaultBody .= "- Organization: " . $request['organization'] . "\n";
    $defaultBody .= "- Date: " . formatDate($request['event_date']) . "\n";
    $defaultBody .= "- Time: " . $timeDisplay . "\n";
    $defaultBody .= "- Volunteers Needed: " . $request['volunteers_needed'] . "\n\n";
    $defaultBody .= "You can now proceed with your event preparations. Our team will contact you soon regarding volunteer assignments.\n\n";
    $defaultBody .= "Best regards,\n";
    $defaultBody .= "EVSU Admin Council";
} else {
    $defaultSubject = "✗ Event Request DECLINED - " . $request['event_name'];
    $defaultBody = "Dear " . $request['requester_name'] . ",\n\n";
    $defaultBody .= "We regret to inform you that your event request has been DECLINED.\n\n";
    $defaultBody .= "Event Details:\n";
    $defaultBody .= "- Event Name: " . $request['event_name'] . "\n";
    $defaultBody .= "- Organization: " . $request['organization'] . "\n";
    $defaultBody .= "- Requested Date: " . formatDate($request['event_date']) . "\n";
    $defaultBody .= "- Requested Time: " . $timeDisplay . "\n\n";
    $defaultBody .= "Reason: [Please specify the reason for declining]\n\n";
    $defaultBody .= "If you have any questions or would like to discuss this decision, please contact us.\n\n";
    $defaultBody .= "Best regards,\n";
    $defaultBody .= "EVSU Admin Council";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitizeInput($_POST['subject']);
    $body = $_POST['body'];
    $hasAttachments = 0;
    
    // Check if admin uploaded new files
    if (isset($_FILES['admin_attachments']) && !empty($_FILES['admin_attachments']['name'][0])) {
        $hasAttachments = 1;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Update request status
        $newStatus = $action === 'approve' ? 'approved' : 'declined';
        $stmt = $db->prepare("
            UPDATE event_requests 
            SET status = ?, 
                reviewed_by = ?, 
                reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$newStatus, $_SESSION['user_id'], $requestId]);
        
        // Save to notification_history table
        $stmt = $db->prepare("
            INSERT INTO notification_history 
            (request_id, action_type, admin_id, recipient_email, subject, body, attachments_sent, sent_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $requestId,
            $action,
            $_SESSION['user_id'],
            $request['requester_email'],
            $subject,
            $body,
            $hasAttachments
        ]);
        
        // Handle admin's new file uploads (if any)
        if ($hasAttachments) {
            $uploadDir = UPLOAD_DIR . 'notifications/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            foreach ($_FILES['admin_attachments']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['admin_attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = basename($_FILES['admin_attachments']['name'][$key]);
                    $fileSize = $_FILES['admin_attachments']['size'][$key];
                    $fileType = $_FILES['admin_attachments']['type'][$key];
                    
                    // Generate unique filename
                    $uniqueName = time() . '_' . $fileName;
                    $filePath = $uploadDir . $uniqueName;
                    
                    if ($fileSize <= MAX_FILE_SIZE) {
                        move_uploaded_file($tmpName, $filePath);
                        // Note: These are temporary files just for email, not saved to attachments table
                    }
                }
            }
        }
        
        // Log in audit_log
        $stmt = $db->prepare("
            INSERT INTO audit_log (request_id, admin_id, action, notes, email_sent) 
            VALUES (?, ?, ?, ?, 1)
        ");
        $actionType = $action === 'approve' ? 'approved' : 'declined';
        $stmt->execute([
            $requestId, 
            $_SESSION['user_id'], 
            $actionType, 
            $subject
        ]);
        
        // Commit transaction
        $db->commit();
        
        // In production: Send actual email here using PHPMailer with admin's attachments
        
        header('Location: dashboard.php?notification_sent=1&action=' . $action);
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        $error = "Failed to process request: " . $e->getMessage();
    }
}

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container">
    <a href="view_request.php?id=<?= $requestId ?>" class="btn btn-outline-secondary mt-3 mb-3">
        <i class="fas fa-arrow-left"></i> Back to Request
    </a>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="email-composer">
        <div class="composer-header">
            <span class="action-badge <?= $action ?>">
                <?= $action === 'approve' ? '✓ APPROVAL' : '✗ DECLINE' ?> Notification
            </span>
            <h4>Send Email Notification</h4>
        </div>
        
        <div class="composer-body">
            <div class="recipient-info">
                <strong><i class="fas fa-envelope"></i> To:</strong> 
                <?= htmlspecialchars($request['requester_email']) ?> 
                (<?= htmlspecialchars($request['requester_name']) ?>)
                <br>
                <strong><i class="fas fa-calendar"></i> Event:</strong> 
                <?= htmlspecialchars($request['event_name']) ?> - 
                <?= formatDate($request['event_date']) ?>
                <br>
                <strong><i class="fas fa-building"></i> Organization:</strong> 
                <?= htmlspecialchars($request['organization']) ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data" data-warn-unsaved="true">
                <div class="email-field">
                    <label>Subject <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-control" 
                           value="<?= htmlspecialchars($defaultSubject) ?>" required>
                </div>
                
                <div class="email-field">
                    <label>Message Body <span class="required">*</span></label>
                    <textarea name="body" class="form-control" required><?= htmlspecialchars($defaultBody) ?></textarea>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Edit the message as needed before sending
                    </small>
                </div>
                
                <!-- Admin Attachment Upload Section -->
                <div class="admin-attachments-section">
                    <h6>
                        <i class="fas fa-paperclip"></i> Attach Files to Send 
                        <span class="badge bg-secondary">Optional</span>
                    </h6>
                    <p class="text-muted mb-3">
                        Upload documents to send along with this notification (guidelines, forms, schedules, etc.)
                    </p>
                    
                    <div class="file-upload-area" onclick="document.getElementById('adminFileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5>Click to Upload Files</h5>
                        <p>PDF, DOC, DOCX, JPG, PNG (Max 5MB per file)</p>
                    </div>
                    <input type="file" id="adminFileInput" name="admin_attachments[]" 
                           class="d-none" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           onchange="displayAdminFiles(this)">
                    <div id="adminFileList" class="mt-2"></div>
                </div>
                
                <div class="d-flex justify-content-between flex-wrap gap-2 mt-4">
                    <a href="view_request.php?id=<?= $requestId ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Send <?= ucfirst($action) ?> Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .email-composer {
        max-width: 900px;
        margin: 30px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(128,0,0,0.15);
        border-top: 4px solid var(--evsu-gold);
    }
    
    .composer-header {
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
        border-radius: 8px 8px 0 0;
        color: white;
    }
    
    .composer-body {
        padding: 30px;
    }
    
    .email-field {
        margin-bottom: 20px;
    }
    
    .email-field label {
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
        color: var(--evsu-maroon);
    }
    
    .email-field input,
    .email-field textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        font-size: 1rem;
    }
    
    .email-field input:focus,
    .email-field textarea:focus {
        border-color: var(--evsu-gold);
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255,215,0,0.25);
    }
    
    .email-field textarea {
        min-height: 300px;
        font-family: 'Courier New', monospace;
        line-height: 1.6;
    }
    
    .recipient-info {
        background: #fffbf0;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid var(--gold-dark);
        line-height: 1.8;
    }
    
    .action-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .action-badge.approve {
        background: #e8f5e9;
        color: #2e7d32;
        border: 2px solid #2e7d32;
    }
    
    .action-badge.decline {
        background: #ffebee;
        color: #c62828;
        border: 2px solid #c62828;
    }
    
    .admin-attachments-section {
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
    }
    
    .admin-attachments-section h6 {
        color: var(--evsu-maroon);
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .file-upload-area {
        border: 3px dashed #dee2e6;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: white;
    }
    
    .file-upload-area:hover {
        border-color: var(--evsu-gold);
        background: #fffbf0;
        transform: translateY(-2px);
    }
    
    .file-upload-area i {
        font-size: 3rem;
        color: var(--evsu-gold);
        margin-bottom: 15px;
        display: block;
    }
    
    .file-upload-area h5 {
        color: var(--evsu-maroon);
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .file-upload-area p {
        color: #6c757d;
        margin: 0;
    }
    
    #adminFileList {
        margin-top: 15px;
    }
    
    #adminFileList > div {
        padding: 12px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    #adminFileList i {
        color: var(--evsu-gold);
        font-size: 1.2rem;
    }
    
    @media (max-width: 768px) {
        .email-composer {
            margin: 20px;
        }
        
        .composer-header,
        .composer-body {
            padding: 20px;
        }
        
        .d-flex.flex-wrap {
            flex-direction: column;
        }
        
        .d-flex.flex-wrap .btn {
            width: 100%;
        }
        
        .file-upload-area {
            padding: 30px 20px;
        }
    }
</style>

<script>
function displayAdminFiles(input) {
    const fileList = document.getElementById('adminFileList');
    fileList.innerHTML = '';
    
    if (input.files.length > 0) {
        fileList.innerHTML = '<div class="alert alert-success mb-2"><strong><i class="fas fa-check-circle"></i> Files Ready to Send:</strong></div>';
        
        Array.from(input.files).forEach(file => {
            const fileItem = document.createElement('div');
            
            const fileSize = (file.size / 1024).toFixed(2);
            const fileIcon = getFileIcon(file.type);
            
            fileItem.innerHTML = `
                <i class="fas fa-${fileIcon}"></i> 
                <strong>${file.name}</strong>
                <span class="text-muted ms-auto">(${fileSize} KB)</span>
            `;
            
            fileList.appendChild(fileItem);
        });
    }
}

function getFileIcon(fileType) {
    if (fileType.includes('pdf')) return 'file-pdf';
    if (fileType.includes('word') || fileType.includes('document')) return 'file-word';
    if (fileType.includes('image')) return 'file-image';
    return 'file';
}
</script>

<?php
// Include footer
include 'includes/footer.php';
?>