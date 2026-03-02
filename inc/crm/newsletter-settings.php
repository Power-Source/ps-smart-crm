<?php
/**
 * Newsletter Integration Settings für PS Smart CRM
 * Verwaltet die Newsletter-Integration mit e-Newsletter Plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_crm' ) ) {
	wp_die( __( 'Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm' ) );
}

// Prüfe ob e-Newsletter aktiv ist
$enewsletter_active = function_exists( 'enewsletter_crm_add_subscriber' );

?>

<div class="wrap">
	<h2><?php _e( 'Newsletter-Integration Einstellungen', 'cpsmartcrm' ); ?></h2>
	
	<?php if ( ! $enewsletter_active ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php _e( 'Warnung:', 'cpsmartcrm' ); ?></strong>
				<?php _e( 'Das e-Newsletter Plugin ist nicht aktiv. Bitte aktivieren Sie es, um die Newsletter-Integration zu nutzen.', 'cpsmartcrm' ); ?>
			</p>
		</div>
	<?php else : ?>
	
		<?php 
		// Handle form submission
		if ( isset( $_POST['save_newsletter_settings'] ) && check_admin_referer( 'wpscrm_newsletter_settings_nonce' ) ) {
			
			// Speichere Standard-Gruppen
			if ( isset( $_POST['default_groups'] ) && is_array( $_POST['default_groups'] ) ) {
				$groups = array_map( 'intval', $_POST['default_groups'] );
				WPsCRM_Newsletter_Integration::set_default_groups( $groups );
			}
			
			// Speichere Auto-Serie Konfiguration
			if ( isset( $_POST['auto_series_enabled'] ) ) {
				$series_config = array(
					'enabled' => 1,
					'series_key' => 'welcome_series',
					'newsletters' => array()
				);
				
				// Parse newsletter delays - improved structure
				if ( isset( $_POST['welcome_newsletters'] ) && is_array( $_POST['welcome_newsletters'] ) ) {
					foreach ( $_POST['welcome_newsletters'] as $nl_item ) {
						if ( ! empty( $nl_item['newsletter_id'] ) ) {
							$series_config['newsletters'][] = array(
								'newsletter_id' => intval( $nl_item['newsletter_id'] ),
								'delay_hours' => intval( $nl_item['delay_hours'] ?? 0 )
							);
						}
					}
				}
				
				WPsCRM_Newsletter_Integration::set_auto_series_config( 'new_customer', $series_config );
			} else {
				// Deaktiviere Serie
				WPsCRM_Newsletter_Integration::set_auto_series_config( 'new_customer', array( 'enabled' => 0 ) );
			}
			
			?>
			<div class="notice notice-success">
				<p><?php _e( 'Einstellungen gespeichert!', 'cpsmartcrm' ); ?></p>
			</div>
			<?php
		}
		
		// Get current settings
		$default_groups = WPsCRM_Newsletter_Integration::get_default_groups();
		$auto_series_config = WPsCRM_Newsletter_Integration::get_auto_series_config( 'new_customer' );
		$all_groups = WPsCRM_Newsletter_Integration::get_all_newsletter_groups();
		
		// Get all available newsletters
		global $email_newsletter;
		$newsletters = array();
		if ( $email_newsletter ) {
			$arg = array();
			$newsletters = $email_newsletter->get_newsletters( $arg );
		}
		?>
		
		<form method="post" class="crm-settings-form">
			<?php wp_nonce_field( 'wpscrm_newsletter_settings_nonce' ); ?>
			
			<!-- Sektion 1: Standard-Gruppen -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( '📧 Standard-Newsletter-Gruppen', 'cpsmartcrm' ); ?></span></h3>
				<div class="inside">
					<p class="description">
						<?php _e( 'Diese Gruppen werden automatisch zugewiesen, wenn neue Kunden hinzugefügt werden.', 'cpsmartcrm' ); ?>
					</p>
					
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<label><?php _e( 'Newsletter-Gruppen auswählen:', 'cpsmartcrm' ); ?></label>
							</th>
							<td>
								<?php if ( ! empty( $all_groups ) ) : ?>
									<div style="border: 1px solid #ddd; padding: 10px; border-radius: 3px; max-height: 250px; overflow-y: auto;">
										<?php foreach ( $all_groups as $group ) : ?>
											<label style="display: block; margin: 8px 0;">
												<input type="checkbox" 
													   name="default_groups[]" 
													   value="<?php echo intval( $group['group_id'] ); ?>"
													   <?php checked( in_array( $group['group_id'], $default_groups ) ); ?> />
												<strong><?php echo esc_html( $group['group_name'] ); ?></strong>
											</label>
										<?php endforeach; ?>
									</div>
								<?php else : ?>
									<p class="description" style="color: #d00;">
										<?php _e( 'Keine Newsletter-Gruppen vorhanden. Bitte erstellen Sie zuerst Gruppen im Newsletter.', 'cpsmartcrm' ); ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>
			</div>
			
			<!-- Sektion 2: Willkommens-Serie Automation -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( '🎯 Willkommens-Serie (Automation)', 'cpsmartcrm' ); ?></span></h3>
				<div class="inside">
					<p class="description">
						<?php _e( 'Automatisch Newsletter senden, wenn neue Kunden hinzugefügt werden. Unterstützt zeitversetzte Serien.', 'cpsmartcrm' ); ?>
					</p>
					
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<label><?php _e( 'Auto-Serie aktivieren:', 'cpsmartcrm' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" 
										   name="auto_series_enabled" 
										   value="1"
										   <?php checked( ! empty( $auto_series_config['enabled'] ) ); ?> />
									<?php _e( 'Ja, aktiviere Willkommens-Serie für neue Kunden', 'cpsmartcrm' ); ?>
								</label>
							</td>
						</tr>
					</table>
					
					<?php if ( ! empty( $newsletters ) ) : ?>
						<div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
							<h4><?php _e( 'Newsletter in der Serie konfigurieren:', 'cpsmartcrm' ); ?></h4>
							<p class="description">
								<?php _e( 'Wähle Newsletter und definiere die Verzögerung (in Stunden) bis zum Versand nach Kundenhinzufügung.', 'cpsmartcrm' ); ?>
							</p>
							
							<table class="widefat" style="margin-top: 10px;">
								<thead>
									<tr>
										<th><?php _e( 'Reihenfolge', 'cpsmartcrm' ); ?></th>
										<th><?php _e( 'Newsletter', 'cpsmartcrm' ); ?></th>
										<th><?php _e( 'Verzögerung (Stunden)', 'cpsmartcrm' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php 
									$series_newsletters = ! empty( $auto_series_config['newsletters'] ) ? $auto_series_config['newsletters'] : array();
									$max_newsletters = max( 5, count( $series_newsletters ) );
									
									for ( $i = 1; $i <= $max_newsletters; $i++ ) :
										$nl_data = isset( $series_newsletters[ $i - 1 ] ) ? $series_newsletters[ $i - 1 ] : array();
										$nl_id = isset( $nl_data['newsletter_id'] ) ? $nl_data['newsletter_id'] : '';
										$nl_delay = isset( $nl_data['delay_hours'] ) ? $nl_data['delay_hours'] : '0';
									?>
										<tr style="background: <?php echo ( $i % 2 ) ? '#fff' : '#f9f9f9'; ?>;">
											<td style="width: 60px; font-weight: bold;">
												<?php echo $i; ?>.
											</td>
											<td>
												<select name="welcome_newsletters[<?php echo $i - 1; ?>][newsletter_id]" class="widefat" style="width: 100%; min-width: 250px;">
													<option value=""><?php _e( '-- Nicht ausgewählt --', 'cpsmartcrm' ); ?></option>
													<?php foreach ( $newsletters as $nl ) : ?>
														<option value="<?php echo intval( $nl['newsletter_id'] ); ?>"
															<?php selected( $nl_id, $nl['newsletter_id'] ); ?>>
															<?php echo esc_html( $nl['subject'] ); ?>
														</option>
													<?php endforeach; ?>
												</select>
											</td>
											<td style="width: 150px;">
												<input type="number" 
														name="welcome_newsletters[<?php echo $i - 1; ?>][delay_hours]" 
														value="<?php echo esc_attr( $nl_delay ); ?>"
														min="0"
														step="1"
														placeholder="0"
														style="width: 100%; padding: 5px;" />
												<small><?php _e( '0 = sofort, 24 = morgen, 72 = in 3 Tagen', 'cpsmartcrm' ); ?></small>
											</td>
										</tr>
									<?php endfor; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<p class="description" style="color: #d00;">
							<?php _e( 'Keine Newsletter vorhanden. Bitte erstellen Sie zuerst Newsletter im Newsletter-Plugin.', 'cpsmartcrm' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Integration Info -->
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'ℹ️ Integration Status', 'cpsmartcrm' ); ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e( 'e-Newsletter Plugin:', 'cpsmartcrm' ); ?></th>
							<td>
								<span style="color: #0a0;">✓ Aktiv</span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Newsletter-Gruppen:', 'cpsmartcrm' ); ?></th>
							<td>
								<?php echo count( $all_groups ); ?> <?php _e( 'Gruppen vorhanden', 'cpsmartcrm' ); ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Newsletter:', 'cpsmartcrm' ); ?></th>
							<td>
								<?php echo count( $newsletters ); ?> <?php _e( 'Newsletter vorhanden', 'cpsmartcrm' ); ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'Auto-Serie:', 'cpsmartcrm' ); ?></th>
							<td>
								<?php if ( ! empty( $auto_series_config['enabled'] ) ) : ?>
									<span style="color: #0a0;">✓ <?php _e( 'Aktiviert', 'cpsmartcrm' ); ?></span>
									<?php if ( ! empty( $auto_series_config['newsletters'] ) ) : ?>
										(<?php echo count( $auto_series_config['newsletters'] ); ?> <?php _e( 'Newsletter geplant', 'cpsmartcrm' ); ?>)
									<?php endif; ?>
								<?php else : ?>
									<span style="color: #999;">✗ <?php _e( 'Deaktiviert', 'cpsmartcrm' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</div>
			</div>
			
			<!-- Submit Button -->
			<p style="margin-top: 20px;">
				<input type="submit" 
					   name="save_newsletter_settings" 
					   class="button button-primary" 
					   value="<?php _e( 'Einstellungen speichern', 'cpsmartcrm' ); ?>" />
			</p>
		</form>
		
	<?php endif; ?>
</div>

<style>
	.crm-settings-form .postbox {
		margin-bottom: 20px;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
	}
	
	.crm-settings-form .hndle {
		font-size: 16px;
		font-weight: 600;
		color: #0073aa;
		padding: 8px 12px;
		background: #f5f5f5;
		margin: 0;
		border-bottom: 1px solid #e0e0e0;
	}
	
	.crm-settings-form .inside {
		padding: 12px;
	}
	
	.crm-settings-form table.form-table th {
		width: 200px;
		padding: 8px;
	}
	
	.crm-settings-form .description {
		font-style: italic;
		color: #666;
		font-size: 12px;
	}
</style>
