/**
 * EVSU Event Management System
 * Modal Management Functions
 * File: assets/js/modals.js
 */

(function() {
    'use strict';

    // ============================================
    // Confirm Delete Modal
    // ============================================
    window.confirmDelete = function(itemName = '') {
        const modalEl = document.getElementById('deleteModal');
        if (!modalEl) {
            console.error('Delete modal not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalEl);
        
        // Update modal content with item name if provided
        if (itemName) {
            const itemNameEl = modalEl.querySelector('.item-name');
            if (itemNameEl) {
                itemNameEl.textContent = itemName;
            }
        }
        
        modal.show();
    };

    // ============================================
    // Confirm Action (Approve/Disapprove)
    // ============================================
    window.confirmAction = function(action, eventData = {}) {
        const modalEl = document.getElementById('confirmModal');
        if (!modalEl) {
            console.error('Confirm modal not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalEl);
        const actionInput = document.getElementById('actionType');
        const title = document.getElementById('confirmTitle');
        const message = document.getElementById('confirmMessage');
        const btn = document.getElementById('confirmBtn');
        
        if (actionInput) {
            actionInput.value = action;
        }
        
        if (action === 'approve') {
            if (title) title.textContent = 'Approve Event Request';
            if (message) message.innerHTML = getApproveMessage(eventData);
            if (btn) {
                btn.className = 'btn btn-success';
                btn.innerHTML = '<i class="fas fa-check"></i> Confirm Approval';
            }
        } else if (action === 'disapprove') {
            if (title) title.textContent = 'Disapprove Event Request';
            if (message) message.innerHTML = getDisapproveMessage(eventData);
            if (btn) {
                btn.className = 'btn btn-danger';
                btn.innerHTML = '<i class="fas fa-times"></i> Confirm Disapproval';
            }
        }
        
        modal.show();
    };

    // ============================================
    // Confirm Cancel Action
    // ============================================
    window.confirmCancel = function(actionId, requestId, eventName) {
        const modalEl = document.getElementById('cancelModal');
        if (!modalEl) {
            console.error('Cancel modal not found');
            return;
        }
        
        const actionIdInput = document.getElementById('cancelActionId');
        const requestIdInput = document.getElementById('cancelRequestId');
        const eventInfoEl = document.getElementById('cancelEventInfo');
        
        if (actionIdInput) actionIdInput.value = actionId;
        if (requestIdInput) requestIdInput.value = requestId;
        if (eventInfoEl) eventInfoEl.innerHTML = `<strong>${eventName}</strong>`;
        
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    };

    // ============================================
    // Generate Approve Message HTML
    // ============================================
    function getApproveMessage(eventData) {
        return `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                You are about to <strong>approve</strong> this event request.
            </div>
            ${eventData.eventName ? `
                <div class="alert alert-info">
                    <strong><i class="fas fa-calendar"></i> ${eventData.eventName}</strong><br>
                    ${eventData.organization ? `<small>${eventData.organization}</small><br>` : ''}
                    ${eventData.eventDate ? `<small>${eventData.eventDate}</small>` : ''}
                </div>
            ` : ''}
            <p>This will move the request to the <strong>Pending Actions</strong> queue where you can send the approval notification to the requester.</p>
            <p class="mb-0"><strong>Are you sure you want to proceed?</strong></p>
        `;
    }

    // ============================================
    // Generate Disapprove Message HTML
    // ============================================
    function getDisapproveMessage(eventData) {
        return `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> 
                You are about to <strong>disapprove</strong> this event request.
            </div>
            ${eventData.eventName ? `
                <div class="alert alert-warning">
                    <strong><i class="fas fa-calendar"></i> ${eventData.eventName}</strong><br>
                    ${eventData.organization ? `<small>${eventData.organization}</small><br>` : ''}
                    ${eventData.eventDate ? `<small>${eventData.eventDate}</small>` : ''}
                </div>
            ` : ''}
            <p>This will move the request to the <strong>Pending Actions</strong> queue where you can send the disapproval notification with a reason.</p>
            <p class="mb-0"><strong>Are you sure you want to proceed?</strong></p>
        `;
    }

    // ============================================
    // User Management Modals
    // ============================================
    window.confirmUserAction = function(userId, action, userName) {
        const modalEl = document.getElementById('userActionModal');
        if (!modalEl) {
            console.error('User action modal not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalEl);
        const title = modalEl.querySelector('.modal-title');
        const message = modalEl.querySelector('.modal-body');
        const confirmBtn = modalEl.querySelector('.btn-primary');
        const userIdInput = document.getElementById('userActionId');
        const actionInput = document.getElementById('userActionType');
        
        if (userIdInput) userIdInput.value = userId;
        if (actionInput) actionInput.value = action;
        
        let titleText, messageHTML, btnClass, btnText;
        
        switch(action) {
            case 'promote':
                titleText = 'Promote to Administrator';
                messageHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-arrow-up"></i> 
                        Are you sure you want to promote <strong>${userName}</strong> to administrator?
                    </div>
                    <p>They will have full access to all admin features including:</p>
                    <ul>
                        <li>Approving/disapproving event requests</li>
                        <li>Managing users</li>
                        <li>Sending notifications</li>
                        <li>Viewing all audit logs</li>
                    </ul>
                `;
                btnClass = 'btn-success';
                btnText = '<i class="fas fa-arrow-up"></i> Confirm Promotion';
                break;
                
            case 'demote':
                titleText = 'Demote from Administrator';
                messageHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-arrow-down"></i> 
                        Are you sure you want to demote <strong>${userName}</strong> to regular user?
                    </div>
                    <p>They will lose all administrative privileges.</p>
                `;
                btnClass = 'btn-warning';
                btnText = '<i class="fas fa-arrow-down"></i> Confirm Demotion';
                break;
                
            case 'delete':
                titleText = 'Delete User';
                messageHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-trash"></i> 
                        Are you sure you want to permanently delete <strong>${userName}</strong>?
                    </div>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone!</p>
                    <p>All data associated with this user will be permanently removed.</p>
                `;
                btnClass = 'btn-danger';
                btnText = '<i class="fas fa-trash"></i> Confirm Deletion';
                break;
        }
        
        if (title) title.textContent = titleText;
        if (message) message.innerHTML = messageHTML;
        if (confirmBtn) {
            confirmBtn.className = 'btn ' + btnClass;
            confirmBtn.innerHTML = btnText;
        }
        
        modal.show();
    };

    // ============================================
    // View Request Details Modal (Quick View)
    // ============================================
    window.showRequestModal = function(requestData) {
        const modalEl = document.getElementById('requestModal');
        if (!modalEl) {
            console.error('Request modal not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalEl);
        
        // Populate modal with request data
        const modalBody = modalEl.querySelector('.modal-body');
        if (modalBody && requestData) {
            modalBody.innerHTML = `
                <div class="mb-3">
                    <strong>Event Name:</strong><br>
                    ${requestData.eventName || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Organization:</strong><br>
                    ${requestData.organization || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Date & Time:</strong><br>
                    ${requestData.eventDate || 'N/A'} at ${requestData.eventTime || 'N/A'}
                </div>
                <div class="mb-3">
                    <strong>Volunteers Needed:</strong><br>
                    ${requestData.volunteersNeeded || 0}
                </div>
                <div class="mb-3">
                    <strong>Description:</strong><br>
                    ${requestData.description || 'No description provided'}
                </div>
            `;
        }
        
        modal.show();
    };

    // ============================================
    // Close All Modals
    // ============================================
    window.closeAllModals = function() {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modalEl => {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    };

    // ============================================
    // Modal Event Listeners
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Cleanup on modal hide
        document.querySelectorAll('.modal').forEach(modalEl => {
            modalEl.addEventListener('hidden.bs.modal', function() {
                // Reset form if exists
                const form = this.querySelector('form');
                if (form) {
                    form.reset();
                }
                
                // Remove validation classes
                this.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                    el.classList.remove('is-invalid', 'is-valid');
                });
            });
        });
    });

})();