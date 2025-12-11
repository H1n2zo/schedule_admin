<?php
/**
 * EVSU Event Management System
 * Submit Request Page - With Date Conflict Check
 * File: submit_request.php
 */

require_once 'config.php';

// Set page configuration
$pageTitle = 'Submit Event Request - EVSU';
$customCSS = ['forms'];
$customJS = ['forms'];

$error = '';
$success = false;
$autoDeclined = false;
$conflictDetails = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = sanitizeInput($_POST['event_name']);
    $organization = sanitizeInput($_POST['organization']);
    $requesterName = sanitizeInput($_POST['requester_name']);
    $requesterEmail = sanitizeInput($_POST['requester_email']);
    $eventDate = $_POST['event_date'];
    $eventTime = $_POST['event_time'];
    $volunteersNeeded = (int)$_POST['volunteers_needed'];
    $description = sanitizeInput($_POST['description']);
    
    // Validate
    if (!filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address';
    } elseif (strtotime($eventDate) < strtotime('today')) {
        $error = 'Event date cannot be in the past';
    } elseif ($volunteersNeeded < 0 || $volunteersNeeded > 100) {
        $error = 'Volunteers needed must be between 0 and 100';
    } else {
        try {
            $db = getDB();
            
            // Check if the date already has an approved event
            $stmt = $db->prepare("
                SELECT id, event_name, organization 
                FROM event_requests 
                WHERE event_date = ? AND status = 'approved'
                LIMIT 1
            ");
            $stmt->execute([$eventDate]);
            $existingEvent = $stmt->fetch();
            
            // Determine initial status
            $initialStatus = 'pending';
            $autoDeclineReason = null;
            
            if ($existingEvent) {
                // Date already has approved event - auto-decline
                $initialStatus = 'declined';
                $autoDeclined = true;
                $conflictDetails = $existingEvent;
                $autoDeclineReason = "Date Conflict: An event has already been approved for " . formatDate($eventDate) . 
                                    " - '" . htmlspecialchars($existingEvent['event_name']) . 
                                    "' by " . htmlspecialchars($existingEvent['organization']) . 
                                    ". Only one event can be approved per date.";
            }
            
            // Insert request regardless
            $stmt = $db->prepare("
                INSERT INTO event_requests 
                (event_name, organization, requester_name, requester_email, 
                 event_date, event_time, volunteers_needed, description, status, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $eventName, $organization, $requesterName, $requesterEmail,
                $eventDate, $eventTime, $volunteersNeeded, $description,
                $initialStatus, $autoDeclineReason
            ]);
            
            $requestId = $db->lastInsertId();
            
            // Handle file uploads
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $uploadDir = UPLOAD_DIR;
                
                foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = basename($_FILES['attachments']['name'][$key]);
                        $fileSize = $_FILES['attachments']['size'][$key];
                        $fileType = $_FILES['attachments']['type'][$key];
                        
                        // Generate unique filename
                        $uniqueName = time() . '_' . $fileName;
                        $filePath = $uploadDir . $uniqueName;
                        
                        if ($fileSize <= MAX_FILE_SIZE) {
                            if (move_uploaded_file($tmpName, $filePath)) {
                                $stmt = $db->prepare("
                                    INSERT INTO attachments 
                                    (request_id, file_name, file_path, file_type, file_size)
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([
                                    $requestId, $fileName, $filePath, $fileType, $fileSize
                                ]);
                            }
                        }
                    }
                }
            }
            
            // If auto-declined, send notification immediately
            if ($autoDeclined) {
                // Log the auto-decline
                $stmt = $db->prepare("
                    INSERT INTO audit_log (request_id, admin_id, action, notes, email_sent) 
                    VALUES (?, 1, 'declined', ?, 1)
                ");
                $stmt->execute([$requestId, $autoDeclineReason]);
                
                // Save to notification history (system auto-decline)
                $declineSubject = "Event Request Declined - Date Conflict";
                $declineBody = "Dear " . $requesterName . ",\n\n";
                $declineBody .= "Thank you for submitting your event request. Unfortunately, we must inform you that your request has been automatically declined due to a scheduling conflict.\n\n";
                $declineBody .= "Your Request:\n";
                $declineBody .= "- Event Name: " . $eventName . "\n";
                $declineBody .= "- Organization: " . $organization . "\n";
                $declineBody .= "- Requested Date: " . formatDate($eventDate) . "\n\n";
                $declineBody .= "Reason for Decline:\n";
                $declineBody .= $autoDeclineReason . "\n\n";
                $declineBody .= "What You Can Do:\n";
                $declineBody .= "- Choose a different date for your event\n";
                $declineBody .= "- Submit a new request with an available date\n";
                $declineBody .= "- Contact us if you have questions about date availability\n\n";
                $declineBody .= "We apologize for any inconvenience and encourage you to submit a new request for an alternative date.\n\n";
                $declineBody .= "Best regards,\n";
                $declineBody .= "EVSU Admin Council";
                
                $stmt = $db->prepare("
                    INSERT INTO notification_history 
                    (request_id, action_type, admin_id, recipient_email, subject, body, attachments_sent) 
                    VALUES (?, 'decline', 1, ?, ?, ?, 0)
                ");
                $stmt->execute([$requestId, $requesterEmail, $declineSubject, $declineBody]);
                
                // In production, send actual email here
                // mail($requesterEmail, $declineSubject, $declineBody);
            }
            
            $success = true;
        } catch (PDOException $e) {
            $error = 'An error occurred while submitting your request. Please try again.';
        }
    }
}

