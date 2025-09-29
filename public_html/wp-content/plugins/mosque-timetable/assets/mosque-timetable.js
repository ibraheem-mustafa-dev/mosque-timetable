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
      this.initPWAInstallPrompt();
      this.initPushNotifications();
      this.initServiceWorker();
    },

    // Prayer countdown functionality for shortcodes
    initPrayerCountdown() {
      const countdownElements = $('.prayer-countdown');
      if (countdownElements.length > 0) {
        this.updateCountdowns();
        setInterval(() => this.updateCountdowns(), 1000);
      }
    },

    updateCountdowns() {
      $('.prayer-countdown').each(function() {
        const $this = $(this);
        const targetTime = $this.data('target-time');

        if (targetTime) {
          const now = new Date().getTime();
          const target = new Date(targetTime).getTime();
          const difference = target - now;

          if (difference > 0) {
            const hours = Math.floor(difference / (1000 * 60 * 60));
            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((difference % (1000 * 60)) / 1000);

            $this.html(`${hours}h ${minutes}m ${seconds}s`);
          } else {
            $this.html('Time reached');
          }
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
              <span class="mosque-pwa-icon">🕌</span>
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
        const registration = await navigator.serviceWorker.register(this.config.serviceWorkerUrl);
        console.log('Service Worker registered successfully:', registration);
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