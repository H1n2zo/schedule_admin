/**
 * EVSU Event Management System
 * Form Validation and Handling
 * File: assets/js/forms.js
 */

(function() {
    'use strict';

    // ============================================
    // File Upload Display
    // ============================================
    window.displayFiles = function(input) {
        const fileList = document.getElementById('fileList');
        if (!fileList) return;
        
        fileList.innerHTML = '';
        
        if (input.files.length > 0) {
            fileList.innerHTML = '<div class="alert alert-info mb-2"><strong><i class="fas fa-file"></i> Selected Files:</strong></div>';
            
            Array.from(input.files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'mb-2 p-2 bg-light rounded';
                
                const fileSize = (file.size / 1024).toFixed(2);
                const fileIcon = getFileIcon(file.type);
                
                fileItem.innerHTML = `
                    <i class="fas fa-${fileIcon} text-primary"></i> 
                    <strong>${file.name}</strong>
                    <span class="text-muted">(${fileSize} KB)</span>
                `;
                
                fileList.appendChild(fileItem);
            });
        }
    };

    // ============================================
    // Get File Icon Based on Type
    // ============================================
    function getFileIcon(fileType) {
        if (fileType.includes('pdf')) return 'file-pdf';
        if (fileType.includes('word') || fileType.includes('document')) return 'file-word';
        if (fileType.includes('image')) return 'file-image';
        return 'file';
    }

    // ============================================
    // Email Validation
    // ============================================
    window.validateEmail = function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    // ============================================


    // ============================================
    // Time Validation (Events allowed 5:00 AM to 8:30 PM only)
    // ============================================
    window.validateEventTime = function(timeInput, isEndTime = false) {
        if (!timeInput || !timeInput.value) return true;
        
        const timeValue = timeInput.value;
        const [hours, minutes] = timeValue.split(':').map(Number);
        const timeInMinutes = hours * 60 + minutes;
        
        // Convert allowed times to minutes
        const allowedStart = 5 * 60; // 5:00 AM = 05:00
        const allowedEnd = 20 * 60 + 30; // 8:30 PM = 20:30
        
        // Check if time falls outside allowed period (before 5:00 AM or after 8:30 PM)
        if (timeInMinutes < allowedStart || timeInMinutes > allowedEnd) {
            const timeType = isEndTime ? 'End time' : 'Start time';
            showFieldError(timeInput, `${timeType} must be between 5:00 AM and 8:30 PM`);
            return false;
        }
        
        clearFieldError(timeInput);
        return true;
    };

    // ============================================
    // Validate Start and End Time Relationship
    // ============================================
    window.validateTimeRange = function(startTimeInput, endTimeInput) {
        if (!startTimeInput || !endTimeInput || !startTimeInput.value || !endTimeInput.value) {
            return true;
        }
        
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        
        if (endTime <= startTime) {
            showFieldError(endTimeInput, 'End time must be after start time');
            return false;
        }
        
        // Check if the duration is reasonable (not more than 12 hours)
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);
        
        const startInMinutes = startHours * 60 + startMinutes;
        const endInMinutes = endHours * 60 + endMinutes;
        
        let durationInMinutes = endInMinutes - startInMinutes;
        
        // Since all events must be within 5:00 AM - 8:30 PM, no overnight events allowed
        if (durationInMinutes < 0) {
            showFieldError(endTimeInput, 'End time must be after start time');
            return false;
        }
        
        if (durationInMinutes > 12 * 60) { // More than 12 hours
            showFieldError(endTimeInput, 'Event duration cannot exceed 12 hours');
            return false;
        }
        
        if (durationInMinutes < 30) { // Less than 30 minutes
            showFieldError(endTimeInput, 'Event must be at least 30 minutes long');
            return false;
        }
        
        clearFieldError(endTimeInput);
        return true;
    };

    // ============================================
    // Date Validation (Must be in future)
    // ============================================
    window.validateEventDate = function(dateInput) {
        if (!dateInput) {
            dateInput = document.querySelector('input[name="event_date"]');
        }
        
        if (!dateInput || !dateInput.value) return true;
        
        const selectedDate = new Date(dateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            showFieldError(dateInput, 'Event date cannot be in the past');
            return false;
        }
        
        // Date is valid - no additional warnings needed since min date already enforces advance notice
        
        clearFieldError(dateInput);
        return true;
    };

    // ============================================
    // Volunteer Count Validation
    // ============================================
    window.validateVolunteerCount = function(input) {
        const value = parseInt(input.value);
        
        if (isNaN(value) || value < 0) {
            showFieldError(input, 'Volunteer count must be a positive number');
            return false;
        }
        
        if (value > 10) {
            showFieldError(input, 'Volunteer count cannot exceed 10');
            return false;
        }
        
        clearFieldError(input);
        return true;
    };

    // ============================================
    // Show Field Error (removed - using the better version at bottom of file)
    // ============================================

    // ============================================
    // Show Field Warning
    // ============================================
    function showFieldWarning(input, message) {
        clearFieldError(input);
        
        const warningDiv = document.createElement('div');
        warningDiv.className = 'text-warning small mt-1';
        warningDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        
        input.parentNode.appendChild(warningDiv);
    }

    // ============================================
    // Clear Field Error (removed - using the better version at bottom of file)
    // ============================================

    // ============================================
    // Show Field Success
    // ============================================
    function showFieldSuccess(input) {
        clearFieldError(input);
        input.classList.add('is-valid');
    }

    // ============================================
    // Form Submission Validation
    // ============================================
    function setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate="true"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate event date
                const dateInput = form.querySelector('input[name="event_date"]');
                if (dateInput && !validateEventDate(dateInput)) {
                    isValid = false;
                }
                
                // Validate event times
                const startTimeInput = form.querySelector('input[name="event_start_time"]');
                const endTimeInput = form.querySelector('input[name="event_end_time"]');
                
                if (startTimeInput && !validateEventTime(startTimeInput, false)) {
                    isValid = false;
                }
                
                if (endTimeInput && !validateEventTime(endTimeInput, true)) {
                    isValid = false;
                }
                
                if (startTimeInput && endTimeInput && !validateTimeRange(startTimeInput, endTimeInput)) {
                    isValid = false;
                }
                
                // Validate volunteers
                const volunteerInput = form.querySelector('input[name="volunteers_needed"]');
                if (volunteerInput && !validateVolunteerCount(volunteerInput)) {
                    isValid = false;
                }
                
                // Validate email
                const emailInput = form.querySelector('input[name="requester_email"]');
                if (emailInput) {
                    if (!validateEmail(emailInput.value)) {
                        showFieldError(emailInput, 'Please enter a valid email address');
                        isValid = false;
                    } else {
                        showFieldSuccess(emailInput);
                    }
                }
                
                // Validate required fields
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        showFieldError(field, 'This field is required');
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showToast('Please fix the errors in the form', 'danger');
                    
                    // Scroll to first error
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                    
                    return false;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    setLoadingState(submitBtn, true);
                }
            });
        });
    }

    // ============================================
    // Real-time Field Validation
    // ============================================
    function setupRealtimeValidation() {
        // Date validation
        const dateInputs = document.querySelectorAll('input[name="event_date"]');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                validateEventDate(this);
            });
        });
        
        // Time validation
        const startTimeInputs = document.querySelectorAll('input[name="event_start_time"]');
        const endTimeInputs = document.querySelectorAll('input[name="event_end_time"]');
        
        startTimeInputs.forEach(input => {
            input.addEventListener('change', function() {
                validateEventTime(this, false);
                // Also validate the time range if end time exists
                const endTimeInput = document.querySelector('input[name="event_end_time"]');
                if (endTimeInput && endTimeInput.value) {
                    validateTimeRange(this, endTimeInput);
                }
            });
        });
        
        endTimeInputs.forEach(input => {
            input.addEventListener('change', function() {
                validateEventTime(this, true);
                // Also validate the time range
                const startTimeInput = document.querySelector('input[name="event_start_time"]');
                if (startTimeInput && startTimeInput.value) {
                    validateTimeRange(startTimeInput, this);
                }
            });
        });
        
        // Volunteer count validation
        const volunteerInputs = document.querySelectorAll('input[name="volunteers_needed"]');
        volunteerInputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateVolunteerCount(this);
            });
        });
        
        // Email validation
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value) {
                    if (!validateEmail(this.value)) {
                        showFieldError(this, 'Please enter a valid email address');
                    } else {
                        showFieldSuccess(this);
                    }
                }
            });
        });
    }





    // ============================================
    // Initialize File Upload Validation
    // ============================================
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const allowedTypes = ['pdf', 'document', 'image'];
            
            if (this.files.length > 0) {
                if (!validateFileSize(this.files, 5)) {
                    this.value = '';
                    return;
                }
                
                if (!validateFileType(this.files, allowedTypes)) {
                    this.value = '';
                    return;
                }
                
                displayFiles(this);
            }
        });
    });

    // ============================================
    // ============================================
    // Dynamic Time Input Management
    // ============================================
    function setupTimeInputValidation() {
        const startTimeInput = document.querySelector('input[name="event_start_time"]');
        const endTimeInput = document.querySelector('input[name="event_end_time"]');
        
        if (!startTimeInput || !endTimeInput) return;
        
        // Function to update end time constraints
        function updateEndTimeConstraints() {
            const startTime = startTimeInput.value;
            
            if (!startTime) {
                // No start time selected - disable end time
                endTimeInput.disabled = true;
                endTimeInput.value = '';
                endTimeInput.placeholder = 'Select start time first';
                clearFieldError(endTimeInput);
                return;
            }
            
            // Enable end time and set minimum to start time + 15 minutes
            endTimeInput.disabled = false;
            endTimeInput.placeholder = '';
            
            // Calculate minimum end time (start time + 15 minutes)
            const [startHour, startMinute] = startTime.split(':').map(Number);
            let minEndHour = startHour;
            let minEndMinute = startMinute + 15;
            
            if (minEndMinute >= 60) {
                minEndHour += 1;
                minEndMinute -= 60;
            }
            
            // Ensure we don't go past 8:30 PM
            const maxEndTime = '20:30';
            const minEndTime = String(minEndHour).padStart(2, '0') + ':' + String(minEndMinute).padStart(2, '0');
            
            if (minEndTime > maxEndTime) {
                // Start time too late for any valid end time
                endTimeInput.disabled = true;
                endTimeInput.value = '';
                showFieldError(startTimeInput, 'Start time too late - no valid end times available');
                return;
            }
            
            endTimeInput.min = minEndTime;
            endTimeInput.max = maxEndTime;
            
            // Clear any existing end time if it's now invalid
            if (endTimeInput.value && endTimeInput.value <= startTime) {
                endTimeInput.value = '';
            }
            
            clearFieldError(startTimeInput);
        }
        
        // Start time change handler
        startTimeInput.addEventListener('change', function() {
            if (this.value) {
                validateEventTime(this, false);
            }
            updateEndTimeConstraints();
        });
        
        // Start time input handler for real-time feedback and correction
        startTimeInput.addEventListener('input', function() {
            const timeValue = this.value;
            if (timeValue) {
                const [hours, minutes] = timeValue.split(':').map(Number);
                const timeInMinutes = hours * 60 + minutes;
                
                const allowedStart = 5 * 60; // 5:00 AM
                const allowedEnd = 20 * 60 + 30; // 8:30 PM
                
                if (timeInMinutes < allowedStart) {
                    // Auto-correct to 5:00 AM
                    this.value = '05:00';
                    showFieldError(this, 'Time corrected to 5:00 AM (minimum allowed time)');
                    setTimeout(() => clearFieldError(this), 2000);
                } else if (timeInMinutes > allowedEnd) {
                    // Auto-correct to 8:30 PM
                    this.value = '20:30';
                    showFieldError(this, 'Time corrected to 8:30 PM (maximum allowed time)');
                    setTimeout(() => clearFieldError(this), 2000);
                } else {
                    clearFieldError(this);
                }
            }
            updateEndTimeConstraints();
        });
        
        // End time change handler
        endTimeInput.addEventListener('change', function() {
            if (this.value && startTimeInput.value) {
                validateEventTime(this, true);
                validateTimeRange(startTimeInput, this);
            }
        });
        
        // End time input handler with auto-correction
        endTimeInput.addEventListener('input', function() {
            if (this.value && !this.disabled) {
                const [hours, minutes] = this.value.split(':').map(Number);
                const timeInMinutes = hours * 60 + minutes;
                
                const allowedStart = 5 * 60; // 5:00 AM
                const allowedEnd = 20 * 60 + 30; // 8:30 PM
                
                // Get minimum end time from the min attribute
                const minEndTime = this.min;
                let minEndTimeInMinutes = allowedStart;
                
                if (minEndTime) {
                    const [minHour, minMinute] = minEndTime.split(':').map(Number);
                    minEndTimeInMinutes = minHour * 60 + minMinute;
                }
                
                if (timeInMinutes < minEndTimeInMinutes) {
                    // Auto-correct to minimum allowed end time
                    this.value = minEndTime || '05:00';
                    showFieldError(this, `End time must be after start time - corrected to ${this.value}`);
                    setTimeout(() => clearFieldError(this), 2000);
                } else if (timeInMinutes > allowedEnd) {
                    // Auto-correct to 8:30 PM
                    this.value = '20:30';
                    showFieldError(this, 'Time corrected to 8:30 PM (maximum allowed time)');
                    setTimeout(() => clearFieldError(this), 2000);
                } else {
                    clearFieldError(this);
                }
            }
        });
        
        // Add additional protection against invalid time entry
        function preventInvalidTimeEntry(input) {
            // Prevent typing invalid times
            input.addEventListener('keydown', function(e) {
                // Allow navigation keys
                if (['Tab', 'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(e.key)) {
                    return;
                }
                
                // For time inputs, we'll let the browser handle most validation
                // but we'll catch it in the input event
            });
            
            // Prevent pasting invalid times
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                
                // Try to parse as time
                if (/^\d{1,2}:\d{2}$/.test(pastedText)) {
                    const [hours, minutes] = pastedText.split(':').map(Number);
                    const timeInMinutes = hours * 60 + minutes;
                    
                    const allowedStart = 5 * 60;
                    const allowedEnd = 20 * 60 + 30;
                    
                    if (timeInMinutes >= allowedStart && timeInMinutes <= allowedEnd) {
                        this.value = pastedText;
                        this.dispatchEvent(new Event('input'));
                    } else {
                        showFieldError(this, 'Pasted time is outside allowed range (5:00 AM - 8:30 PM)');
                        setTimeout(() => clearFieldError(this), 2000);
                    }
                }
            });
        }
        
        // Apply protection to both inputs
        preventInvalidTimeEntry(startTimeInput);
        preventInvalidTimeEntry(endTimeInput);
        
        // Initialize on page load
        updateEndTimeConstraints();
        
        // If form has errors and both times are set, re-enable end time
        if (startTimeInput.value) {
            updateEndTimeConstraints();
            if (endTimeInput.getAttribute('value')) {
                endTimeInput.value = endTimeInput.getAttribute('value');
            }
        }
    }

    // Initialize on Page Load
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        setupFormValidation();
        setupRealtimeValidation();
        setupTimeInputValidation();
    });

})();
// ============================================
// Modern File Upload Functionality
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const fileUploadArea = document.querySelector('.file-upload-area');
    const fileInput = document.querySelector('.file-input');
    const fileList = document.getElementById('file-list');
    
    if (fileUploadArea && fileInput) {
        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--evsu-gold)';
            this.style.background = '#fffbf0';
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = '#f8f9fa';
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = '#f8f9fa';
            
            const files = e.dataTransfer.files;
            fileInput.files = files;
            displaySelectedFiles(files);
        });
        
        // File input change
        fileInput.addEventListener('change', function() {
            displaySelectedFiles(this.files);
        });
    }
    
    function displaySelectedFiles(files) {
        if (!fileList) return;
        
        fileList.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'file-item-info';
            
            const fileIcon = document.createElement('i');
            fileIcon.className = 'fas ' + getFileIcon(file.type) + ' file-item-icon';
            
            const fileName = document.createElement('span');
            fileName.className = 'file-item-name';
            fileName.textContent = file.name;
            
            const fileSize = document.createElement('span');
            fileSize.className = 'file-item-size';
            fileSize.textContent = '(' + formatFileSize(file.size) + ')';
            
            fileInfo.appendChild(fileIcon);
            fileInfo.appendChild(fileName);
            fileInfo.appendChild(fileSize);
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                removeFile(index);
            };
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            fileList.appendChild(fileItem);
        });
    }
    
    function getFileIcon(fileType) {
        if (fileType.includes('pdf')) return 'fa-file-pdf';
        if (fileType.includes('word') || fileType.includes('document')) return 'fa-file-word';
        if (fileType.includes('image')) return 'fa-file-image';
        return 'fa-file';
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function removeFile(index) {
        const dt = new DataTransfer();
        const files = fileInput.files;
        
        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }
        
        fileInput.files = dt.files;
        displaySelectedFiles(fileInput.files);
    }
});

