<?php
/**
 * CRCY Dispatch - Support Request Submission
 * Submit CRCY Support Request Page - Modern Design
 */

require_once 'config.php';

$pageTitle = 'Request CRCY Support - CRCY Dispatch';
$customCSS = ['forms'];
$customJS = ['forms'];

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Rate limiting check
    if (!checkRateLimit($clientIP, 5, 3600)) { // 5 requests per hour
        logSecurityEvent('rate_limit_exceeded', ['ip' => $clientIP, 'action' => 'submit_request']);
        $error = 'Too many requests. Please wait before submitting another request.';
    } else {
        // Get and validate form data
        $eventName = sanitizeInput($_POST['event_name'] ?? '');
        $organization = sanitizeInput($_POST['organization'] ?? '');
        $requesterName = sanitizeInput($_POST['requester_name'] ?? '');
        $requesterEmail = validateEmail($_POST['requester_email'] ?? '');
        $requesterPosition = sanitizeInput($_POST['requester_position'] ?? '');
        $eventDate = $_POST['event_date'] ?? '';
        $eventStartTime = $_POST['event_start_time'] ?? '';
        $eventEndTime = $_POST['event_end_time'] ?? null;
        $venue = sanitizeInput($_POST['venue'] ?? '');
        $expectedParticipants = max(0, (int)($_POST['expected_participants'] ?? 0));
        $volunteersNeeded = (int)($_POST['volunteers_needed'] ?? 0);
        $volunteerRoles = sanitizeInput($_POST['volunteer_roles'] ?? '');
        $eventDescription = sanitizeInput($_POST['event_description'] ?? '');
        $specialRequirements = sanitizeInput($_POST['special_requirements'] ?? '');
        $contactNumber = preg_replace('/[^0-9]/', '', $_POST['contact_number'] ?? ''); // Only numbers allowed
        
        // Comprehensive validation
        $validationErrors = [];
        
        if (empty($eventName) || strlen($eventName) < 3) {
            $validationErrors[] = 'Event name must be at least 3 characters long';
        }
        
        if (empty($organization) || strlen($organization) < 2) {
            $validationErrors[] = 'Organization name is required';
        }
        
        if (empty($requesterName) || strlen($requesterName) < 2) {
            $validationErrors[] = 'Requester name is required';
        }
        
        if (!$requesterEmail) {
            $validationErrors[] = 'Please provide a valid email address';
        }
        
        if (empty($venue) || strlen($venue) < 3) {
            $validationErrors[] = 'Venue must be specified';
        }
        
        // Contact number validation (optional but must be valid if provided)
        if (!empty($contactNumber) && strlen($contactNumber) !== 11) {
            $validationErrors[] = 'Contact number must be exactly 11 digits (e.g., 09123456789)';
        }
        
        if ($volunteersNeeded < 1 || $volunteersNeeded > 10) {
            $validationErrors[] = 'Volunteers needed must be between 1 and 10';
        }
        
        // Time validation
        if (empty($eventStartTime)) {
            $validationErrors[] = 'Start time is required';
        } else {
            // Validate start time is within allowed hours (5:00 AM to 8:30 PM)
            $startHour = (int)date('H', strtotime($eventStartTime));
            $startMinute = (int)date('i', strtotime($eventStartTime));
            $startTimeInMinutes = $startHour * 60 + $startMinute;
            
            if ($startTimeInMinutes < (5 * 60) || $startTimeInMinutes > (20 * 60 + 30)) {
                $validationErrors[] = 'Start time must be between 5:00 AM and 8:30 PM';
            }
        }
        
        if (empty($eventEndTime)) {
            $validationErrors[] = 'End time is required';
        } else {
            // Validate end time is within allowed hours (5:00 AM to 8:30 PM)
            $endHour = (int)date('H', strtotime($eventEndTime));
            $endMinute = (int)date('i', strtotime($eventEndTime));
            $endTimeInMinutes = $endHour * 60 + $endMinute;
            
            if ($endTimeInMinutes < (5 * 60) || $endTimeInMinutes > (20 * 60 + 30)) {
                $validationErrors[] = 'End time must be between 5:00 AM and 8:30 PM';
            }
        }
        
        if ($eventStartTime && $eventEndTime) {
            if (strtotime($eventEndTime) <= strtotime($eventStartTime)) {
                $validationErrors[] = 'End time must be after start time';
            }
            
            // Check duration (max 12 hours, min 30 minutes)
            $duration = strtotime($eventEndTime) - strtotime($eventStartTime);
            if ($duration > 12 * 3600) { // 12 hours in seconds
                $validationErrors[] = 'Event duration cannot exceed 12 hours';
            }
            if ($duration < 30 * 60) { // 30 minutes in seconds
                $validationErrors[] = 'Event must be at least 30 minutes long';
            }
        }
        
        if (!isset($_FILES['attachments']) || empty($_FILES['attachments']['name'][0])) {
            $validationErrors[] = 'At least one attachment is required (event proposal, flyer, or related document)';
        }
        
        // Date validation
        $dateValidationErrors = validateEventDate($eventDate, $eventStartTime);
        $validationErrors = array_merge($validationErrors, $dateValidationErrors);
        
        // Check for duplicate submissions
        if ($requesterEmail && checkDuplicateRequest($requesterEmail, $eventName, $eventDate)) {
            $validationErrors[] = 'You have already submitted a similar request recently. Please wait 24 hours before resubmitting.';
        }
        
        if (!empty($validationErrors)) {
            $error = implode('. ', $validationErrors);
            recordRateLimitAttempt($clientIP);
        } else {
        // Check for date conflicts
        $conflicts = checkDateConflict($eventDate, $eventStartTime, $eventEndTime);
        
        if (!empty($conflicts)) {
            $conflictEvent = $conflicts[0];
            $conflictTime = formatTime($conflictEvent['event_time']);
            $error = 'Time conflict with "' . $conflictEvent['event_name'] . '" at ' . $conflictTime . '. Please choose a different time or date.';
        } else {
            try {
                $db = getDB();
                
                // Insert CRCY support request
                $stmt = $db->prepare("
                    INSERT INTO support_requests 
                    (event_name, organization, requester_name, requester_email, requester_position,
                     event_date, event_time, event_end_time, venue, expected_participants,
                     volunteers_needed, volunteer_roles, event_description, special_requirements,
                     contact_number, status, submitted_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $eventName, $organization, $requesterName, $requesterEmail, $requesterPosition,
                    $eventDate, $eventStartTime, $eventEndTime, $venue, $expectedParticipants,
                    $volunteersNeeded, $volunteerRoles, $eventDescription, $specialRequirements,
                    $contactNumber
                ]);
                
                $requestId = $db->lastInsertId();
                
                // Handle file uploads with enhanced security
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                    $uploadDir = UPLOAD_DIR;
                    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
                    $maxFileSize = MAX_ATTACHMENT_SIZE; // 10MB
                    
                    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                            $originalFileName = $_FILES['attachments']['name'][$key];
                            $fileSize = $_FILES['attachments']['size'][$key];
                            $fileType = $_FILES['attachments']['type'][$key];
                            
                            // Validate file
                            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
                            
                            if (!in_array($fileExtension, $allowedTypes)) {
                                throw new Exception("Invalid file type: {$originalFileName}. Only PDF, DOC, DOCX, JPG, PNG, GIF files are allowed.");
                            }
                            
                            if ($fileSize > $maxFileSize) {
                                throw new Exception("File too large: {$originalFileName}. Maximum size is 10MB.");
                            }
                            
                            // Sanitize filename and generate unique name
                            $safeFileName = sanitizeFilename($originalFileName);
                            $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                            $filePath = $uploadDir . $uniqueFileName;
                            
                            // Additional security: Check file content
                            if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                $imageInfo = getimagesize($tmpName);
                                if ($imageInfo === false) {
                                    throw new Exception("Invalid image file: {$originalFileName}");
                                }
                            }
                            
                            if (move_uploaded_file($tmpName, $filePath)) {
                                // Save attachment info to database
                                $stmt = $db->prepare("
                                    INSERT INTO attachments 
                                    (request_id, file_name, file_path, file_type, file_size)
                                    VALUES (?, ?, ?, ?, ?)
                                ");
                                $stmt->execute([$requestId, $safeFileName, $filePath, $fileType, $fileSize]);
                            } else {
                                throw new Exception("Failed to upload file: {$originalFileName}");
                            }
                        } else {
                            $uploadErrors = [
                                UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                                UPLOAD_ERR_PARTIAL => 'File upload incomplete',
                                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                                UPLOAD_ERR_NO_TMP_DIR => 'Server error: no temp directory',
                                UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write file',
                                UPLOAD_ERR_EXTENSION => 'Server error: upload blocked by extension'
                            ];
                            
                            $errorCode = $_FILES['attachments']['error'][$key];
                            $errorMessage = $uploadErrors[$errorCode] ?? 'Unknown upload error';
                            throw new Exception("Upload error for {$originalFileName}: {$errorMessage}");
                        }
                    }
                }
                
                $success = true;
                
                // Send confirmation email
                require_once 'send_email.php';
                $emailSent = sendConfirmationEmail($requestId, $requesterEmail, $requesterName, $eventName, $eventDate);
                
                if (!$emailSent) {
                    // Log email failure but don't fail the request
                    logSecurityEvent('email_send_failed', [
                        'request_id' => $requestId,
                        'requester_email' => $requesterEmail,
                        'reason' => 'Failed to send confirmation email'
                    ]);
                }
                
                // Log successful submission
                logSecurityEvent('request_submitted', [
                    'request_id' => $requestId,
                    'requester_email' => $requesterEmail,
                    'event_name' => $eventName,
                    'event_date' => $eventDate
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                $error = $e->getMessage();
                
                // Log the error
                logSecurityEvent('request_submission_error', [
                    'error' => $e->getMessage(),
                    'requester_email' => $requesterEmail ?? 'unknown'
                ]);
            }
        }
        }
    }
}

