<?php
/**
 * Spam Email Management for Support Requests
 * Block specific emails from submitting requests
 * PDO Version
 */

/**
 * Check if an email is marked as spam
 */
function isSpamEmail($db, $email) {
    $sql = "SELECT is_spam FROM spam_emails WHERE email = ? AND is_spam = TRUE";
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Mark an email as spam
 */
function markEmailAsSpam($db, $email, $reason, $marked_by_admin_id) {
    try {
        // Insert or update spam_emails table
        $sql = "INSERT INTO spam_emails (email, is_spam, spam_marked_at, spam_marked_by, spam_reason)
                VALUES (?, TRUE, NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE
                is_spam = TRUE,
                spam_marked_at = NOW(),
                spam_marked_by = ?,
                spam_reason = ?";
        
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([$email, $marked_by_admin_id, $reason, $marked_by_admin_id, $reason])) {
            // Log the action
            logSpamEmailAction($db, $email, 'marked_spam', $reason, $marked_by_admin_id);
            
            // Mark all existing support requests from this email as spam
            $update_sql = "UPDATE support_requests 
                          SET is_spam_request = TRUE, spam_checked_at = NOW()
                          WHERE requester_email = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$email]);
            
            return ['success' => true, 'message' => 'Email marked as spam'];
        }
        
        return ['success' => false, 'message' => 'Failed to mark email as spam'];
        
    } catch (Exception $e) {
        error_log("Error marking email as spam: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Unmark an email as spam (mark as genuine)
 */
function markEmailAsGenuine($db, $email, $marked_by_admin_id) {
    try {
        // Update spam_emails table
        $sql = "UPDATE spam_emails 
                SET is_spam = FALSE, 
                    spam_marked_at = NOW(),
                    spam_marked_by = ?
                WHERE email = ?";
        
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([$marked_by_admin_id, $email])) {
            // Log the action
            logSpamEmailAction($db, $email, 'unmarked_spam', 'Marked as genuine', $marked_by_admin_id);
            
            // Unmark support requests from this email
            $update_sql = "UPDATE support_requests 
                          SET is_spam_request = FALSE, spam_checked_at = NOW()
                          WHERE requester_email = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$email]);
            
            return ['success' => true, 'message' => 'Email marked as genuine'];
        }
        
        return ['success' => false, 'message' => 'Failed to unmark email'];
        
    } catch (Exception $e) {
        error_log("Error unmarking email: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Toggle spam status for an email
 */
function toggleEmailSpamStatus($db, $email, $marked_by_admin_id, $reason = 'Toggled by admin') {
    // Check current status
    if (isSpamEmail($db, $email)) {
        return markEmailAsGenuine($db, $email, $marked_by_admin_id);
    } else {
        return markEmailAsSpam($db, $email, $reason, $marked_by_admin_id);
    }
}

/**
 * Block spam email from submitting new requests
 */
function canSubmitSupportRequest($db, $email) {
    return !isSpamEmail($db, $email);
}

/**
 * Get all spam emails
 */
function getSpamEmails($db) {
    $sql = "SELECT * FROM spam_emails 
            WHERE is_spam = TRUE 
            ORDER BY spam_marked_at DESC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all genuine emails (previously marked but now genuine)
 */
function getGenuineEmails($db) {
    $sql = "SELECT * FROM spam_emails 
            WHERE is_spam = FALSE 
            ORDER BY spam_marked_at DESC";
    
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get support requests from a specific email
 */
function getSupportRequestsByEmail($db, $email) {
    $sql = "SELECT * FROM support_requests 
            WHERE requester_email = ? 
            ORDER BY submitted_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get emails with most support requests (potential spam)
 */
function getEmailsByRequestCount($db, $limit = 50) {
    $sql = "SELECT 
                requester_email,
                COUNT(*) as request_count,
                MAX(submitted_at) as last_request,
                MIN(submitted_at) as first_request,
                se.is_spam
            FROM support_requests sr
            LEFT JOIN spam_emails se ON sr.requester_email = se.email
            GROUP BY requester_email
            ORDER BY request_count DESC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Log spam email action
 */
function logSpamEmailAction($db, $email, $action, $reason, $marked_by) {
    $sql = "INSERT INTO spam_email_log (email, action, reason, marked_by) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$email, $action, $reason, $marked_by]);
}

/**
 * Get spam statistics
 */
function getSpamEmailStats($db) {
    $sql = "SELECT 
            COUNT(DISTINCT requester_email) as total_unique_emails,
            COUNT(DISTINCT CASE WHEN is_spam_request = TRUE THEN requester_email END) as spam_emails,
            COUNT(*) as total_requests,
            COUNT(CASE WHEN is_spam_request = TRUE THEN 1 END) as spam_requests
            FROM support_requests";
    
    $stmt = $db->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Auto-check support request against spam list
 */
function checkSupportRequestForSpam($db, $request_id) {
    // Get request details
    $sql = "SELECT requester_email FROM support_requests WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        return false;
    }
    
    // Check if email is spam
    $is_spam = isSpamEmail($db, $request['requester_email']);
    
    // Update request
    $update_sql = "UPDATE support_requests 
                   SET is_spam_request = ?, spam_checked_at = NOW()
                   WHERE id = ?";
    $update_stmt = $db->prepare($update_sql);
    $update_stmt->execute([$is_spam, $request_id]);
    
    return $is_spam;
}

/**
 * Bulk mark all requests from an email as spam
 */
function markAllRequestsFromEmailAsSpam($db, $email) {
    $sql = "UPDATE support_requests 
            SET is_spam_request = TRUE, spam_checked_at = NOW()
            WHERE requester_email = ?";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([$email]);
}
?>