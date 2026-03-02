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
		if ( function_exists( 'register_nav_menu' ) ) {
			register_nav_menu( 'wpscrm_webapp_menu', __( 'WebApp Template Menü', 'cpsmartcrm' ) );
		}

		add_action( 'widgets_init', array( __CLASS__, 'register_widget_areas' ) );

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
		if ( self::is_agent_user( $user_id ) ) {
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
		} elseif ( is_singular() ) {
			self::$current_view = 'page';
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

		if ( is_user_logged_in() && ! in_array( self::$current_view, array( 'login', 'access_denied' ), true ) ) {
			self::render_global_app_header();
		}
		
		// Content basierend auf View
		switch ( self::$current_view ) {
			case 'login':
				self::render_login();
				break;
			case 'access_denied':
				self::render_access_denied();
				break;
				case 'page':
					self::render_page_container();
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
		$frontend_settings = self::get_webapp_frontend_settings();
		$header_bg = ! empty( $frontend_settings['webapp_header_bg_color'] ) ? $frontend_settings['webapp_header_bg_color'] : $theme_color;
		$header_text = ! empty( $frontend_settings['webapp_header_text_color'] ) ? $frontend_settings['webapp_header_text_color'] : '#ffffff';
		$content_bg = ! empty( $frontend_settings['webapp_content_bg_color'] ) ? $frontend_settings['webapp_content_bg_color'] : $settings['background_color'];
		$container_bg = ! empty( $frontend_settings['webapp_container_bg_color'] ) ? $frontend_settings['webapp_container_bg_color'] : '#ffffff';
		$container_width = absint( $frontend_settings['webapp_container_max_width'] );
		$container_width = $container_width > 0 ? $container_width : 1200;
		$container_padding = absint( $frontend_settings['webapp_container_padding'] );
		$container_padding = $container_padding > 0 ? $container_padding : 16;
		$module_radius = absint( $frontend_settings['webapp_module_radius'] );
		$module_radius = $module_radius >= 0 ? $module_radius : 8;
		$module_shadow = self::get_module_shadow_value( $frontend_settings['webapp_module_shadow'] );
		
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
					--app-bg-color: <?php echo esc_attr( $content_bg ); ?>;
					--app-header-bg: <?php echo esc_attr( $header_bg ); ?>;
					--app-header-text: <?php echo esc_attr( $header_text ); ?>;
					--app-container-bg: <?php echo esc_attr( $container_bg ); ?>;
					--app-container-max-width: <?php echo esc_attr( $container_width ); ?>px;
					--app-container-padding: <?php echo esc_attr( $container_padding ); ?>px;
					--app-module-radius: <?php echo esc_attr( $module_radius ); ?>px;
					--app-module-shadow: <?php echo esc_attr( $module_shadow ); ?>;
				}
			</style>
		</head>
		<body class="wpscrm-app-mode wpscrm-<?php echo esc_attr( self::$current_view ); ?>-view <?php echo self::$user_role ? 'wpscrm-role-' . esc_attr( self::$user_role ) : ''; ?>">
			<div id="wpscrm-app-wrapper">
		<?php
	}

	/**
	 * Render optional global app header bar
	 */
	private static function render_global_app_header() {
		$settings = self::get_webapp_frontend_settings();
		if ( empty( $settings['webapp_enable_custom_design'] ) || empty( $settings['webapp_header_enabled'] ) ) {
			return;
		}

		$app_title = ! empty( $settings['webapp_header_title'] ) ? $settings['webapp_header_title'] : get_bloginfo( 'name' );
		$logo = ! empty( $settings['webapp_header_logo'] ) ? $settings['webapp_header_logo'] : '';
		$show_logo = ! empty( $settings['webapp_header_show_logo'] ) && ! empty( $logo );

		echo '<div class="wpscrm-global-app-header" role="banner">';
		echo '<div class="wpscrm-global-app-header-inner">';
		echo '<div class="wpscrm-global-app-brand">';
		if ( $show_logo ) {
			echo '<img class="wpscrm-global-app-logo" src="' . esc_url( $logo ) . '" alt="' . esc_attr( $app_title ) . '">';
		}
		echo '<span class="wpscrm-global-app-title">' . esc_html( $app_title ) . '</span>';
		echo '</div>';

		if ( is_active_sidebar( 'wpscrm_webapp_header_widget' ) ) {
			echo '<div class="wpscrm-global-app-header-widgets">';
			dynamic_sidebar( 'wpscrm_webapp_header_widget' );
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';
	}
	
	/**
	 * Render HTML Footer
	 */
	private static function render_footer( $settings ) {
		$frontend_settings = self::get_webapp_frontend_settings();
		?>
			<?php if ( is_active_sidebar( 'wpscrm_webapp_after_content_widget' ) ) : ?>
				<div class="wpscrm-webapp-after-content-widgets">
					<?php dynamic_sidebar( 'wpscrm_webapp_after_content_widget' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $frontend_settings['webapp_show_footer_notice'] ) ) : ?>
				<footer class="wpscrm-webapp-footer-note">
					<?php echo wp_kses_post( wpautop( $frontend_settings['webapp_footer_notice'] ) ); ?>
				</footer>
			<?php endif; ?>

			<?php if ( is_active_sidebar( 'wpscrm_webapp_footer_widget' ) ) : ?>
				<div class="wpscrm-webapp-footer-widgets">
					<?php dynamic_sidebar( 'wpscrm_webapp_footer_widget' ); ?>
				</div>
			<?php endif; ?>

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

				// Halte interne Navigation im App-Container
				$(document).on('click', 'a[href]', function(e) {
					var $link = $(this);
					if ($link.is('[data-no-app]')) return;

					var href = $link.attr('href');
					if (!href || href.indexOf('#') === 0) return;
					if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0 || href.indexOf('javascript:') === 0) return;

					try {
						var url = new URL(href, window.location.origin);
						if (url.origin !== window.location.origin) return;
						if (url.pathname.indexOf('/wp-admin/') === 0 || url.pathname.indexOf('/wp-login.php') === 0) return;
						if (url.searchParams.get('app') === '1') return;

						url.searchParams.set('app', '1');
						e.preventDefault();
						window.location.href = url.toString();
					} catch (err) {
						// ignore invalid URLs
					}
				});
			});
			</script>
		</body>
		</html>
		<?php
	}

	/**
	 * Register widget areas for app template
	 */
	public static function register_widget_areas() {
		if ( ! function_exists( 'register_sidebar' ) ) {
			return;
		}

		register_sidebar( array(
			'name' => __( 'WebApp Header Widgets', 'cpsmartcrm' ),
			'id' => 'wpscrm_webapp_header_widget',
			'before_widget' => '<div class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'name' => __( 'WebApp Content Widgets', 'cpsmartcrm' ),
			'id' => 'wpscrm_webapp_after_content_widget',
			'before_widget' => '<div class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'name' => __( 'WebApp Footer Widgets', 'cpsmartcrm' ),
			'id' => 'wpscrm_webapp_footer_widget',
			'before_widget' => '<div class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );
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
		?>
		<?php self::render_desktop_app_controls( 'agent-dashboard' ); ?>
		<div class="wpscrm-app-content">
			<?php self::render_frontend_dashboard_module( 'agent-dashboard', 'crm_agent-dashboard' ); ?>
		</div>
		<?php
		self::render_app_navigation( 'agent' );
	}
	
	/**
	 * Render Customer Dashboard
	 */
	private static function render_customer_dashboard() {
		?>
		<?php self::render_desktop_app_controls( 'customer-portal' ); ?>
		<div class="wpscrm-app-content">
			<?php self::render_frontend_dashboard_module( 'customer-portal', 'crm_customer-portal' ); ?>
		</div>
		<?php
		self::render_app_navigation( 'customer' );
	}

	/**
	 * Render generic page content inside app container
	 */
	private static function render_page_container() {
		self::render_desktop_app_controls( self::$user_role === 'agent' ? 'agent-dashboard' : 'customer-portal' );

		$content_id = get_queried_object_id();
		$content_post = $content_id ? get_post( $content_id ) : null;

		echo '<div class="wpscrm-app-content">';
		if ( $content_post instanceof WP_Post && $content_post->post_status === 'publish' ) {
			global $post;
			$previous_post = $post;
			$post = $content_post;
			setup_postdata( $post );

			echo '<div class="wpscrm-dashboard-page-content">';
			echo apply_filters( 'the_content', $content_post->post_content );
			echo '</div>';

			wp_reset_postdata();
			$post = $previous_post;
		} else {
			echo '<div class="wpscrm-error"><p>' . esc_html__( 'Seiteninhalt konnte nicht geladen werden.', 'cpsmartcrm' ) . '</p></div>';
		}
		echo '</div>';

	}

	/**
	 * Render WordPress menu as app-level navigation
	 */
	private static function render_wp_app_menu( $inline = false ) {
		$frontend_settings = self::get_webapp_frontend_settings();
		if ( empty( $frontend_settings['webapp_show_top_menu'] ) ) {
			return;
		}

		$container_class = $inline ? 'wpscrm-app-topmenu wpscrm-app-topmenu-inline' : 'wpscrm-app-topmenu';
		$menu_class = $inline ? 'wpscrm-app-topmenu-list wpscrm-app-topmenu-list-inline' : 'wpscrm-app-topmenu-list';

		$menu_html = '';
		if ( has_nav_menu( 'wpscrm_webapp_menu' ) ) {
			$menu_html = wp_nav_menu( array(
				'theme_location' => 'wpscrm_webapp_menu',
				'container' => 'nav',
				'container_class' => $container_class,
				'menu_class' => $menu_class,
				'echo' => false,
				'fallback_cb' => false,
			) );
		}

		if ( empty( $menu_html ) ) {
			$menu_location = apply_filters( 'wpscrm_pwa_menu_location', 'primary' );
			$menu_html = wp_nav_menu( array(
				'theme_location' => $menu_location,
				'container' => 'nav',
				'container_class' => $container_class,
				'menu_class' => $menu_class,
				'echo' => false,
				'fallback_cb' => false,
			) );
		}

		if ( empty( $menu_html ) ) {
			$menus = wp_get_nav_menus();
			if ( ! empty( $menus ) && isset( $menus[0]->term_id ) ) {
				$menu_html = wp_nav_menu( array(
					'menu' => (int) $menus[0]->term_id,
					'container' => 'nav',
					'container_class' => $container_class,
					'menu_class' => $menu_class,
					'echo' => false,
					'fallback_cb' => false,
				) );
			}
		}

		if ( ! empty( $menu_html ) ) {
			echo $menu_html;
		}
	}

	/**
	 * Render Desktop Controls for App View / Push Settings
	 *
	 * @param string $module_id Frontend module id
	 */
	private static function render_desktop_app_controls( $module_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$frontend_settings = self::get_webapp_frontend_settings();
		$user_id = get_current_user_id();
		$web_url = self::get_dashboard_web_url( $module_id );
		$push_prefs = class_exists( 'WPsCRM_PWA_Manager' )
			? WPsCRM_PWA_Manager::get_user_push_preferences( $user_id )
			: array(
				'enabled' => true,
				'events' => array(
					'assignments' => true,
					'replies' => true,
					'status' => true,
				),
			);

		$nonce = wp_create_nonce( 'wpscrm_pwa' );
		$show_webview = self::is_visible_for_current_role( $frontend_settings['webapp_show_webview_roles'] );
		$show_push = self::is_visible_for_current_role( $frontend_settings['webapp_show_push_roles'] );

		if ( ! $show_webview && ! $show_push && empty( $frontend_settings['webapp_show_top_menu'] ) ) {
			return;
		}

		?>
		<div class="wpscrm-app-desktop-controls">
			<div class="wpscrm-app-desktop-controls-left">
				<?php if ( $show_webview ) : ?>
				<a class="btn btn-secondary btn-sm" href="<?php echo esc_url( $web_url ); ?>">
					<?php _e( 'Webansicht', 'cpsmartcrm' ); ?>
				</a>
				<?php endif; ?>
			</div>
			<div class="wpscrm-app-desktop-controls-center">
				<?php self::render_wp_app_menu( true ); ?>
			</div>
			<div class="wpscrm-app-desktop-controls-right">
			<?php if ( $show_push ) : ?>
			<form class="wpscrm-push-inline-form" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<label class="wpscrm-push-master">
					<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $push_prefs['enabled'] ) ); ?>>
					<span><?php _e( 'Push Notifications', 'cpsmartcrm' ); ?></span>
				</label>
				<div class="wpscrm-push-events">
					<label><input type="checkbox" name="events[]" value="assignments" <?php checked( ! empty( $push_prefs['events']['assignments'] ) ); ?>> <?php _e( 'Zuweisungen', 'cpsmartcrm' ); ?></label>
					<label><input type="checkbox" name="events[]" value="replies" <?php checked( ! empty( $push_prefs['events']['replies'] ) ); ?>> <?php _e( 'Antworten', 'cpsmartcrm' ); ?></label>
					<label><input type="checkbox" name="events[]" value="status" <?php checked( ! empty( $push_prefs['events']['status'] ) ); ?>> <?php _e( 'Status', 'cpsmartcrm' ); ?></label>
				</div>
				<button type="submit" class="btn btn-primary btn-sm"><?php _e( 'Speichern', 'cpsmartcrm' ); ?></button>
				<span class="wpscrm-push-inline-msg" aria-live="polite"></span>
			</form>
			<?php endif; ?>
			</div>
		</div>
		<script>
		jQuery(function($) {
			var $form = $('.wpscrm-push-inline-form').last();
			if (!$form.length) return;

			var $enabled = $form.find('input[name="enabled"]');
			var $events = $form.find('.wpscrm-push-events input[type="checkbox"]');
			var $msg = $form.find('.wpscrm-push-inline-msg');

			function toggleEvents() {
				$events.prop('disabled', !$enabled.is(':checked'));
			}
			toggleEvents();
			$enabled.on('change', toggleEvents);

			$form.on('submit', async function(e) {
				e.preventDefault();
				$msg.text('...');

				var enabled = $enabled.is(':checked');
				var events = [];
				$events.filter(':checked').each(function(){ events.push($(this).val()); });

				try {
					if (enabled && window.wpscrmPWA && typeof window.wpscrmPWA.subscribe === 'function') {
						await window.wpscrmPWA.subscribe();
					}
					if (!enabled && window.wpscrmPWA && typeof window.wpscrmPWA.unsubscribe === 'function') {
						await window.wpscrmPWA.unsubscribe();
					}

					$.post('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
						action: 'wpscrm_save_push_prefs',
						nonce: $form.data('nonce'),
						enabled: enabled ? 1 : 0,
						events: events
					}).done(function(resp) {
						if (resp && resp.success) {
							$msg.text('<?php echo esc_js( __( 'Gespeichert', 'cpsmartcrm' ) ); ?>');
						} else {
							$msg.text('<?php echo esc_js( __( 'Fehler beim Speichern', 'cpsmartcrm' ) ); ?>');
						}
					}).fail(function() {
						$msg.text('<?php echo esc_js( __( 'Fehler beim Speichern', 'cpsmartcrm' ) ); ?>');
					});
				} catch (err) {
					$msg.text('<?php echo esc_js( __( 'Push nicht verfügbar', 'cpsmartcrm' ) ); ?>');
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Get original web dashboard URL (non-app)
	 *
	 * @param string $module_id Frontend module id
	 * @return string
	 */
	private static function get_dashboard_web_url( $module_id ) {
		$page_url = '';

		if ( class_exists( 'WPsCRM_Frontend_Settings' ) && method_exists( 'WPsCRM_Frontend_Settings', 'get_page_url' ) ) {
			$page_url = WPsCRM_Frontend_Settings::get_page_url( $module_id );
		}

		if ( ! empty( $page_url ) ) {
			return $page_url;
		}

		return remove_query_arg( array( 'app', 'view', 'action', 'ticket_id', 'faq_id', 'status' ), home_url( add_query_arg( array() ) ) );
	}

	/**
	 * Render existing Frontend Dashboard module
	 *
	 * @param string $module_id Frontend module id
	 * @param string $shortcode_fallback Shortcode without brackets
	 */
	private static function render_frontend_dashboard_module( $module_id, $shortcode_fallback ) {
		$rendered = false;

		if ( class_exists( 'WPsCRM_Frontend_Settings' ) && method_exists( 'WPsCRM_Frontend_Settings', 'get_option' ) ) {
			$page_option_key = ( $module_id === 'agent-dashboard' ) ? 'agent_dashboard_page' : 'customer_portal_page';
			$page_id = absint( WPsCRM_Frontend_Settings::get_option( $page_option_key ) );

			if ( $page_id > 0 ) {
				$page = get_post( $page_id );
				if ( $page instanceof WP_Post && $page->post_status === 'publish' ) {
					global $post;
					$previous_post = $post;
					$post = $page;
					setup_postdata( $post );

					echo '<div class="wpscrm-dashboard-page-content">';
					echo apply_filters( 'the_content', $page->post_content );
					echo '</div>';

					wp_reset_postdata();
					$post = $previous_post;
					$rendered = true;
				}
			}
		}

		if ( ! $rendered && function_exists( 'wpscrm_get_frontend_manager' ) ) {
			$manager = wpscrm_get_frontend_manager();
			if ( $manager && method_exists( $manager, 'get_module' ) ) {
				$module = $manager->get_module( $module_id );
				if ( $module && method_exists( $module, 'render' ) ) {
					echo $module->render();
					$rendered = true;
				}
			}
		}

		if ( ! $rendered ) {
			$output = do_shortcode( '[' . $shortcode_fallback . ']' );
			if ( ! empty( trim( $output ) ) ) {
				echo $output;
				$rendered = true;
			}
		}

		if ( ! $rendered ) {
			echo '<div class="wpscrm-error"><p>' . esc_html__( 'Dashboard konnte nicht geladen werden.', 'cpsmartcrm' ) . '</p></div>';
		}
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
		$frontend_settings = self::get_webapp_frontend_settings();
		if ( ! self::is_visible_for_current_role( $frontend_settings['webapp_show_bottom_nav_roles'] ) ) {
			return;
		}

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
		
		if ( ! $ticket || ( $ticket->user_id != $user_id && ! self::is_agent_user( $user_id ) ) ) {
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
					$is_agent = self::is_agent_user( $reply->author_id );
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
	 * Safe agent detection helper
	 *
	 * @param int $user_id
	 * @return bool
	 */
	private static function is_agent_user( $user_id ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return false;
		}

		if ( class_exists( 'WPsCRM_Check' ) && method_exists( 'WPsCRM_Check', 'isAgent' ) ) {
			return (bool) WPsCRM_Check::isAgent( $user_id );
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User ) {
			return false;
		}

		$roles = (array) $user->roles;
		$agent_roles = (array) apply_filters( 'wpscrm_agent_role_slugs', array( 'agent', 'administrator', 'editor' ) );

		return ! empty( array_intersect( $roles, $agent_roles ) );
	}

	/**
	 * Get frontend webapp customization settings
	 *
	 * @return array
	 */
	private static function get_webapp_frontend_settings() {
		$defaults = array(
			'webapp_enable_custom_design' => 0,
			'webapp_header_enabled' => 0,
			'webapp_header_show_logo' => 1,
			'webapp_header_logo' => '',
			'webapp_header_title' => get_bloginfo( 'name' ),
			'webapp_header_bg_color' => '',
			'webapp_header_text_color' => '',
			'webapp_content_bg_color' => '',
			'webapp_container_bg_color' => '',
			'webapp_container_max_width' => 1200,
			'webapp_container_padding' => 16,
			'webapp_module_radius' => 8,
			'webapp_module_shadow' => 'soft',
			'webapp_show_top_menu' => 1,
			'webapp_show_webview_roles' => array( 'agent', 'customer' ),
			'webapp_show_push_roles' => array( 'agent', 'customer' ),
			'webapp_show_bottom_nav_roles' => array( 'agent', 'customer' ),
			'webapp_show_footer_notice' => 1,
			'webapp_footer_notice' => '© ' . gmdate( 'Y' ) . ' ' . get_bloginfo( 'name' ),
		);

		$settings = get_option( 'CRM_frontend_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$merged = wp_parse_args( $settings, $defaults );

		foreach ( array( 'webapp_show_webview_roles', 'webapp_show_push_roles', 'webapp_show_bottom_nav_roles' ) as $roles_key ) {
			if ( ! isset( $merged[ $roles_key ] ) || ! is_array( $merged[ $roles_key ] ) ) {
				$merged[ $roles_key ] = $defaults[ $roles_key ];
			}
		}

		return $merged;
	}

	/**
	 * Check if current role is visible in setting
	 *
	 * @param array $allowed_roles
	 * @return bool
	 */
	private static function is_visible_for_current_role( $allowed_roles ) {
		$allowed_roles = is_array( $allowed_roles ) ? array_map( 'sanitize_key', $allowed_roles ) : array();
		$current = self::$user_role ? self::$user_role : 'guest';

		return in_array( $current, $allowed_roles, true );
	}

	/**
	 * Get module/card shadow value
	 *
	 * @param string $shadow_key
	 * @return string
	 */
	private static function get_module_shadow_value( $shadow_key ) {
		switch ( sanitize_key( $shadow_key ) ) {
			case 'none':
				return 'none';
			case 'medium':
				return '0 6px 18px rgba(0,0,0,0.12)';
			case 'soft':
			default:
				return '0 2px 8px rgba(0,0,0,0.08)';
		}
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