// Include header
include 'includes/header.php';

// Include navbar
include 'includes/navbar.php';
?>

<div class="container">
    <div class="form-container">
        <?php if ($success): ?>
            <div class="success-container">
                <?php if ($autoDeclined): ?>
                    <!-- Auto-Declined Message -->
                    <div class="decline-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h2 style="color: #c62828; font-weight: 800; margin-bottom: 20px;">
                        Request Automatically Declined
                    </h2>
                    
                    <div class="alert alert-danger" style="max-width: 600px; margin: 0 auto 30px;">
                        <h5><i class="fas fa-exclamation-triangle"></i> Date Conflict Detected</h5>
                        <p class="mb-2">The date you selected already has an approved event:</p>
                        <div class="conflict-details">
                            <strong><?= htmlspecialchars($conflictDetails['event_name']) ?></strong><br>
                            <small>by <?= htmlspecialchars($conflictDetails['organization']) ?></small>
                        </div>
                    </div>
                    
                    <p style="font-size: 1.1rem; color: #6c757d; margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
                        Your request has been recorded and a notification email has been sent to you. 
                        <strong>Only one event can be approved per date.</strong> 
                        Please choose a different date and submit a new request.
                    </p>
                    
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="submit_request.php" class="btn btn-submit">
                            <i class="fas fa-calendar-plus"></i> Submit New Request
                        </a>
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Normal Success Message -->
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="color: var(--evsu-maroon); font-weight: 800; margin-bottom: 20px;">
                        Request Submitted Successfully!
                    </h2>
                    <p style="font-size: 1.2rem; color: #6c757d; margin-bottom: 30px;">
                        Your event request has been received and is now pending review by the council administrators.
                        You will receive an email notification once your request has been reviewed.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="submit_request.php" class="btn btn-submit">
                            <i class="fas fa-plus"></i> Submit Another Request
                        </a>
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="form-header">
                <h2><i class="fas fa-calendar-plus"></i> Submit Event Request</h2>
                <p>Fill out the form below to request event support and volunteers</p>
            </div>
            
            <div class="form-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <h6><i class="fas fa-info-circle"></i> Important Information</h6>
                    <ul style="margin: 0; color: #6c757d;">
                        <li><strong>Date Availability:</strong> Only one event can be approved per date</li>
                        <li><strong>Advance Notice:</strong> Submit requests at least 1 week in advance</li>
                        <li><strong>Detailed Description:</strong> Provide clear event details and volunteer needs</li>
                        <li><strong>Attachments:</strong> Include supporting documents if available</li>
                        <li><strong>Automatic Decline:</strong> If your chosen date has an approved event, your request will be automatically declined with notification</li>
                    </ul>
                </div>
                
                <form method="POST" enctype="multipart/form-data" data-validate="true" data-warn-unsaved="true">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Event Name <span class="required">*</span>
                            </label>
                            <input type="text" name="event_name" class="form-control" 
                                   placeholder="e.g., Annual Sports Festival" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Organization <span class="required">*</span>
                            </label>
                            <input type="text" name="organization" class="form-control" 
                                   placeholder="e.g., Student Council" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Your Full Name <span class="required">*</span>
                            </label>
                            <input type="text" name="requester_name" class="form-control" 
                                   placeholder="Juan Dela Cruz" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Your Email <span class="required">*</span>
                            </label>
                            <input type="email" name="requester_email" class="form-control" 
                                   placeholder="yourname@evsu.edu.ph" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Event Date <span class="required">*</span>
                            </label>
                            <input type="date" name="event_date" class="form-control" 
                                   min="<?= date('Y-m-d') ?>" required id="eventDateInput">
                            <small class="form-text text-muted" id="dateAvailability"></small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Event Time <span class="required">*</span>
                            </label>
                            <input type="time" name="event_time" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                Volunteers Needed <span class="required">*</span>
                            </label>
                            <input type="number" name="volunteers_needed" class="form-control" 
                                   min="0" max="100" value="10" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Event Description <span class="required">*</span>
                        </label>
                        <textarea name="description" class="form-control" rows="6" 
                                  placeholder="Provide detailed information about your event, its purpose, activities, and what volunteers will be doing..." 
                                  data-max-length="2000"
                                  required></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            Attachments <span style="color: #6c757d; font-weight: normal;">(Optional)</span>
                        </label>
                        <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h5 style="color: var(--evsu-maroon); font-weight: 600; margin-bottom: 10px;">
                                Click to Upload Files
                            </h5>
                            <p style="color: #6c757d; margin: 0;">
                                PDF, DOC, DOCX, JPG, PNG (Max 5MB per file)
                            </p>
                        </div>
                        <input type="file" id="fileInput" name="attachments[]" 
                               class="d-none" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               onchange="displayFiles(this)">
                        <div id="fileList" class="mt-2"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <a href="index.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.decline-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #c62828 0%, #8e0000 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    animation: declinePulse 0.5s ease;
    box-shadow: 0 10px 30px rgba(198, 40, 40, 0.3);
}

