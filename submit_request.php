<?php
/**
 * EVSU Event Management System
 * Submit Request Page - Updated with External Assets
 * File: submit_request.php
 */

require_once 'config.php';

// Set page configuration
$pageTitle = 'Submit Event Request - EVSU';
$customCSS = ['forms'];
$customJS = ['forms'];

$error = '';
$success = false;

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
            $stmt = $db->prepare("
                INSERT INTO event_requests 
                (event_name, organization, requester_name, requester_email, 
                 event_date, event_time, volunteers_needed, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $eventName, $organization, $requesterName, $requesterEmail,
                $eventDate, $eventTime, $volunteersNeeded, $description
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
                    <h6><i class="fas fa-info-circle"></i> Before You Submit</h6>
                    <ul style="margin: 0; color: #6c757d;">
                        <li>Make sure your event date is at least 1 week from today</li>
                        <li>Provide detailed description of your event and volunteer needs</li>
                        <li>Attach any supporting documents (proposals, permits, etc.)</li>
                        <li>You will receive email notification about your request status</li>
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
                                   min="<?= date('Y-m-d') ?>" required>
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

<?php
// Include footer
include 'includes/footer.php';
?>