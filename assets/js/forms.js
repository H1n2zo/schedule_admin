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
    // EVSU Email Validation
    // ============================================
    window.validateEVSUEmail = function(email) {
        return email.endsWith('@evsu.edu.ph');
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
        
        // Check if date is at least 1 week from now (optional requirement)
        const oneWeekFromNow = new Date();
        oneWeekFromNow.setDate(oneWeekFromNow.getDate() + 7);
        oneWeekFromNow.setHours(0, 0, 0, 0);
        
        if (selectedDate < oneWeekFromNow) {
            showFieldWarning(dateInput, 'Recommended: Submit requests at least 1 week in advance');
        }
        
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
        
        if (value > 100) {
            showFieldError(input, 'Volunteer count cannot exceed 100');
            return false;
        }
        
        clearFieldError(input);
        return true;
    };

    // ============================================
    // Show Field Error
    // ============================================
    function showFieldError(input, message) {
        clearFieldError(input);
        
        input.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
    }

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
    // Clear Field Error
    // ============================================
    function clearFieldError(input) {
        input.classList.remove('is-invalid');
        input.classList.remove('is-valid');
        
        const feedback = input.parentNode.querySelector('.invalid-feedback, .text-warning');
        if (feedback) {
            feedback.remove();
        }
    }

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
                    } else if (!validateEVSUEmail(emailInput.value)) {
                        showFieldWarning(emailInput, 'Email should be from @evsu.edu.ph domain');
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
    // Character Counter for Textareas
    // ============================================
    function setupCharacterCounters() {
        const textareas = document.querySelectorAll('textarea[data-max-length]');
        
        textareas.forEach(textarea => {
            const maxLength = parseInt(textarea.dataset.maxLength);
            
            const counter = document.createElement('div');
            counter.className = 'text-muted small mt-1';
            counter.id = textarea.id + '_counter';
            textarea.parentNode.appendChild(counter);
            
            function updateCounter() {
                const remaining = maxLength - textarea.value.length;
                counter.textContent = `${textarea.value.length} / ${maxLength} characters`;
                
                if (remaining < 0) {
                    counter.classList.add('text-danger');
                    counter.classList.remove('text-muted');
                } else if (remaining < 50) {
                    counter.classList.add('text-warning');
                    counter.classList.remove('text-muted');
                } else {
                    counter.className = 'text-muted small mt-1';
                }
            }
            
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    }

    // ============================================
    // File Size Validation
    // ============================================
    function validateFileSize(files, maxSizeMB = 5) {
        let valid = true;
        
        Array.from(files).forEach(file => {
            const sizeMB = file.size / (1024 * 1024);
            if (sizeMB > maxSizeMB) {
                showToast(`File "${file.name}" exceeds ${maxSizeMB}MB limit`, 'danger');
                valid = false;
            }
        });
        
        return valid;
    }

    // ============================================
    // File Type Validation
    // ============================================
    function validateFileType(files, allowedTypes) {
        let valid = true;
        
        Array.from(files).forEach(file => {
            const isAllowed = allowedTypes.some(type => file.type.includes(type));
            if (!isAllowed) {
                showToast(`File type not allowed: "${file.name}"`, 'danger');
                valid = false;
            }
        });
        
        return valid;
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
    // Initialize on Page Load
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        setupFormValidation();
        setupRealtimeValidation();
        setupCharacterCounters();
    });

})();