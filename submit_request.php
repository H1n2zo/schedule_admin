<?php
/**
 * EVSU Event Management System
 * Submit Request Page - With Date Blocking
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
                        <li><strong>Date Availability:</strong> Only one event can be approved per date</li>
                        <li><strong>Blocked Dates:</strong> Dates with approved events are disabled and cannot be selected</li>
                        <li><strong>Advance Notice:</strong> Submit requests at least 1 week in advance</li>
                        <li><strong>Detailed Description:</strong> Provide clear event details and volunteer needs</li>
                        <li><strong>Attachments:</strong> Include supporting documents if available</li>
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

input[type="date"]:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.date-legend {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.875rem;
}

.date-legend-item {
    display: inline-flex;
    align-items: center;
    margin-right: 15px;
    margin-bottom: 5px;
}

.date-legend-item i {
    margin-right: 5px;
}
</style>

<script>
let occupiedDates = {};

// Load occupied dates on page load
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('eventDateInput');
    const availabilityMsg = document.getElementById('dateAvailability');
    
    // Fetch occupied dates
    fetch('check_date_availability.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                occupiedDates = data.occupied_dates;
                console.log('Occupied dates loaded:', Object.keys(occupiedDates).length);
            }
        })
        .catch(error => {
            console.error('Error loading occupied dates:', error);
        });
    
    // Validate date selection
    if (dateInput && availabilityMsg) {
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (!selectedDate) return;
            
            // Check if date is occupied
            if (occupiedDates[selectedDate]) {
                const event = occupiedDates[selectedDate];
                availabilityMsg.className = 'form-text occupied';
                availabilityMsg.innerHTML = `
                    <i class="fas fa-times-circle"></i> 
                    This date is occupied by "${event.event_name}" (${event.organization}). 
                    Please choose another date.
                `;
                
                // Clear the input
                this.value = '';
                
                // Show alert
                setTimeout(() => {
                    alert(`Date Unavailable!\n\nThe date you selected already has an approved event:\n\n"${event.event_name}"\nby ${event.organization}\n\nPlease select a different date.`);
                }, 100);
            } else {
                availabilityMsg.className = 'form-text available';
                availabilityMsg.innerHTML = '<i class="fas fa-check-circle"></i> This date is available!';
            }
        });
        
        // Block occupied dates using input event
        dateInput.addEventListener('input', function() {
            const selectedDate = this.value;
            if (selectedDate && occupiedDates[selectedDate]) {
                this.value = '';
            }
        });
    }
});

// Display uploaded files
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
// Include footer
include 'includes/footer.php';
?>