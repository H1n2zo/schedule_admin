/**
 * EVSU Event Management System
 * Gmail Calendar Style Dashboard JavaScript Functions
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
        currentUrl.searchParams.delete('event'); // Clear event selection when selecting date
        window.location.href = currentUrl.toString();
    };

    // ============================================
    // Show Day Events Modal
    // ============================================
    window.showDayEvents = function(date) {
        // Format the date for display
        const dateObj = new Date(date + 'T00:00:00');
        const formattedDate = dateObj.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Update modal title
        document.getElementById('dayEventsModalLabel').textContent = `Events for ${formattedDate}`;
        
        // Show loading state
        const modalContent = document.getElementById('dayEventsContent');
        modalContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" style="color: var(--evsu-maroon);" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading events for this day...</p>
            </div>
        `;
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('dayEventsModal'));
        modal.show();
        
        // Fetch events for this date
        fetch('get_day_events.php?date=' + date)
            .then(response => {

                return response.text();
            })
            .then(html => {

                // Check if response is JSON (starts with { or [)
                if (html.trim().startsWith('{') || html.trim().startsWith('[')) {
                    console.error('Received JSON instead of HTML:', html);
                    modalContent.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Unexpected response format. Please check the server configuration.
                        </div>
                    `;
                } else {
                    modalContent.innerHTML = html;
                }
            })
            .catch(error => {
                console.error('Error loading day events:', error);
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading events for this day. Please try again.
                    </div>
                `;
            });
    };

    // ============================================
    // Month Navigation Functions
    // ============================================
    window.navigateMonth = function(direction) {
        const currentUrl = new URL(window.location.href);
        const currentMonth = parseInt(currentUrl.searchParams.get('month')) || new Date().getMonth() + 1;
        const currentYear = parseInt(currentUrl.searchParams.get('year')) || new Date().getFullYear();
        
        let newMonth = currentMonth + direction;
        let newYear = currentYear;
        
        if (newMonth > 12) {
            newMonth = 1;
            newYear++;
        } else if (newMonth < 1) {
            newMonth = 12;
            newYear--;
        }
        
        currentUrl.searchParams.set('month', newMonth);
        currentUrl.searchParams.set('year', newYear);
        currentUrl.searchParams.delete('date'); // Clear selected date when changing months
        window.location.href = currentUrl.toString();
    };

    window.navigateToToday = function() {
        const today = new Date();
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('month', today.getMonth() + 1);
        currentUrl.searchParams.set('year', today.getFullYear());
        currentUrl.searchParams.set('date', today.toISOString().split('T')[0]);
        window.location.href = currentUrl.toString();
    };

    // ============================================
    // Status Filter Functions
    // ============================================
    window.filterByStatus = function(status) {
        const currentUrl = new URL(window.location.href);
        if (status === 'all') {
            currentUrl.searchParams.delete('filter');
        } else {
            currentUrl.searchParams.set('filter', status);
        }
        currentUrl.searchParams.delete('date'); // Clear selected date when filtering
        window.location.href = currentUrl.toString();
    };

    // ============================================
    // View Event in Modal
    // ============================================
    window.viewEvent = function(eventId, event) {
        if (event) {
            event.stopPropagation(); // Prevent date selection
        }
        
        // Load event details via AJAX and show modal
        loadEventDetails(eventId);
    };

    // ============================================
    // Load Event Details for Modal
    // ============================================
    function loadEventDetails(eventId) {
        // Show loading state
        const modalContent = document.getElementById('eventDetailsContent');
        modalContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" style="color: var(--evsu-maroon);" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading event details...</p>
            </div>
        `;
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        modal.show();
        
        // Fetch event details
        fetch('get_event_details.php?id=' + eventId)
            .then(response => response.text())
            .then(html => {
                modalContent.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading event details:', error);
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading event details. Please try again.
                    </div>
                `;
            });
    }

    // ============================================
    // Approve/Decline Event Functions
    // ============================================
    window.approveEvent = function(eventId, eventName, eventOrg, eventDate, eventTime) {

        
        // Populate event details in the modal
        const eventDetails = document.getElementById('approveEventDetails');
        if (eventDetails) {
            eventDetails.innerHTML = `
                <strong>${eventName}</strong><br>
                ${eventOrg}<br>
                ${eventDate} at ${eventTime}
            `;
        } else {
            console.error('approveEventDetails element not found');
        }
        
        const confirmBtn = document.getElementById('confirmApproveBtn');
        if (confirmBtn) {
            confirmBtn.onclick = function() {

                updateEventStatus(eventId, 'approved');
            };
        } else {
            console.error('confirmApproveBtn element not found');
        }
        
        const modalElement = document.getElementById('approveEventModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('approveEventModal element not found');
        }
    };

    window.declineEvent = function(eventId, eventName, eventOrg, eventDate, eventTime) {

        
        // Populate event details in the modal
        const eventDetails = document.getElementById('declineEventDetails');
        if (eventDetails) {
            eventDetails.innerHTML = `
                <strong>${eventName}</strong><br>
                ${eventOrg}<br>
                ${eventDate} at ${eventTime}
            `;
        } else {
            console.error('declineEventDetails element not found');
        }
        
        // Clear previous reason
        const reasonField = document.getElementById('declineReason');
        if (reasonField) {
            reasonField.value = '';
        } else {
            console.error('declineReason element not found');
        }
        
        const confirmBtn = document.getElementById('confirmDeclineBtn');
        if (confirmBtn) {
            confirmBtn.onclick = function() {

                const reason = document.getElementById('declineReason').value.trim();
                if (reason === '') {
                    alert('Please provide a reason for declining this request.');
                    return;
                }

                updateEventStatus(eventId, 'declined', reason);
            };
        } else {
            console.error('confirmDeclineBtn element not found');
        }
        
        const modalElement = document.getElementById('declineEventModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            console.error('declineEventModal element not found');
        }
    };

    function updateEventStatus(eventId, status, reason = '') {

        
        const formData = new FormData();
        formData.append('id', eventId);
        formData.append('status', status);
        if (reason) {
            formData.append('reason', reason);
        }
        

        
        fetch('update_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {

            return response.text();
        })
        .then(text => {

            try {
                const data = JSON.parse(text);

                
                if (data.success) {
                    // Close all modals
                    const modals = document.querySelectorAll('.modal');
                    modals.forEach(modal => {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) bsModal.hide();
                    });
                    
                    // Reload the page to show updated status
                    window.location.reload();
                } else {
                    alert('Error updating event status: ' + (data.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                console.error('Response text:', text);
                alert('Invalid response from server. Check console for details.');
            }
        })
        .catch(error => {
            console.error('Error updating event status:', error);
            alert('Error updating event status. Please try again.');
        });
    }

})();