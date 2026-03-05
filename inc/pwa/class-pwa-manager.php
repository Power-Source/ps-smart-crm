<?php
/**
 * PWA Manager - Progressive Web App Funktionalität
 * 
 * Verwandelt CRM-Dashboards in installierbare Web-Apps:
 * - Web App Manifest (App-Name, Icons, Theme)
 * - Service Worker (Installation, Offline-Cache)
 * - Push Notifications (Ticket-Updates, Nachrichten)
 * - Standalone Template-Modus (kein WordPress-Theme)
 * 
 * @package PS Smart Business
 * @subpackage PWA
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPsCRM_PWA_Manager {
	
	/**
	 * Initialize PWA functionality
	 */
	public static function init() {
		// Manifest endpoint
		add_action( 'init', array( __CLASS__, 'register_endpoints' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_manifest_request' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_service_worker_request' ) );
		
		// Add manifest link to head
		add_action( 'wp_head', array( __CLASS__, 'add_manifest_link' ), 1 );
		
		// Add meta tags for PWA
		add_action( 'wp_head', array( __CLASS__, 'add_pwa_meta_tags' ), 2 );
		
		// Register service worker
		add_action( 'wp_footer', array( __CLASS__, 'register_service_worker' ), 999 );
		
		// Enqueue PWA scripts
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_pwa_scripts' ) );
		
		// Admin settings
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		
		// Push Notification AJAX handlers
		add_action( 'wp_ajax_wpscrm_subscribe_push', array( __CLASS__, 'ajax_subscribe_push' ) );
		add_action( 'wp_ajax_wpscrm_unsubscribe_push', array( __CLASS__, 'ajax_unsubscribe_push' ) );
		add_action( 'wp_ajax_wpscrm_save_push_prefs', array( __CLASS__, 'ajax_save_push_preferences' ) );
	}
	
	/**
	 * Register custom endpoints
	 */
	public static function register_endpoints() {
		add_rewrite_rule( '^crm-manifest\.json$', 'index.php?crm_manifest=1', 'top' );
		add_rewrite_rule( '^crm-service-worker\.js$', 'index.php?crm_sw=1', 'top' );
		add_rewrite_tag( '%crm_manifest%', '1' );
		add_rewrite_tag( '%crm_sw%', '1' );
	}
	
	/**
	 * Maybe flush rewrite rules if needed
	 */
	public static function maybe_flush_rewrite_rules() {
		// Prüfe ob ein Flush benötigt wird (nach Pfad-Fix vom 2026-03-05)
		if ( ! get_option( 'wpscrm_pwa_paths_fixed_20260305' ) ) {
			flush_rewrite_rules();
			update_option( 'wpscrm_pwa_paths_fixed_20260305', true );
		}
	}
	
	/**
	 * Handle manifest.json request
	 */
	public static function handle_manifest_request() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$is_manifest_request = get_query_var( 'crm_manifest' ) || basename( trim( $request_path, '/' ) ) === 'crm-manifest.json';

		if ( ! $is_manifest_request ) {
			return;
		}
		
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Cache-Control: max-age=3600' ); // 1 Stunde Cache
		
		$manifest = self::generate_manifest();
		echo wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}
	
	/**
	 * Handle service-worker.js request
	 */
	public static function handle_service_worker_request() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
		$is_service_worker_request = get_query_var( 'crm_sw' ) || basename( trim( $request_path, '/' ) ) === 'crm-service-worker.js';

		if ( ! $is_service_worker_request ) {
			return;
		}
		
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: max-age=0' ); // Kein Cache für SW
		
		include plugin_dir_path( __FILE__ ) . 'service-worker.js.php';
		exit;
	}
	
	/**
	 * Generate Web App Manifest
	 */
	private static function generate_manifest() {
		$settings = self::get_pwa_settings();
		
		$manifest = array(
			'name' => $settings['app_name'],
			'short_name' => $settings['app_short_name'],
			'description' => $settings['app_description'],
			'start_url' => self::get_app_start_url(),
			'scope' => home_url( '/' ),
			'display' => 'standalone',
			'orientation' => 'portrait-primary',
			'theme_color' => $settings['theme_color'],
			'background_color' => $settings['background_color'],
			'icons' => self::get_app_icons( $settings ),
			'categories' => array( 'business', 'productivity' ),
			'shortcuts' => self::get_app_shortcuts(),
		);
		
		return apply_filters( 'wpscrm_pwa_manifest', $manifest );
	}
	
	/**
	 * Get app start URL
	 */
	private static function get_app_start_url() {
		// User zur richtigen Dashboard-Seite leiten basierend auf Rolle
		return add_query_arg( 'app', '1', home_url( '/' ) );
	}
	
	/**
	 * Get app icons
	 */
	private static function get_app_icons( $settings ) {
		$icons = array();
		
		// Benutzerdefiniertes Icon (wenn hochgeladen)
		if ( ! empty( $settings['icon_512'] ) ) {
			$icons[] = array(
				'src' => $settings['icon_512'],
				'sizes' => '512x512',
				'type' => 'image/png',
				'purpose' => 'any maskable',
			);
		}
		
		if ( ! empty( $settings['icon_192'] ) ) {
			$icons[] = array(
				'src' => $settings['icon_192'],
				'sizes' => '192x192',
				'type' => 'image/png',
				'purpose' => 'any maskable',
			);
		}
		
		// Fallback: Default Icons
		if ( empty( $icons ) ) {
			$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
			$icons = array(
				array(
					'src' => $plugin_url . 'assets/img/icon-512.png',
					'sizes' => '512x512',
					'type' => 'image/png',
					'purpose' => 'any maskable',
				),
				array(
					'src' => $plugin_url . 'assets/img/icon-192.png',
					'sizes' => '192x192',
					'type' => 'image/png',
					'purpose' => 'any maskable',
				),
			);
		}
		
		return $icons;
	}
	
	/**
	 * Get app shortcuts (Quick Actions)
	 */
	private static function get_app_shortcuts() {
		$shortcuts = array();
		
		// Nur für eingeloggte User
		if ( ! is_user_logged_in() ) {
			return $shortcuts;
		}
		
		$user_id = get_current_user_id();
		
		// Support-Plugin aktiv?
		if ( class_exists( 'WPsCRM_Support_Integration' ) && WPsCRM_Support_Integration::is_support_active() ) {
			// Kunde: Neues Ticket
			if ( ! WPsCRM_Check::isAgent( $user_id ) ) {
				$shortcuts[] = array(
					'name' => __( 'Neues Ticket', 'cpsmartcrm' ),
					'short_name' => __( 'Ticket', 'cpsmartcrm' ),
					'description' => __( 'Support-Ticket erstellen', 'cpsmartcrm' ),
					'url' => add_query_arg( array( 'app' => '1', 'action' => 'new_ticket' ), home_url( '/' ) ),
					'icons' => array(
						array(
							'src' => plugin_dir_url( dirname( __FILE__ ) ) . 'assets/img/icon-ticket.png',
							'sizes' => '96x96',
						),
					),
				);
			}
		}
		
		return apply_filters( 'wpscrm_pwa_shortcuts', $shortcuts );
	}
	
	/**
	 * Add manifest link to <head>
	 */
	public static function add_manifest_link() {
		// Nur auf Frontend-Seiten
		if ( is_admin() ) {
			return;
		}
		
		$manifest_url = home_url( '/crm-manifest.json' );
		echo '<link rel="manifest" href="' . esc_url( $manifest_url ) . '">' . "\n";
	}
	
	/**
	 * Add PWA meta tags
	 */
	public static function add_pwa_meta_tags() {
		if ( is_admin() ) {
			return;
		}
		
		$settings = self::get_pwa_settings();
		
		echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( $settings['app_short_name'] ) . '">' . "\n";
		echo '<meta name="theme-color" content="' . esc_attr( $settings['theme_color'] ) . '">' . "\n";
		
		// iOS Icons
		if ( ! empty( $settings['icon_512'] ) ) {
			echo '<link rel="apple-touch-icon" href="' . esc_url( $settings['icon_512'] ) . '">' . "\n";
		}
	}
	
	/**
	 * Register Service Worker
	 */
	public static function register_service_worker() {
		if ( is_admin() ) {
			return;
		}
		
		$sw_url = home_url( '/crm-service-worker.js' );
		?>
		<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function() {
				navigator.serviceWorker.register('<?php echo esc_js( $sw_url ); ?>', {
					scope: '/'
				}).then(function(registration) {
					console.log('CRM Service Worker registered:', registration);
				}).catch(function(error) {
					console.log('CRM Service Worker registration failed:', error);
				});
			});
		}
		</script>
		<?php
	}
	
	/**
	 * Enqueue PWA scripts
	 */
	public static function enqueue_pwa_scripts() {
		if ( is_admin() ) {
			return;
		}
		
		wp_enqueue_script(
			'wpscrm-pwa',
			plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/pwa.js',
			array( 'jquery' ),
			WPSCRM_VERSION,
			true
		);
		
		wp_localize_script( 'wpscrm-pwa', 'wpscrmPWA', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wpscrm_pwa' ),
			'vapidPublicKey' => self::get_vapid_public_key(),
			'i18n' => array(
				'install_prompt' => __( 'Installieren', 'cpsmartcrm' ),
				'install_later' => __( 'Später', 'cpsmartcrm' ),
				'install_title' => __( 'Als App installieren', 'cpsmartcrm' ),
				'install_text' => __( 'Installiere das CRM Dashboard als App auf deinem Gerät für schnellen Zugriff.', 'cpsmartcrm' ),
				'notifications_enable' => __( 'Benachrichtigungen aktivieren', 'cpsmartcrm' ),
				'notifications_enabled' => __( 'Benachrichtigungen aktiviert', 'cpsmartcrm' ),
			),
		) );
	}
	
	/**
	 * AJAX: Subscribe to push notifications
	 */
	public static function ajax_subscribe_push() {
		check_ajax_referer( 'wpscrm_pwa', 'nonce' );
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Nicht eingeloggt', 'cpsmartcrm' ) ) );
		}
		
		$subscription = isset( $_POST['subscription'] ) ? json_decode( stripslashes( $_POST['subscription'] ), true ) : null;
		
		if ( ! $subscription || ! isset( $subscription['endpoint'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungültige Subscription', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		
		// Speichere Subscription in User Meta
		$subscriptions = get_user_meta( $user_id, '_wpscrm_push_subscriptions', true );
		if ( ! is_array( $subscriptions ) ) {
			$subscriptions = array();
		}
		
		// Verhindere Duplikate
		$endpoint = $subscription['endpoint'];
		$subscriptions[ md5( $endpoint ) ] = array(
			'subscription' => $subscription,
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
			'created' => current_time( 'mysql' ),
		);
		
		update_user_meta( $user_id, '_wpscrm_push_subscriptions', $subscriptions );
		
		wp_send_json_success( array( 'message' => __( 'Push-Benachrichtigungen aktiviert', 'cpsmartcrm' ) ) );
	}
	
	/**
	 * AJAX: Unsubscribe from push notifications
	 */
	public static function ajax_unsubscribe_push() {
		check_ajax_referer( 'wpscrm_pwa', 'nonce' );
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Nicht eingeloggt', 'cpsmartcrm' ) ) );
		}
		
		$endpoint = isset( $_POST['endpoint'] ) ? sanitize_text_field( $_POST['endpoint'] ) : '';
		
		if ( empty( $endpoint ) ) {
			wp_send_json_error( array( 'message' => __( 'Kein Endpoint angegeben', 'cpsmartcrm' ) ) );
		}
		
		$user_id = get_current_user_id();
		$subscriptions = get_user_meta( $user_id, '_wpscrm_push_subscriptions', true );
		
		if ( is_array( $subscriptions ) ) {
			unset( $subscriptions[ md5( $endpoint ) ] );
			update_user_meta( $user_id, '_wpscrm_push_subscriptions', $subscriptions );
		}
		
		wp_send_json_success( array( 'message' => __( 'Push-Benachrichtigungen deaktiviert', 'cpsmartcrm' ) ) );
	}

	/**
	 * AJAX: Save push preferences
	 */
	public static function ajax_save_push_preferences() {
		check_ajax_referer( 'wpscrm_pwa', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Nicht eingeloggt', 'cpsmartcrm' ) ) );
		}

		$user_id = get_current_user_id();
		$enabled = isset( $_POST['enabled'] ) ? (bool) absint( $_POST['enabled'] ) : false;
		$events_raw = isset( $_POST['events'] ) ? (array) $_POST['events'] : array();
		$allowed_events = array( 'assignments', 'replies', 'status' );

		$events = array(
			'assignments' => false,
			'replies' => false,
			'status' => false,
		);

		foreach ( $events_raw as $event ) {
			$event = sanitize_key( wp_unslash( $event ) );
			if ( in_array( $event, $allowed_events, true ) ) {
				$events[ $event ] = true;
			}
		}

		$preferences = array(
			'enabled' => $enabled,
			'events' => $events,
			'updated' => current_time( 'mysql' ),
		);

		update_user_meta( $user_id, '_wpscrm_push_preferences', $preferences );

		wp_send_json_success( array(
			'message' => __( 'Push-Einstellungen gespeichert', 'cpsmartcrm' ),
			'preferences' => $preferences,
		) );
	}

	/**
	 * Get user push preferences
	 *
	 * @param int $user_id
	 * @return array
	 */
	public static function get_user_push_preferences( $user_id ) {
		$defaults = array(
			'enabled' => true,
			'events' => array(
				'assignments' => true,
				'replies' => true,
				'status' => true,
			),
		);

		$prefs = get_user_meta( $user_id, '_wpscrm_push_preferences', true );
		if ( ! is_array( $prefs ) ) {
			return $defaults;
		}

		$prefs = wp_parse_args( $prefs, $defaults );
		$prefs['events'] = wp_parse_args( is_array( $prefs['events'] ) ? $prefs['events'] : array(), $defaults['events'] );

		return $prefs;
	}

	/**
	 * Check if notification is allowed by user preferences
	 *
	 * @param int $user_id
	 * @param array $notification
	 * @return bool
	 */
	private static function is_notification_allowed( $user_id, $notification ) {
		$prefs = self::get_user_push_preferences( $user_id );

		if ( empty( $prefs['enabled'] ) ) {
			return false;
		}

		$event = isset( $notification['event'] ) ? sanitize_key( $notification['event'] ) : '';
		if ( empty( $event ) ) {
			return true;
		}

		if ( isset( $prefs['events'][ $event ] ) && ! $prefs['events'][ $event ] ) {
			return false;
		}

		return true;
	}
	
	/**
	 * Send push notification to user
	 * 
	 * @param int $user_id - User ID
	 * @param array $notification - Notification data (title, body, icon, url)
	 */
	public static function send_push_notification( $user_id, $notification ) {
		if ( ! self::is_notification_allowed( $user_id, $notification ) ) {
			return false;
		}

		$subscriptions = get_user_meta( $user_id, '_wpscrm_push_subscriptions', true );
		
		if ( ! is_array( $subscriptions ) || empty( $subscriptions ) ) {
			return false;
		}
		
		$vapid_private_key = self::get_vapid_private_key();
		$vapid_public_key = self::get_vapid_public_key();
		
		if ( empty( $vapid_private_key ) || empty( $vapid_public_key ) ) {
			return false;
		}
		
		// Web Push Library benötigt (wird später mit Composer hinzugefügt)
		// Für jetzt: Placeholder für API-Aufruf
		
		$sent_count = 0;
		
		foreach ( $subscriptions as $key => $sub_data ) {
			$subscription = $sub_data['subscription'];
			
			try {
				// TODO: Web Push API aufrufen
				// Minislot/web-push-php Library verwenden
				$sent_count++;
			} catch ( Exception $e ) {
				// Fehlerhafte Subscription entfernen
				unset( $subscriptions[ $key ] );
			}
		}
		
		// Update Subscriptions (fehlerhafte entfernt)
		update_user_meta( $user_id, '_wpscrm_push_subscriptions', $subscriptions );
		
		return $sent_count;
	}
	
	/**
	 * Get PWA settings
	 */
	public static function get_pwa_settings() {
		$defaults = array(
			'app_name' => get_bloginfo( 'name' ) . ' ' . __( 'Dashboard', 'cpsmartcrm' ),
			'app_short_name' => __( 'CRM Dashboard', 'cpsmartcrm' ),
			'app_description' => __( 'Kundenmanagement und Support-System', 'cpsmartcrm' ),
			'theme_color' => '#1e3a8a',
			'background_color' => '#ffffff',
			'icon_512' => '',
			'icon_192' => '',
		);
		
		$settings = get_option( 'wpscrm_pwa_settings', array() );
		
		return wp_parse_args( $settings, $defaults );
	}
	
	/**
	 * Register admin settings
	 */
	public static function register_settings() {
		register_setting( 'wpscrm_pwa_settings', 'wpscrm_pwa_settings', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
		) );
	}
	
	/**
	 * Sanitize settings
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();
		
		$sanitized['app_name'] = isset( $input['app_name'] ) ? sanitize_text_field( $input['app_name'] ) : '';
		$sanitized['app_short_name'] = isset( $input['app_short_name'] ) ? sanitize_text_field( $input['app_short_name'] ) : '';
		$sanitized['app_description'] = isset( $input['app_description'] ) ? sanitize_textarea_field( $input['app_description'] ) : '';
		$sanitized['theme_color'] = isset( $input['theme_color'] ) ? sanitize_hex_color( $input['theme_color'] ) : '#1e3a8a';
		$sanitized['background_color'] = isset( $input['background_color'] ) ? sanitize_hex_color( $input['background_color'] ) : '#ffffff';
		$sanitized['icon_512'] = isset( $input['icon_512'] ) ? esc_url_raw( $input['icon_512'] ) : '';
		$sanitized['icon_192'] = isset( $input['icon_192'] ) ? esc_url_raw( $input['icon_192'] ) : '';
		
		return $sanitized;
	}
	
	/**
	 * Get VAPID public key for push notifications
	 */
	private static function get_vapid_public_key() {
		$key = get_option( 'wpscrm_vapid_public_key' );
		
		if ( empty( $key ) ) {
			// Generate VAPID keys
			self::generate_vapid_keys();
			$key = get_option( 'wpscrm_vapid_public_key' );
		}
		
		return $key;
	}
	
	/**
	 * Get VAPID private key for push notifications
	 */
	private static function get_vapid_private_key() {
		return get_option( 'wpscrm_vapid_private_key' );
	}
	
	/**
	 * Generate VAPID keys (simplified - full implementation needs web-push library)
	 */
	private static function generate_vapid_keys() {
		// Placeholder: In Production mit web-push-php Library generieren
		// Für jetzt: Demo-Keys (NICHT für Production verwenden!)
		
		if ( ! get_option( 'wpscrm_vapid_public_key' ) ) {
			update_option( 'wpscrm_vapid_public_key', 'DEMO_PUBLIC_KEY_' . wp_generate_password( 64, false ) );
			update_option( 'wpscrm_vapid_private_key', 'DEMO_PRIVATE_KEY_' . wp_generate_password( 64, false ) );
		}
	}
	
}

// Initialize
add_action( 'init', array( 'WPsCRM_PWA_Manager', 'init' ) );
