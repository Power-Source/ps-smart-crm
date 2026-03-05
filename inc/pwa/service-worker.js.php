<?php
/**
 * Service Worker für PS CRM Progressive Web App
 * 
 * Funktionen:
 * - App-Installation ermöglichen
 * - Offline-Caching von Assets
 * - Push-Benachrichtigungen empfangen
 * - Background Sync für Ticket-Antworten
 * 
 * @package PS Smart CRM
 * @subpackage PWA
 */

// Verhindere direktes PHP-Parsen bei Browser-Request
if ( ! defined( 'ABSPATH' ) ) {
	// Service Worker wird von WordPress handled, aber falls direkt aufgerufen
	// Header bereits gesetzt in class-pwa-manager.php
}

$cache_version = 'wpscrm-v' . WPSCRM_VERSION;
$home_url = home_url( '/' );
$plugin_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );

?>
// Service Worker für PS CRM Dashboard
const CACHE_VERSION = '<?php echo esc_js( $cache_version ); ?>';
const CACHE_NAME = 'wpscrm-app-cache-' + CACHE_VERSION;
const HOME_URL = '<?php echo esc_js( $home_url ); ?>';
const PLUGIN_URL = '<?php echo esc_js( $plugin_url ); ?>';

// Assets die gecached werden sollen
const STATIC_ASSETS = [
	HOME_URL,
	PLUGIN_URL + 'assets/css/smartcrm.css',
	PLUGIN_URL + 'assets/js/pwa.js',
	PLUGIN_URL + 'assets/img/icon-192.png',
	PLUGIN_URL + 'assets/img/icon-512.png',
];

// Routes die gecached werden sollen
const CACHE_ROUTES = [
	'/kunde/dashboard/',
	'/agent/dashboard/',
];

/**
 * Service Worker Installation
 */
self.addEventListener('install', event => {
	console.log('[Service Worker] Installing...');
	
	event.waitUntil(
		caches.open(CACHE_NAME).then(cache => {
			console.log('[Service Worker] Caching static assets');
			return cache.addAll(STATIC_ASSETS.filter(url => url)); // Filter empty URLs
		}).catch(err => {
			console.log('[Service Worker] Cache failed:', err);
		})
	);
	
	// Aktiviere neuen Service Worker sofort
	self.skipWaiting();
});

/**
 * Service Worker Aktivierung
 */
self.addEventListener('activate', event => {
	console.log('[Service Worker] Activating...');
	
	event.waitUntil(
		// Lösche alte Caches
		caches.keys().then(cacheNames => {
			return Promise.all(
				cacheNames.map(cacheName => {
					if (cacheName !== CACHE_NAME && cacheName.startsWith('wpscrm-')) {
						console.log('[Service Worker] Deleting old cache:', cacheName);
						return caches.delete(cacheName);
					}
				})
			);
		})
	);
	
	// Übernehme Kontrolle sofort
	return self.clients.claim();
});

/**
 * Fetch Handler - Network First mit Cache Fallback
 */
self.addEventListener('fetch', event => {
	const { request } = event;
	const url = new URL(request.url);
	
	// Ignoriere Chrome Extensions und andere Protokolle
	if (!request.url.startsWith('http')) {
		return;
	}
	
	// Ignoriere wp-admin
	if (url.pathname.startsWith('/wp-admin/') || url.pathname.startsWith('/wp-login.php')) {
		return;
	}
	
	// AJAX Requests: Immer Network (keine Cache)
	if (url.pathname.includes('admin-ajax.php') || url.pathname.includes('/wp-json/')) {
		event.respondWith(fetch(request));
		return;
	}
	
	// Strategie: Network First, Cache Fallback
	event.respondWith(
		fetch(request)
			.then(response => {
				// Clone Response für Cache
				const responseClone = response.clone();
				
				// Cache nur erfolgreiche GET-Requests
				if (request.method === 'GET' && response.status === 200) {
					caches.open(CACHE_NAME).then(cache => {
						cache.put(request, responseClone);
					});
				}
				
				return response;
			})
			.catch(error => {
				// Netzwerkfehler: Versuche aus Cache zu laden
				return caches.match(request).then(cachedResponse => {
					if (cachedResponse) {
						console.log('[Service Worker] Serving from cache:', request.url);
						return cachedResponse;
					}
					
					// Kein Cache vorhanden: Offline-Seite
					if (request.mode === 'navigate') {
						return caches.match(HOME_URL + '?offline=1');
					}
					
					// Für Assets: Fehler durchreichen
					return Promise.reject(error);
				});
			})
	);
});

