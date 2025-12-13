<?php
/**
 * EVSU Event Management System
 * Submit Request Page - Updated with Start/End Time and Required Attachments
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
    $eventStartTime = $_POST['event_start_time'];
    $eventEndTime = $_POST['event_end_time'];
    $volunteersNeeded = (int)$_POST['volunteers_needed'];
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    
    // Validate required fields
    if (!filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address';
    } elseif (strtotime($eventDate) < strtotime('today')) {
        $error = 'Event date cannot be in the past';
    } elseif ($volunteersNeeded < 0 || $volunteersNeeded > 100) {
        $error = 'Volunteers needed must be between 0 and 100';
    } elseif (strtotime($eventEndTime) <= strtotime($eventStartTime)) {
        $error = 'End time must be after start time';
    } elseif (!isset($_FILES['attachments']) || empty($_FILES['attachments']['name'][0])) {
        $error = 'At least one attachment is required';
    } else {
        try {
            $db = getDB();
            
            // Double-check date isn't occupied (server-side validation)
            $stmt = $db->prepare("
                SELECT id, event_name, organization 
                FROM event_requests 
                WHERE event_date = ? AND status = 'approved'
                LIMIT 1
            ");
            $stmt->execute([$eventDate]);
            $existingEvent = $stmt->fetch();
            
            if ($existingEvent) {
                $error = 'The selected date already has an approved event. Please choose a different date.';
            } else {
                // Insert request with pending status
                $stmt = $db->prepare("
                    INSERT INTO event_requests 
                    (event_name, organization, requester_name, requester_email, 
                     event_date, event_time, event_end_time, volunteers_needed, description, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $eventName, $organization, $requesterName, $requesterEmail,
                    $eventDate, $eventStartTime, $eventEndTime, $volunteersNeeded, $description
                ]);
                
                $requestId = $db->lastInsertId();
                
                // Handle file uploads (REQUIRED)
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
            }
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
                    <h6><i class="fas fa-info-circle"></i> Important Information</h6>
                    <ul style="margin: 0; color: #6c757d;">
                        <li><strong>Blocked Dates:</strong> Only one event can be approved per date</li>
                        <li><strong>Advance Notice:</strong> Submit requests at least 1 week in advance</li>
                        <li><strong>Time Details:</strong> Provide both start and end times for your event</li>
                        <li><strong>Attachments:</strong> At least one supporting document is required</li>
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
                                Start Time <span class="required">*</span>
                            </label>
                            <input type="time" name="event_start_time" class="form-control" required id="startTime">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">
                                End Time <span class="required">*</span>
                            </label>
                            <input type="time" name="event_end_time" class="form-control" required id="endTime">
                            <small class="form-text text-muted" id="timeValidation"></small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">
                                Volunteers Needed <span class="required">*</span>
                            </label>
                            <input type="number" name="volunteers_needed" class="form-control" 
                                   min="0" max="100" value="10" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Event Description <span style="color: #6c757d; font-weight: normal;">(Optional)</span>
                        </label>
                        <textarea name="description" class="form-control" rows="6" 
                                  placeholder="Provide detailed information about your event, its purpose, activities, and what volunteers will be doing..." 
                                  data-max-length="2000"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            Attachments <span class="required">*</span>
                        </label>
                        <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h5 style="color: var(--evsu-maroon); font-weight: 600; margin-bottom: 10px;">
                                Click to Upload Files
                            </h5>
                            <p style="color: #6c757d; margin: 0;">
                                PDF, DOC, DOCX, JPG, PNG (Max 5MB per file) - <strong>REQUIRED</strong>
                            </p>
                        </div>
                        <input type="file" id="fileInput" name="attachments[]" 
                               class="d-none" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               onchange="displayFiles(this)" required>
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
#dateAvailability, #timeValidation {
    display: block;
    margin-top: 5px;
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

#dateAvailability.available, #timeValidation.valid {
    color: #2e7d32;
    background: #e8f5e9;
    border-left: 4px solid #2e7d32;
}

#dateAvailability.occupied, #timeValidation.invalid {
    color: #c62828;
    font-weight: 700;
    background: #ffebee;
    border-left: 4px solid #c62828;
    animation: shake 0.5s;
}

#dateAvailability.checking {
    color: #0288d1;
    background: #e1f5fe;
    border-left: 4px solid #0288d1;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}
</style>

<script>
let occupiedDates = {};
let datesLoaded = false;

// Time validation
document.addEventListener('DOMContentLoaded', function() {
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');
    const timeValidation = document.getElementById('timeValidation');
    
    function validateTimes() {
        if (startTime.value && endTime.value) {
            if (endTime.value <= startTime.value) {
                timeValidation.className = 'form-text invalid';
                timeValidation.innerHTML = '<i class="fas fa-times-circle"></i> End time must be after start time';
                endTime.setCustomValidity('End time must be after start time');
            } else {
                timeValidation.className = 'form-text valid';
                timeValidation.innerHTML = '<i class="fas fa-check-circle"></i> Time range is valid';
                endTime.setCustomValidity('');
            }
        }
    }
    
    startTime.addEventListener('change', validateTimes);
    endTime.addEventListener('change', validateTimes);
    
    // Date availability check
    const dateInput = document.getElementById('eventDateInput');
    const availabilityMsg = document.getElementById('dateAvailability');
    
    if (availabilityMsg) {
        availabilityMsg.className = 'form-text text-muted checking';
        availabilityMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading available dates...';
    }
    
    fetch('check_date_availability.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                occupiedDates = data.occupied_dates;
                datesLoaded = true;
                
                if (availabilityMsg) {
                    availabilityMsg.className = 'form-text text-muted';
                    availabilityMsg.innerHTML = '<i class="fas fa-info-circle"></i> Select a date to check availability';
                }
            }
        });
    
    if (dateInput && availabilityMsg) {
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (!selectedDate) {
                availabilityMsg.className = 'form-text text-muted';
                availabilityMsg.innerHTML = '<i class="fas fa-info-circle"></i> Select a date to check availability';
                return;
            }
            
            if (!datesLoaded) {
                availabilityMsg.className = 'form-text text-muted checking';
                availabilityMsg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking availability...';
                return;
            }
            
            if (occupiedDates[selectedDate]) {
                const event = occupiedDates[selectedDate];
                availabilityMsg.className = 'form-text occupied';
                availabilityMsg.innerHTML = `
                    <i class="fas fa-times-circle"></i> 
                    <strong>NOT AVAILABLE</strong> - This date is already taken by "${event.event_name}" (${event.organization})
                `;
                this.value = '';
            } else {
                availabilityMsg.className = 'form-text available';
                availabilityMsg.innerHTML = '<i class="fas fa-check-circle"></i> <strong>AVAILABLE!</strong> This date is free for your event.';
            }
        });
    }
});

function displayFiles(input) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    if (input.files.length > 0) {
        const listContainer = document.createElement('div');
        listContainer.className = 'uploaded-files';
        
        Array.from(input.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <i class="fas fa-file"></i>
                <span class="file-name">${file.name}</span>
                <span class="file-size">${(file.size / 1024).toFixed(1)} KB</span>
            `;
            listContainer.appendChild(fileItem);
        });
        
        fileList.appendChild(listContainer);
    }
}
</script>

<?php
include 'includes/footer.php';
?>