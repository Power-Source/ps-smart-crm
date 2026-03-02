<?php
/**
 * PWA Settings - Admin-UI für Progressive Web App Konfiguration
 * 
 * Einstellungen:
 * - App-Name & Beschreibung
 * - App-Icons (512x512, 192x192)
 * - Theme-Farben (Theme Color, Background Color)
 * - Push Notifications (VAPID Keys)
 * 
 * @package PS Smart CRM
 * @subpackage PWA
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Füge PWA Tab zur CRM Options-Page hinzu
 */
add_action( 'admin_init', 'wpscrm_register_pwa_settings', 11 );

function wpscrm_register_pwa_settings() {
	// Settings registrieren
	register_setting( 'wpscrm_pwa_settings', 'wpscrm_pwa_settings', array(
		'sanitize_callback' => 'wpscrm_sanitize_pwa_settings'
	) );
	
	add_settings_section(
		'section_pwa',
		__( 'Progressive Web App - Einstellungen', 'cpsmartcrm' ),
		'wpscrm_section_pwa_desc',
		'wpscrm_pwa_settings'
	);
	
	add_settings_field(
		'pwa_general',
		__( 'Web App Konfiguration', 'cpsmartcrm' ),
		'wpscrm_render_pwa_settings_fields',
		'wpscrm_pwa_settings',
		'section_pwa'
	);
}

/**
 * Section Description
 */