.decline-icon i {
    font-size: 4rem;
    color: white;
}

@keyframes declinePulse {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.conflict-details {
    background: white;
    padding: 15px;
    border-radius: 6px;
    margin-top: 10px;
    border-left: 4px solid #c62828;
}

#dateAvailability {
    display: block;
    margin-top: 5px;
    font-weight: 600;
}

#dateAvailability.available {
    color: #2e7d32;
}

#dateAvailability.occupied {
    color: #c62828;
}

#dateAvailability.checking {
    color: #0288d1;
}
</style>

<script>
// Check date availability when date changes
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('eventDateInput');
    const availabilityMsg = document.getElementById('dateAvailability');
    
    if (dateInput && availabilityMsg) {
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (!selectedDate) return;
            
            availabilityMsg.className = 'form-text text-muted checking';
            availabilityMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
            
            // Simple AJAX check (you can implement this with fetch API)
            fetch('check_date_availability.php?date=' + selectedDate)
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        availabilityMsg.className = 'form-text available';
                        availabilityMsg.innerHTML = '<i class="fas fa-check-circle"></i> This date is available!';
                    } else {
                        availabilityMsg.className = 'form-text occupied';
                        availabilityMsg.innerHTML = '<i class="fas fa-times-circle"></i> This date has an approved event. Your request will be automatically declined if submitted.';
                    }
                })
                .catch(() => {
                    availabilityMsg.className = 'form-text text-muted';
                    availabilityMsg.innerHTML = '';
                });
        });
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>