/**
 * Mosque Timetable Frontend JavaScript
 * For public-facing pages, shortcodes, PWA, and push notifications
 * Version: 3.4.0
 */

/* eslint-env browser, jquery */
(function ($) {
  'use strict';

  // Frontend object (public pages only)
  window.MosqueTimetableFrontend = {
    config: {
      ajaxUrl: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.ajaxUrl) || '/wp-admin/admin-ajax.php',
      nonce: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.nonce) || '',
      serviceWorkerUrl: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.serviceWorkerUrl) || '',
      manifestUrl: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.manifestUrl) || '',
      vapidPublicKey: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.vapidPublicKey) || '',
      strings: (typeof mosqueTimetable !== 'undefined' && mosqueTimetable.strings) || {}
    },

    t(key, fallback) {
      const dict = this.config.strings || {};
      return (dict[key] && String(dict[key])) || fallback || key;
    },

    // Initialize frontend functionality
    init() {
      this.initPrayerCountdown();
      this.initPrayerBar();
      this.initNextPrayerHighlight();
      this.initPWAInstallPrompt();
      this.initPushNotifications();
      this.initServiceWorker();
    },

    // Highlight next prayer in today's mobile card
    initNextPrayerHighlight() {
      var todayCard = document.querySelector('.mosque-prayer-card[data-next-prayer]');
      if ( ! todayCard ) return;
      var nextKey = todayCard.getAttribute('data-next-prayer').toLowerCase();
      // Map prayer keys to display names used in the card
      var nameMap = { fajr: 'fajr', sunrise: 'sunrise', dhuhr: 'dhuhr', zuhr: 'dhuhr', asr: 'asr', maghrib: 'maghrib', isha: 'isha', jummah: 'jummah' };
      var target = nameMap[nextKey] || nextKey;
      var items = todayCard.querySelectorAll('.mosque-prayer-time-item');
      items.forEach(function(item) {
        var nameEl = item.querySelector('.mosque-prayer-time-name');
        if ( nameEl && nameEl.textContent.trim().toLowerCase().indexOf(target) !== -1 ) {
          item.classList.add('mt-next-prayer-item');
        }
      });
    },

    // â”€â”€ Prayer Bar â€” [mosque_prayer_bar] â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    initPrayerBar() {
      // Restore dismissed state for each bar on page load.
      $('.mpb-hello-bar').each( function() {
        const barId      = this.id;
        const storageKey = 'mpb-dismissed-' + barId;
        const dismissed  = localStorage.getItem( storageKey );

        if ( dismissed ) {
          // Respect 24-hour dismissal window.
          if ( Date.now() - parseInt( dismissed, 10 ) < 86400000 ) {
            $( this ).addClass( 'mpb-hidden' );
          } else {
            localStorage.removeItem( storageKey );
          }
        }
      });

      // Dismiss on click.
      $( document ).on( 'click', '.mpb-dismiss', function() {
        const barId = $( this ).data( 'bar-id' ) || $( this ).closest( '.mpb-hello-bar' ).attr( 'id' );
        const $bar  = barId ? $( '#' + barId ) : $( this ).closest( '.mpb-hello-bar' );

        $bar.addClass( 'mpb-hidden' );

        if ( barId ) {
          localStorage.setItem( 'mpb-dismissed-' + barId, Date.now() );
        }
      });
    },

    // Prayer countdown functionality for shortcodes + Ramadan countdowns
    initPrayerCountdown() {
      const hasPrayer  = $( '.prayer-countdown' ).length > 0;
      const hasRamadan = $( '.mt-ramadan-countdown' ).length > 0;
      if ( hasPrayer || hasRamadan ) {
        this.updateCountdowns();
        this.updateRamadanCountdowns();
        setInterval( () => {
          this.updateCountdowns();
          this.updateRamadanCountdowns();
        }, 1000 );
      }
    },

    updateRamadanCountdowns() {
      const pad = n => String( n ).padStart( 2, '0' );

      $( '.mt-ramadan-countdown' ).each( function() {
        const $el          = $( this );
        const target       = $el.data( 'target' );
        const suhoorNext   = $el.data( 'suhoor-next' );
        const $label       = $el.siblings( '.mt-ramadan-time-label' ).length ? $el.siblings( '.mt-ramadan-time-label' ) : $el.closest( 'div' ).find( '.mt-ramadan-time-label' );
        const $parentLabel = $el.closest( '.mt-ramadan-countdown-block, .mt-ramadan-time-col' ).find( '.mt-ramadan-time-label, .mt-ramadan-time-col-label' );

        if ( ! target ) return;

        const now    = Date.now();
        let end      = new Date( target ).getTime();
        let diff     = end - now;
        let isSuhoor = false;

        // If Iftar reached, switch to next Suhoor countdown.
        if ( diff <= 0 && suhoorNext ) {
          end      = new Date( suhoorNext ).getTime();
          diff     = end - now;
          isSuhoor = true;

          if ( $parentLabel.length ) {
            $parentLabel.text( MosqueTimetableFrontend.t( 'untilSuhoor', 'Until Suhoor' ) );
          }
        }

        if ( diff <= 0 ) {
          $el.text( isSuhoor ? MosqueTimetableFrontend.t( 'suhoor', 'Suhoor!' ) : MosqueTimetableFrontend.t( 'iftar', 'Iftar!' ) );
          $el.css( 'color', 'var(--mosque-secondary)' );
          return;
        }

        const h = Math.floor( diff / 3600000 );
        const m = Math.floor( ( diff % 3600000 ) / 60000 );
        const s = Math.floor( ( diff % 60000 ) / 1000 );
        $el.text( pad(h) + ':' + pad(m) + ':' + pad(s) );
      });
    },

    updateCountdowns() {
      const pad = n => String( n ).padStart( 2, '0' );

      $('.prayer-countdown').each( function() {
        const $el     = $( this );
        let target    = $el.data( 'target' );  // data-target="YYYY-MM-DD HH:MM:SS"
        const layout  = $el.data( 'layout' );  // 'header', 'desktop-bar', or undefined (card)
        const schedule = $el.data( 'schedule' ); // Array of upcoming prayers

        if ( ! target ) return;

        const now  = Date.now();
        let end  = new Date( target.replace(/-/g, '/') ).getTime(); // Safari compat
        let diff = end - now;

        // If target reached and we have a schedule, roll over to the next prayer.
        if ( diff <= 0 && schedule && schedule.length ) {
          const nextPrayer = schedule.find(p => new Date(p.datetime.replace(/-/g, '/')).getTime() > now);

          if ( nextPrayer ) {
            $el.data( 'target', nextPrayer.datetime );
            $el.data( 'prayer', nextPrayer.name );

            // Update UI elements based on layout.
            if ( layout === 'header' ) {
              $el.find( '.pci-name' ).text( nextPrayer.name );
              $el.find( '.pci-time' ).text( nextPrayer.formatted_time );
            } else if ( layout === 'desktop-bar' ) {
              $el.find( '.pcb-name' ).text( nextPrayer.name );
              $el.find( '.pcb-time' ).text( nextPrayer.formatted_time );
            } else {
              $el.find( '.countdown-next-prayer' ).text( nextPrayer.name );
              $el.find( '.countdown-next-time' ).text( nextPrayer.formatted_time );
            }

            target = nextPrayer.datetime;
            end = new Date( target.replace(/-/g, '/') ).getTime();
            diff = end - now;
          }
        }

        if ( diff <= 0 ) {
          if ( layout === 'header' ) {
            $el.find( '.pci-countdown' ).text( '00:00' );
          } else if ( layout === 'desktop-bar' ) {
            $el.find( '.pcb-countdown' ).text( '00:00:00' );
          } else {
            $el.find( '.countdown-number' ).text( '00' );
          }
          return;
        }

        const h = Math.floor( diff / 3600000 );
        const m = Math.floor( ( diff % 3600000 ) / 60000 );
        const s = Math.floor( ( diff % 60000 ) / 1000 );

        if ( layout === 'header' ) {
          const display = h > 0
            ? pad(h) + ':' + pad(m)
            : pad(m) + ':' + pad(s);
          $el.find( '.pci-countdown' ).text( display );
        } else if ( layout === 'desktop-bar' ) {
          $el.find( '.pcb-countdown' ).text( pad(h) + ':' + pad(m) + ':' + pad(s) );
        } else {
          const $nums = $el.find( '.countdown-number' );
          $nums.eq(0).text( pad(h) );
          $nums.eq(1).text( pad(m) );
          $nums.eq(2).text( pad(s) );
        }
      });
    },

    // PWA install prompt
    initPWAInstallPrompt() {
      if (!this.config.manifestUrl) return;

      let deferredPrompt;

      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        this.showInstallBanner();
      });

      $(document).on('click', '.install-pwa-btn', () => {
        if (deferredPrompt) {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
              this.hideInstallBanner();
            }
            deferredPrompt = null;
          });
        }
      });

      $(document).on('click', '.dismiss-pwa-banner', () => {
        this.hideInstallBanner();
        localStorage.setItem('pwa-banner-dismissed', Date.now());
      });
    },

    showInstallBanner() {
      // Check if banner was recently dismissed
      const dismissed = localStorage.getItem('pwa-banner-dismissed');
      if (dismissed && (Date.now() - parseInt(dismissed)) < 24 * 60 * 60 * 1000) {
        return;
      }

      if ($('.mosque-pwa-banner').length === 0) {
        const banner = `
          <div class="mosque-pwa-banner" role="complementary" aria-label="Install Prayer Times App">
            <div class="mosque-pwa-content">
              <span class="mosque-pwa-icon">&#128332;</span>
              <div class="mosque-pwa-text">
                <strong>Install Prayer Times App</strong>
                <p>Get quick access to prayer times on your device</p>
              </div>
            </div>
            <div class="mosque-pwa-actions">
              <button class="install-pwa-btn">Install</button>
              <button class="dismiss-pwa-banner">Maybe Later</button>
            </div>
          </div>
        `;
        $('body').append(banner);
      }
    },

    hideInstallBanner() {
      $('.mosque-pwa-banner').fadeOut(300, function() {
        $(this).remove();
      });
    },

    // Push notifications
    initPushNotifications() {
      if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return;
      }

      // Subscribe button handler
      $(document).on('click', '.subscribe-notifications-btn', () => {
        this.subscribeToPushNotifications();
      });

      // Unsubscribe button handler
      $(document).on('click', '.unsubscribe-notifications-btn', () => {
        this.unsubscribeFromPushNotifications();
      });

      // Check current subscription status
      this.updateSubscriptionUI();
    },

    async subscribeToPushNotifications() {
      try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: this.urlBase64ToUint8Array(this.config.vapidPublicKey)
        });

        // Send subscription to server
        const response = await fetch(`${this.config.ajaxUrl}?action=subscribe_push_notifications`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': this.config.nonce
          },
          body: JSON.stringify({
            subscription: subscription,
            alarms: [5, 15], // Default alarms
            sunrise_warning: true
          })
        });

        if (response.ok) {
          this.showNotification('Successfully subscribed to prayer time notifications!', 'success');
          this.updateSubscriptionUI();
        } else {
          throw new Error('Failed to subscribe');
        }
      } catch (error) {
        console.error('Error subscribing to push notifications:', error);
        this.showNotification('Failed to subscribe to notifications', 'error');
      }
    },

    async unsubscribeFromPushNotifications() {
      try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
          await subscription.unsubscribe();

          // Send unsubscribe to server
          await fetch(`${this.config.ajaxUrl}?action=unsubscribe_push_notifications`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': this.config.nonce
            },
            body: JSON.stringify({
              endpoint: subscription.endpoint
            })
          });

          this.showNotification('Unsubscribed from notifications', 'success');
          this.updateSubscriptionUI();
        }
      } catch (error) {
        console.error('Error unsubscribing from push notifications:', error);
        this.showNotification('Failed to unsubscribe', 'error');
      }
    },

    async updateSubscriptionUI() {
      try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
          $('.subscribe-notifications-btn').hide();
          $('.unsubscribe-notifications-btn').show();
          $('.notification-status').text('Notifications enabled');
        } else {
          $('.subscribe-notifications-btn').show();
          $('.unsubscribe-notifications-btn').hide();
          $('.notification-status').text('Notifications disabled');
        }
      } catch (error) {
        console.error('Error checking subscription status:', error);
      }
    },

    // Service Worker registration
    async initServiceWorker() {
      if (!('serviceWorker' in navigator) || !this.config.serviceWorkerUrl) {
        return;
      }

      try {
        const _registration = await navigator.serviceWorker.register(this.config.serviceWorkerUrl);
        // Service Worker registered successfully (registration stored but not used)
      } catch (error) {
        console.error('Service Worker registration failed:', error);
      }
    },

    // Utility functions
    urlBase64ToUint8Array(base64String) {
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

    showNotification(message, type = 'info') {
      const notification = $(`
        <div class="mosque-notification mosque-notification-${type}">
          <span>${message}</span>
          <button class="mosque-notification-close">&times;</button>
        </div>
      `);

      $('body').append(notification);
      notification.fadeIn(300);

      // Auto-hide after 5 seconds
      setTimeout(() => {
        notification.fadeOut(300, function() {
          $(this).remove();
        });
      }, 5000);

      // Close button handler
      notification.find('.mosque-notification-close').on('click', function() {
        notification.fadeOut(300, function() {
          $(this).remove();
        });
      });
    }
  };

  // Initialize when document is ready
  $(document).ready(function() {
    window.MosqueTimetableFrontend.init();
  });

})(jQuery);