// ============================================
// Multi-Step Form Navigation
// ============================================

let currentStep = 1;
const totalSteps = 5;

// Initialize multi-step form
document.addEventListener('DOMContentLoaded', function() {
    initializeMultiStepForm();
    setupFormValidation();
    setupRealtimeValidation();
});

function initializeMultiStepForm() {
    updateProgressBar();
    updateStepVisibility();
    updateNavigationButtons();
    
    // Add event listeners for form inputs to update review
    const form = document.getElementById('multiStepForm');
    if (form) {
        form.addEventListener('input', updateReviewData);
        form.addEventListener('change', updateReviewData);
    }
}

function changeStep(direction) {
    const newStep = currentStep + direction;
    
    if (newStep < 1 || newStep > totalSteps) {
        return;
    }
    
    // Validate current step before moving forward
    if (direction > 0 && !validateCurrentStep()) {
        return;
    }
    
    currentStep = newStep;
    updateProgressBar();
    updateStepVisibility();
    updateNavigationButtons();
    
    // Update review data when reaching review step
    if (currentStep === 5) {
        updateReviewData();
    }
}

function updateProgressBar() {
    const progressFill = document.getElementById('progressFill');
    const steps = document.querySelectorAll('.step');
    
    if (progressFill) {
        const progressPercent = (currentStep / totalSteps) * 100;
        progressFill.style.width = progressPercent + '%';
    }
    
    steps.forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNumber === currentStep) {
            step.classList.add('active');
        } else if (stepNumber < currentStep) {
            step.classList.add('completed');
        }
    });
}

