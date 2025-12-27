/**
 * EVSU Event Management System
 * Modal Management Functions
 * File: assets/js/modals.js
 */

(function() {
    'use strict';

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