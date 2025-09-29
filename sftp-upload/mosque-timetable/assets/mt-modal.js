/**
 * Mosque Timetable Export Modal
 * Version: 3.0.0
 */

/* eslint-env browser */

(function() {
    'use strict';

    // Global modal object
    window.MosqueTimetableModal = {
        // Configuration
        config: {
            restUrl: (typeof mosqueTimetableModal !== 'undefined' && mosqueTimetableModal.restUrl) || '',
            restNonce: (typeof mosqueTimetableModal !== 'undefined' && mosqueTimetableModal.restNonce) || '',
            currentMonth: (typeof mosqueTimetableModal !== 'undefined' && mosqueTimetableModal.currentMonth) || new Date().getMonth() + 1,
            currentYear: (typeof mosqueTimetableModal !== 'undefined' && mosqueTimetableModal.currentYear) || new Date().getFullYear(),
            siteUrl: (typeof mosqueTimetableModal !== 'undefined' && mosqueTimetableModal.siteUrl) || '',
            strings: (typeof mosqueTimetableModal !== 'undefined' && mosqueTimetableModal.strings) || {}
        },

        // Initialize modal functionality
        init: function(config) {
            this.config = Object.assign(this.config, config || {});
            this.initModal();
            this.initEventHandlers();
        },

        // Create and inject modal HTML
        initModal: function() {
            const modalHTML = `
                <div id="mt-export-modal" class="mt-modal-overlay">
                    <div class="mt-modal">
                        <div class="mt-modal-header">
                            <h3>📅 Export Prayer Calendar</h3>
                            <button type="button" class="mt-modal-close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <form id="mt-export-form">
                                <!-- Date Range Section -->
                                <div class="mt-form-section">
                                    <h4>📆 Date Range</h4>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="date_range" value="year" checked>
                                        <span>Full Year ${this.config.currentYear}</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="date_range" value="month">
                                        <span>Selected Month Only</span>
                                    </label>
                                </div>

                                <!-- Prayer Times Section -->
                                <div class="mt-form-section">
                                    <h4>🕌 Prayer Times</h4>
                                    <p class="mt-form-note">Start times are always included</p>
                                    <label class="mt-checkbox-option">
                                        <input type="checkbox" name="include_jamah" value="1" checked>
                                        <span>Include Jamāʿah (congregation) times</span>
                                    </label>
                                </div>

                                <!-- Notifications Section -->
                                <div class="mt-form-section">
                                    <h4>🔔 Notifications (VALARM)</h4>
                                    <p class="mt-form-note">Create calendar reminders before each prayer</p>
                                    <div class="mt-checkbox-grid">
                                        <label class="mt-checkbox-option">
                                            <input type="checkbox" name="alarms[]" value="0">
                                            <span>At prayer time</span>
                                        </label>
                                        <label class="mt-checkbox-option">
                                            <input type="checkbox" name="alarms[]" value="5">
                                            <span>5 minutes before</span>
                                        </label>
                                        <label class="mt-checkbox-option">
                                            <input type="checkbox" name="alarms[]" value="10">
                                            <span>10 minutes before</span>
                                        </label>
                                        <label class="mt-checkbox-option">
                                            <input type="checkbox" name="alarms[]" value="20">
                                            <span>20 minutes before</span>
                                        </label>
                                        <label class="mt-checkbox-option">
                                            <input type="checkbox" name="alarms[]" value="30">
                                            <span>30 minutes before</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Jummah Section -->
                                <div class="mt-form-section">
                                    <h4>🕌 Jummah (Friday) Prayers</h4>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="jummah" value="both" checked>
                                        <span>Both Jummah services</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="jummah" value="1st">
                                        <span>1st Jummah only</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="jummah" value="2nd">
                                        <span>2nd Jummah only</span>
                                    </label>
                                </div>

                                <!-- Sunrise Warning Section -->
                                <div class="mt-form-section">
                                    <h4>🌅 End of Fajr Warning</h4>
                                    <p class="mt-form-note">Optional reminder before sunrise (end of Fajr time)</p>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="sunrise_alarm" value="" checked>
                                        <span>No sunrise warning</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="sunrise_alarm" value="15">
                                        <span>15 minutes before sunrise</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="sunrise_alarm" value="30">
                                        <span>30 minutes before sunrise</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="sunrise_alarm" value="45">
                                        <span>45 minutes before sunrise</span>
                                    </label>
                                    <label class="mt-radio-option">
                                        <input type="radio" name="sunrise_alarm" value="60">
                                        <span>1 hour before sunrise</span>
                                    </label>
                                </div>
                            </form>
                        </div>
                        <div class="mt-modal-footer">
                            <button type="button" class="mt-btn mt-btn-secondary" id="mt-modal-cancel">Cancel</button>
                            <button type="button" class="mt-btn mt-btn-primary" id="mt-download-ics">📥 Download .ics</button>
                            <button type="button" class="mt-btn mt-btn-primary" id="mt-google-calendar">📅 Add to Google</button>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if present
            const existingModal = document.getElementById('mt-export-modal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        },

        // Initialize event handlers
        initEventHandlers: function() {
            const self = this;

            // Open modal when export button is clicked
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('mosque-export-btn')) {
                    e.preventDefault();
                    self.openModal();
                }
            });

            // Close modal events
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('mt-modal-close') ||
                    e.target.id === 'mt-modal-cancel' ||
                    e.target.classList.contains('mt-modal-overlay')) {
                    self.closeModal();
                }
            });

            // Download ICS
            document.addEventListener('click', function(e) {
                if (e.target.id === 'mt-download-ics') {
                    self.downloadICS();
                }
            });

            // Google Calendar
            document.addEventListener('click', function(e) {
                if (e.target.id === 'mt-google-calendar') {
                    self.addToGoogle();
                }
            });

            // Prevent modal close when clicking inside modal content
            document.addEventListener('click', function(e) {
                if (e.target.closest('.mt-modal') && !e.target.classList.contains('mt-modal-overlay')) {
                    e.stopPropagation();
                }
            });

            // ESC key to close modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeModal();
                }
            });
        },

        // Open modal
        openModal: function() {
            const modal = document.getElementById('mt-export-modal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                // Focus management for accessibility
                const firstInput = modal.querySelector('input[type="radio"]:checked');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        },

        // Close modal
        closeModal: function() {
            const modal = document.getElementById('mt-export-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        },

        // Collect form data
        getFormData: function() {
            const form = document.getElementById('mt-export-form');
            const formData = new FormData(form);
            const data = {};

            // Convert FormData to object
            for (const [key, value] of formData.entries()) {
                if (key.endsWith('[]')) {
                    const arrayKey = key.slice(0, -2);
                    if (!data[arrayKey]) {
                        data[arrayKey] = [];
                    }
                    data[arrayKey].push(value);
                } else {
                    data[key] = value;
                }
            }

            // Add current month/year context
            data.year = this.config.currentYear;
            data.month = this.config.currentMonth;

            return data;
        },

        // Download ICS file
        downloadICS: function() {
            const formData = this.getFormData();

            // Build query string
            const params = new URLSearchParams();
            for (const [key, value] of Object.entries(formData)) {
                if (Array.isArray(value)) {
                    value.forEach(v => params.append(key + '[]', v));
                } else {
                    params.set(key, value);
                }
            }

            // Create download URL
            const downloadUrl = `${this.config.restUrl}/export-ics?${params.toString()}`;

            // Trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `prayer-times-${formData.year}-${formData.month || 'full'}.ics`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            this.closeModal();
        },

        // Add to Google Calendar
        addToGoogle: function() {
            // Google Calendar doesn't support bulk import via URL
            // Show instructions instead
            alert('To add to Google Calendar:\\n\\n1. Click "Download .ics" to save the file\\n2. Open Google Calendar\\n3. Click the "+" next to "Other calendars"\\n4. Select "Import"\\n5. Choose the downloaded .ics file');
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Will be initialized by the main script with proper config
        });
    }

})();