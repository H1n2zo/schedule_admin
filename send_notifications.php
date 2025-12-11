<?php
/**
 * EVSU Event Management System
 * Send Notification Page - Updated with External Assets
 * File: send_notification.php
 */

require_once 'config.php';
requireAdmin();

// Set page configuration
$pageTitle = 'Send Notification - EVSU Admin';
$customCSS = ['forms'];
$customJS = ['forms'];

$actionId = isset($_GET['action_id']) ? (int)$_GET['action_id'] : 0;

$db = getDB();
$stmt = $db->prepare("
    SELECT 
        pa.id as action_id,
        pa.action_type,
        er.*
    FROM pending_actions pa
    JOIN event_requests er ON pa.request_id = er.id
    WHERE pa.id = ? AND er.status = 'pending_notification'
");
$stmt->execute([$actionId]);
$action = $stmt->fetch();

if (!$action) {
    header('Location: pending_actions.php');
    exit;
}

// Pre-fill email templates
if ($action['action_type'] === 'approve') {
    $defaultSubject = "✓ Event Request APPROVED - " . $action['event_name'];
    $defaultBody = "Dear " . $action['requester_name'] . ",\n\n";
    $defaultBody .= "Great news! Your event request has been APPROVED.\n\n";
    $defaultBody .= "Event Details:\n";
    $defaultBody .= "- Event Name: " . $action['event_name'] . "\n";
    $defaultBody .= "- Organization: " . $action['organization'] . "\n";
    $defaultBody .= "- Date: " . formatDate($action['event_date']) . "\n";
    $defaultBody .= "- Time: " . formatTime($action['event_time']) . "\n";
    $defaultBody .= "- Volunteers Needed: " . $action['volunteers_needed'] . "\n\n";
    $defaultBody .= "You can now proceed with your event preparations. Our team will contact you soon regarding volunteer assignments.\n\n";
    $defaultBody .= "Best regards,\n";
    $defaultBody .= "EVSU Admin Council";
} else {
    $defaultSubject = "✗ Event Request DISAPPROVED - " . $action['event_name'];
    $defaultBody = "Dear " . $action['requester_name'] . ",\n\n";
    $defaultBody .= "We regret to inform you that your event request has been DISAPPROVED.\n\n";
    $defaultBody .= "Event Details:\n";
    $defaultBody .= "- Event Name: " . $action['event_name'] . "\n";
    $defaultBody .= "- Organization: " . $action['organization'] . "\n";
    $defaultBody .= "- Requested Date: " . formatDate($action['event_date']) . "\n\n";
    $defaultBody .= "Reason: [Please specify the reason for disapproval]\n\n";
    $defaultBody .= "If you have any questions or would like to discuss this decision, please contact us.\n\n";
    $defaultBody .= "Best regards,\n";
    $defaultBody .= "EVSU Admin Council";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitizeInput($_POST['subject']);
    $body = $_POST['body']; // Don't sanitize too much, preserve formatting
    $notifyMethod = $_POST['notify_method'] ?? 'email';
    
    // Update request status
    $newStatus = $action['action_type'] === 'approve' ? 'approved' : 'disapproved';
    $stmt = $db->prepare("UPDATE event_requests SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $action['id']]);
    
    // Log notification sent
    $stmt = $db->prepare("
        INSERT INTO audit_log (request_id, admin_id, action, notes, email_sent) 
        VALUES (?, ?, 'notification_sent', ?, 1)
    ");
    $stmt->execute([$action['id'], $_SESSION['user_id'], $subject]);
    
    // Delete from pending actions
    $stmt = $db->prepare("DELETE FROM pending_actions WHERE id = ?");
    $stmt->execute([$actionId]);
    
    // In production, actually send email here using PHPMailer or similar
    // mail($action['requester_email'], $subject, $body);
    
    header('Location: pending_actions.php?sent=1');
    exit;
}

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container">
    <a href="pending_actions.php" class="btn btn-outline-secondary mt-3 mb-3">
        <i class="fas fa-arrow-left"></i> Back to Pending Actions
    </a>
    
    <div class="email-composer">
        <div class="composer-header">
            <span class="action-badge <?= $action['action_type'] ?>">
                <?= $action['action_type'] === 'approve' ? '✓ APPROVAL' : '✗ DISAPPROVAL' ?> Notification
            </span>
            <h4>Send Email Notification</h4>
        </div>
        
        <div class="composer-body">
            <div class="recipient-info">
                <strong><i class="fas fa-envelope"></i> To:</strong> 
                <?= htmlspecialchars($action['requester_email']) ?> 
                (<?= htmlspecialchars($action['requester_name']) ?>)
                <br>
                <strong><i class="fas fa-calendar"></i> Event:</strong> 
                <?= htmlspecialchars($action['event_name']) ?> - 
                <?= formatDate($action['event_date']) ?>
            </div>
            
            <form method="POST" data-warn-unsaved="true">
                <div class="email-field">
                    <label>Notification Method</label>
                    <select name="notify_method" class="form-select">
                        <option value="email">Email Only</option>
                        <option value="dashboard">Website Dashboard Only</option>
                        <option value="both">Both Email & Dashboard</option>
                    </select>
                </div>
                
                <div class="email-field">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" 
                           value="<?= htmlspecialchars($defaultSubject) ?>" required>
                </div>
                
                <div class="email-field">
                    <label>Message Body</label>
                    <textarea name="body" class="form-control" required><?= htmlspecialchars($defaultBody) ?></textarea>
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Edit the message as needed before sending
                    </small>
                </div>
                
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <a href="pending_actions.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>