function updateStepVisibility() {
    const stepContents = document.querySelectorAll('.step-content');
    
    stepContents.forEach((content, index) => {
        const stepNumber = index + 1;
        content.classList.remove('active');
        
        if (stepNumber === currentStep) {
            content.classList.add('active');
        }
    });
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    if (prevBtn) {
        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
    }
    
    if (nextBtn && submitBtn) {
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-flex';
        } else {
            nextBtn.style.display = 'inline-flex';
            submitBtn.style.display = 'none';
        }
    }
}

function validateCurrentStep() {
    const currentStepContent = document.querySelector(`.step-content[data-step="${currentStep}"]`);
    if (!currentStepContent) return true;
    
    const requiredFields = currentStepContent.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Additional validation for specific steps
    if (currentStep === 1) {
        const dateInput = currentStepContent.querySelector('input[name="event_date"]');
        if (dateInput && !validateEventDate(dateInput)) {
            isValid = false;
        }
        
        const startTimeInput = currentStepContent.querySelector('input[name="event_start_time"]');
        const endTimeInput = currentStepContent.querySelector('input[name="event_end_time"]');
        
        if (startTimeInput && !validateEventTime(startTimeInput, false)) {
            isValid = false;
        }
        
        if (endTimeInput && !validateEventTime(endTimeInput, true)) {
            isValid = false;
        }
        
        if (startTimeInput && endTimeInput && !validateTimeRange(startTimeInput, endTimeInput)) {
            isValid = false;
        }
    }
    
    if (currentStep === 3) {
        const volunteerInput = currentStepContent.querySelector('input[name="volunteers_needed"]');
        if (volunteerInput && !validateVolunteerCount(volunteerInput)) {
            isValid = false;
        }
    }
    
    if (currentStep === 4) {
        const fileInput = currentStepContent.querySelector('input[type="file"]');
        if (fileInput && fileInput.files.length === 0) {
            showFieldError(fileInput.parentNode, 'At least one attachment is required');
            isValid = false;
        }
    }
    
    if (!isValid) {
        // Scroll to first error
        const firstError = currentStepContent.querySelector('.field-input.error, .input-container.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    return isValid;
}

function updateReviewData() {
    const form = document.getElementById('multiStepForm');
    if (!form) return;
    
    // Event Information
    updateReviewField('review-event-name', form.querySelector('[name="event_name"]')?.value);
    updateReviewField('review-organization', form.querySelector('[name="organization"]')?.value);
    
    const eventDate = form.querySelector('[name="event_date"]')?.value;
    const startTime = form.querySelector('[name="event_start_time"]')?.value;
    const endTime = form.querySelector('[name="event_end_time"]')?.value;
    let dateTimeText = '';
    if (eventDate) {
        dateTimeText = new Date(eventDate).toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        if (startTime) {
            dateTimeText += ` at ${startTime}`;
            if (endTime) {
                dateTimeText += ` - ${endTime}`;
            }
        }
    }
    updateReviewField('review-datetime', dateTimeText);
    updateReviewField('review-venue', form.querySelector('[name="venue"]')?.value);
    
    // Your Details
    updateReviewField('review-name', form.querySelector('[name="requester_name"]')?.value);
    updateReviewField('review-email', form.querySelector('[name="requester_email"]')?.value);
    updateReviewField('review-position', form.querySelector('[name="requester_position"]')?.value || 'Not specified');
    updateReviewField('review-contact', form.querySelector('[name="contact_number"]')?.value || 'Not provided');
    
    // Requirements
    updateReviewField('review-participants', form.querySelector('[name="expected_participants"]')?.value || 'Not specified');
    updateReviewField('review-volunteers', form.querySelector('[name="volunteers_needed"]')?.value);
    updateReviewField('review-roles', form.querySelector('[name="volunteer_roles"]')?.value || 'Not specified');
    
    // Event Details
    updateReviewField('review-description', form.querySelector('[name="event_description"]')?.value || 'Not provided');
    updateReviewField('review-special', form.querySelector('[name="special_requirements"]')?.value || 'None');
    
    // Files
    const fileInput = form.querySelector('[name="attachments[]"]');
    let filesText = 'No files selected';
    if (fileInput && fileInput.files.length > 0) {
        const fileNames = Array.from(fileInput.files).map(file => file.name);
        filesText = fileNames.join(', ');
    }
    updateReviewField('review-files', filesText);
}

function updateReviewField(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value || '-';
    }
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545 !important';
    errorDiv.style.fontSize = '0.875rem !important';
    errorDiv.style.marginTop = '0.5rem !important';
    errorDiv.style.display = 'block !important';
    errorDiv.style.width = '100% !important';
    errorDiv.style.clear = 'both !important';
    
    // Find the field group container and append error at the end (after hint if present)
    const fieldGroup = field.closest('.field-group') || field.closest('.form-group');
    if (fieldGroup) {
        fieldGroup.appendChild(errorDiv);
        fieldGroup.classList.add('error');
    } else {
        // Fallback to parent of input container
        const inputContainer = field.closest('.input-container');
        if (inputContainer && inputContainer.parentNode) {
            inputContainer.parentNode.appendChild(errorDiv);
            inputContainer.parentNode.classList.add('error');
        } else {
            // Last resort
            field.parentNode.appendChild(errorDiv);
            field.parentNode.classList.add('error');
        }
    }
}

function clearFieldError(field) {
    field.classList.remove('error');
    
    // Clear from field group first
    const fieldGroup = field.closest('.field-group') || field.closest('.form-group');
    if (fieldGroup) {
        fieldGroup.classList.remove('error');
        const errorDiv = fieldGroup.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    // Also clear from input container (fallback)
    const container = field.closest('.input-container') || field.parentNode;
    container.classList.remove('error');
    const errorDiv = container.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Make functions globally available
window.changeStep = changeStep;

// ============================================
// Form Animation and Enhancement
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced input focus effects
    document.querySelectorAll('.field-input').forEach(input => {
        input.addEventListener('focus', function() {
            const container = this.closest('.input-container');
            if (container) {
                container.style.transform = 'translateY(-2px)';
                container.style.boxShadow = '0 8px 25px rgba(255, 215, 0, 0.15)';
            }
        });
        
        input.addEventListener('blur', function() {
            const container = this.closest('.input-container');
            if (container) {
                container.style.transform = 'translateY(0)';
                container.style.boxShadow = 'none';
            }
        });
    });
});

// ============================================
// Form Validation Enhancement
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.modern-form');
    if (!form) return;
    
    // Real-time validation
    form.addEventListener('input', function(e) {
        const input = e.target;
        if (input.matches('.modern-input, .modern-textarea')) {
            validateField(input);
        }
    });
    
    function validateField(field) {
        const wrapper = field.closest('.input-wrapper, .textarea-wrapper');
        const isValid = field.checkValidity();
        
        if (wrapper) {
            if (isValid && field.value.trim() !== '') {
                wrapper.style.borderColor = '#28a745';
                field.style.borderColor = '#28a745';
            } else if (!isValid && field.value.trim() !== '') {
                wrapper.style.borderColor = '#dc3545';
                field.style.borderColor = '#dc3545';
            } else {
                wrapper.style.borderColor = '#e9ecef';
                field.style.borderColor = '#e9ecef';
            }
        }
    }
});