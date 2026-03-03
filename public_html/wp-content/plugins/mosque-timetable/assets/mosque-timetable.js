/**
 * Mosque Timetable Frontend JavaScript
 * For public-facing pages, shortcodes, PWA, and push notifications
 * Version: 3.1.0-frontend
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
      this.initPWAInstallPrompt();
      this.initPushNotifications();
      this.initServiceWorker();
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
        const $el    = $( this );
        const target = $el.data( 'target' );
        if ( ! target ) return;

        const now    = Date.now();
        const end    = new Date( target ).getTime();
        const diff   = end - now;

        if ( diff <= 0 ) {
          $el.text( 'Iftar!' );
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
        const target  = $el.data( 'target' );  // data-target="YYYY-MM-DD HH:MM:SS"
        const layout  = $el.data( 'layout' );  // 'header' or undefined (card)

        if ( ! target ) return;

        const now  = Date.now();
        const end  = new Date( target ).getTime();
        const diff = end - now;

        if ( diff <= 0 ) {
          // Prayer time reached â€” show zeros and let PHP re-render on next page load.
          if ( layout === 'header' ) {
            $el.find( '.pci-countdown' ).text( '00:00' );
          } else {
            $el.find( '.countdown-number' ).text( '00' );
          }
          return;
        }

        const h = Math.floor( diff / 3600000 );
        const m = Math.floor( ( diff % 3600000 ) / 60000 );
        const s = Math.floor( ( diff % 60000 ) / 1000 );

        if ( layout === 'header' ) {
          // Compact: H:MM or HH:MM (no seconds to keep pill tight; add :ss only if <1 h)
          const display = h > 0
            ? pad(h) + ':' + pad(m)
            : pad(m) + ':' + pad(s);
          $el.find( '.pci-countdown' ).text( display );
        } else {
          // Card: update each countdown-number span in order (h, m, s)
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
          <div class="mosque-pwa-banner">
            <div class="mosque-pwa-content">
              <span class="mosque-pwa-icon">ðŸ•Œ</span>
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
