<?php
/**
 * App Template Manager - Standalone App-Modus ohne WordPress-Theme
 * 
 * Wenn ?app=1 Parameter vorhanden ist, wird das normale Theme umgangen
 * und ein Clean-Template nur für Dashboard-Content geladen.
 * 
 * Features:
 * - Login-Screen für nicht eingeloggte User
 * - Role-Detection: Kunde vs. Agent
 * - Routing zu passendem Dashboard
 * - Clean HTML ohne Theme-Overhead
 * 
 * @package PS Smart CRM
 * @subpackage PWA
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPsCRM_App_Template {
	
	private static $is_app_mode = false;
	private static $current_view = 'dashboard';
	private static $user_role = null;
	
	/**
	 * Initialize App Template System
	 */
	public static function init() {
		// Prüfe ob App-Modus aktiv
		add_action( 'template_redirect', array( __CLASS__, 'detect_app_mode' ), 5 );
	}
	
	/**
	 * Detect if app mode (?app=1) is active
	 */
	public static function detect_app_mode() {
		if ( isset( $_GET['app'] ) && $_GET['app'] == '1' ) {
			self::$is_app_mode = true;
			
			// Verhindere Theme-Laden
			add_filter( 'template_include', array( __CLASS__, 'load_app_template' ), 999 );
			
			// Remove admin bar
			show_admin_bar( false );
			
			// Bestimme View
			self::determine_view();
		}
	}
	
	/**
	 * Determine which view to show
	 */
	private static function determine_view() {
		if ( ! is_user_logged_in() ) {
			self::$current_view = 'login';
			return;
		}
		
		$user_id = get_current_user_id();
		
		// Bestimme Rolle
		if ( WPsCRM_Check::isAgent( $user_id ) ) {
			self::$user_role = 'agent';
		} else {
			// Prüfe ob User ein Kunde ist
			global $wpdb;
			$kunde_table = WPsCRM_TABLE . 'kunde';
			$is_customer = $wpdb->get_var( $wpdb->prepare(
				"SELECT ID_kunde FROM $kunde_table WHERE user_id = %d AND eliminato = 0 LIMIT 1",
				$user_id
			) );
			
			if ( $is_customer ) {
				self::$user_role = 'customer';
			} else {
				// Weder Agent noch Kunde: Zugriff verweigern
				self::$current_view = 'access_denied';
				return;
			}
		}
		
		// Bestimme spezifische View aus Query-Parameter
		if ( isset( $_GET['view'] ) ) {
			self::$current_view = sanitize_key( $_GET['view'] );
		} else {
			self::$current_view = 'dashboard';
		}
	}
	
	/**
	 * Load App Template
	 */
	public static function load_app_template( $template ) {
		if ( ! self::$is_app_mode ) {
			return $template;
		}
		
		// Hole Settings
		$settings = WPsCRM_PWA_Manager::get_pwa_settings();
		
		// Starte Output Buffer
		ob_start();
		
		// Header
		self::render_header( $settings );
		
		// Content basierend auf View
		switch ( self::$current_view ) {
			case 'login':
				self::render_login();
				break;
			case 'access_denied':
				self::render_access_denied();
				break;
			case 'dashboard':
				self::render_dashboard();
				break;
			case 'support':
				self::render_support();
				break;
			case 'faq':
				self::render_faq();
				break;
			default:
				self::render_dashboard();
		}
		
		// Footer
		self::render_footer( $settings );
		
		$output = ob_get_clean();
		echo $output;
		
		exit; // Wichtig: Verhindere weitere WordPress-Verarbeitung
	}
	
	/**
	 * Render HTML Header
	 */
	private static function render_header( $settings ) {
		$title = $settings['app_name'];
		$theme_color = $settings['theme_color'];
		$plugin_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );
		
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
			<meta name="theme-color" content="<?php echo esc_attr( $theme_color ); ?>">
			<title><?php echo esc_html( $title ); ?></title>
			
			<?php wp_head(); ?>
			
			<link rel="stylesheet" href="<?php echo esc_url( $plugin_url . 'css/smartcrm.css' ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( $plugin_url . 'assets/css/app-mode.css' ); ?>">
			
			<style>
				:root {
					--app-theme-color: <?php echo esc_attr( $theme_color ); ?>;
					--app-bg-color: <?php echo esc_attr( $settings['background_color'] ); ?>;
				}
			</style>
		</head>
		<body class="wpscrm-app-mode wpscrm-<?php echo esc_attr( self::$current_view ); ?>-view <?php echo self::$user_role ? 'wpscrm-role-' . esc_attr( self::$user_role ) : ''; ?>">
			<div id="wpscrm-app-wrapper">
		<?php
	}
	
	/**
	 * Render HTML Footer
	 */
	private static function render_footer( $settings ) {
		?>
			</div><!-- #wpscrm-app-wrapper -->
			
			<?php wp_footer(); ?>
			
			<script>
			// App-spezifische Init-Logik
			jQuery(document).ready(function($) {
				// Verhindere accidental Seiten-Reload
				if (window.history && window.history.pushState) {
					$(window).on('popstate', function() {
						// Custom back-button handling
					});
				}
				
				// Pull-to-refresh disable (optional)
				document.body.style.overscrollBehavior = 'contain';
			});
			</script>
		</body>
		</html>
		<?php
	}
	
	/**
	 * Render Login Screen
	 */
	private static function render_login() {
		$settings = WPsCRM_PWA_Manager::get_pwa_settings();
		$plugin_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );
		$logo_url = ! empty( $settings['icon_512'] ) ? $settings['icon_512'] : $plugin_url . 'assets/img/icon-512.png';
		
		?>
		<div class="wpscrm-login-screen">
			<div class="wpscrm-login-container">
				<div class="wpscrm-login-logo">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $settings['app_name'] ); ?>">
				</div>
				
				<h1><?php echo esc_html( $settings['app_name'] ); ?></h1>
				<p class="wpscrm-login-subtitle"><?php echo esc_html( $settings['app_description'] ); ?></p>
				
				<div class="wpscrm-login-form">
					<?php
					$redirect_url = add_query_arg( 'app', '1', home_url( '/' ) );
					wp_login_form( array(
						'redirect' => $redirect_url,
						'form_id' => 'wpscrm-loginform',
						'label_username' => __( 'Benutzername oder E-Mail', 'cpsmartcrm' ),
						'label_password' => __( 'Passwort', 'cpsmartcrm' ),
						'label_remember' => __( 'Angemeldet bleiben', 'cpsmartcrm' ),
						'label_log_in' => __( 'Anmelden', 'cpsmartcrm' ),
						'remember' => true,
					) );
					?>
					
					<div class="wpscrm-login-links">
						<a href="<?php echo esc_url( wp_lostpassword_url( $redirect_url ) ); ?>">
							<?php _e( 'Passwort vergessen?', 'cpsmartcrm' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Render Access Denied Screen
	 */
	private static function render_access_denied() {
		?>
		<div class="wpscrm-access-denied">
			<div class="wpscrm-message-container">
				<div class="wpscrm-icon-error">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
					</svg>
				</div>
				<h1><?php _e( 'Zugriff verweigert', 'cpsmartcrm' ); ?></h1>
				<p><?php _e( 'Sie haben keine Berechtigung, auf dieses Dashboard zuzugreifen.', 'cpsmartcrm' ); ?></p>
				<p>
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="btn btn-primary">
						<?php _e( 'Abmelden', 'cpsmartcrm' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Render Dashboard (role-based)
	 */
	private static function render_dashboard() {
		if ( self::$user_role === 'agent') {
			self::render_agent_dashboard();
		} else {
			self::render_customer_dashboard();
		}
	}
	
	/**
	 * Render Agent Dashboard
	 */
	private static function render_agent_dashboard() {
		$user_id = get_current_user_id();
		$user = wp_get_current_user();
		
		?>
		<div class="wpscrm-app-header">
			<div class="wpscrm-app-header-content">
				<h1><?php _e( 'Agent Dashboard', 'cpsmartcrm' ); ?></h1>
				<div class="wpscrm-user-info">
					<?php echo get_avatar( $user_id, 40 ); ?>
					<span><?php echo esc_html( $user->display_name ); ?></span>
				</div>
			</div>
		</div>
		
		<div class="wpscrm-app-content">
			<!-- Hier kommt das bestehende Agent-Dashboard -->
			<?php do_action( 'wpscrm_app_agent_dashboard', $user_id ); ?>
			
			<div class="wpscrm-placeholder">
				<p><?php _e( 'Agent-Dashboard wird hier geladen...', 'cpsmartcrm' ); ?></p>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=crmdash' ) ); ?>" class="btn btn-primary">
					<?php _e( 'Zum Admin-Dashboard', 'cpsmartcrm' ); ?>
				</a></p>
			</div>
		</div>
		
		<?php self::render_app_navigation( 'agent' ); ?>
		<?php
	}
	
	/**
	 * Render Customer Dashboard
	 */
	private static function render_customer_dashboard() {
		$user_id = get_current_user_id();
		$user = wp_get_current_user();
		
		// Hole Kunde-Daten
		global $wpdb;
		$kunde_table = WPsCRM_TABLE . 'kunde';
		$customer = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $kunde_table WHERE user_id = %d AND eliminato = 0 LIMIT 1",
			$user_id
		) );
		
		?>
		<div class="wpscrm-app-header">
			<div class="wpscrm-app-header-content">
				<h1><?php echo esc_html( sprintf( __( 'Willkommen, %s', 'cpsmartcrm' ), $customer->nome ?? $user->display_name ) ); ?></h1>
				<div class="wpscrm-user-info">
					<?php echo get_avatar( $user_id, 40 ); ?>
				</div>
			</div>
		</div>
		
		<div class="wpscrm-app-content">
			<?php
			// Support-Integration aktiv?
			if ( class_exists( 'WPsCRM_Support_Integration' ) && WPsCRM_Support_Integration::is_support_active() ) {
				self::render_support_widget( $user_id, $customer );
			}
			
			// FAQ verfügbar?
			if ( class_exists( 'PSource_Support_FAQ' ) ) {
				self::render_faq_widget();
			}
			
			// Weitere Customer-Widgets
			do_action( 'wpscrm_app_customer_dashboard', $user_id, $customer );
			?>
		</div>
		
		<?php self::render_app_navigation( 'customer' ); ?>
		<?php
	}
	
	/**
	 * Render Support Widget (Ticket-Übersicht)
	 */
	private static function render_support_widget( $user_id, $customer ) {
		?>
		<div class="wpscrm-widget wpscrm-support-widget">
			<div class="wpscrm-widget-header">
				<h2><?php _e( 'Meine Support-Tickets', 'cpsmartcrm' ); ?></h2>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support', 'action' => 'new' ), home_url( '/' ) ) ); ?>" class="btn btn-primary btn-sm">
					<?php _e( 'Neues Ticket', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<div class="wpscrm-widget-content">
				<?php
				// Hole User-Tickets
				$tickets = psource_support_get_tickets( array(
					'user_id' => $user_id,
					'orderby' => 'ticket_updated',
					'order' => 'DESC',
					'limit' => 5,
				) );
				
				if ( ! empty( $tickets ) ) {
					echo '<div class="wpscrm-ticket-list">';
					foreach ( $tickets as $ticket ) {
						$status_class = 'status-' . $ticket->ticket_status;
						$status_label = psource_support_get_ticket_status_name( $ticket->ticket_status );
						
						?>
						<div class="wpscrm-ticket-item">
							<div class="wpscrm-ticket-status <?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( $status_label ); ?>
							</div>
							<div class="wpscrm-ticket-content">
								<h3><a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support', 'ticket_id' => $ticket->ticket_id ), home_url( '/' ) ) ); ?>">
									<?php echo esc_html( $ticket->title ); ?>
								</a></h3>
								<div class="wpscrm-ticket-meta">
									<span><?php echo esc_html( sprintf( __( 'Ticket #%d', 'cpsmartcrm' ), $ticket->ticket_id ) ); ?></span>
									<span><?php echo esc_html( human_time_diff( strtotime( $ticket->ticket_updated ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'ago', 'cpsmartcrm' ); ?></span>
								</div>
							</div>
						</div>
						<?php
					}
					echo '</div>';
				} else {
					echo '<p class="wpscrm-no-items">' . __( 'Keine Tickets vorhanden', 'cpsmartcrm' ) . '</p>';
				}
				?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Render FAQ Widget
	 */
	private static function render_faq_widget() {
		?>
		<div class="wpscrm-widget wpscrm-faq-widget">
			<div class="wpscrm-widget-header">
				<h2><?php _e( 'Häufig gestellte Fragen', 'cpsmartcrm' ); ?></h2>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'faq' ), home_url( '/' ) ) ); ?>" class="btn btn-secondary btn-sm">
					<?php _e( 'Alle anzeigen', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<div class="wpscrm-widget-content">
				<p class="wpscrm-placeholder"><?php _e( 'FAQ-Artikel werden hier angezeigt...', 'cpsmartcrm' ); ?></p>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Render App Navigation
	 */
	private static function render_app_navigation( $role ) {
		?>
		<nav class="wpscrm-app-nav">
			<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'dashboard' ), home_url( '/' ) ) ); ?>" class="wpscrm-nav-item <?php echo self::$current_view === 'dashboard' ? 'active' : ''; ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
					<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
				</svg>
				<span><?php _e( 'Dashboard', 'cpsmartcrm' ); ?></span>
			</a>
			
			<?php if ( $role === 'customer' && class_exists( 'WPsCRM_Support_Integration' ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) ) ); ?>" class="wpscrm-nav-item <?php echo self::$current_view === 'support' ? 'active' : ''; ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
						<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
					</svg>
					<span><?php _e( 'Support', 'cpsmartcrm' ); ?></span>
				</a>
			<?php endif; ?>
			
			<?php if ( $role === 'customer' && class_exists( 'PSource_Support_FAQ' ) ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'faq' ), home_url( '/' ) ) ); ?>" class="wpscrm-nav-item <?php echo self::$current_view === 'faq' ? 'active' : ''; ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
						<path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
					</svg>
					<span><?php _e( 'FAQ', 'cpsmartcrm' ); ?></span>
				</a>
			<?php endif; ?>
			
			<a href="<?php echo esc_url( wp_logout_url( add_query_arg( 'app', '1', home_url( '/' ) ) ) ); ?>" class="wpscrm-nav-item">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
					<path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
				</svg>
				<span><?php _e( 'Abmelden', 'cpsmartcrm' ); ?></span>
			</a>
		</nav>
		<?php
	}
	
	/**
	 * Render Support View (Full Page)
	 */
	private static function render_support() {
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		$user_id = get_current_user_id();
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		$ticket_id = isset( $_GET['ticket_id'] ) ? absint( $_GET['ticket_id'] ) : 0;
		
		?>
		<div class="wpscrm-app-page wpscrm-support-page">
			<?php
			if ( $action === 'new' ) {
				self::render_new_ticket_form( $user_id );
			} elseif ( $ticket_id > 0 ) {
				self::render_ticket_detail( $ticket_id, $user_id );
			} else {
				self::render_tickets_list( $user_id );
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Render Tickets List
	 */
	private static function render_tickets_list( $user_id ) {
		?>
		<div class="wpscrm-page-header">
			<h1><?php _e( 'Meine Support-Tickets', 'cpsmartcrm' ); ?></h1>
			<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support', 'action' => 'new' ), home_url( '/' ) ) ); ?>" class="btn btn-primary">
				<?php _e( '+ Neues Ticket', 'cpsmartcrm' ); ?>
			</a>
		</div>
		
		<?php
		// Filter
		$status_filter = isset( $_GET['status'] ) ? absint( $_GET['status'] ) : -1;
		$base_url = add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) );
		?>
		<div class="wpscrm-ticket-filters">
			<a href="<?php echo esc_url( $base_url ); ?>" class="filter-btn <?php echo $status_filter === -1 ? 'active' : ''; ?>">
				<?php _e( 'Alle', 'cpsmartcrm' ); ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'status', '1', $base_url ) ); ?>" class="filter-btn <?php echo $status_filter === 1 ? 'active' : ''; ?>">
				<?php _e( 'Offen', 'cpsmartcrm' ); ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'status', '2', $base_url ) ); ?>" class="filter-btn <?php echo $status_filter === 2 ? 'active' : ''; ?>">
				<?php _e( 'In Bearbeitung', 'cpsmartcrm' ); ?>
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'status', '4', $base_url ) ); ?>" class="filter-btn <?php echo $status_filter === 4 ? 'active' : ''; ?>">
				<?php _e( 'Gelöst', 'cpsmartcrm' ); ?>
			</a>
		</div>
		
		<?php
		// Hole Tickets
		$args = array(
			'user_id' => $user_id,
			'orderby' => 'ticket_updated',
			'order' => 'DESC',
		);
		
		if ( $status_filter >= 0 ) {
			$args['ticket_status'] = $status_filter;
		}
		
		$tickets = function_exists( 'psource_support_get_tickets' ) ? psource_support_get_tickets( $args ) : array();
		
		if ( ! empty( $tickets ) ) {
			echo '<div class="wpscrm-tickets-grid">';
			foreach ( $tickets as $ticket ) {
				$status_class = 'status-' . $ticket->ticket_status;
				$status_label = function_exists( 'psource_support_get_ticket_status_name' ) 
					? psource_support_get_ticket_status_name( $ticket->ticket_status ) 
					: 'Status ' . $ticket->ticket_status;
				
				$ticket_url = add_query_arg( array( 'app' => '1', 'view' => 'support', 'ticket_id' => $ticket->ticket_id ), home_url( '/' ) );
				
				// Anzahl ungelesener Antworten
				$unread_count = 0; // TODO: Implementieren
				
				?>
				<div class="wpscrm-ticket-card">
					<div class="wpscrm-ticket-card-header">
						<span class="wpscrm-ticket-badge <?php echo esc_attr( $status_class ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
						<span class="wpscrm-ticket-id">#<?php echo esc_html( $ticket->ticket_id ); ?></span>
					</div>
					<h3><a href="<?php echo esc_url( $ticket_url ); ?>">
						<?php echo esc_html( $ticket->title ); ?>
					</a></h3>
					<div class="wpscrm-ticket-card-meta">
						<span><?php echo esc_html( $ticket->get_category_name() ?: __( 'Keine Kategorie', 'cpsmartcrm' ) ); ?></span>
						<span><?php echo esc_html( human_time_diff( strtotime( $ticket->ticket_updated ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'ago', 'cpsmartcrm' ); ?></span>
					</div>
					<?php if ( $unread_count > 0 ) : ?>
						<div class="wpscrm-ticket-unread">
							<?php echo esc_html( sprintf( _n( '%d neue Antwort', '%d neue Antworten', $unread_count, 'cpsmartcrm' ), $unread_count ) ); ?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
			echo '</div>';
		} else {
			?>
			<div class="wpscrm-empty-state">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
					<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
				</svg>
				<h3><?php _e( 'Noch keine Tickets', 'cpsmartcrm' ); ?></h3>
				<p><?php _e( 'Erstellen Sie Ihr erstes Support-Ticket', 'cpsmartcrm' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support', 'action' => 'new' ), home_url( '/' ) ) ); ?>" class="btn btn-primary">
					<?php _e( 'Neues Ticket erstellen', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<?php
		}
	}
	
	/**
	 * Render Ticket Detail mit Konversation
	 */
	private static function render_ticket_detail( $ticket_id, $user_id ) {
		$ticket = function_exists( 'psource_support_get_ticket' ) ? psource_support_get_ticket( $ticket_id ) : false;
		
		if ( ! $ticket || ( $ticket->user_id != $user_id && ! WPsCRM_Check::isAgent( $user_id ) ) ) {
			?>
			<div class="wpscrm-error">
				<p><?php _e( 'Ticket nicht gefunden oder Sie haben keine Berechtigung.', 'cpsmartcrm' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) ) ); ?>" class="btn btn-secondary">
					<?php _e( 'Zurück zur Übersicht', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<?php
			return;
		}
		
		$status_label = function_exists( 'psource_support_get_ticket_status_name' ) 
			? psource_support_get_ticket_status_name( $ticket->ticket_status ) 
			: 'Status ' . $ticket->ticket_status;
		
		?>
		<div class="wpscrm-page-header">
			<div class="wpscrm-back-btn">
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) ) ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
						<path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
					</svg>
					<?php _e( 'Zurück', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<h1><?php _e( 'Ticket', 'cpsmartcrm' ); ?> #<?php echo esc_html( $ticket_id ); ?></h1>
		</div>
		
		<div class="wpscrm-ticket-detail">
			<div class="wpscrm-ticket-detail-header">
				<h2><?php echo esc_html( $ticket->title ); ?></h2>
				<span class="wpscrm-ticket-badge status-<?php echo esc_attr( $ticket->ticket_status ); ?>">
					<?php echo esc_html( $status_label ); ?>
				</span>
			</div>
			
			<div class="wpscrm-ticket-detail-meta">
				<div class="meta-item">
					<strong><?php _e( 'Kategorie:', 'cpsmartcrm' ); ?></strong>
					<?php echo esc_html( $ticket->get_category_name() ?: __( 'Keine Kategorie', 'cpsmartcrm' ) ); ?>
				</div>
				<div class="meta-item">
					<strong><?php _e( 'Erstellt:', 'cpsmartcrm' ); ?></strong>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ticket->ticket_opened ) ) ); ?>
				</div>
			</div>
			
			<!-- Ticket-Konversation -->
			<div class="wpscrm-ticket-conversation">
				<?php
				// Hole Ticket-Antworten
				$replies = function_exists( 'psource_support_get_ticket_replies' ) 
					? psource_support_get_ticket_replies( $ticket_id ) 
					: array();
				
				// Erste Nachricht (Ticket-Text)
				?>
				<div class="wpscrm-message wpscrm-message-customer">
					<div class="wpscrm-message-avatar">
						<?php echo get_avatar( $ticket->user_id, 40 ); ?>
					</div>
					<div class="wpscrm-message-content">
						<div class="wpscrm-message-header">
							<strong><?php echo esc_html( get_userdata( $ticket->user_id )->display_name ); ?></strong>
							<span class="wpscrm-message-time">
								<?php echo esc_html( human_time_diff( strtotime( $ticket->ticket_opened ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'ago', 'cpsmartcrm' ); ?>
							</span>
						</div>
						<div class="wpscrm-message-body">
							<?php echo wp_kses_post( wpautop( $ticket->message ) ); ?>
						</div>
					</div>
				</div>
				
				<!-- Antworten -->
				<?php foreach ( $replies as $reply ) :
					$is_agent = WPsCRM_Check::isAgent( $reply->author_id );
					$message_class = $is_agent ? 'wpscrm-message-agent' : 'wpscrm-message-customer';
				?>
					<div class="wpscrm-message <?php echo esc_attr( $message_class ); ?>">
						<div class="wpscrm-message-avatar">
							<?php echo get_avatar( $reply->author_id, 40 ); ?>
						</div>
						<div class="wpscrm-message-content">
							<div class="wpscrm-message-header">
								<strong><?php echo esc_html( get_userdata( $reply->author_id )->display_name ); ?></strong>
								<?php if ( $is_agent ) : ?>
									<span class="wpscrm-agent-badge"><?php _e( 'Agent', 'cpsmartcrm' ); ?></span>
								<?php endif; ?>
								<span class="wpscrm-message-time">
									<?php echo esc_html( human_time_diff( strtotime( $reply->reply_date ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'ago', 'cpsmartcrm' ); ?>
								</span>
							</div>
							<div class="wpscrm-message-body">
								<?php echo wp_kses_post( wpautop( $reply->reply_body ) ); ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			
			<!-- Antwort-Formular -->
			<?php if ( in_array( $ticket->ticket_status, array( 0, 1, 2, 3 ) ) ) : // Nicht für gelöste/geschlossene Tickets ?>
				<div class="wpscrm-reply-form">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="wpscrm-ajax-form" id="wpscrm-ticket-reply-form">
						<input type="hidden" name="action" value="wpscrm_add_ticket_reply">
						<input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket_id ); ?>">
						<?php wp_nonce_field( 'wpscrm_ticket_reply', 'nonce' ); ?>
						
						<textarea name="reply_body" rows="4" placeholder="<?php esc_attr_e( 'Ihre Antwort...', 'cpsmartcrm' ); ?>" required></textarea>
						
						<div class="wpscrm-form-actions">
							<button type="submit" class="btn btn-primary">
								<?php _e( 'Antworten', 'cpsmartcrm' ); ?>
							</button>
						</div>
					</form>
				</div>
				<script>
				jQuery(document).ready(function($) {
					$('#wpscrm-ticket-reply-form').on('submit', function(e) {
						e.preventDefault();
						
						var $form = $(this);
						var $submit = $form.find('[type="submit"]');
						
						$submit.prop('disabled', true).text('<?php _e( 'Wird gesendet...', 'cpsmartcrm' ); ?>');
						
						$.post($form.attr('action'), $form.serialize())
							.done(function(response) {
								if (response.success) {
									window.location.reload();
								} else {
									alert(response.data.message || '<?php _e( 'Fehler beim Senden', 'cpsmartcrm' ); ?>');
									$submit.prop('disabled', false).text('<?php _e( 'Antworten', 'cpsmartcrm' ); ?>');
								}
							})
							.fail(function() {
								alert('<?php _e( 'Fehler beim Senden', 'cpsmartcrm' ); ?>');
								$submit.prop('disabled', false).text('<?php _e( 'Antworten', 'cpsmartcrm' ); ?>');
							});
					});
				});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}
	
	/**
	 * Render New Ticket Form
	 */
	private static function render_new_ticket_form( $user_id ) {
		?>
		<div class="wpscrm-page-header">
			<div class="wpscrm-back-btn">
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) ) ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
						<path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
					</svg>
					<?php _e( 'Zurück', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<h1><?php _e( 'Neues Support-Ticket', 'cpsmartcrm' ); ?></h1>
		</div>
		
		<div class="wpscrm-form-container">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" class="wpscrm-ajax-form" id="wpscrm-new-ticket-form">
				<input type="hidden" name="action" value="wpscrm_create_ticket">
				<?php wp_nonce_field( 'wpscrm_create_ticket', 'nonce' ); ?>
				
				<div class="wpscrm-form-field">
					<label for="ticket_title"><?php _e( 'Betreff', 'cpsmartcrm' ); ?> *</label>
					<input type="text" id="ticket_title" name="title" required maxlength="200">
				</div>
				
				<div class="wpscrm-form-field">
					<label for="ticket_category"><?php _e( 'Kategorie', 'cpsmartcrm' ); ?></label>
					<select id="ticket_category" name="category_id">
						<option value=""><?php _e( '-- Wählen --', 'cpsmartcrm' ); ?></option>
						<?php
						if ( function_exists( 'psource_support_get_ticket_categories' ) ) {
							$categories = psource_support_get_ticket_categories();
							foreach ( $categories as $cat ) {
								echo '<option value="' . esc_attr( $cat->ticket_category_id ) . '">' . esc_html( $cat->ticket_category_name ) . '</option>';
							}
						}
						?>
					</select>
				</div>
				
				<div class="wpscrm-form-field">
					<label for="ticket_priority"><?php _e( 'Priorität', 'cpsmartcrm' ); ?></label>
					<select id="ticket_priority" name="ticket_priority">
						<option value="1"><?php _e( 'Normal', 'cpsmartcrm' ); ?></option>
						<option value="0"><?php _e( 'Niedrig', 'cpsmartcrm' ); ?></option>
						<option value="2"><?php _e( 'Hoch', 'cpsmartcrm' ); ?></option>
						<option value="3"><?php _e( 'Kritisch', 'cpsmartcrm' ); ?></option>
					</select>
				</div>
				
				<div class="wpscrm-form-field">
					<label for="ticket_message"><?php _e( 'Nachricht', 'cpsmartcrm' ); ?> *</label>
					<textarea id="ticket_message" name="message" rows="8" required></textarea>
				</div>
				
				<div class="wpscrm-form-actions">
					<button type="submit" class="btn btn-primary">
						<?php _e( 'Ticket erstellen', 'cpsmartcrm' ); ?>
					</button>
					<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) ) ); ?>" class="btn btn-secondary">
						<?php _e( 'Abbrechen', 'cpsmartcrm' ); ?>
					</a>
				</div>
			</form>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			$('#wpscrm-new-ticket-form').on('submit', function(e) {
				e.preventDefault();
				
				var $form = $(this);
				var $submit = $form.find('[type="submit"]');
				
				$submit.prop('disabled', true).text('<?php _e( 'Wird erstellt...', 'cpsmartcrm' ); ?>');
				
				$.post($form.attr('action'), $form.serialize())
					.done(function(response) {
						if (response.success && response.data.ticket_id) {
							// Redirect zum neuen Ticket
							window.location.href = '<?php echo esc_js( add_query_arg( array( 'app' => '1', 'view' => 'support' ), home_url( '/' ) ) ); ?>&ticket_id=' + response.data.ticket_id;
						} else {
							alert(response.data.message || '<?php _e( 'Fehler beim Erstellen', 'cpsmartcrm' ); ?>');
							$submit.prop('disabled', false).text('<?php _e( 'Ticket erstellen', 'cpsmartcrm' ); ?>');
						}
					})
					.fail(function() {
						alert('<?php _e( 'Fehler beim Erstellen', 'cpsmartcrm' ); ?>');
						$submit.prop('disabled', false).text('<?php _e( 'Ticket erstellen', 'cpsmartcrm' ); ?>');
					});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Render FAQ View (Full Page)
	 */
	private static function render_faq() {
		$faq_id = isset( $_GET['faq_id'] ) ? absint( $_GET['faq_id'] ) : 0;
		
		?>
		<div class="wpscrm-app-page wpscrm-faq-page">
			<?php
			if ( $faq_id > 0 ) {
				self::render_faq_single( $faq_id );
			} else {
				self::render_faq_list();
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Render FAQ List
	 */
	private static function render_faq_list() {
		?>
		<div class="wpscrm-page-header">
			<h1><?php _e( 'Häufig gestellte Fragen', 'cpsmartcrm' ); ?></h1>
		</div>
		
		<!-- Suchfeld -->
		<div class="wpscrm-faq-search">
			<form method="get" action="">
				<input type="hidden" name="app" value="1">
				<input type="hidden" name="view" value="faq">
				<input 
					type="search" 
					name="s" 
					placeholder="<?php esc_attr_e( 'FAQ durchsuchen...', 'cpsmartcrm' ); ?>" 
					value="<?php echo esc_attr( isset( $_GET['s'] ) ? $_GET['s'] : '' ); ?>"
					class="wpscrm-search-input"
				>
				<button type="submit" class="btn btn-primary">
					<?php _e( 'Suchen', 'cpsmartcrm' ); ?>
				</button>
			</form>
		</div>
		
		<?php
		// FAQ Kategorien holen
		$categories = function_exists( 'psource_support_get_faq_categories' ) ? psource_support_get_faq_categories() : array();
		$selected_cat = isset( $_GET['cat_id'] ) ? absint( $_GET['cat_id'] ) : 0;
		$search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		
		// Kategorie-Filter
		if ( ! empty( $categories ) ) {
			$base_url = add_query_arg( array( 'app' => '1', 'view' => 'faq' ), home_url( '/' ) );
			?>
			<div class="wpscrm-faq-categories">
				<a href="<?php echo esc_url( $base_url ); ?>" class="faq-cat-btn <?php echo $selected_cat === 0 ? 'active' : ''; ?>">
					<?php _e( 'Alle', 'cpsmartcrm' ); ?>
				</a>
				<?php foreach ( $categories as $cat ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'cat_id', $cat->cat_id, $base_url ) ); ?>" class="faq-cat-btn <?php echo $selected_cat === absint( $cat->cat_id ) ? 'active' : ''; ?>">
						<?php echo esc_html( $cat->cat_name ); ?> 
						<span class="faq-count">(<?php echo absint( $cat->qcount ); ?>)</span>
					</a>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		// FAQs holen
		$faq_args = array(
			'per_page' => 20,
			'orderby' => 'faq_id',
			'order' => 'DESC',
		);
		
		if ( $selected_cat > 0 ) {
			$faq_args['category'] = $selected_cat;
		}
		
		if ( $search_term ) {
			$faq_args['s'] = $search_term;
		}
		
		$faqs = function_exists( 'psource_support_get_faqs' ) ? psource_support_get_faqs( $faq_args ) : array();
		
		if ( ! empty( $faqs ) ) {
			?>
			<div class="wpscrm-faq-list">
				<?php foreach ( $faqs as $faq ) : 
					$faq_url = add_query_arg( array( 'app' => '1', 'view' => 'faq', 'faq_id' => $faq->faq_id ), home_url( '/' ) );
				?>
					<div class="wpscrm-faq-item">
						<h3 class="wpscrm-faq-question">
							<a href="<?php echo esc_url( $faq_url ); ?>">
								<?php echo esc_html( $faq->question ); ?>
							</a>
						</h3>
						<div class="wpscrm-faq-meta">
							<?php if ( $faq->help_views > 0 ) : ?>
								<span class="faq-views">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
										<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
									</svg>
									<?php echo absint( $faq->help_views ); ?> <?php _e( 'Aufrufe', 'cpsmartcrm' ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
		} else {
			?>
			<div class="wpscrm-empty-state">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="64" height="64">
					<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
				</svg>
				<h3><?php _e( 'Keine FAQs gefunden', 'cpsmartcrm' ); ?></h3>
				<?php if ( $search_term ) : ?>
					<p><?php _e( 'Versuchen Sie es mit anderen Suchbegriffen', 'cpsmartcrm' ); ?></p>
				<?php else : ?>
					<p><?php _e( 'Noch keine FAQs verfügbar', 'cpsmartcrm' ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}
	}
	
	/**
	 * Render FAQ Single
	 */
	private static function render_faq_single( $faq_id ) {
		$faq = function_exists( 'psource_support_get_faq' ) ? psource_support_get_faq( $faq_id ) : false;
		
		if ( ! $faq ) {
			?>
			<div class="wpscrm-error">
				<p><?php _e( 'FAQ nicht gefunden', 'cpsmartcrm' ); ?></p>
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'faq' ), home_url( '/' ) ) ); ?>" class="btn btn-secondary">
					<?php _e( 'Zurück zur Übersicht', 'cpsmartcrm' ); ?>
				</a>
			</div>
			<?php
			return;
		}
		
		// Update help views count
		if ( function_exists( 'psource_support_update_faq' ) ) {
			psource_support_update_faq( $faq_id, array( 'help_views' => $faq->help_views + 1 ) );
		}
		
		?>
		<div class="wpscrm-page-header">
			<div class="wpscrm-back-btn">
				<a href="<?php echo esc_url( add_query_arg( array( 'app' => '1', 'view' => 'faq' ), home_url( '/' ) ) ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
						<path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
					</svg>
					<?php _e( 'Zurück', 'cpsmartcrm' ); ?>
				</a>
			</div>
		</div>
		
		<div class="wpscrm-faq-single">
			<h1 class="wpscrm-faq-title"><?php echo esc_html( $faq->question ); ?></h1>
			
			<div class="wpscrm-faq-content">
				<?php echo wp_kses_post( wpautop( $faq->answer ) ); ?>
			</div>
			
			<!-- War diese Antwort hilfreich? -->
			<div class="wpscrm-faq-helpful">
				<h4><?php _e( 'War diese Antwort hilfreich?', 'cpsmartcrm' ); ?></h4>
				<div class="wpscrm-faq-vote-buttons">
					<button type="button" class="btn-vote btn-vote-yes" data-faq-id="<?php echo esc_attr( $faq_id ); ?>" data-vote="yes">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
							<path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/>
						</svg>
						<?php _e( 'Ja', 'cpsmartcrm' ); ?>
						<span class="vote-count"><?php echo absint( $faq->help_yes ); ?></span>
					</button>
					<button type="button" class="btn-vote btn-vote-no" data-faq-id="<?php echo esc_attr( $faq_id ); ?>" data-vote="no">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
							<path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"/>
						</svg>
						<?php _e( 'Nein', 'cpsmartcrm' ); ?>
						<span class="vote-count"><?php echo absint( $faq->help_no ); ?></span>
					</button>
				</div>
				<div class="wpscrm-faq-vote-message" style="display:none;"></div>
			</div>
		</div>
		
		<script>
		jQuery(document).ready(function($) {
			$('.btn-vote').on('click', function() {
				var $btn = $(this);
				var faqId = $btn.data('faq-id');
				var vote = $btn.data('vote');
				
				$btn.prop('disabled', true);
				
				$.post('<?php echo admin_url( "admin-ajax.php" ); ?>', {
					action: 'wpscrm_vote_faq',
					faq_id: faqId,
					vote: vote,
					nonce: '<?php echo wp_create_nonce( "wpscrm_faq_vote" ); ?>'
				})
				.done(function(response) {
					if (response.success) {
						// Update Zähler
						if (vote === 'yes') {
							$('.btn-vote-yes .vote-count').text(response.data.yes_count);
						} else {
							$('.btn-vote-no .vote-count').text(response.data.no_count);
						}
						
						// Danke-Nachricht
						$('.wpscrm-faq-vote-message')
							.text('<?php _e( "Vielen Dank für Ihr Feedback!", "cpsmartcrm" ); ?>')
							.css('color', '#10b981')
							.fadeIn();
						
						// Buttons deaktivieren
						$('.btn-vote').prop('disabled', true);
					}
				})
				.fail(function() {
					$btn.prop('disabled', false);
				});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Check if currently in app mode
	 */
	public static function is_app_mode() {
		return self::$is_app_mode;
	}
	
	/**
	 * Get current user role
	 */
	public static function get_user_role() {
		return self::$user_role;
	}
	
}

// Initialize
add_action( 'init', array( 'WPsCRM_App_Template', 'init' ) );
