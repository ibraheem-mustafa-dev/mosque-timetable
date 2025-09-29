/**
 * Mosque Timetable Admin JavaScript
 * Version: 3.0.0
 */

/* eslint-env browser, jquery */

(function($) {
    'use strict';

    // Global admin object
    window.MosqueTimetableAdmin = {
        // Configuration
        config: {
            ajaxUrl: (typeof mosqueTimetableAdmin !== 'undefined' && mosqueTimetableAdmin.ajaxUrl) || '/wp-admin/admin-ajax.php',
            nonce: (typeof mosqueTimetableAdmin !== 'undefined' && mosqueTimetableAdmin.nonce) || '',
            strings: (typeof mosqueTimetableAdmin !== 'undefined' && mosqueTimetableAdmin.strings) || {},  // ← use localized strings
            currentMonth: 1,
            currentYear: new Date().getFullYear(),
            unsavedChanges: false,
            autoSaveTimer: null
        },

        // Translation helper function
        t: function(key, fallback) {
            const dict = this.config && this.config.strings ? this.config.strings : {};
            return (dict[key] && String(dict[key])) || fallback || key;
        },

        // Initialize admin functionality
        init: function() {
            this.initMonthTabs();
            this.initTableEvents();
            this.initAutoSave();
            this.initUnsavedChangesWarning();
            this.initFormValidation();
            this.initImportModal();
            this.initGenerateDatesButtons();
            this.initSaveButtons();
            this.initDataManagementButtons();
            this.initHijriRecalculate();
            this.initYearArchiveBrowser();
            this.initPdfUpload();
        },
        

        // Initialize month tab functionality
        initMonthTabs: function () {
            const self = this;
            // Clean previous
            jQuery(document).off('click.mt-tabs');

            // Legacy tabs (.nav-tab / #month-<n>)
            if (jQuery('.nav-tab').length) {
                jQuery(document).on('click.mt-tabs', '.nav-tab', function (e) {
                    e.preventDefault();
                    const monthNumber = parseInt(jQuery(this).data('month'), 10) || 1;
                    if (self.config.unsavedChanges && !confirm(self.config.strings.unsavedChanges)) return;

                    jQuery('.nav-tab').removeClass('nav-tab-active');
                    jQuery(this).addClass('nav-tab-active');
                    jQuery('.tab-content').removeClass('active');
                    jQuery('#month-' + monthNumber).addClass('active');

                    self.config.currentMonth = monthNumber;
                    self.hideUnsavedChangesWarning();
                    if (self.loadMonthData) self.loadMonthData(monthNumber);
                });
                // don't force switchToMonth(1) here; legacy already shows January
                return;
            }

            // Modern tabs (.mosque-month-tab / #month-panel-<n>)
            if (jQuery('.mosque-month-tab').length) {
                jQuery(document).on('click.mt-tabs', '.mosque-month-tab', function () {
                    const monthNumber = parseInt(jQuery(this).data('month'), 10) || 1;
                    if (self.config.unsavedChanges && !confirm(self.config.strings.unsavedChanges)) return;
                    self.switchToMonth(monthNumber);
                });
                self.switchToMonth(1);
            }
        },

        switchToMonth: function (monthNumber) {
            // Modern
            if (jQuery('.mosque-month-tab').length) {
                jQuery('.mosque-month-tab').removeClass('active');
                jQuery(`.mosque-month-tab[data-month="${monthNumber}"]`).addClass('active');
                jQuery('.mosque-month-panel').removeClass('active');
                jQuery('#month-panel-' + monthNumber).addClass('active');
            } else {
                // Legacy
                jQuery('.nav-tab').removeClass('nav-tab-active');
                jQuery(`.nav-tab[data-month="${monthNumber}"]`).addClass('nav-tab-active');
                jQuery('.tab-content').removeClass('active');
                jQuery('#month-' + monthNumber).addClass('active');
            }

            this.config.currentMonth = monthNumber;
            this.config.unsavedChanges = false;
            this.hideUnsavedChangesWarning();
            if (this.loadMonthData) this.loadMonthData(monthNumber);
            if (this.updateMonthIndicators) this.updateMonthIndicators();
        },

        // Load month data via AJAX
        loadMonthData: function(monthNumber) {
            const panel = $(`#month-panel-${monthNumber}`);
            const tableContainer = panel.find('.mosque-admin-table-wrapper');
            
            this.showLoading(tableContainer);
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'get_month_timetable',
                    nonce: this.config.nonce,
                    month: monthNumber,
                    year: this.config.currentYear
                },
                success: function(response) {
                    if (response.success) {
                        MosqueTimetableAdmin.renderMonthTable(monthNumber, response.data);
                    } else {
                        MosqueTimetableAdmin.showError(response.data || MosqueTimetableAdmin.t('loadError', 'Failed to load month data'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error loading month data:', xhr, status, error);
                    console.error('Response Text:', xhr.responseText);
                    if (xhr.status === 0) {
                        MosqueTimetableAdmin.showError(MosqueTimetableAdmin.t('networkError', 'Network error: Could not connect to server'));
                    } else if (xhr.status === 403) {
                        MosqueTimetableAdmin.showError(MosqueTimetableAdmin.t('permissionError', 'Permission denied: Please refresh the page'));
                    } else if (xhr.status === 500) {
                        MosqueTimetableAdmin.showError(MosqueTimetableAdmin.t('serverError', 'Server error: Please try again later'));
                    } else {
                        MosqueTimetableAdmin.showError(MosqueTimetableAdmin.t('connectionError', 'Error connecting to server: ') + error);
                    }
                }
            });
        },

        // Render month table
        renderMonthTable: function(monthNumber, data) {
            const panel = $(`#month-panel-${monthNumber}`);
            const tableContainer = panel.find('.mosque-admin-table-wrapper');
            
            let html = `
                <table class="mosque-admin-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Date</th>
                            <th>Hijri Date</th>
                            <th>Fajr Start</th>
                            <th>Fajr Jamaat</th>
                            <th>Sunrise</th>
                            <th>Zuhr Start</th>
                            <th>Zuhr Jamaat</th>
                            <th>Asr Start</th>
                            <th>Asr Jamaat</th>
                            <th>Maghrib Start</th>
                            <th>Maghrib Jamaat</th>
                            <th>Isha Start</th>
                            <th>Isha Jamaat</th>
                            <th>Jummah 1</th>
                            <th>Jummah 2</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // Generate days for the month
            const daysInMonth = this.getDaysInMonth(monthNumber, this.config.currentYear);
            
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = this.formatDateForInput(this.config.currentYear, monthNumber, day);
                const dayData = data && data.days ? data.days.find(d => d.day_number == day) : null;
                const isFriday = this.isFriday(this.config.currentYear, monthNumber, day);
                
                html += `<tr class="${isFriday ? 'friday-row' : ''}" data-day="${day}">`;
                
                // Day number
                html += `<td><span class="day-number">${day}</span></td>`;
                
                // Date input
                html += `
                    <td>
                        <input type="date" 
                               name="date_full[${day}]" 
                               value="${dayData ? dayData.date_full : dateStr}"
                               class="date-input"
                               data-day="${day}">
                    </td>
                `;
                
                // Hijri date (auto-calculated, readonly)
                html += `
                    <td>
                        <input type="text" 
                               name="hijri_date[${day}]" 
                               value="${dayData ? dayData.hijri_date : ''}"
                               class="hijri-date"
                               readonly>
                    </td>
                `;
                
                // Prayer time inputs
                const prayers = [
                    'fajr_start', 'fajr_jamaat', 'sunrise',
                    'zuhr_start', 'zuhr_jamaat', 'asr_start', 'asr_jamaat',
                    'maghrib_start', 'maghrib_jamaat', 'isha_start', 'isha_jamaat',
                    'jummah_1', 'jummah_2'
                ];
                
                prayers.forEach(function(prayer) {
                    const inputType = prayer === 'sunrise' ? 'time' : 'time';
                    const value = dayData ? dayData[prayer] : '';
                    const required = !prayer.startsWith('jummah');
                    
                    html += `
                        <td>
                            <input type="${inputType}" 
                                   name="${prayer}[${day}]" 
                                   value="${value}"
                                   class="prayer-time-input"
                                   ${required ? 'required' : ''}
                                   data-prayer="${prayer}"
                                   data-day="${day}">
                        </td>
                    `;
                });
                
                html += '</tr>';
            }

            html += `
                    </tbody>
                </table>
            `;

            tableContainer.html(html);
            
            // Initialize events for the new table
            this.initTableEvents();
        },

        // Initialize table input events
        initTableEvents: function() {
            const self = this;
            
            // Date change events
            $(document).off('change', '.date-input').on('change', '.date-input', function() {
                const input = $(this);
                const day = input.data('day');
                const dateValue = input.val();
                
                if (dateValue) {
                    // Calculate and update Hijri date
                    self.calculateHijriDate(day, dateValue);
                    self.markAsUnsaved();
                }
            });
            
            // Prayer time change events
            $(document).off('change', '.prayer-time-input').on('change', '.prayer-time-input', function() {
                self.markAsUnsaved();
                self.validatePrayerTime($(this));
            });
            
            // Auto-save trigger
            $(document).off('input', '.mosque-admin-table input').on('input', '.mosque-admin-table input', function() {
                self.triggerAutoSave();
            });

            // Paste a row of times into the first time input of the row
            $(document).off('paste.rowfill')
                .on('paste.rowfill', '.mosque-admin-table tbody tr .prayer-time-input:first', function (e) {
                    const txt = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                    if (!txt) return;

                    // split by tab/comma/semicolon
                    const parts = txt.trim().split(/\t|,|;/).map(s => s.trim());
                    if (parts.length < 12) return;

                    const row = $(this).closest('tr');
                    const inputs = row.find('.prayer-time-input');
                    inputs.each(function (i) {
                        if (parts[i]) $(this).val(parts[i]).trigger('change');
                    });

                    e.preventDefault();
                });

        },

        // Calculate Hijri date
        calculateHijriDate: function(day, gregorianDate) {
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'calculate_hijri_date',
                    nonce: this.config.nonce,
                    date: gregorianDate
                },
                success: function(response) {
                    if (response.success) {
                        $(`.hijri-date[name="hijri_date[${day}]"]`).val(response.data);
                    }
                }
            });
        },

        // Validate prayer time input
        validatePrayerTime: function(input) {
            const value = input.val();
            const prayer = input.data('prayer');

            if (value && !this.isValidTime(value)) {
                input.addClass('error');
                this.showError(this.t('invalidTime', 'Invalid time format. Please use HH:MM format.'));
                return false;
            } else {
                input.removeClass('error');
            }

            // Prayer-specific validation rules
            if (value && prayer) {
                const row = input.closest('tr');
                const sunrise = row.find('[data-prayer="sunrise"]').val();
                const maghrib = row.find('[data-prayer="maghrib"]').val();

                switch(prayer) {
                    case 'fajr':
                    case 'fajr_jamaat':
                        // Fajr should be before sunrise
                        if (sunrise && this.compareTime(value, sunrise) >= 0) {
                            input.addClass('error');
                            this.showError('Fajr time must be before sunrise');
                            return false;
                        }
                        break;

                    case 'maghrib':
                    case 'maghrib_jamaat':
                        // Maghrib is sunset, should be reasonable time
                        if (!this.isReasonableTime(value, 'maghrib')) {
                            input.addClass('warning');
                            this.showWarning('Maghrib time seems unusual for this location');
                        }
                        break;

                    case 'isha':
                    case 'isha_jamaat':
                        // Isha should be after Maghrib
                        if (maghrib && this.compareTime(value, maghrib) <= 0) {
                            input.addClass('error');
                            this.showError('Isha time must be after Maghrib');
                            return false;
                        }
                        break;
                }
            }

            // Check prayer time sequence logic
            this.validatePrayerSequence(input);

            return true;
        },

        // Validate prayer time sequence
        validatePrayerSequence: function(input) {
            const day = input.data('day') || input.closest('tr').find('.day-number').text();
            const row = input.closest('tr');

            // Get all prayer times for this day
            const times = {};
            row.find('.prayer-time-input').each(function() {
                const prayer = $(this).data('prayer');
                const value = $(this).val();
                if (value) {
                    times[prayer] = value;
                }
            });

            // Enhanced sequence validation with day-specific error messages
            const sequence = ['fajr_start', 'sunrise', 'zuhr_start', 'asr_start', 'maghrib_start', 'isha_start'];
            let hasErrors = false;

            for (let i = 1; i < sequence.length; i++) {
                const prev = times[sequence[i-1]];
                const curr = times[sequence[i]];

                if (prev && curr && this.compareTime(prev, curr) >= 0) {
                    const currentInput = row.find(`input[data-prayer="${sequence[i]}"]`);
                    currentInput.addClass('sequence-error');

                    // Show day-specific error message
                    this.showError(`Day ${day}: ${sequence[i-1]} must be before ${sequence[i]}`);
                    hasErrors = true;
                } else {
                    row.find(`input[data-prayer="${sequence[i]}"]`).removeClass('sequence-error');
                }
            }

            // Additional validation for jamaat times
            const jamaatPairs = [
                ['fajr_start', 'fajr_jamaat'],
                ['zuhr_start', 'zuhr_jamaat'],
                ['asr_start', 'asr_jamaat'],
                ['maghrib_start', 'maghrib_jamaat'],
                ['isha_start', 'isha_jamaat']
            ];

            jamaatPairs.forEach(([start, jamaat]) => {
                const startTime = times[start];
                const jamaatTime = times[jamaat];

                if (startTime && jamaatTime && this.compareTime(jamaatTime, startTime) < 0) {
                    const jamaatInput = row.find(`input[data-prayer="${jamaat}"]`);
                    jamaatInput.addClass('sequence-error');
                    this.showError(`Day ${day}: ${jamaat} cannot be before ${start}`);
                    hasErrors = true;
                }
            });

            return !hasErrors;
        },

        // Initialize auto-save functionality
        initAutoSave: function() {
            const self = this;
            
            // Manual save button
            $(document).on('click', '.mosque-save-month-btn', function() {
                const monthNumber = self.config.currentMonth;
                self.saveMonth(monthNumber);
            });
            
            // Auto-save save now button
            $(document).on('click', '.mosque-save-now-btn', function() {
                const monthNumber = self.config.currentMonth;
                self.saveMonth(monthNumber);
            });
        },

        // Trigger auto-save
        triggerAutoSave: function() {
            const self = this;
            
            if (this.config.autoSaveTimer) {
                clearTimeout(this.config.autoSaveTimer);
            }
            
            this.config.autoSaveTimer = setTimeout(function() {
                if (self.config.unsavedChanges) {
                    self.saveMonth(self.config.currentMonth, true);
                }
            }, 30000); // Auto-save after 30 seconds of inactivity
        },

        // Save month data
        saveMonth: function(monthNumber, isAutoSave = false) {
            const panel = $(`#month-panel-${monthNumber}`);
            const formData = this.collectMonthData(panel);
            
            if (!isAutoSave) {
                $('.mosque-save-month-btn').addClass('mosque-btn-loading');
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'save_month_timetable',
                    nonce: this.config.nonce,
                    month: monthNumber,
                    year: this.config.currentYear,
                    data: formData
                },
                success: function(response) {
                    $('.mosque-save-month-btn').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        if (!isAutoSave) {
                            MosqueTimetableAdmin.showSuccess(MosqueTimetableAdmin.config.strings.saveSuccess);
                        }
                        MosqueTimetableAdmin.config.unsavedChanges = false;
                        MosqueTimetableAdmin.hideUnsavedChangesWarning();
                        MosqueTimetableAdmin.updateMonthIndicators();
                    } else {
                        MosqueTimetableAdmin.showError(response.data || MosqueTimetableAdmin.config.strings.saveError);
                    }
                },
                error: function(xhr, status, error) {
                    $('.mosque-save-month-btn').removeClass('mosque-btn-loading');
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.includes('Security check failed')) {
                        MosqueTimetableAdmin.showError('Security check failed. Please refresh the page and try again.');
                        // Try to refresh nonce
                        MosqueTimetableAdmin.refreshNonce();
                    } else {
                        MosqueTimetableAdmin.showError('Error saving data: ' + (error || 'Unknown error'));
                    }
                }
            });
        },

        // Collect month data from form
        collectMonthData: function(panel) {
            const data = { days: [] };
            
            panel.find('.mosque-admin-table tbody tr').each(function() {
                const row = $(this);
                const day = row.data('day');
                const dayData = { day_number: day };
                
                row.find('input').each(function() {
                    const input = $(this);
                    const name = input.attr('name');
                    const value = input.val();
                    
                    if (name && value) {
                        const fieldName = name.replace(/\[\d+\]/, '');
                        dayData[fieldName] = value;
                    }
                });
                
                if (Object.keys(dayData).length > 1) { // More than just day_number
                    data.days.push(dayData);
                }
            });
            
            return data;
        },

        // Initialize unsaved changes warning
        initUnsavedChangesWarning: function() {
            const self = this;
            
            // Show warning when leaving page with unsaved changes
            $(window).on('beforeunload', function(e) {
                if (self.config.unsavedChanges) {
                    const message = self.config.strings.unsavedChanges;
                    e.originalEvent.returnValue = message;
                    return message;
                }
            });
        },

        // Mark as unsaved
        markAsUnsaved: function() {
            this.config.unsavedChanges = true;
            this.showUnsavedChangesWarning();
        },

        // Show unsaved changes warning
        showUnsavedChangesWarning: function() {
            if ($('.mosque-unsaved-changes').length === 0) {
                const warningHtml = `
                    <div class="mosque-unsaved-changes">
                        <div class="mosque-unsaved-text">You have unsaved changes</div>
                        <button class="mosque-save-now-btn">Save Now</button>
                    </div>
                `;
                $('body').append(warningHtml);
            }
            $('.mosque-unsaved-changes').addClass('show');
        },

        // Hide unsaved changes warning
        hideUnsavedChangesWarning: function() {
            $('.mosque-unsaved-changes').removeClass('show');
        },

        // Initialize form validation
        initFormValidation: function() {
            $(document).on('submit', '.mosque-admin-form', function(e) {
                const isValid = MosqueTimetableAdmin.validateForm($(this));
                if (!isValid) {
                    e.preventDefault();
                }
            });
        },

        // Validate form
        validateForm: function(form) {
            let isValid = true;
            
            form.find('input[required]').each(function() {
                const input = $(this);
                if (!input.val()) {
                    input.addClass('error');
                    isValid = false;
                } else {
                    input.removeClass('error');
                }
            });
            
            return isValid;
        },

        // Update month indicators
        updateMonthIndicators: function() {
            // This would check if each month has data and add indicators
            $('.mosque-month-tab').each(function() {
                // const _month = $(this).data('month'); // Unused
                // Add logic to check if month has data
                // $(this).addClass('has-data');
            });
        },

        initImportModal: function () {
            const $ = jQuery;
            const ajaxurl = this.config.ajaxUrl || (window.ajaxurl || '/wp-admin/admin-ajax.php');
            const nonce = this.config.nonce || (window.mosqueTimetableAdmin && window.mosqueTimetableAdmin.nonce) || '';

            $(document).off('click.mt-import-open')
                .on('click.mt-import-open', '#csv-import-btn,#xlsx-import-btn,#paste-import-btn', function () {
                    const method = this.id.includes('csv') ? 'csv' : (this.id.includes('xlsx') ? 'xlsx' : 'paste');
                    $('#import-modal').addClass('show').fadeIn();
                    $('.import-tab-btn').removeClass('active');
                    $(`.import-tab-btn[data-method="${method}"]`).addClass('active');
                    $('.import-method').removeClass('active');
                    $(`#${method}-import`).addClass('active');
                });

            $(document).off('click.mt-import-close')
                .on('click.mt-import-close', '.mosque-modal-close,#cancel-import', () => {
                    $('#import-modal').removeClass('show').fadeOut();
                });

            $(document).off('click.mt-import-tab')
                .on('click.mt-import-tab', '.import-tab-btn', function () {
                    const method = jQuery(this).data('method');
                    $('.import-tab-btn').removeClass('active');
                    jQuery(this).addClass('active');
                    $('.import-method').removeClass('active');
                    jQuery('#' + method + '-import').addClass('active');
                });

            // Helper to finish and refresh the modern table if available
            const finish = (ok, msg) => {
                if (ok) {
                    jQuery('#import-modal').removeClass('show').fadeOut();
                    const successMsg = msg || this.t('importSuccess', 'Import completed successfully!');
                    if (window.MosqueTimetableAdmin && MosqueTimetableAdmin.loadMonthData) {
                        MosqueTimetableAdmin.showSuccess(successMsg);
                        MosqueTimetableAdmin.loadMonthData(MosqueTimetableAdmin.config.currentMonth);
                        if (MosqueTimetableAdmin.updateMonthIndicators) {
                            MosqueTimetableAdmin.updateMonthIndicators();
                        }
                    } else {
                        alert(successMsg);
                        location.reload();
                    }
                } else {
                    MosqueTimetableAdmin.showError(msg || this.t('importError', 'Error importing file. Please check format and try again.'));
                }
            };

            // Execute import when clicking the footer button
            $(document).off('click.mt-import-go')
                .on('click.mt-import-go', '#execute-import', () => {
                    const month = jQuery('#import-month').val();
                    const method = jQuery('.import-tab-btn.active').data('method') || 'csv';

                    if (!month) {
                        finish(false, this.t('noMonth', 'Please select a month.'));
                        return;
                    }

                    if (method === 'csv') {
                        const f = document.getElementById('csv-file');
                        if (!f || !f.files || !f.files[0]) {
                            finish(false, this.t('noFile', 'Please select a file before importing.'));
                            return;
                        }
                        const fd = new FormData();
                        fd.append('action', 'import_csv_timetable');
                        fd.append('nonce', nonce);
                        fd.append('month', month);
                        fd.append('csv_file', f.files[0]);

                        jQuery.ajax({ url: ajaxurl, method: 'POST', data: fd, processData: false, contentType: false })
                            .done(resp => finish(resp && resp.success, (resp && resp.data) || this.t('importSuccess', 'CSV imported successfully.')))
                            .fail(xhr => finish(false, this.t('importError', 'CSV import error: ') + (xhr.statusText || xhr.status)));

                    } else if (method === 'xlsx') {
                        const xf = document.getElementById('xlsx-file');
                        if (!xf || !xf.files || !xf.files[0]) {
                            finish(false, this.t('noFile', 'Please select a file before importing.'));
                            return;
                        }
                        const xfd = new FormData();
                        xfd.append('action', 'import_xlsx_timetable');
                        xfd.append('nonce', nonce);
                        xfd.append('month', month);
                        xfd.append('xlsx_file', xf.files[0]);

                        jQuery.ajax({ url: ajaxurl, method: 'POST', data: xfd, processData: false, contentType: false })
                            .done(resp => finish(resp && resp.success, (resp && resp.data) || this.t('importSuccess', 'Excel file imported successfully.')))
                            .fail(xhr => finish(false, this.t('importError', 'XLSX import error: ') + (xhr.statusText || xhr.status)));

                    } else { // paste
                        const text = (jQuery('#paste-data').val() || '').trim();
                        if (!text) {
                            finish(false, this.t('noPaste', 'Please paste your timetable data before importing.'));
                            return;
                        }
                        jQuery.post(ajaxurl, {
                            action: 'import_paste_data',
                            nonce: nonce,
                            month: month,
                            paste_data: text
                        })
                        .done(resp => finish(resp && resp.success, (resp && resp.data) || this.t('importSuccess', 'Pasted data imported successfully.')))
                        .fail(xhr => finish(false, this.t('importError', 'Paste import error: ') + (xhr.statusText || xhr.status)));
                    }
                });
        },


        initGenerateDatesButtons: function () {
            const self = this;

            jQuery(document).off('click.mt-gen-all')
                .on('click.mt-gen-all', '#generate-all-dates', function () {
                    if (confirm('Generate date structure for all 12 months? This will create day numbers, dates, and Hijri dates.')) {
                        self.generateAllDates();
                    }
                });

            // Support both .generate-month-dates (modern) and .populate-month-btn (legacy)
            jQuery(document).off('click.mt-gen-month')
                .on('click.mt-gen-month', '.generate-month-dates,.populate-month-btn', function () {
                    const month = parseInt(jQuery(this).data('month'), 10);
                    self.generateMonthDates(month);
                });
        },

        // Initialize save buttons
        initSaveButtons: function() {
            const self = this;

            // Save All Months button
            $(document).on('click', '#save-all-months', function() {
                console.log('Save All Months clicked');
                if (confirm('Save all months? This will save all prayer time data.')) {
                    console.log('User confirmed, calling saveAllMonths');
                    self.saveAllMonths();
                }
            });

            // Save individual month (existing functionality enhanced)
            $(document).on('click', '.save-month-btn', function() {
                const month = $(this).data('month');
                self.saveMonth(month || self.config.currentMonth);
            });
        },

        // Initialize data management buttons
        initDataManagementButtons: function() {
            const self = this;

            // Export CSV button
            $(document).on('click', '#export-csv-btn', function() {
                self.exportCSV();
            });

            // Clear all data button (using inline onclick for confirmation)
            window.clearAllData = function() {
                self.clearAllData();
            };

            // Reset structure button (using inline onclick for confirmation)
            window.resetToEmptyStructure = function() {
                self.resetToEmptyStructure();
            };

            // Regenerate dates button (using inline onclick for confirmation)
            window.regenerateAllDates = function() {
                self.regenerateAllDates();
            };
        },

        // Process pasted CSV data
        processPastedData: function(month, data) {
            const self = this;
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'import_paste_data',
                    nonce: this.config.nonce,
                    month: month,
                    paste_data: data
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess('Data imported successfully!');
                        self.loadMonthData(month);
                        self.updateMonthIndicators();
                    } else {
                        self.showError(response.data || 'Import failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Paste import error:', xhr, status, error);
                    self.showError('Error processing data: ' + error);
                }
            });
        },

        // Generate all dates
        generateAllDates: function() {
            const self = this;
            
            console.log('generateAllDates called');
            console.log('AJAX URL:', this.config.ajaxUrl);
            console.log('Nonce:', this.config.nonce);
            
            $('#generate-all-dates').addClass('mosque-btn-loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'generate_all_dates',
                    nonce: this.config.nonce,
                    year: this.config.currentYear
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    $('#generate-all-dates').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess(self.t('generateSuccess', 'All dates generated successfully! Hijri dates calculated automatically.'));
                        // Reload current month to show updated data
                        self.loadMonthData(self.config.currentMonth);
                        self.updateMonthIndicators();
                    } else {
                        console.log('Response not successful:', response);
                        self.showError(response.data || self.t('generateError', 'Failed to generate dates'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr, status, error);
                    console.log('Response Text:', xhr.responseText);
                    $('#generate-all-dates').removeClass('mosque-btn-loading');
                    self.showError('Error connecting to server: ' + error);
                }
            });
        },

        generateMonthDates: function (month) {
            const self = this;
            const $btn = jQuery(`.generate-month-dates[data-month="${month}"], .populate-month-btn[data-month="${month}"]`);
            $btn.addClass('mosque-btn-loading');

            // Try modern action first
            jQuery.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: { action: 'generate_month_dates', nonce: this.config.nonce, month, year: this.config.currentYear }
            }).done(function (resp) {
                if (resp && resp.success) {
                    self.showSuccess('Month dates generated successfully.');
                    if (parseInt(month) === self.config.currentMonth) self.loadMonthData(month);
                } else {
                    // Fallback to legacy action name
                    jQuery.post(self.config.ajaxUrl, {
                        action: 'populate_month_dates',
                        nonce: self.config.nonce,
                        month: month,
                        year: self.config.currentYear
                    }).done(function (r2) {
                        if (r2 && r2.success) {
                            self.showSuccess('Month dates generated successfully.');
                            if (parseInt(month) === self.config.currentMonth) self.loadMonthData(month);
                        } else {
                            self.showError((r2 && r2.data) || 'Failed to generate dates');
                        }
                    }).fail(function () {
                        self.showError('Failed to generate dates');
                    });
                }
            }).fail(function () {
                self.showError('Server error: Please try again later');
            }).always(function () {
                $btn.removeClass('mosque-btn-loading');
            });
        },

        // Save all months
        saveAllMonths: function() {
            const self = this;
            
            $('#save-all-months').addClass('mosque-btn-loading');
            
            // Collect data from all month panels
            const allMonthsData = {};
            
            for (let month = 1; month <= 12; month++) {
                const panel = $(`#month-panel-${month}`);
                if (panel.length) {
                    allMonthsData[month] = this.collectMonthData(panel);
                }
            }
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'save_all_months',
                    nonce: this.config.nonce,
                    year: this.config.currentYear,
                    data: allMonthsData
                },
                success: function(response) {
                    $('#save-all-months').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess(self.t('saveSuccess', 'All months saved successfully!'));
                        self.config.unsavedChanges = false;
                        self.hideUnsavedChangesWarning();
                        self.updateMonthIndicators();
                    } else {
                        self.showError(response.data || self.t('saveError', 'Failed to save all months'));
                    }
                },
                error: function() {
                    $('#save-all-months').removeClass('mosque-btn-loading');
                    self.showError(self.t('saveError', 'Error saving data'));
                }
            });
        },

        // Export CSV
        exportCSV: function() {
            window.location.href = this.config.ajaxUrl + '?action=export_csv_calendar&nonce=' + this.config.nonce;
            this.showSuccess('CSV export started...');
        },

        // Clear all data
        clearAllData: function() {
            const self = this;
            
            $('#clear-all-data-btn').addClass('mosque-btn-loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'clear_all_data',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    $('#clear-all-data-btn').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess('All data cleared successfully!');
                        self.loadMonthData(self.config.currentMonth);
                        self.updateMonthIndicators();
                    } else {
                        self.showError(response.data || 'Failed to clear data');
                    }
                },
                error: function() {
                    $('#clear-all-data-btn').removeClass('mosque-btn-loading');
                    self.showError('Error clearing data');
                }
            });
        },

        // Reset to empty structure
        resetToEmptyStructure: function() {
            const self = this;
            
            $('#reset-structure-btn').addClass('mosque-btn-loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'reset_empty_structure',
                    nonce: this.config.nonce,
                    year: this.config.currentYear
                },
                success: function(response) {
                    $('#reset-structure-btn').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess('Structure reset successfully! Dates preserved, prayer times cleared.');
                        self.loadMonthData(self.config.currentMonth);
                        self.updateMonthIndicators();
                    } else {
                        self.showError(response.data || 'Failed to reset structure');
                    }
                },
                error: function() {
                    $('#reset-structure-btn').removeClass('mosque-btn-loading');
                    self.showError('Error resetting structure');
                }
            });
        },

        // Regenerate all dates
        regenerateAllDates: function() {
            const self = this;
            
            $('#regenerate-dates-btn').addClass('mosque-btn-loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'regenerate_all_dates',
                    nonce: this.config.nonce,
                    year: this.config.currentYear
                },
                success: function(response) {
                    $('#regenerate-dates-btn').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess('All dates regenerated successfully! Hijri dates updated.');
                        self.loadMonthData(self.config.currentMonth);
                        self.updateMonthIndicators();
                    } else {
                        self.showError(response.data || 'Failed to regenerate dates');
                    }
                },
                error: function() {
                    $('#regenerate-dates-btn').removeClass('mosque-btn-loading');
                    self.showError('Error regenerating dates');
                }
            });
        },

        // Close any modal (generic)
        closeModal: function() {
            $('.mosque-modal-overlay').fadeOut(function() {
                $(this).remove();
            });
        },

        // Initialize Hijri recalculate functionality
        initHijriRecalculate: function() {
            const self = this;

            // Recalculate Hijri dates button
            $(document).on('click', '.recalc-hijri-btn', function() {
                const button = $(this);
                const month = button.data('month');
                const adjustmentInput = $(`#hijri-adj-${month}`);
                const adjustment = adjustmentInput.val() || 0;
                
                if (confirm(`Recalculate all Hijri dates for this month with ${adjustment} day adjustment?`)) {
                    self.recalculateHijriDates(month, adjustment, button);
                }
            });
        },

        // Recalculate Hijri dates for a specific month
        recalculateHijriDates: function(month, adjustment, button) {
            const self = this;
            
            button.addClass('mosque-btn-loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'recalculate_hijri_dates',
                    nonce: this.config.nonce,
                    month: month,
                    adjustment: adjustment,
                    year: this.config.currentYear
                },
                success: function(response) {
                    button.removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess(`Hijri dates recalculated with ${adjustment} day adjustment!`);
                        // Reload the month if it's currently active
                        if (parseInt(month) === self.config.currentMonth) {
                            self.loadMonthData(month);
                        }
                    } else {
                        self.showError(response.data || 'Failed to recalculate Hijri dates');
                    }
                },
                error: function() {
                    button.removeClass('mosque-btn-loading');
                    self.showError('Error recalculating Hijri dates');
                }
            });
        },

        // Utility functions
        getDaysInMonth: function(month, year) {
            return new Date(year, month, 0).getDate();
        },

        isFriday: function(year, month, day) {
            const date = new Date(year, month - 1, day);
            return date.getDay() === 5;
        },

        formatDateForInput: function(year, month, day) {
            return year + '-' + 
                   String(month).padStart(2, '0') + '-' + 
                   String(day).padStart(2, '0');
        },

        isValidTime: function(timeString) {
            const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
            return timeRegex.test(timeString);
        },

        // Refresh nonce if needed  
        refreshNonce: function() {
            const self = this;
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'refresh_admin_nonce'
                },
                success: function(response) {
                    if (response.success && response.data.nonce) {
                        self.config.nonce = response.data.nonce;
                    }
                }
            });
        },

        // UI helper functions
        showLoading: function(container) {
            container.html('<div class="mosque-loading"><div class="mosque-spinner"></div>Loading...</div>');
        },

        showError: function(message) {
            this.showMessage(message, 'error');
        },

        showSuccess: function(message) {
            this.showMessage(message, 'success');
        },

        showMessage: function(message, type) {
            const messageHtml = `
                <div class="mosque-message mosque-message-${type}">
                    <div class="mosque-message-icon">${type === 'error' ? '⚠️' : '✅'}</div>
                    <div>${message}</div>
                    <button class="mosque-message-close">&times;</button>
                </div>
            `;
            
            // Remove existing messages
            $('.mosque-message').remove();
            
            // Add new message - try multiple containers
            if ($('.mosque-admin-container').length > 0) {
                $('.mosque-admin-container').prepend(messageHtml);
            } else if ($('.wrap').length > 0) {
                $('.wrap').prepend(messageHtml);
            } else {
                $('body').prepend(messageHtml);
            }
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.mosque-message').fadeOut();
            }, 5000);
            
            // Manual close
            $('.mosque-message-close').on('click', function() {
                $(this).closest('.mosque-message').fadeOut();
            });
        },

        initYearArchiveBrowser: function () {
            const self = this;

            // Support multiple id variants seen across templates
            jQuery(document).off('click.mt-year-load')
                .on('click.mt-year-load', '#load-year,#load-year-data', function () {
                    console.log('🔘 Load Year button clicked');
                    const yr = parseInt(jQuery('#year-selector').val() || self.config.currentYear, 10);
                    console.log('📅 Selected year:', yr);
                    if (yr) {
                        self.loadYearData(yr);
                    } else {
                        console.error('❌ No valid year selected');
                    }
                });

            jQuery(document).off('click.mt-year-new')
                .on('click.mt-year-new', '#new-year-btn,#create-new-year', function () {
                    const yr = parseInt(jQuery('#year-selector').val() || self.config.currentYear, 10);
                    if (yr && confirm(`Create data structure for year ${yr}?`)) {
                        self.createNewYear(yr);
                    }
                });

            jQuery(document).off('change.mt-year-select')
                .on('change.mt-year-select', '#year-selector', function () {
                    self.updateYearInfo(jQuery(this).val());
                });
        },

        // Load data for specific year
        loadYearData: function(year) {
            const self = this;

            console.log('🔄 Loading year data for:', year);
            console.log('📊 Current config:', self.config);

            // Update current year in config and reload data
            self.config.currentYear = year;

            // Show loading message
            self.showSuccess(`Loading data for year ${year}...`);
            
            // Reload current month data with new year
            self.loadMonthData(self.config.currentMonth);
            
            // Update page display
            $('.mosque-page-header h1').text(`Prayer Timetables - ${year}`);

            // Update month headings
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            $('.tab-content h2').each(function() {
                const monthIndex = $(this).closest('.tab-content').attr('id').replace('month-', '') - 1;
                if (monthNames[monthIndex]) {
                    $(this).text(`${monthNames[monthIndex]} ${year}`);
                }
            });
        },

        // Create new year structure
        createNewYear: function(year) {
            const self = this;
            
            $('#create-new-year').addClass('mosque-btn-loading');
            
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'generate_all_dates',
                    nonce: this.config.nonce,
                    year: year
                },
                success: function(response) {
                    $('#create-new-year').removeClass('mosque-btn-loading');
                    
                    if (response.success) {
                        self.showSuccess(`Year ${year} structure created successfully!`);
                        self.loadYearData(year);
                    } else {
                        self.showError(response.data || `Failed to create year ${year} structure`);
                    }
                },
                error: function(xhr, status, error) {
                    $('#create-new-year').removeClass('mosque-btn-loading');
                    console.error('AJAX Error creating year:', xhr, status, error);
                    self.showError('Error creating year structure: ' + error);
                }
            });
        },

        // Update year information display
        updateYearInfo: function(year) {
            // This could be enhanced to show year-specific info
            // For now, just update the current year reference
            this.config.currentYear = year;
        },

        // Initialize PDF upload functionality
        initPdfUpload: function() {
            const self = this;

            // Upload PDF button
            $(document).on('click', '.mt-upload-pdf-btn', function() {
                const button = $(this);
                const month = button.data('month');
                const form = button.closest('.mt-pdf-upload-form');
                const fileInput = form.find('.mt-pdf-file-input')[0];

                if (!fileInput.files[0]) {
                    self.showError('Please select a PDF file first');
                    return;
                }

                const file = fileInput.files[0];
                if (file.type !== 'application/pdf') {
                    self.showError('Please select a valid PDF file');
                    return;
                }

                // Show loading state
                button.addClass('mosque-btn-loading').prop('disabled', true);

                // Create FormData
                const formData = new FormData();
                formData.append('action', 'upload_month_pdf');
                formData.append('nonce', self.config.nonce);
                formData.append('month', month);
                formData.append('year', self.config.currentYear);
                formData.append('pdf_file', file);

                // Upload
                $.ajax({
                    url: self.config.ajaxUrl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        button.removeClass('mosque-btn-loading').prop('disabled', false);

                        if (response.success) {
                            self.showSuccess('PDF uploaded successfully!');
                            // Refresh the tab to show the new PDF
                            location.reload();
                        } else {
                            self.showError(response.data || 'Upload failed');
                        }
                    },
                    error: function(xhr, status, error) {
                        button.removeClass('mosque-btn-loading').prop('disabled', false);
                        self.showError('Upload failed: ' + error);
                    }
                });
            });

            // Remove PDF button
            $(document).on('click', '.mt-remove-pdf-btn', function() {
                const button = $(this);
                const month = button.data('month');

                if (!confirm('Are you sure you want to remove this PDF?')) {
                    return;
                }

                // Show loading state
                button.addClass('mosque-btn-loading').prop('disabled', true);

                $.ajax({
                    url: self.config.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'remove_month_pdf',
                        nonce: self.config.nonce,
                        month: month,
                        year: self.config.currentYear
                    },
                    success: function(response) {
                        button.removeClass('mosque-btn-loading').prop('disabled', false);

                        if (response.success) {
                            self.showSuccess('PDF removed successfully!');
                            // Refresh the tab to update the UI
                            location.reload();
                        } else {
                            self.showError(response.data || 'Remove failed');
                        }
                    },
                    error: function(xhr, status, error) {
                        button.removeClass('mosque-btn-loading').prop('disabled', false);
                        self.showError('Remove failed: ' + error);
                    }
                });
            });

            // File input change handler (show selected file name)
            $(document).on('change', '.mt-pdf-file-input', function() {
                const input = $(this);
                const label = input.siblings('label');
                const uploadBtn = input.siblings('.mt-upload-pdf-btn');

                if (input[0].files[0]) {
                    const filename = input[0].files[0].name;
                    label.text('📁 ' + filename);
                    uploadBtn.show();
                } else {
                    label.text('📁 Choose PDF File');
                    uploadBtn.hide();
                }
            });
        },

        // Helper method to compare times in HH:MM format
        compareTime: function(time1, time2) {
            const [h1, m1] = time1.split(':').map(Number);
            const [h2, m2] = time2.split(':').map(Number);
            const minutes1 = h1 * 60 + m1;
            const minutes2 = h2 * 60 + m2;
            return minutes1 - minutes2;
        },

        // Helper method to check if time is reasonable for a prayer
        isReasonableTime: function(time, prayer) {
            const [hour] = time.split(':').map(Number);

            switch(prayer) {
                case 'fajr':
                    return hour >= 3 && hour <= 7; // Fajr typically 3-7 AM
                case 'maghrib':
                    return hour >= 16 && hour <= 20; // Maghrib typically 4-8 PM
                case 'isha':
                    return hour >= 18 && hour <= 23; // Isha typically 6-11 PM
                default:
                    return true; // No specific validation for other prayers
            }
        },

        // Helper method to show warning messages
        showWarning: function(message) {
            this.showMessage(message, 'warning');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MosqueTimetableAdmin.init();
    });

})(jQuery);