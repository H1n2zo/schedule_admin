/**
 * EVSU Event Management System
 * Global JavaScript Functions
 * File: assets/js/main.js
 */

(function() {
    'use strict';

    // ============================================
    // Initialize on DOM Load
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        initializeTooltips();
        initializePopovers();
        autoHideAlerts();
        setupFormChangeDetection();
    });

    // ============================================
    // Bootstrap Tooltips Initialization
    // ============================================
    function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // ============================================
    // Bootstrap Popovers Initialization
    // ============================================
    function initializePopovers() {
        const popoverTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="popover"]')
        );
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }

    // ============================================
    // Admin Shortcut (Ctrl+Shift+A to login page)
    // ============================================
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && (e.key === 'A' || e.key === 'a')) {
            e.preventDefault();
            window.location.href = 'login.php';
        }
    });

    // ============================================
    // Form Change Detection
    // ============================================
    let formChanged = false;

    function setupFormChangeDetection() {
        const forms = document.querySelectorAll('form[data-warn-unsaved="true"]');
        
        forms.forEach(form => {
            form.addEventListener('change', function() {
                formChanged = true;
            });
            
            form.addEventListener('input', function() {
                formChanged = true;
            });
            
            form.addEventListener('submit', function() {
                formChanged = false;
            });
        });
    }

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });

    // ============================================
    // Auto-dismiss Alerts
    // ============================================
    function autoHideAlerts() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            if (alert.classList.contains('alert-dismissible')) {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });
    }

    // ============================================
    // Smooth Scroll to Top
    // ============================================
    window.scrollToTop = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    // ============================================
    // Show/Hide Back to Top Button
    // ============================================
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });

        backToTopBtn.addEventListener('click', function() {
            scrollToTop();
        });
    }

    // ============================================
    // Confirm Before Delete
    // ============================================
    window.confirmDelete = function(itemName) {
        return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
    };

    // ============================================
    // Format Date Helper
    // ============================================
    window.formatDate = function(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    };

    // ============================================
    // Format Time Helper
    // ============================================
    window.formatTime = function(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    };

    // ============================================
    // Copy to Clipboard
    // ============================================
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showToast('Copied to clipboard!', 'success');
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                showToast('Failed to copy', 'error');
            });
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showToast('Copied to clipboard!', 'success');
            } catch (err) {
                console.error('Failed to copy: ', err);
                showToast('Failed to copy', 'error');
            }
            document.body.removeChild(textarea);
        }
    };

    // ============================================
    // Show Toast Notification
    // ============================================
    window.showToast = function(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // ============================================
    // Loading State Handler
    // ============================================
    window.setLoadingState = function(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.classList.add('loading');
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    };

    // ============================================
    // Debounce Function
    // ============================================
    window.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // ============================================
    // Local Storage Helpers
    // ============================================
    window.setStorage = function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.error('Error saving to localStorage:', e);
            return false;
        }
    };

    window.getStorage = function(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (e) {
            console.error('Error reading from localStorage:', e);
            return null;
        }
    };

    window.removeStorage = function(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.error('Error removing from localStorage:', e);
            return false;
        }
    };

    // ============================================
    // Console Welcome Message
    // ============================================
    console.log('%cEVSU Event Management System', 'color: #800000; font-size: 20px; font-weight: bold;');
    console.log('%cDeveloped for Eastern Visayas State University', 'color: #FFD700; font-size: 12px;');
    console.log('%c⚠️ Warning: Using this console may allow attackers to impersonate you and steal your information. Do not enter or paste code that you do not understand.', 'color: #dc3545; font-size: 14px; font-weight: bold;');

})();