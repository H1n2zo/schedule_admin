/**
 * EVSU Event Management System
 * Dashboard JavaScript Functions
 * File: assets/js/dashboard.js
 */

(function() {
    'use strict';

    // ============================================
    // Calendar Date Selection
    // ============================================
    window.selectDate = function(date) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('date', date);
        window.location.href = currentUrl.toString();
    };

    // ============================================
    // View Request Details
    // ============================================
    window.viewRequest = function(id) {
        window.location.href = 'view_request.php?id=' + id;
    };

    // ============================================
    // Filter Requests by Status
    // ============================================
    window.filterByStatus = function(status) {
        const items = document.querySelectorAll('.request-list-item');
        const emptyMessage = document.getElementById('emptyMessage');
        let visibleCount = 0;
        
        items.forEach(item => {
            if (status === 'all' || item.dataset.status === status) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Handle empty message
        if (emptyMessage) {
            if (visibleCount === 0) {
                emptyMessage.style.display = 'block';
                const statusText = status === 'all' ? '' : status + ' ';
                emptyMessage.textContent = `No ${statusText}requests found.`;
            } else {
                emptyMessage.style.display = 'none';
            }
        } else if (visibleCount === 0) {
            const requestsList = document.getElementById('requestsList');
            if (requestsList) {
                const msg = document.createElement('p');
                msg.id = 'emptyMessage';
                msg.className = 'text-muted';
                const statusText = status === 'all' ? '' : status + ' ';
                msg.textContent = `No ${statusText}requests found.`;
                requestsList.parentNode.insertBefore(msg, requestsList);
            }
        }
    };

    // ============================================
    // Calendar Navigation
    // ============================================
    window.navigateMonth = function(month, year) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('month', month);
        currentUrl.searchParams.set('year', year);
        // Remove date parameter when navigating months
        currentUrl.searchParams.delete('date');
        window.location.href = currentUrl.toString();
    };

    // ============================================
    // Clear Date Filter
    // ============================================
    window.clearDateFilter = function() {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('date');
        window.location.href = currentUrl.toString();
    };

    // ============================================
    // Highlight Today's Events
    // ============================================
    function highlightTodaysEvents() {
        const today = new Date().toISOString().split('T')[0];
        const todayCell = document.querySelector(`.calendar-day[data-date="${today}"]`);
        
        if (todayCell && !todayCell.classList.contains('today')) {
            todayCell.classList.add('today');
        }
    }

    // ============================================
    // Initialize on Page Load
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        highlightTodaysEvents();
        initializeCalendarInteractions();
        initializeFilterButtons();
    });

    // ============================================
    // Calendar Interactions
    // ============================================
    function initializeCalendarInteractions() {
        const calendarDays = document.querySelectorAll('.calendar-day:not(.empty)');
        
        calendarDays.forEach(day => {
            // Add keyboard accessibility
            day.setAttribute('tabindex', '0');
            
            // Keyboard navigation
            day.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const date = this.getAttribute('data-date') || this.getAttribute('onclick').match(/'([^']+)'/)[1];
                    selectDate(date);
                }
            });
        });
    }

    // ============================================
    // Initialize Filter Buttons
    // ============================================
    function initializeFilterButtons() {
        const filterButtons = document.querySelectorAll('.btn-check');
        
        filterButtons.forEach(button => {
            button.addEventListener('change', function() {
                const status = this.id.replace('filter', '').toLowerCase();
                filterByStatus(status);
            });
        });
    }

    // ============================================
    // Quick Stats Update (for dynamic updates)
    // ============================================
    window.updateStats = function(pending, approved, pendingActions) {
        const pendingEl = document.querySelector('.stats-card .text-warning');
        const approvedEl = document.querySelector('.stats-card .text-success');
        const actionsEl = document.querySelector('.stats-card .text-info');
        
        if (pendingEl) pendingEl.textContent = pending;
        if (approvedEl) approvedEl.textContent = approved;
        if (actionsEl) actionsEl.textContent = pendingActions;
    };

    // ============================================
    // Export Calendar Data (Future Feature)
    // ============================================
    window.exportCalendar = function(format = 'csv') {
        showToast('Export feature coming soon!', 'info');
        // TODO: Implement calendar export functionality
    };

    // ============================================
    // Print Calendar View
    // ============================================
    window.printCalendar = function() {
        window.print();
    };

    // ============================================
    // Search Requests (Live Search)
    // ============================================
    window.searchRequests = function(searchTerm) {
        const items = document.querySelectorAll('.request-list-item');
        searchTerm = searchTerm.toLowerCase();
        let visibleCount = 0;
        
        items.forEach(item => {
            const eventName = item.querySelector('h6').textContent.toLowerCase();
            const organization = item.querySelector('small').textContent.toLowerCase();
            
            if (eventName.includes(searchTerm) || organization.includes(searchTerm)) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Update empty message
        const emptyMessage = document.getElementById('emptyMessage');
        if (emptyMessage) {
            if (visibleCount === 0) {
                emptyMessage.style.display = 'block';
                emptyMessage.textContent = 'No requests match your search.';
            } else {
                emptyMessage.style.display = 'none';
            }
        }
    };

    // ============================================
    // Add Search Input Event Listener
    // ============================================
    const searchInput = document.getElementById('searchRequests');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            searchRequests(e.target.value);
        }, 300));
    }

})();