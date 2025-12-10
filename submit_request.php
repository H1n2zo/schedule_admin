<?php
require_once 'config.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Event Request - EVSU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --evsu-maroon: #800000;
            --evsu-gold: #FFD700;
            --maroon-dark: #5c0000;
            --gold-dark: #d4af37;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(128,0,0,0.3);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: white !important;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
            color: var(--evsu-gold) !important;
        }
        
        .form-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(128,0,0,0.15);
            overflow: hidden;
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .form-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .form-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0;
        }
        
        .form-body {
            padding: 40px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--evsu-maroon);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--evsu-gold);
            box-shadow: 0 0 0 0.2rem rgba(255,215,0,0.25);
        }
        
        .required {
            color: #dc3545;
            margin-left: 3px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--evsu-maroon) 0%, var(--maroon-dark) 100%);
            border: none;
            color: white;
            padding: 15px 50px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(128,0,0,0.3);
            background: linear-gradient(135deg, var(--maroon-dark) 0%, #3a0000 100%);
        }
        
        .btn-back {
            background: transparent;
            border: 2px solid var(--evsu-maroon);
            color: var(--evsu-maroon);
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: var(--evsu-maroon);
            color: white;
        }
        
        .file-upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: var(--evsu-gold);
            background: #fffbf0;
        }
        
        .file-upload-area i {
            font-size: 3rem;
            color: var(--evsu-gold);
            margin-bottom: 15px;
        }
        
        .success-container {
            text-align: center;
            padding: 60px 40px;
        }
        
        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: successPop 0.5s ease;
        }
        
        .success-icon i {
            font-size: 4rem;
            color: white;
        }
        
        @keyframes successPop {
            0% {
                transform: scale(0);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .info-box {
            background: #fffbf0;
            border-left: 4px solid var(--evsu-gold);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-box h6 {
            color: var(--evsu-maroon);
            font-weight: 700;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a href="index.php" class="navbar-brand">
        EVSU Event Management System
            </a>
        </div>
    </nav>

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
                    <div class="d-flex gap-3 justify-content-center">
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
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <h6><i class="fas fa-info-circle"></i> Before You Submit</h6>
                        <ul style="margin: 0; color: #6c757d;">
                            <li>Make sure your event date is at least 1 weeks from today</li>
                            <li>Provide detailed description of your event and volunteer needs</li>
                            <li>Attach any supporting documents (proposals, permits, etc.)</li>
                            <li>You will receive email notification about your request status</li>
                        </ul>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
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
                        
                        <div class="d-flex justify-content-between align-items-center">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function displayFiles(input) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            
            if (input.files.length > 0) {
                fileList.innerHTML = '<div class="alert alert-info"><strong>Selected Files:</strong></div>';
                Array.from(input.files).forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'mb-1';
                    fileItem.innerHTML = `<i class="fas fa-file"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                    fileList.appendChild(fileItem);
                });
            }
        }
    </script>
</body>
</html>