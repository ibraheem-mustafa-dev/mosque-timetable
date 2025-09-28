/**
 * Mosque Timetable Frontend JavaScript
 * Version: 3.0.0
 */

/* global jQuery, mosqueTimetable, MosqueTimetable */

(function($) {
    'use strict';

    // Global mosque timetable object
    window.MosqueTimetable = {
        // Configuration
        config: {
            restUrl: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.restUrl) || '/wp-json/mosque/v1/',
            nonce: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.nonce) || '',
            restNonce: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.restNonce) || '',
            ajaxUrl: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.ajaxUrl) || '/wp-admin/admin-ajax.php',
            currentYear: new Date().getFullYear(),
            currentMonth: new Date().getMonth() + 1,
            timers: {}
        },

        // Initialize all functionality
        init: function() {
            this.initMonthSelector();
            this.initCountdownTimers();
            this.initExportButtons();
            this.initSubscribeButtons();
            this.initPWAFeatures();
            this.updatePrayerHighlights();
            this.scheduleUpdates();
            this.initExportModal();
            this.initPrayerBar();
            this.initPushNotifications();
        },

        // Initialize month selector functionality
        initMonthSelector: function() {
            $('.mosque-month-selector').on('change', function() {
                const selectedValue = $(this).val();
                const [year, month] = selectedValue.split('-');
                
                if (year && month) {
                    window.MosqueTimetable.loadMonthTimetable(year, month);
                }
            });
        },

        // Load specific month timetable via AJAX
        loadMonthTimetable: function(year, month) {
            const container = $('.mosque-timetable-container');
            const tableContainer = container.find('.mosque-timetable');
            
            // Show loading state
            this.showLoading(tableContainer);
            
            // Make AJAX request
            $.ajax({
                url: this.config.restUrl + 'prayer-times/' + year + '/' + month,
                method: 'GET',
                headers: {
                    'X-WP-Nonce': this.config.restNonce
                },
                success: function(data) {
                    if (data && data.success) {
                        window.MosqueTimetable.renderTimetable(data.data, tableContainer);
                        window.MosqueTimetable.updatePrayerHighlights();
                    } else {
                        window.MosqueTimetable.showError(tableContainer, 'Failed to load timetable data');
                    }
                },
                error: function() {
                    window.MosqueTimetable.showError(tableContainer, 'Error connecting to server');
                }
            });
        },

        // Render timetable HTML
        renderTimetable: function(data, container) {
            let html = `
                <table class="mosque-timetable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Hijri</th>
                            <th>Day</th>
                            <th>Fajr</th>
                            <th>Sunrise</th>
                            <th>Zuhr/Jummah</th>
                            <th>Asr</th>
                            <th>Maghrib</th>
                            <th>Isha</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.days.forEach(function(day) {
                const isToday = MosqueTimetable.isToday(day.date_full);
                const isFriday = MosqueTimetable.isFriday(day.date_full);
                const dayName = MosqueTimetable.getDayName(day.date_full);
                
                let rowClass = '';
                if (isToday) rowClass += ' today';
                if (isFriday) rowClass += ' friday';

                html += `<tr class="${rowClass}" data-date="${day.date_full}">`;
                
                // Date column
                html += `
                    <td>
                        <div class="date-gregorian">${MosqueTimetable.formatDate(day.date_full)}</div>
                    </td>
                `;
                
                // Hijri date column
                html += `
                    <td>
                        <div class="date-hijri">${day.hijri_date}</div>
                    </td>
                `;
                
                // Day column
                html += `
                    <td>
                        <div class="day-name ${isFriday ? 'friday' : ''}">${dayName}</div>
                    </td>
                `;
                
                // Prayer time columns
                html += MosqueTimetable.renderPrayerCell(day.fajr_start, day.fajr_jamaat);
                html += `<td><div class="prayer-single">${day.sunrise}</div></td>`;
                
                // Zuhr/Jummah column (special handling for Friday)
                if (isFriday && (day.jummah_1 || day.jummah_2)) {
                    html += `
                        <td>
                            <div class="jummah-times">
                                ${day.jummah_1 ? `<div class="jummah-1">J1: ${day.jummah_1}</div>` : ''}
                                ${day.jummah_2 ? `<div class="jummah-2">J2: ${day.jummah_2}</div>` : ''}
                            </div>
                        </td>
                    `;
                } else {
                    html += MosqueTimetable.renderPrayerCell(day.zuhr_start, day.zuhr_jamaat);
                }
                
                html += MosqueTimetable.renderPrayerCell(day.asr_start, day.asr_jamaat);
                html += MosqueTimetable.renderPrayerCell(day.maghrib_start, day.maghrib_jamaat);
                html += MosqueTimetable.renderPrayerCell(day.isha_start, day.isha_jamaat);
                
                html += '</tr>';
            });

            html += `
                    </tbody>
                </table>
            `;

            container.html(html);
        },

        // Render individual prayer cell
        renderPrayerCell: function(startTime, jamaatTime) {
            if (!startTime) return '<td></td>';
            
            return `
                <td>
                    <div class="prayer-time">
                        <div class="prayer-start">${startTime}</div>
                        ${jamaatTime ? `<div class="prayer-jamaat">${jamaatTime}</div>` : ''}
                    </div>
                </td>
            `;
        },

        // Initialize countdown timers
        initCountdownTimers: function() {
            $('.prayer-countdown').each(function() {
                const container = $(this);
                MosqueTimetable.startCountdown(container);
            });
        },

        // Start countdown timer for specific container
        startCountdown: function(container) {
            const updateCountdown = function() {
                MosqueTimetable.getCurrentPrayerData(function(data) {
                    if (data && data.nextPrayer) {
                        const nextPrayerTime = new Date(data.nextPrayer.datetime);
                        const now = new Date();
                        const timeDiff = nextPrayerTime - now;

                        if (timeDiff > 0) {
                            const timeLeft = MosqueTimetable.calculateTimeLeft(timeDiff);
                            MosqueTimetable.updateCountdownDisplay(container, data.nextPrayer, timeLeft);
                        } else {
                            // Prayer time has passed, refresh data
                            setTimeout(updateCountdown, 1000);
                        }
                    }
                });
            };

            // Update immediately and then every second
            updateCountdown();
            const timerId = setInterval(updateCountdown, 1000);
            
            // Store timer ID for cleanup
            this.config.timers[container.attr('id') || 'countdown'] = timerId;
        },

        // Calculate time left until next prayer
        calculateTimeLeft: function(timeDiff) {
            const hours = Math.floor(timeDiff / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

            return { hours, minutes, seconds };
        },

        // Update countdown display
        updateCountdownDisplay: function(container, nextPrayer, timeLeft) {
            const html = `
                <div class="countdown-header">
                    <div class="countdown-title">Next Prayer</div>
                    <div class="countdown-next-prayer">${nextPrayer.name}</div>
                    <div class="countdown-next-time">${nextPrayer.time}</div>
                </div>
                <div class="countdown-timer">
                    <div class="countdown-unit">
                        <span class="countdown-number">${String(timeLeft.hours).padStart(2, '0')}</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-number">${String(timeLeft.minutes).padStart(2, '0')}</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-number">${String(timeLeft.seconds).padStart(2, '0')}</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>
            `;
            
            container.html(html);
        },

        // Get current prayer data from API
        getCurrentPrayerData: function(callback) {
            $.ajax({
                url: this.config.restUrl + 'today-prayers',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': this.config.restNonce
                },
                success: function(data) {
                    if (data && data.success) {
                        callback(data.data);
                    } else {
                        callback(null);
                    }
                },
                error: function() {
                    callback(null);
                }
            });
        },

        // Initialize export buttons
        initExportButtons: function() {
            $('.mosque-export-btn').on('click', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const originalText = button.text();
                
                button.text('Exporting...');
                button.prop('disabled', true);
                
                // Get current month/year or all year
                const monthSelector = $('.mosque-month-selector');
                const selectedValue = monthSelector.val() || 'all';
                
                MosqueTimetable.exportICS(selectedValue, function(success) {
                    button.text(originalText);
                    button.prop('disabled', false);
                    
                    if (success) {
                        MosqueTimetable.showSuccess('Calendar exported successfully!');
                    } else {
                        MosqueTimetable.showError(null, 'Export failed. Please try again.');
                    }
                });
            });
        },

        // Export ICS calendar
        exportICS: function(period, callback) {
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'export_ics_calendar',
                    nonce: this.config.nonce,
                    period: period,
                    prayer_types: 'both', // start times and jamaat times
                    reminder: 15, // 15 minutes before
                    jummah_option: 'both' // both Jummah prayers
                },
                success: function(response) {
                    if (response.success && response.data.download_url) {
                        // Trigger download
                        const link = document.createElement('a');
                        link.href = response.data.download_url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        callback(true);
                    } else {
                        callback(false);
                    }
                },
                error: function() {
                    callback(false);
                }
            });
        },

        // Initialize subscribe buttons
        initSubscribeButtons: function() {
            $('.mosque-subscribe-btn').on('click', function(e) {
                const url = $(this).data('url');
                if (url) {
                    window.open(url, '_blank');
                } else {
                    e.preventDefault();
                    MosqueTimetable.showError(null, 'Subscription URL not configured');
                }
            });
        },

        // Initialize PWA features
        initPWAFeatures: function() {
            if ('serviceWorker' in navigator && mosqueTimetable.serviceWorkerUrl) {
                navigator.serviceWorker.register(mosqueTimetable.serviceWorkerUrl)
                    .then(function() {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function() {
                        console.log('ServiceWorker registration failed');
                    });
            }

            // Show install prompt
            this.initInstallPrompt();
            
            // Request notification permission
            this.initNotifications();
        },

        // Initialize install prompt for PWA
        initInstallPrompt: function() {
            let _deferredPrompt;
            let installPromptShown = localStorage.getItem('mosque-pwa-prompt-dismissed');

            window.addEventListener('beforeinstallprompt', function(e) {
                e.preventDefault();
                _deferredPrompt = e;
                
                // Don't show if user previously dismissed
                if (installPromptShown) {
                    return;
                }
                
                // Show custom install CTA after 3 seconds
                setTimeout(function() {
                    if ($('.mosque-pwa-cta').length === 0) {
                        MosqueTimetable.showPWACTA(_deferredPrompt);
                    }
                }, 3000);
            });

            // Handle install for manually triggered prompts
            $(document).on('click', '.mosque-pwa-btn, .mosque-header-cta', function(e) {
                e.preventDefault();
                if (_deferredPrompt) {
                    _deferredPrompt.prompt();
                    _deferredPrompt.userChoice.then(function(choiceResult) {
                        if (choiceResult.outcome === 'accepted') {
                            console.log('User accepted the install prompt');
                            $('.mosque-pwa-cta').remove();
                        }
                        _deferredPrompt = null;
                    });
                } else {
                    // Fallback for browsers that don't support beforeinstallprompt
                    MosqueTimetable.showInstallInstructions();
                }
            });

            // Handle close button
            $(document).on('click', '.mosque-pwa-close', function(e) {
                e.stopPropagation();
                $('.mosque-pwa-cta').remove();
                localStorage.setItem('mosque-pwa-prompt-dismissed', 'true');
            });
        },

        // Show PWA CTA button
        showPWACTA: function() {
            const ctaHtml = `
                <div class="mosque-pwa-cta">
                    <button class="mosque-pwa-btn" type="button">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M19,20H4C2.89,20 2,19.1 2,18V6C2,4.89 2.89,4 4,4H10L12,6H19A2,2 0 0,1 21,8H21L4,8V18L6.14,10H23.21L20.93,18.5C20.7,19.37 19.92,20 19,20Z"/>
                        </svg>
                        <span class="text">Add to Home Screen</span>
                    </button>
                    <button class="mosque-pwa-close" type="button" aria-label="Close">×</button>
                </div>
            `;
            
            $('body').append(ctaHtml);
            
            // Animate in
            setTimeout(function() {
                $('.mosque-pwa-cta').addClass('show');
            }, 100);
        },

        // Show install instructions for unsupported browsers
        showInstallInstructions: function() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            
            let instructions = '';
            if (isIOS) {
                instructions = 'To install: Tap the Share button <svg width="16" height="16" viewBox="0 0 24 24"><path d="M18,16.08C17.24,16.08 16.56,16.38 16.04,16.85L8.91,12.7C8.96,12.47 9,12.24 9,12C9,11.76 8.96,11.53 8.91,11.3L15.96,7.19C16.5,7.69 17.21,8 18,8A3,3 0 0,0 21,5A3,3 0 0,0 18,2A3,3 0 0,0 15,5C15,5.24 15.04,5.47 15.09,5.7L8.04,9.81C7.5,9.31 6.79,9 6,9A3,3 0 0,0 3,12A3,3 0 0,0 6,15C6.79,15 7.5,14.69 8.04,14.19L15.16,18.34C15.11,18.55 15.08,18.77 15.08,19C15.08,20.61 16.39,21.91 18,21.91C19.61,21.91 20.92,20.6 20.92,19A2.84,2.84 0 0,0 18,16.08Z"/></svg> then "Add to Home Screen"';
            } else if (isAndroid) {
                instructions = 'To install: Tap the menu button (⋮) then "Add to Home screen" or "Install app"';
            } else {
                instructions = 'To install: Look for the install icon in your browser\'s address bar or menu';
            }
            
            alert(instructions);
        },

        // Initialize notifications
        initNotifications: function() {
            if ('Notification' in window && navigator.serviceWorker) {
                if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            }
        },

        // Update prayer time highlights
        updatePrayerHighlights: function() {
            // Remove existing highlights
            $('.mosque-timetable tr').removeClass('next-prayer');
            $('.prayer-item').removeClass('next-prayer');
            
            this.getCurrentPrayerData(function(data) {
                if (data && data.nextPrayer) {
                    // Highlight next prayer in timetable
                    const nextPrayerName = data.nextPrayer.name.toLowerCase();

                    // Highlight prayer chips in the prayer bar
                    $('.mosque-prayer-chip').removeClass('next-prayer');
                    $(`.mosque-prayer-chip[data-prayer="${nextPrayerName}"]`).addClass('next-prayer');

                    // Highlight prayer items/cells in the timetable
                    $(`.prayer-item:contains("${data.nextPrayer.name}")`).addClass('next-prayer');
                    
                    // Highlight today's row if it exists
                    const todayRow = $(`.mosque-timetable tr[data-date="${MosqueTimetable.getTodayDate()}"]`);
                    if (todayRow.length > 0) {
                        todayRow.addClass('next-prayer');
                    }
                }
            });
        },

        // Schedule regular updates
        scheduleUpdates: function() {
            // Update highlights every minute
            setInterval(function() {
                MosqueTimetable.updatePrayerHighlights();
            }, 60000);

            // Refresh data every hour
            setInterval(function() {
                const monthSelector = $('.mosque-month-selector');
                if (monthSelector.length > 0) {
                    const selectedValue = monthSelector.val();
                    if (selectedValue) {
                        const [year, month] = selectedValue.split('-');
                        MosqueTimetable.loadMonthTimetable(year, month);
                    }
                }
            }, 3600000);
        },

        // Utility functions
        isToday: function(dateString) {
            const today = new Date();
            const date = new Date(dateString);
            return date.toDateString() === today.toDateString();
        },

        isFriday: function(dateString) {
            const date = new Date(dateString);
            return date.getDay() === 5; // Friday is day 5
        },

        getDayName: function(dateString) {
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const date = new Date(dateString);
            return days[date.getDay()];
        },

        getTodayDate: function() {
            const today = new Date();
            return today.getFullYear() + '-' + 
                   String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(today.getDate()).padStart(2, '0');
        },

        formatDate: function(dateString) {
            const date = new Date(dateString);
            return String(date.getDate()).padStart(2, '0');
        },

        // UI helper functions
        showLoading: function(container) {
            container.html(`
                <div class="mosque-timetable-loading">
                    <div class="mosque-spinner"></div>
                    Loading prayer times...
                </div>
            `);
        },

        showError: function(container, message) {
            const errorHtml = `
                <div class="mosque-error">
                    <strong>Error:</strong> ${message}
                </div>
            `;
            
            if (container && container.length > 0) {
                container.html(errorHtml);
            } else {
                // Show global error
                if ($('.mosque-error').length === 0) {
                    $('.mosque-timetable-container').prepend(errorHtml);
                    setTimeout(function() {
                        $('.mosque-error').fadeOut();
                    }, 5000);
                }
            }
        },

        showSuccess: function(message) {
            const successHtml = `
                <div class="mosque-success">
                    ${message}
                </div>
            `;
            
            if ($('.mosque-success').length === 0) {
                $('.mosque-timetable-container').prepend(successHtml);
                setTimeout(function() {
                    $('.mosque-success').fadeOut();
                }, 3000);
            }
        },

        // Initialize export modal
        initExportModal: function() {
            // Initialize the modal when MosqueTimetableModal is available
            if (typeof window.MosqueTimetableModal !== 'undefined') {
                window.MosqueTimetableModal.init({
                    restUrl: this.config.restUrl,
                    currentMonth: this.config.currentMonth,
                    currentYear: this.config.currentYear,
                    nonce: this.config.restNonce
                });
            }
        },

        // Initialize prayer bar functionality
        initPrayerBar: function() {
            const prayerBar = $('.mosque-prayer-bar-prayers');
            const chips = $('.mosque-prayer-chip');

            if (prayerBar.length === 0 || chips.length === 0) {
                return;
            }

            // Auto-scroll to next prayer on mobile
            this.centerNextPrayerChip();

            // Add click handlers for chips
            chips.on('click', this.handleChipClick.bind(this));

            // Add keyboard navigation
            prayerBar.on('keydown', this.handleKeyNavigation.bind(this));

            // Auto-scroll when window resizes
            $(window).on('resize', this.centerNextPrayerChip.bind(this));
        },

        // Center the next prayer chip in view
        centerNextPrayerChip: function() {
            const prayerBar = $('.mosque-prayer-bar-prayers');
            const nextChip = $('.mosque-prayer-chip.next-prayer');

            if (prayerBar.length === 0 || nextChip.length === 0) {
                return;
            }

            // Only auto-scroll on mobile (≤ 768px)
            if ($(window).width() <= 768) {
                const chipOffset = nextChip.position().left;
                const chipWidth = nextChip.outerWidth();
                const containerWidth = prayerBar.width();
                const scrollLeft = chipOffset - (containerWidth / 2) + (chipWidth / 2);

                prayerBar.animate({
                    scrollLeft: scrollLeft
                }, 300);
            }
        },

        // Handle chip click
        handleChipClick: function(e) {
            const chip = $(e.currentTarget);
            const prayerName = chip.data('prayer');

            // Update active state
            $('.mosque-prayer-chip').removeClass('active').attr('aria-selected', 'false').attr('tabindex', '-1');
            chip.addClass('active').attr('aria-selected', 'true').attr('tabindex', '0');

            // Focus the clicked chip
            chip.focus();

            // Optional: Scroll to the corresponding row in the table/card
            this.scrollToPrayerRow(prayerName);
        },

        // Handle keyboard navigation for prayer chips
        handleKeyNavigation: function(e) {
            const chips = $('.mosque-prayer-chip');
            const currentChip = $('.mosque-prayer-chip:focus');
            let currentIndex = chips.index(currentChip);
            let targetIndex = currentIndex;

            switch (e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    targetIndex = currentIndex > 0 ? currentIndex - 1 : chips.length - 1;
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    targetIndex = currentIndex < chips.length - 1 ? currentIndex + 1 : 0;
                    break;
                case 'Home':
                    e.preventDefault();
                    targetIndex = 0;
                    break;
                case 'End':
                    e.preventDefault();
                    targetIndex = chips.length - 1;
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    currentChip.click();
                    return;
                default:
                    return;
            }

            // Move focus to target chip
            chips.attr('tabindex', '-1').attr('aria-selected', 'false').removeClass('active');
            const targetChip = chips.eq(targetIndex);
            targetChip.attr('tabindex', '0').attr('aria-selected', 'true').addClass('active').focus();

            // Ensure chip is visible on mobile
            if ($(window).width() <= 768) {
                const prayerBar = $('.mosque-prayer-bar-prayers');
                const chipOffset = targetChip.position().left;
                const chipWidth = targetChip.outerWidth();
                const containerWidth = prayerBar.width();
                const scrollLeft = chipOffset - (containerWidth / 2) + (chipWidth / 2);

                prayerBar.animate({
                    scrollLeft: scrollLeft
                }, 200);
            }
        },

        // Scroll to corresponding prayer row (optional enhancement)
        // Initialize push notifications
        initPushNotifications: function() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return; // Push notifications not supported
            }

            this.addPushNotificationButtons();
            this.initServiceWorker();
        },

        // Add push notification buttons to the UI
        addPushNotificationButtons: function() {
            const buttonHtml = '<button class="mt-push-reminder-btn" id="mt-push-reminder-btn" style="display: none;">' +
                '<span class="dashicons dashicons-bell"></span> Prayer Reminders' +
                '</button>';

            // Add to prayer bar if it exists
            const prayerBar = $('.mosque-prayer-bar, .mosque-sticky-prayer-bar');
            if (prayerBar.length > 0) {
                prayerBar.append(buttonHtml);
            }

            // Add to today page or main timetable if prayer bar doesn't exist
            if (prayerBar.length === 0) {
                const container = $('.mosque-timetable-container, .today-prayers-container');
                if (container.length > 0) {
                    container.prepend('<div class="mt-push-container">' + buttonHtml + '</div>');
                }
            }

            // Check subscription status and show/hide button
            this.checkSubscriptionStatus();

            // Bind click event
            $(document).on('click', '#mt-push-reminder-btn', this.handlePushButtonClick.bind(this));
        },

        // Initialize service worker
        initServiceWorker: function() {
            if ('serviceWorker' in navigator) {
                const serviceWorkerUrl = (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.serviceWorkerUrl) || '/wp-content/plugins/mosque-timetable/assets/sw.js';
                navigator.serviceWorker.register(serviceWorkerUrl)
                    .then(function() {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function() {
                        console.log('ServiceWorker registration failed');
                    });
            }
        },

        // Check current subscription status
        checkSubscriptionStatus: function() {

            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return;
            }

            navigator.serviceWorker.ready.then(function(registration) {
                return registration.pushManager.getSubscription();
            }).then(function(subscription) {
                const button = $('#mt-push-reminder-btn');

                if (subscription) {
                    button.text('🔔 Turn off reminders').removeClass('subscribe').addClass('unsubscribe');
                } else {
                    button.text('🔕 Prayer Reminders').removeClass('unsubscribe').addClass('subscribe');
                }

                button.show();
            }).catch(function(err) {
                console.log('Error checking subscription status:', err);
            });
        },

        // Handle push notification button click
        handlePushButtonClick: function(e) {
            e.preventDefault();

            const button = $(e.target);

            if (button.hasClass('subscribe')) {
                this.showPrePermissionModal();
            } else if (button.hasClass('unsubscribe')) {
                this.unsubscribeFromPush();
            }
        },

        // Show pre-permission modal
        showPrePermissionModal: function() {
            const modalHtml = `
                <div id="mt-push-modal" class="mt-modal-overlay">
                    <div class="mt-modal-content">
                        <div class="mt-modal-header">
                            <h3>Prayer Reminders</h3>
                            <button class="mt-modal-close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <p>Get notified before prayer times so you never miss a prayer.</p>

                            <div class="mt-reminder-options">
                                <h4>Reminder Times:</h4>
                                <div class="mt-checkbox-group">
                                    <label><input type="checkbox" value="5" checked> 5 minutes before</label>
                                    <label><input type="checkbox" value="10" checked> 10 minutes before</label>
                                    <label><input type="checkbox" value="15" checked> 15 minutes before</label>
                                    <label><input type="checkbox" value="20"> 20 minutes before</label>
                                    <label><input type="checkbox" value="30"> 30 minutes before</label>
                                </div>
                            </div>

                            <div class="mt-sunrise-warning">
                                <label>
                                    <input type="checkbox" id="mt-sunrise-warning">
                                    Warn me before Fajr ends (sunrise)
                                </label>
                            </div>

                            <div class="mt-privacy-note">
                                <p><small><em>We will only send prayer reminder notifications. No personal data is stored beyond your subscription preferences. You can unsubscribe at any time.</em></small></p>
                            </div>
                        </div>
                        <div class="mt-modal-footer">
                            <button class="mt-btn mt-btn-secondary" id="mt-cancel-push">Cancel</button>
                            <button class="mt-btn mt-btn-primary" id="mt-enable-push">Enable Reminders</button>
                        </div>
                    </div>
                </div>`;

            $('body').append(modalHtml);
            $('#mt-push-modal').show();

            // Bind modal events
            $('#mt-cancel-push, .mt-modal-close').on('click', this.hidePrePermissionModal);
            $('#mt-enable-push').on('click', this.requestPushPermission.bind(this));
            $('#mt-push-modal').on('click', function(e) {
                if (e.target.id === 'mt-push-modal') {
                    MosqueTimetable.hidePrePermissionModal();
                }
            });
        },

        // Hide pre-permission modal
        hidePrePermissionModal: function() {
            $('#mt-push-modal').remove();
        },

        // Request push permission and subscribe
        requestPushPermission: function() {
            const _self = this;

            if (!('Notification' in window)) {
                alert('This browser does not support notifications');
                return;
            }

            // Get selected reminder offsets
            const selectedOffsets = [];
            $('#mt-push-modal input[type="checkbox"]:checked').each(function() {
                const value = $(this).val();
                if (value) {
                    selectedOffsets.push(parseInt(value));
                }
            });

            const sunriseWarning = $('#mt-sunrise-warning').is(':checked');

            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    _self.subscribeUserToPush(selectedOffsets, sunriseWarning);
                } else {
                    alert('Permission denied. You can enable notifications later in your browser settings.');
                    _self.hidePrePermissionModal();
                }
            });
        },

        // Subscribe user to push notifications
        subscribeUserToPush: function(offsets, sunriseWarning) {
            const _self = this;

            navigator.serviceWorker.ready.then(function(registration) {
                // Get VAPID public key from server
                return fetch(_self.config.restUrl + 'today-prayers', {
                    headers: {
                        'X-WP-Nonce': _self.config.restNonce
                    }
                }).then(function() {
                    // Get VAPID key from global config if available
                    const vapidPublicKey = (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.vapidPublicKey) || null;

                    if (!vapidPublicKey) {
                        throw new Error('VAPID public key not configured');
                    }

                    return registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: _self.urlBase64ToUint8Array(vapidPublicKey)
                    });
                });
            }).then(function(subscription) {
                // Send subscription to server
                return fetch(_self.config.restUrl + 'subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': _self.config.restNonce
                    },
                    body: JSON.stringify({
                        subscription: subscription.toJSON(),
                        offsets: offsets,
                        sunrise_warning: sunriseWarning
                    })
                });
            }).then(function(response) {
                return response.json();
            }).then(function(data) {
                if (data.success) {
                    _self.hidePrePermissionModal();
                    _self.checkSubscriptionStatus();
                    alert('Prayer reminders enabled! You will receive notifications at your selected times.');
                } else {
                    throw new Error(data.message || 'Failed to subscribe');
                }
            }).catch(function(err) {
                console.error('Failed to subscribe user: ', err);
                alert('Failed to enable reminders: ' + err.message);
                _self.hidePrePermissionModal();
            });
        },

        // Unsubscribe from push notifications
        unsubscribeFromPush: function() {
            const _self = this;

            navigator.serviceWorker.ready.then(function(registration) {
                return registration.pushManager.getSubscription();
            }).then(function(subscription) {
                if (subscription) {
                    // Unsubscribe from server
                    return fetch(_self.config.restUrl + 'unsubscribe', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': _self.config.restNonce
                        },
                        body: JSON.stringify({
                            endpoint: subscription.endpoint
                        })
                    }).then(function() {
                        return subscription.unsubscribe();
                    });
                }
            }).then(function() {
                _self.checkSubscriptionStatus();
                alert('Prayer reminders disabled.');
            }).catch(function(err) {
                console.error('Failed to unsubscribe user: ', err);
                alert('Failed to disable reminders');
            });
        },

        // Convert VAPID key to Uint8Array
        urlBase64ToUint8Array: function(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        },

        scrollToPrayerRow: function(prayerName) {
            if (!prayerName) return;

            // On mobile, scroll to the appropriate prayer card or chip
            if ($(window).width() <= 480) {
                // First try to find specific prayer card
                const prayerCard = $(`.mosque-prayer-card[data-prayer="${prayerName}"]`);
                if (prayerCard.length > 0) {
                    $('html, body').animate({
                        scrollTop: prayerCard.offset().top - 100
                    }, 300);
                    return;
                }

                // Fallback to today's prayer card
                const todayCard = $('.mosque-prayer-card.today');
                if (todayCard.length > 0) {
                    $('html, body').animate({
                        scrollTop: todayCard.offset().top - 100
                    }, 300);
                }
            } else {
                // On desktop, scroll to specific prayer column in today's row
                const todayRow = $('.mosque-timetable tr.today');
                if (todayRow.length > 0) {
                    // Try to find the specific prayer column
                    const prayerCell = todayRow.find(`[data-prayer="${prayerName}"], .prayer-${prayerName}`);
                    if (prayerCell.length > 0) {
                        $('html, body').animate({
                            scrollTop: todayRow.offset().top - 150
                        }, 300);

                        // Highlight the specific prayer cell temporarily
                        prayerCell.addClass('highlighted');
                        setTimeout(() => {
                            prayerCell.removeClass('highlighted');
                        }, 2000);
                    } else {
                        // Fallback to scrolling to today's row
                        $('html, body').animate({
                            scrollTop: todayRow.offset().top - 150
                        }, 300);
                    }
                }
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MosqueTimetable.init();
    });

    // Clean up timers when page unloads
    $(window).on('beforeunload', function() {
        Object.values(MosqueTimetable.config.timers).forEach(function(timerId) {
            clearInterval(timerId);
        });
    });

})(jQuery);