function wpscrm_section_pwa_desc() {
	?>
	<div class="wpscrm-pwa-info" style="background: #f0f6fc; padding: 1rem; border-left: 4px solid #0284c7; margin-bottom: 1.5rem;">
		<h3 style="margin: 0 0 0.5rem; color: #0284c7;">
			<?php _e( '📱 Installierbare Web-App', 'cpsmartcrm' ); ?>
		</h3>
		<p style="margin: 0;">
			<?php _e( 'Verwandeln Sie Ihre CRM-Dashboards in eine installierbare App! Kunden und Agenten können das Dashboard als Web-App auf ihrem Smartphone, Tablet oder Desktop installieren – mit Offline-Modus und Push-Benachrichtigungen.', 'cpsmartcrm' ); ?>
		</p>
		<p style="margin: 0.5rem 0 0; font-size: 0.9em; color: #64748b;">
			<?php _e( 'App-URL mit <code>?app=1</code> Parameter: Zeigt Clean-Template ohne WordPress-Theme an.', 'cpsmartcrm' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Render Settings Fields
 */
function wpscrm_render_pwa_settings_fields() {
	$settings = WPsCRM_PWA_Manager::get_pwa_settings();
	$plugin_url = plugin_dir_url( dirname( __FILE__ ) );
	
	?>
	<table class="form-table" role="presentation">
		<!-- App-Name -->
		<tr>
			<th scope="row">
				<label for="pwa_app_name"><?php _e( 'App-Name', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<input type="text" 
					id="pwa_app_name" 
					name="wpscrm_pwa_settings[app_name]" 
					value="<?php echo esc_attr( $settings['app_name'] ); ?>" 
					class="regular-text">
				<p class="description">
					<?php _e( 'Voller Name der App (erscheint beim Installieren)', 'cpsmartcrm' ); ?>
				</p>
			</td>
		</tr>
		
		<!-- App-Short-Name -->
		<tr>
			<th scope="row">
				<label for="pwa_app_short_name"><?php _e( 'App-Kurzname', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<input type="text" 
					id="pwa_app_short_name"
					name="wpscrm_pwa_settings[app_short_name]" 
					value="<?php echo esc_attr( $settings['app_short_name'] ); ?>" 
					class="regular-text">
				<p class="description">
					<?php _e( 'Kurzname (max. 12 Zeichen, für Homescreen-Icon)', 'cpsmartcrm' ); ?>
				</p>
			</td>
		</tr>
		
		<!-- App-Beschreibung -->
		<tr>
			<th scope="row">
				<label for="pwa_app_description"><?php _e( 'Beschreibung', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<textarea 
					id="pwa_app_description"
					name="wpscrm_pwa_settings[app_description]" 
					rows="3" 
					class="large-text"><?php echo esc_textarea( $settings['app_description'] ); ?></textarea>
				<p class="description">
					<?php _e( 'Kurze Beschreibung der App', 'cpsmartcrm' ); ?>
				</p>
			</td>
		</tr>
		
		<!-- Theme Color -->
		<tr>
			<th scope="row">
				<label for="pwa_theme_color"><?php _e( 'Theme-Farbe', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<input type="text" 
					id="pwa_theme_color"
					name="wpscrm_pwa_settings[theme_color]" 
					value="<?php echo esc_attr( $settings['theme_color'] ); ?>" 
					class="wpscrm-color-picker"
					data-default-color="#1e3a8a">
				<p class="description">
					<?php _e( 'Hauptfarbe der App (Header, Browser-Leiste)', 'cpsmartcrm' ); ?>
				</p>
			</td>
		</tr>
		
		<!-- Background Color -->
		<tr>
			<th scope="row">
				<label for="pwa_background_color"><?php _e( 'Hintergrundfarbe', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<input type="text" 
					id="pwa_background_color"
					name="wpscrm_pwa_settings[background_color]" 
					value="<?php echo esc_attr( $settings['background_color'] ); ?>" 
					class="wpscrm-color-picker"
					data-default-color="#ffffff">
				<p class="description">
					<?php _e( 'Hintergrundfarbe beim App-Start (Splash-Screen)', 'cpsmartcrm' ); ?>
				</p>
			</td>
		</tr>

		<!-- Icon 512x512 -->
		<tr>
			<th scope="row">
				<label for="pwa_icon_512"><?php _e( 'App-Icon (512x512)', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<input type="text" 
					id="pwa_icon_512" 
					name="wpscrm_pwa_settings[icon_512]" 
					value="<?php echo esc_url( $settings['icon_512'] ); ?>" 
					class="regular-text wpscrm-icon-url">
				<button type="button" class="button wpscrm-upload-icon" data-target="pwa_icon_512">
					<?php _e( 'Hochladen', 'cpsmartcrm' ); ?>
				</button>
				<p class="description">
					<?php _e( 'Hochauflösendes Icon (512x512 Pixel, PNG)', 'cpsmartcrm' ); ?>
				</p>
				<?php if ( ! empty( $settings['icon_512'] ) ) : ?>
					<div class="wpscrm-icon-preview">
						<img src="<?php echo esc_url( $settings['icon_512'] ); ?>" style="max-width: 128px; border: 1px solid #ddd; padding: 4px; margin-top: 8px;">
					</div>
				<?php endif; ?>
			</td>
		</tr>
		
		<!-- Icon 192x192 -->
		<tr>
			<th scope="row">
				<label for="pwa_icon_192"><?php _e( 'App-Icon (192x192)', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<input type="text" 
					id="pwa_icon_192"
					name="wpscrm_pwa_settings[icon_192]" 
					value="<?php echo esc_url( $settings['icon_192'] ); ?>" 
					class="regular-text wpscrm-icon-url">
				<button type="button" class="button wpscrm-upload-icon" data-target="pwa_icon_192">
					<?php _e( 'Hochladen', 'cpsmartcrm' ); ?>
				</button>
				<p class="description">
					<?php _e( 'Kleineres Icon (192x192 Pixel, PNG)', 'cpsmartcrm' ); ?>
				</p>
				<?php if ( ! empty( $settings['icon_192'] ) ) : ?>
					<div class="wpscrm-icon-preview">
						<img src="<?php echo esc_url( $settings['icon_192'] ); ?>" style="max-width: 96px; border: 1px solid #ddd; padding: 4px; margin-top: 8px;">
					</div>
				<?php endif; ?>
			</td>
		</tr>
		
		<!-- Test Links -->
		<tr>
			<th scope="row">
				<?php _e( 'App Testing', 'cpsmartcrm' ); ?>
			</th>
			<td>
				<p>
					<a href="<?php echo esc_url( add_query_arg( 'app', '1', home_url( '/' ) ) ); ?>" target="_blank" class="button button-secondary">
						<?php _e( '🚀 App-Modus testen', 'cpsmartcrm' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/crm-manifest.json' ) ); ?>" target="_blank" class="button button-secondary">
						<?php _e( '📄 Manifest ansehen', 'cpsmartcrm' ); ?>
					</a>
				</p>
				<p class="description">
					<?php _e( 'Öffne die App-URL auf deinem Smartphone um die Installation zu testen.', 'cpsmartcrm' ); ?>
				</p>
			</td>
		</tr>
	</table>
	
	<style>
		.wpscrm-pwa-info h3 { display: flex; align-items: center; gap: 0.5rem; }
		.wpscrm-icon-preview { margin-top: 0.5rem; }
	</style>
	
	<script>
	jQuery(document).ready(function($) {
		// Color Picker
		if ($.fn.wpColorPicker) {
			$('.wpscrm-color-picker').wpColorPicker();
		}
		
		// Media Uploader für Icons
		$('.wpscrm-upload-icon').on('click', function(e) {
			e.preventDefault();
			
			var button = $(this);
			var targetId = button.data('target');
			var targetInput = $('#' + targetId);
			
			var mediaUploader = wp.media({
				title: '<?php _e( 'App-Icon auswählen', 'cpsmartcrm' ); ?>',
				button: {
					text: '<?php _e( 'Icon verwenden', 'cpsmartcrm' ); ?>'
				},
				multiple: false,
				library: {
					type: 'image/png'
				}
			});
			
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				targetInput.val(attachment.url);
				
				// Preview aktualisieren
				var preview = targetInput.closest('td').find('.wpscrm-icon-preview');
				if (preview.length === 0) {
					preview = $('<div class="wpscrm-icon-preview"></div>');
					targetInput.closest('td').append(preview);
				}
				
				var maxWidth = targetId.includes('512') ? 128 : 96;
				preview.html('<img src="' + attachment.url + '" style="max-width: ' + maxWidth + 'px; border: 1px solid #ddd; padding: 4px; margin-top: 8px;">');
			});
			
			mediaUploader.open();
		});
	});
	</script>
	<?php
}

/**
 * Sanitize PWA Settings
 */
function wpscrm_sanitize_pwa_settings( $input ) {
	$sanitized = array();
	
	$sanitized['app_name'] = isset( $input['app_name'] ) ? sanitize_text_field( $input['app_name'] ) : '';
	$sanitized['app_short_name'] = isset( $input['app_short_name'] ) ? sanitize_text_field( $input['app_short_name'] ) : '';
	$sanitized['app_description'] = isset( $input['app_description'] ) ? sanitize_textarea_field( $input['app_description'] ) : '';
	$sanitized['theme_color'] = isset( $input['theme_color'] ) ? sanitize_hex_color( $input['theme_color'] ) : '#1e3a8a';
	$sanitized['background_color'] = isset( $input['background_color'] ) ? sanitize_hex_color( $input['background_color'] ) : '#ffffff';
	$sanitized['icon_512'] = isset( $input['icon_512'] ) ? esc_url_raw( $input['icon_512'] ) : '';
	$sanitized['icon_192'] = isset( $input['icon_192'] ) ? esc_url_raw( $input['icon_192'] ) : '';
	
	// Flush Rewrite Rules wenn Einstellungen gespeichert werden
	flush_rewrite_rules();
	
	return $sanitized;
}