/**
 * Push Notification Handler
 */
self.addEventListener('push', event => {
	console.log('[Service Worker] Push received');
	
	let notificationData = {
		title: 'PS CRM',
		body: 'Neue Benachrichtigung',
		icon: PLUGIN_URL + 'assets/img/icon-192.png',
		badge: PLUGIN_URL + 'assets/img/badge-72.png',
		data: {
			url: HOME_URL
		}
	};
	
	// Parse Push-Daten
	if (event.data) {
		try {
			const data = event.data.json();
			notificationData = {
				...notificationData,
				...data
			};
		} catch (e) {
			notificationData.body = event.data.text();
		}
	}
	
	event.waitUntil(
		self.registration.showNotification(notificationData.title, {
			body: notificationData.body,
			icon: notificationData.icon,
			badge: notificationData.badge,
			tag: notificationData.tag || 'wpscrm-notification',
			data: notificationData.data,
			requireInteraction: notificationData.requireInteraction || false,
			actions: notificationData.actions || []
		})
	);
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', event => {
	console.log('[Service Worker] Notification clicked');
	
	event.notification.close();
	
	const urlToOpen = event.notification.data?.url || HOME_URL;
	
	event.waitUntil(
		clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
			// Suche nach bereits geöffnetem Tab
			for (let client of windowClients) {
				if (client.url === urlToOpen && 'focus' in client) {
					return client.focus();
				}
			}
			
			// Öffne neuen Tab
			if (clients.openWindow) {
				return clients.openWindow(urlToOpen);
			}
		})
	);
});

/**
 * Background Sync (für Offline-Ticket-Antworten)
 */
self.addEventListener('sync', event => {
	console.log('[Service Worker] Background sync:', event.tag);
	
	if (event.tag === 'sync-ticket-replies') {
		event.waitUntil(syncTicketReplies());
	}
});

/**
 * Sync Ticket Replies die offline gespeichert wurden
 */
async function syncTicketReplies() {
	try {
		const db = await openDB();
		const replies = await db.getAll('pending-replies');
		
		for (let reply of replies) {
			try {
				const response = await fetch(reply.url, {
					method: 'POST',
					headers: reply.headers,
					body: reply.body
				});
				
				if (response.ok) {
					await db.delete('pending-replies', reply.id);
					console.log('[Service Worker] Synced reply:', reply.id);
				}
			} catch (err) {
				console.log('[Service Worker] Sync failed for reply:', reply.id, err);
			}
		}
	} catch (err) {
		console.log('[Service Worker] Background sync error:', err);
	}
}

/**
 * IndexedDB Helper (vereinfacht)
 */
function openDB() {
	return new Promise((resolve, reject) => {
		const request = indexedDB.open('wpscrm-offline', 1);
		
		request.onerror = () => reject(request.error);
		request.onsuccess = () => resolve({
			db: request.result,
			getAll: (store) => {
				return new Promise((res, rej) => {
					const transaction = request.result.transaction(store, 'readonly');
					const objectStore = transaction.objectStore(store);
					const getAllRequest = objectStore.getAll();
					getAllRequest.onsuccess = () => res(getAllRequest.result);
					getAllRequest.onerror = () => rej(getAllRequest.error);
				});
			},
			delete: (store, id) => {
				return new Promise((res, rej) => {
					const transaction = request.result.transaction(store, 'readwrite');
					const objectStore = transaction.objectStore(store);
					const deleteRequest = objectStore.delete(id);
					deleteRequest.onsuccess = () => res();
					deleteRequest.onerror = () => rej(deleteRequest.error);
				});
			}
		});
		
		request.onupgradeneeded = (event) => {
			const db = event.target.result;
			if (!db.objectStoreNames.contains('pending-replies')) {
				db.createObjectStore('pending-replies', { keyPath: 'id', autoIncrement: true });
			}
		};
	});
}

console.log('[Service Worker] Loaded - Version:', CACHE_VERSION);
