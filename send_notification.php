<?php
require_once 'config.php';
requireAdmin();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification - EVSU Admin</title>
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
        .email-composer { max-width: 900px; margin: 30px auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(128,0,0,0.15); border-top: 4px solid var(--evsu-gold); }
        .composer-header { padding: 20px 30px; border-bottom: 1px solid #e9ecef; background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%); border-radius: 8px 8px 0 0; color: white; }
        .composer-body { padding: 30px; }
        .email-field { margin-bottom: 20px; }
        .email-field label { font-weight: 600; margin-bottom: 8px; display: block; color: var(--evsu-maroon); }
        .email-field input, .email-field textarea, .email-field select { width: 100%; padding: 10px; border: 1px solid #dee2e6; border-radius: 6px; }
        .email-field input:focus, .email-field textarea:focus, .email-field select:focus { border-color: var(--evsu-gold); outline: none; box-shadow: 0 0 0 0.2rem rgba(255,215,0,0.25); }
        .email-field textarea { min-height: 300px; font-family: monospace; }
        .recipient-info { background: #fffbf0; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid var(--gold-dark); }
        .action-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: 600; margin-bottom: 15px; }
        .action-badge.approve { background: #e8f5e9; color: #2e7d32; border: 2px solid #2e7d32; }
        .action-badge.disapprove { background: #ffebee; color: #c62828; border: 2px solid #c62828; }
        .btn-primary { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-primary:hover { background-color: var(--maroon-dark); border-color: var(--maroon-dark); }
        .btn-outline-secondary { color: var(--evsu-maroon); border-color: var(--evsu-maroon); }
        .btn-outline-secondary:hover { background-color: var(--evsu-maroon); border-color: var(--evsu-maroon); color: white; }
    </style>
</head>
<body>
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
                
                <form method="POST">
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
                        <input type="text" name="subject" value="<?= htmlspecialchars($defaultSubject) ?>" required>
                    </div>
                    
                    <div class="email-field">
                        <label>Message Body</label>
                        <textarea name="body" required><?= htmlspecialchars($defaultBody) ?></textarea>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Edit the message as needed before sending
                        </small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>