include 'includes/header.php';
?>

<!-- Multi-Step Form Container -->
<div class="multi-step-container">
    <!-- Progress Header -->
    <div class="progress-header">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-10">
                    <div class="form-header-content">
                        <div class="form-logo">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h1 class="form-title">REQUEST CRCY SUPPORT</h1>
                        <p class="form-subtitle">Submit your request for College Red Cross Youth volunteer support</p>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="progress-steps">
                            <div class="step active" data-step="1">
                                <div class="step-circle">1</div>
                                <div class="step-label">Event Info</div>
                            </div>
                            <div class="step" data-step="2">
                                <div class="step-circle">2</div>
                                <div class="step-label">Your Details</div>
                            </div>
                            <div class="step" data-step="3">
                                <div class="step-circle">3</div>
                                <div class="step-label">Requirements</div>
                            </div>
                            <div class="step" data-step="4">
                                <div class="step-circle">4</div>
                                <div class="step-label">Event Details</div>
                            </div>
                            <div class="step" data-step="5">
                                <div class="step-circle">5</div>
                                <div class="step-label">Review</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="form-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-8">
                    <div class="form-card">
                        <?php if ($success): ?>
                            <!-- Success Step -->
                            <div class="step-content success-step">
                                <div class="success-animation">
                                    <div class="success-checkmark">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                </div>
                                
                                <h2 class="success-title">Request Submitted Successfully!</h2>
                                <p class="success-subtitle">Your CRCY support request has been submitted and is now under review.</p>
                                
                                <div class="request-id-display">
                                    <div class="request-id-label">Request ID</div>
                                    <div class="request-id-value"><?= $requestId ?></div>
                                </div>
                                
                                <div class="next-steps">
                                    <h4>What happens next:</h4>
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-icon"><i class="bi bi-search"></i></div>
                                            <div class="timeline-content">
                                                <strong>Review Process</strong>
                                                <p>CRCY administrators will review your request within 24-48 hours</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-icon"><i class="bi bi-envelope"></i></div>
                                            <div class="timeline-content">
                                                <strong>Email Notification</strong>
                                                <p>You'll receive an email notification with the approval status</p>
                                            </div>
                                        </div>
                                        <div class="timeline-item">
                                            <div class="timeline-icon"><i class="bi bi-people"></i></div>
                                            <div class="timeline-content">
                                                <strong>Volunteer Coordination</strong>
                                                <p>If approved, volunteer assignments will be coordinated offline</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="success-actions">
                                    <a href="index.php" class="btn-primary">
                                        <i class="bi bi-house" style="color: white !important;"></i> Back to Home
                                    </a>
                                    <a href="submit_request.php" class="btn-secondary">
                                        <i class="bi bi-plus"></i> Submit Another Request
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span><?= htmlspecialchars($error) ?></span>
                                </div>
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data" class="multi-step-form" id="multiStepForm">
                                
                                <!-- Step 1: Event Information -->
                                <div class="step-content active" data-step="1">
                                    <div class="step-header">
                                        <div class="step-icon">
                                            <i class="bi bi-calendar-event"></i>
                                        </div>
                                        <div class="step-info">
                                            <h3>Event Information</h3>
                                            <p>Tell us about your event</p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-fields">
                                        <div class="field-group">
                                            <label class="field-label">Event Name <span class="required">*</span></label>
                                            <div class="input-container">
                                                <i class="fas fa-calendar-alt field-icon"></i>
                                                <input type="text" name="event_name" class="field-input" required
                                                       placeholder="Enter your event name"
                                                       value="<?= htmlspecialchars($_POST['event_name'] ?? '') ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="field-group">
                                            <label class="field-label">Organization <span class="required">*</span></label>
                                            <div class="input-container">
                                                <i class="fas fa-building field-icon"></i>
                                                <input type="text" name="organization" class="field-input" required
                                                       placeholder="Your organization name"
                                                       value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="field-row">
                                            <div class="field-group">
                                                <label class="field-label">Event Date <span class="required">*</span></label>
                                                <?php $nextAvailable = getNextAvailableDate(); ?>
                                                <div class="input-container">
                                                    <i class="fas fa-calendar field-icon"></i>
                                                    <input type="date" name="event_date" class="field-input" required
                                                           min="<?= date('Y-m-d', strtotime('+' . MIN_ADVANCE_DAYS . ' days')) ?>"
                                                           value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>">
                                                </div>
                                                <div class="field-hint">
                                                    Next available: <?= date('F j, Y', strtotime($nextAvailable)) ?>
                                                </div>
                                            </div>
                                            
                                            <div class="field-group">
                                                <label class="field-label">Venue <span class="required">*</span></label>
                                                <div class="input-container">
                                                    <i class="fas fa-map-marker-alt field-icon"></i>
                                                    <input type="text" name="venue" class="field-input" required
                                                           placeholder="Event location/venue"
                                                           value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="field-row">
                                            <div class="field-group">
                                                <label class="field-label">Start Time <span class="required">*</span></label>
                                                <div class="input-container">
                                                    <i class="fas fa-clock field-icon"></i>
                                                    <input type="time" name="event_start_time" class="field-input" required
                                                           min="05:00" max="20:30"
                                                           value="<?= htmlspecialchars($_POST['event_start_time'] ?? '') ?>">
                                                </div>
                                                <div class="field-hint">
                                                    Events can start from 5:00 AM onwards
                                                </div>
                                            </div>
                                            
                                            <div class="field-group">
                                                <label class="field-label">End Time <span class="required">*</span></label>
                                                <div class="input-container">
                                                    <i class="fas fa-clock field-icon"></i>
                                                    <input type="time" name="event_end_time" class="field-input" required
                                                           min="05:00" max="20:30" disabled
                                                           value="<?= htmlspecialchars($_POST['event_end_time'] ?? '') ?>"
                                                           placeholder="Select start time first">
                                                </div>
                                                <div class="field-hint">
                                                    No events beyond 8:30 PM
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Step 2: Your Details -->
                                <div class="step-content" data-step="2">
                                    <div class="step-header">
                                        <div class="step-icon">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div class="step-info">
                                            <h3>Your Details</h3>
                                            <p>Tell us about yourself</p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-fields">
                                        <div class="field-row">
                                            <div class="field-group">
                                                <label class="field-label">Full Name <span class="required">*</span></label>
                                                <div class="input-container">
                                                    <i class="fas fa-user field-icon"></i>
                                                    <input type="text" name="requester_name" class="field-input" required
                                                           placeholder="Your full name"
                                                           value="<?= htmlspecialchars($_POST['requester_name'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="field-group">
                                                <label class="field-label">Position/Title</label>
                                                <div class="input-container">
                                                    <i class="fas fa-id-badge field-icon"></i>
                                                    <input type="text" name="requester_position" class="field-input"
                                                           placeholder="e.g., Event Coordinator, President"
                                                           value="<?= htmlspecialchars($_POST['requester_position'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="field-row">
                                            <div class="field-group">
                                                <label class="field-label">Email Address <span class="required">*</span></label>
                                                <div class="input-container">
                                                    <i class="fas fa-envelope field-icon"></i>
                                                    <input type="email" name="requester_email" class="field-input" required
                                                           placeholder="your.email@example.com"
                                                           value="<?= htmlspecialchars($_POST['requester_email'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="field-group">
                                                <label class="field-label">Contact Number</label>
                                                <div class="input-container">
                                                    <i class="fas fa-phone field-icon"></i>
                                                    <input type="tel" name="contact_number" class="field-input"
                                                           placeholder="e.g., 09123456789"
                                                           pattern="[0-9]{11}"
                                                           maxlength="11"
                                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                           title="Please enter an 11-digit phone number (numbers only)"
                                                           value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Step 3: Requirements -->
                                <div class="step-content" data-step="3">
                                    <div class="step-header">
                                        <div class="step-icon">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <div class="step-info">
                                            <h3>Volunteer Requirements</h3>
                                            <p>How many volunteers do you need?</p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-fields">
                                        <div class="field-row">
                                            <div class="field-group">
                                                <label class="field-label">Expected Participants</label>
                                                <div class="input-container">
                                                    <i class="fas fa-users field-icon"></i>
                                                    <input type="number" name="expected_participants" class="field-input" min="0"
                                                           placeholder="Estimated number of event attendees"
                                                           value="<?= htmlspecialchars($_POST['expected_participants'] ?? '') ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="field-group">
                                                <label class="field-label">Volunteers Needed <span class="required">*</span></label>
                                                <div class="input-container">
                                                    <i class="fas fa-user-friends field-icon"></i>
                                                    <input type="number" name="volunteers_needed" class="field-input" required
                                                           min="1" max="10" placeholder="1-10 volunteers"
                                                           value="<?= htmlspecialchars($_POST['volunteers_needed'] ?? '') ?>">
                                                </div>
                                                <div class="field-hint">
                                                    We can deploy up to 10 volunteers per event
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="field-group">
                                            <label class="field-label">Volunteer Roles/Tasks</label>
                                            <div class="input-container">
                                                <i class="fas fa-tasks field-icon"></i>
                                                <textarea name="volunteer_roles" class="field-input" rows="4"
                                                          placeholder="Describe specific roles: registration, crowd control, first aid, setup/cleanup, etc."><?= htmlspecialchars($_POST['volunteer_roles'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Step 4: Event Details -->
                                <div class="step-content" data-step="4">
                                    <div class="step-header">
                                        <div class="step-icon">
                                            <i class="bi bi-clipboard-check"></i>
                                        </div>
                                        <div class="step-info">
                                            <h3>Event Details</h3>
                                            <p>Tell us more about your event</p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-fields">
                                        <div class="field-group">
                                            <label class="field-label">Event Description</label>
                                            <div class="input-container">
                                                <i class="fas fa-align-left field-icon"></i>
                                                <textarea name="event_description" class="field-input" rows="5"
                                                          placeholder="Provide detailed description of your event, objectives, and activities"><?= htmlspecialchars($_POST['event_description'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="field-group">
                                            <label class="field-label">Special Requirements</label>
                                            <div class="input-container">
                                                <i class="fas fa-exclamation-circle field-icon"></i>
                                                <textarea name="special_requirements" class="field-input" rows="4"
                                                          placeholder="Any special equipment, training, or requirements for volunteers"><?= htmlspecialchars($_POST['special_requirements'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="field-group">
                                            <label class="field-label">Attachments <span class="required">*</span></label>
                                            <div class="file-upload-area">
                                                <div class="file-upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="file-upload-text">
                                                    <h6>Drop files here or click to browse</h6>
                                                    <p>Upload event proposals, flyers, or related documents</p>
                                                    <small>PDF, DOC, DOCX, JPG, PNG files • Max 10MB per file</small>
                                                </div>
                                                <input type="file" name="attachments[]" class="file-input" multiple required
                                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                            </div>
                                            <div id="file-list" class="file-list"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Step 5: Review -->
                                <div class="step-content" data-step="5">
                                    <div class="step-header">
                                        <div class="step-icon">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                        <div class="step-info">
                                            <h3>Review & Submit</h3>
                                            <p>Please review your information before submitting</p>
                                        </div>
                                    </div>
                                    
                                    <div class="review-sections">
                                        <div class="review-section">
                                            <h4><i class="fas fa-calendar-alt"></i> Event Information</h4>
                                            <div class="review-grid">
                                                <div class="review-item">
                                                    <span class="review-label">Event Name:</span>
                                                    <span class="review-value" id="review-event-name">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Organization:</span>
                                                    <span class="review-value" id="review-organization">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Date & Time:</span>
                                                    <span class="review-value" id="review-datetime">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Venue:</span>
                                                    <span class="review-value" id="review-venue">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="review-section">
                                            <h4><i class="fas fa-user"></i> Your Details</h4>
                                            <div class="review-grid">
                                                <div class="review-item">
                                                    <span class="review-label">Name:</span>
                                                    <span class="review-value" id="review-name">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Email:</span>
                                                    <span class="review-value" id="review-email">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Position:</span>
                                                    <span class="review-value" id="review-position">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Contact:</span>
                                                    <span class="review-value" id="review-contact">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="review-section">
                                            <h4><i class="fas fa-users"></i> Requirements</h4>
                                            <div class="review-grid">
                                                <div class="review-item">
                                                    <span class="review-label">Expected Participants:</span>
                                                    <span class="review-value" id="review-participants">-</span>
                                                </div>
                                                <div class="review-item">
                                                    <span class="review-label">Volunteers Needed:</span>
                                                    <span class="review-value" id="review-volunteers">-</span>
                                                </div>
                                                <div class="review-item full-width">
                                                    <span class="review-label">Volunteer Roles:</span>
                                                    <span class="review-value" id="review-roles">-</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="review-section">
                                            <h4><i class="fas fa-clipboard-list"></i> Event Details</h4>
                                            <div class="review-grid">
                                                <div class="review-item full-width">
                                                    <span class="review-label">Description:</span>
                                                    <span class="review-value" id="review-description">-</span>
                                                </div>
                                                <div class="review-item full-width">
                                                    <span class="review-label">Special Requirements:</span>
                                                    <span class="review-value" id="review-special">-</span>
                                                </div>
                                                <div class="review-item full-width">
                                                    <span class="review-label">Attachments:</span>
                                                    <span class="review-value" id="review-files">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Navigation Buttons -->
                                <div class="step-navigation">
                                    <div class="nav-left">
                                        <a href="index.php" class="btn-cancel">
                                            <i class="fas fa-home"></i> Back to Home
                                        </a>
                                        <button type="button" class="btn-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                                            <i class="fas fa-arrow-left"></i> Previous
                                        </button>
                                    </div>
                                    <div class="nav-right">
                                        <button type="button" class="btn-primary" id="nextBtn" onclick="changeStep(1)">
                                            Next <i class="fas fa-arrow-right" style="color: white !important;"></i>
                                        </button>
                                        <button type="button" class="btn-primary" id="submitBtn" style="display: none;" onclick="confirmSubmission()">
                                            <i class="bi bi-send" style="color: white !important;"></i> Submit Request
                                        </button>
                                    </div>
                                </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Confirmation modal before submission
function confirmSubmission() {
    const eventName = document.querySelector('input[name="event_name"]').value;
    const eventDate = document.querySelector('input[name="event_date"]').value;
    const startTime = document.querySelector('input[name="event_start_time"]').value;
    const endTime = document.querySelector('input[name="event_end_time"]').value;
    const volunteersNeeded = document.querySelector('input[name="volunteers_needed"]').value;
    
    const formattedDate = eventDate ? new Date(eventDate).toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    }) : 'Not specified';
    
    const timeRange = (startTime && endTime) ? `${formatTime(startTime)} - ${formatTime(endTime)}` : 
                      startTime ? formatTime(startTime) : 'Not specified';
    
    // Create modal HTML
    const modalHTML = `
        <div id="confirmModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="bi bi-question-circle"></i>                       CONFIRM SUBMISSION    </h3>
                </div>
                <div class="modal-body">
                    <p><strong>Are you sure you want to submit this request?</strong></p>
                    <div class="confirmation-details">
                        <div class="detail-item">
                            <span class="detail-label">Event:</span>
                            <span class="detail-value">${eventName || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date:</span>
                            <span class="detail-value">${formattedDate}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Time:</span>
                            <span class="detail-value">${timeRange}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Volunteers:</span>
                            <span class="detail-value">${volunteersNeeded || 'Not specified'}</span>
                        </div>
                    </div>
                    <div class="warning-note">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>Once submitted, you cannot edit this request.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeConfirmModal()">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                    <button type="button" class="btn-primary" onclick="proceedWithSubmission()">
                        <i class="bi bi-check"></i> Yes, Submit Request
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal with animation
    setTimeout(() => {
        document.getElementById('confirmModal').classList.add('show');
    }, 10);
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.remove();
    }, 300);
}

function proceedWithSubmission() {
    closeConfirmModal();
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Submit the form
    document.getElementById('multiStepForm').submit();
}

// Multi-step form conflict checking integration
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name="event_date"]');
    const timeInput = document.querySelector('input[name="event_start_time"]');
    const endTimeInput = document.querySelector('input[name="event_end_time"]');
    
    if (!dateInput || !timeInput) return;
    
    // Create conflict alert for step 1
    const conflictAlert = document.createElement('div');
    conflictAlert.className = 'field-hint conflict-alert';
    conflictAlert.style.display = 'none';
    conflictAlert.style.color = '#dc3545';
    conflictAlert.style.background = '#fff5f5';
    conflictAlert.style.padding = '0.75rem';
    conflictAlert.style.borderRadius = '8px';
    conflictAlert.style.border = '1px solid #fed7d7';
    conflictAlert.style.marginTop = '0.5rem';
    
    const dateContainer = dateInput.closest('.field-group');
    if (dateContainer) {
        dateContainer.appendChild(conflictAlert);
    }
    
    function checkConflicts() {
        const selectedDate = dateInput.value;
        const selectedTime = timeInput.value;
        const selectedEndTime = endTimeInput.value;
        
        if (!selectedDate) {
            conflictAlert.style.display = 'none';
            return;
        }
        
        // If time is provided, check for time-based conflicts
        if (selectedTime) {
            const formData = new FormData();
            formData.append('date', selectedDate);
            formData.append('time', selectedTime);
            if (selectedEndTime) {
                formData.append('end_time', selectedEndTime);
            }
            
            fetch('check_date_availability.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    const conflictEvent = data.conflicting_event;
                    conflictAlert.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Time Conflict!</strong><br>
                        "${conflictEvent.name}" is scheduled at ${conflictEvent.time} in ${conflictEvent.venue}.<br>
                        Please choose a different time (1-hour buffer required).
                    `;
                    conflictAlert.style.display = 'block';
                } else {
                    conflictAlert.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error checking time conflicts:', error);
                conflictAlert.style.display = 'none';
            });
        } else {
            // Just check date for existing events
            fetch('check_date_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'date=' + encodeURIComponent(selectedDate)
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict && data.events) {
                    const eventsList = data.events.map(event => 
                        `• ${event.name} at ${event.time}${event.end_time ? ' - ' + event.end_time : ''}`
                    ).join('<br>');
                    
                    conflictAlert.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        <strong>Events on this date:</strong>
                        ${eventsList}<br>
                        <small>• Choose a time that doesn't conflict (1-hour buffer required).</small>
                    `;
                    conflictAlert.style.color = '#856404';
                    conflictAlert.style.background = '#fff3cd';
                    conflictAlert.style.borderColor = '#ffeaa7';
                    conflictAlert.style.display = 'block';
                } else {
                    conflictAlert.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error checking date:', error);
                conflictAlert.style.display = 'none';
            });
        }
    }
    
    // Add event listeners
    dateInput.addEventListener('change', checkConflicts);
    timeInput.addEventListener('change', checkConflicts);
    if (endTimeInput) {
        endTimeInput.addEventListener('change', checkConflicts);
    }
});
</script>

<?php include 'includes/footer.php'; ?>