/**
 * PS CRM PWA Frontend JavaScript
 * 
 * - Install-Button ("Als App installieren")
 * - Push Notification Subscription
 * - Offline-Status Detection
 * - Quick Actions
 * 
 * @package PS Smart Business
 * @subpackage PWA
 */

(function($) {
	'use strict';
	
	let deferredPrompt = null;
	let pushSubscription = null;
	let pushFeatureAvailable = false;

	function isPushSecureContext() {
		return window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
	}
	
	/**
	 * Initialize PWA features
	 */
	function initPWA() {
		// App Install Prompt
		initInstallPrompt();
		
		// Push Notifications
		initPushNotifications();
		
		// Offline Status
		initOfflineDetection();
		
		// Update Check
		checkForUpdates();
	}
	
	/**
	 * Initialize Install Prompt
	 */
	function initInstallPrompt() {
		$(document).on('click', '.wpscrm-install-app-btn', handleInstallClick);

		// beforeinstallprompt Event
		window.addEventListener('beforeinstallprompt', (e) => {
			console.log('[PWA] Install prompt available');
			e.preventDefault();
			deferredPrompt = e;
			
			// Zeige Install-Button
			showInstallButton();
		});
		
		// App installed Event
		window.addEventListener('appinstalled', () => {
			console.log('[PWA] App installed');
			deferredPrompt = null;
			hideInstallButton();
			
			// Optional: Analytics tracking
			if (typeof gtag === 'function') {
				gtag('event', 'app_installed', {
					event_category: 'pwa',
					event_label: 'CRM Dashboard'
				});
			}
		});
	}
	
	/**
	 * Show Install Button
	 */
	function showInstallButton() {
		// Prüfe ob statischer Button bereits existiert
		if ($('.wpscrm-install-app-btn').length > 0) {
			$('.wpscrm-install-app-btn').show();
			return;
		}
		
		// Erstelle Install-Banner
		const banner = $('<div>', {
			id: 'wpscrm-install-banner',
			class: 'wpscrm-install-banner',
			html: `
				<div class="wpscrm-install-content">
					<div class="wpscrm-install-icon">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="32" height="32">
							<path d="M19 12v7H5v-7H3v9h18v-9h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2v9.67z"/>
						</svg>
					</div>
					<div class="wpscrm-install-text">
						<strong>${wpscrmPWA.i18n.install_title}</strong>
						<p>${wpscrmPWA.i18n.install_text}</p>
					</div>
					<div class="wpscrm-install-actions">
						<button id="wpscrm-install-app-btn" class="btn btn-primary">
							${wpscrmPWA.i18n.install_prompt}
						</button>
						<button id="wpscrm-dismiss-install-btn" class="btn btn-secondary">
							${wpscrmPWA.i18n.install_later}
						</button>
					</div>
				</div>
			`
		});
		
		// Füge Banner zum Body hinzu
		$('body').append(banner);
		
		// Fade in
		setTimeout(() => {
			banner.addClass('show');
		}, 500);
		
		// Event Handler
		$('#wpscrm-install-app-btn').on('click', handleInstallClick);
		$('#wpscrm-dismiss-install-btn').on('click', () => {
			banner.removeClass('show');
			setTimeout(() => banner.remove(), 300);
			
			// Merke: User hat abgelehnt (24h nicht mehr anzeigen)
			localStorage.setItem('wpscrm_install_dismissed', Date.now());
		});
	}
	
	/**
	 * Hide Install Button
	 */
	function hideInstallButton() {
		$('#wpscrm-install-banner').fadeOut(300, function() {
			$(this).remove();
		});
	}
	
	/**
	 * Handle Install Click
	 */
	async function handleInstallClick(e) {
		e.preventDefault();
		
		if (!deferredPrompt) {
			console.log('[PWA] No install prompt available');

			const isiOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
			if (isiOS) {
				alert('Installation auf iOS: Im Browser auf Teilen tippen und "Zum Home-Bildschirm" auswählen.');
			} else {
				alert('Installationsdialog aktuell nicht verfügbar. Öffne die Seite in Chrome/Edge über HTTPS und versuche es erneut.');
			}
			return;
		}
		
		// Zeige Browser Install Prompt
		deferredPrompt.prompt();
		
		// Warte auf User-Entscheidung
		const { outcome } = await deferredPrompt.userChoice;
		console.log('[PWA] User choice:', outcome);
		
		if (outcome === 'accepted') {
			console.log('[PWA] User accepted install');
		} else {
			console.log('[PWA] User dismissed install');
		}
		
		// Prompt kann nur 1x genutzt werden
		deferredPrompt = null;
		hideInstallButton();
	}
	
	/**
	 * Initialize Push Notifications
	 */
	function initPushNotifications() {
		// Prüfe Browser-Support
		if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
			console.info('[PWA] Push wird von diesem Browser nicht unterstützt.');
			return;
		}

		if (!isPushSecureContext()) {
			console.info('[PWA] Push ist nur über HTTPS (oder localhost) verfügbar.');
			$('.wpscrm-enable-notifications-btn')
				.prop('disabled', true)
				.attr('title', 'Push-Benachrichtigungen erfordern HTTPS oder localhost.');
			return;
		}

		pushFeatureAvailable = true;
		
		// Prüfe aktuellen Permission-Status
		checkNotificationPermission();
		
		// Button-Handler
		$(document).on('click', '.wpscrm-enable-notifications-btn', handleNotificationToggle);
	}
	
	/**
	 * Check Notification Permission
	 */
	function checkNotificationPermission() {
		if (Notification.permission === 'granted') {
			// Bereits erlaubt: Subscribe to push
			subscribeToPush();
		} else if (Notification.permission === 'denied') {
			// Abgelehnt: Zeige Info
			console.log('[PWA] Notifications denied by user');
		}
	}
	
	/**
	 * Handle Notification Toggle
	 */
	async function handleNotificationToggle(e) {
		e.preventDefault();

		if (!pushFeatureAvailable) {
			console.info('[PWA] Push ist in der aktuellen Umgebung nicht verfügbar.');
			return;
		}
		
		const $btn = $(this);
		
		if (Notification.permission === 'granted') {
			// Bereits aktiviert: Deaktivieren
			await unsubscribeFromPush();
			$btn.text(wpscrmPWA.i18n.notifications_enable);
		} else {
			// Aktivieren
			const permission = await Notification.requestPermission();
			
			if (permission === 'granted') {
				await subscribeToPush();
				$btn.text(wpscrmPWA.i18n.notifications_enabled);
			}
		}
	}
	
	/**
	 * Subscribe to Push Notifications
	 */
	async function subscribeToPush() {
		try {
			const registration = await navigator.serviceWorker.ready;
			
			// Prüfe ob bereits subscribed
			let subscription = await registration.pushManager.getSubscription();
			
			if (!subscription) {
				// Erstelle neue Subscription
				const vapidPublicKey = wpscrmPWA.vapidPublicKey;
				const convertedKey = urlBase64ToUint8Array(vapidPublicKey);
				
				subscription = await registration.pushManager.subscribe({
					userVisibleOnly: true,
					applicationServerKey: convertedKey
				});
				
				console.log('[PWA] Push subscription created:', subscription);
			}
			
			// Sende Subscription an Server
			await saveSubscription(subscription);
			
			pushSubscription = subscription;
			
		} catch (err) {
			console.error('[PWA] Push subscription failed:', err);
		}
	}
	
	/**
	 * Unsubscribe from Push Notifications
	 */
	async function unsubscribeFromPush() {
		try {
			if (!pushSubscription) {
				const registration = await navigator.serviceWorker.ready;
				pushSubscription = await registration.pushManager.getSubscription();
			}
			
			if (pushSubscription) {
				await pushSubscription.unsubscribe();
				
				// Entferne subscription vom Server
				await removeSubscription(pushSubscription.endpoint);
				
				pushSubscription = null;
				console.log('[PWA] Push subscription removed');
			}
		} catch (err) {
			console.error('[PWA] Unsubscribe failed:', err);
		}
	}
	
	/**
	 * Save Subscription to Server
	 */
	async function saveSubscription(subscription) {
		return $.ajax({
			url: wpscrmPWA.ajaxurl,
			type: 'POST',
			data: {
				action: 'wpscrm_subscribe_push',
				nonce: wpscrmPWA.nonce,
				subscription: JSON.stringify(subscription)
			}
		});
	}
	
	/**
	 * Remove Subscription from Server
	 */
	async function removeSubscription(endpoint) {
		return $.ajax({
			url: wpscrmPWA.ajaxurl,
			type: 'POST',
			data: {
				action: 'wpscrm_unsubscribe_push',
				nonce: wpscrmPWA.nonce,
				endpoint: endpoint
			}
		});
	}
	
	/**
	 * Initialize Offline Detection
	 */
	function initOfflineDetection() {
		// Online/Offline Events
		window.addEventListener('online', () => {
			console.log('[PWA] Back online');
			showConnectionStatus('online');
		});
		
		window.addEventListener('offline', () => {
			console.log('[PWA] Gone offline');
			showConnectionStatus('offline');
		});
		
		// Initial Status
		if (!navigator.onLine) {
			showConnectionStatus('offline');
		}
	}
	
	/**
	 * Show Connection Status Banner
	 */
	function showConnectionStatus(status) {
		// Entferne alten Banner
		$('.wpscrm-connection-banner').remove();
		
		const messages = {
			online: 'Verbindung wiederhergestellt',
			offline: 'Keine Internetverbindung'
		};
		
		const iconSvg = status === 'online' 
			? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>'
			: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>';
		
		const banner = $('<div>', {
			class: 'wpscrm-connection-banner wpscrm-connection-' + status,
			html: `
				<div class="wpscrm-connection-content">
					${iconSvg}
					<span>${messages[status]}</span>
				</div>
			`
		});
		
		$('body').append(banner);
		
		// Auto-hide bei online
		if (status === 'online') {
			setTimeout(() => {
				banner.fadeOut(300, function() {
					$(this).remove();
				});
			}, 3000);
		}
	}
	
	/**
	 * Check for Service Worker Updates
	 */
	function checkForUpdates() {
		if (!('serviceWorker' in navigator)) return;
		
		navigator.serviceWorker.ready.then(registration => {
			// Check every 1 hour
			setInterval(() => {
				registration.update();
			}, 3600000);
		});
		
		// Handle controller change (neue Version)
		navigator.serviceWorker.addEventListener('controllerchange', () => {
			console.log('[PWA] New version available, reloading...');
			window.location.reload();
		});
	}
	
	/**
	 * Helper: Convert VAPID key to Uint8Array
	 */
	function urlBase64ToUint8Array(base64String) {
		const padding = '='.repeat((4 - base64String.length % 4) % 4);
		const base64 = (base64String + padding)
			.replace(/\-/g, '+')
			.replace(/_/g, '/');
		
		const rawData = window.atob(base64);
		const outputArray = new Uint8Array(rawData.length);
		
		for (let i = 0; i < rawData.length; ++i) {
			outputArray[i] = rawData.charCodeAt(i);
		}
		
		return outputArray;
	}
	
	/**
	 * Check if running in Standalone Mode (installed as app)
	 */
	function isStandaloneMode() {
		return window.matchMedia('(display-mode: standalone)').matches 
			|| window.navigator.standalone 
			|| document.referrer.includes('android-app://');
	}
	
	/**
	 * Add body class if in app mode
	 */
	function detectAppMode() {
		if (isStandaloneMode()) {
			$('body').addClass('wpscrm-standalone-app');
			console.log('[PWA] Running in standalone mode');
		}
	}
	
	// Initialize on DOM ready
	$(document).ready(function() {
		initPWA();
		detectAppMode();
	});
	
	// Expose public API
	window.wpscrmPWA = window.wpscrmPWA || {};
	window.wpscrmPWA.subscribe = subscribeToPush;
	window.wpscrmPWA.unsubscribe = unsubscribeFromPush;
	window.wpscrmPWA.isStandalone = isStandaloneMode;
	window.wpscrmPWA.promptInstall = handleInstallClick;
	
})(jQuery);
