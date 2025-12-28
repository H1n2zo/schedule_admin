<?php
/**
 * CRCY Dispatch System
 * Email Sending Functions
 * File: send_email.php
 */

require_once 'config.php';

/**
 * Send email using PHP's built-in mail() function
 * For production, consider using PHPMailer with SMTP
 */
function sendConfirmationEmail($requestId, $requesterEmail, $requesterName, $eventName, $eventDate) {
    try {
        $subject = "CRCY Support Request Confirmation - Request ID $requestId";
        
        $message = "
        <html>
        <head>
            <title>CRCY Support Request Confirmation</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #800000; color: white; padding: 20px; text-align: center;'>
                    <h1>CRCY Dispatch System</h1>
                    <p>College Red Cross Youth - EVSU</p>
                </div>
                
                <div style='padding: 30px; background: #f9f9f9;'>
                    <h2 style='color: #800000;'>Request Submitted Successfully!</h2>
                    
                    <p>Dear <strong>$requesterName</strong>,</p>
                    
                    <p>Thank you for submitting your CRCY support request. We have received your request and it is now under review.</p>
                    
                    <div style='background: white; padding: 20px; border-left: 4px solid #FFD700; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #800000;'>Request Details:</h3>
                        <p><strong>Request ID:</strong> $requestId</p>
                        <p><strong>Event Name:</strong> $eventName</p>
                        <p><strong>Event Date:</strong> " . date('F j, Y', strtotime($eventDate)) . "</p>
                        <p><strong>Status:</strong> Under Review</p>
                    </div>
                    
                    <h3 style='color: #800000;'>What happens next:</h3>
                    <ol>
                        <li><strong>Review Process:</strong> CRCY administrators will review your request within 24-48 hours</li>
                        <li><strong>Email Notification:</strong> You'll receive an email notification with the approval status</li>
                        <li><strong>Volunteer Coordination:</strong> If approved, volunteer assignments will be coordinated offline</li>
                    </ol>
                    
                    <p><strong>Important:</strong> Please keep this Request ID for your records: <code style='background: #f0f0f0; padding: 2px 6px; font-family: monospace;'>$requestId</code></p>
                    
                    <p>If you have any questions, please contact the CRCY office.</p>
                    
                    <p>Best regards,<br>
                    <strong>CRCY Dispatch System</strong><br>
                    College Red Cross Youth - EVSU</p>
                </div>
                
                <div style='background: #800000; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
        
        // Send email
        $sent = mail($requesterEmail, $subject, $message, $headers);
        
        if ($sent) {
            // Log successful email
            logSecurityEvent('email_sent', [
                'to' => $requesterEmail,
                'subject' => $subject,
                'request_id' => $requestId,
                'method' => 'xampp_smtp',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            return true;
        } else {
            // Log email failure
            logSecurityEvent('email_failed', [
                'to' => $requesterEmail,
                'subject' => $subject,
                'request_id' => $requestId,
                'method' => 'xampp_smtp',
                'error' => 'mail() function returned false - check XAMPP SMTP configuration'
            ]);
            return false;
        }
        
    } catch (Exception $e) {
        // Log email error
        logSecurityEvent('email_error', [
            'to' => $requesterEmail,
            'error' => $e->getMessage(),
            'request_id' => $requestId
        ]);
        return false;
    }
}

/**
 * Send status update email (approve/decline)
 */
function sendStatusUpdateEmail($requestId, $requesterEmail, $requesterName, $eventName, $eventDate, $status, $reason = '') {
    try {
        $statusText = ucfirst($status);
        $subject = "CRCY Support Request $statusText - Request ID $requestId";
        
        $statusColor = $status === 'approved' ? '#28a745' : '#dc3545';
        $statusIcon = $status === 'approved' ? '✅' : '❌';
        
        $message = "
        <html>
        <head>
            <title>CRCY Support Request Update</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #800000; color: white; padding: 20px; text-align: center;'>
                    <h1>CRCY Dispatch System</h1>
                    <p>College Red Cross Youth - EVSU</p>
                </div>
                
                <div style='padding: 30px; background: #f9f9f9;'>
                    <h2 style='color: $statusColor;'>$statusIcon Request $statusText</h2>
                    
                    <p>Dear <strong>$requesterName</strong>,</p>
                    
                    <p>Your CRCY support request has been <strong>$status</strong>.</p>
                    
                    <div style='background: white; padding: 20px; border-left: 4px solid $statusColor; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #800000;'>Request Details:</h3>
                        <p><strong>Request ID:</strong> $requestId</p>
                        <p><strong>Event Name:</strong> $eventName</p>
                        <p><strong>Event Date:</strong> " . date('F j, Y', strtotime($eventDate)) . "</p>
                        <p><strong>Status:</strong> <span style='color: $statusColor; font-weight: bold;'>$statusText</span></p>
                    </div>";
        
        if ($status === 'approved') {
            $message .= "
                    <div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='color: #155724; margin-top: 0;'>Next Steps:</h3>
                        <p>Your request has been approved! A CRCY coordinator will contact you soon to:</p>
                        <ul>
                            <li>Confirm volunteer assignments</li>
                            <li>Coordinate logistics for your event</li>
                            <li>Provide volunteer contact information</li>
                        </ul>
                        <p><strong>Please ensure you have the necessary preparations ready for the volunteers.</strong></p>
                    </div>";
        } else {
            $message .= "
                    <div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='color: #721c24; margin-top: 0;'>Reason for Decline:</h3>
                        <p>" . htmlspecialchars($reason) . "</p>
                        <p>You may submit a new request with different dates or requirements if needed.</p>
                    </div>";
        }
        
        $message .= "
                    <p>If you have any questions, please contact the CRCY office.</p>
                    
                    <p>Best regards,<br>
                    <strong>CRCY Dispatch System</strong><br>
                    College Red Cross Youth - EVSU</p>
                </div>
                
                <div style='background: #800000; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Headers for HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">" . "\r\n";
        $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
        
        // Send email
        $sent = mail($requesterEmail, $subject, $message, $headers);
        
        if ($sent) {
            logSecurityEvent('status_email_sent', [
                'to' => $requesterEmail,
                'subject' => $subject,
                'request_id' => $requestId,
                'status' => $status,
                'method' => 'xampp_smtp'
            ]);
            return true;
        } else {
            logSecurityEvent('status_email_failed', [
                'to' => $requesterEmail,
                'subject' => $subject,
                'status' => $status,
                'request_id' => $requestId,
                'method' => 'xampp_smtp',
                'error' => 'mail() function returned false - check XAMPP SMTP configuration'
            ]);
            return false;
        }
        
    } catch (Exception $e) {
        logSecurityEvent('status_email_error', [
            'to' => $requesterEmail,
            'error' => $e->getMessage(),
            'request_id' => $requestId,
            'status' => $status
        ]);
        return false;
    }
}


?>