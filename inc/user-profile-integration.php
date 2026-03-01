<?php
/**
 * PS Smart CRM - User Profile Integration
 * 
 * Erweitert User-Profile um Agent-Rollen Auswahl
 * 
 * @package PS-Smart-CRM
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zeigt Agent-Rollen Dropdown im User-Profil
 */
function wpscrm_show_user_agent_role_field( $user ) {
	// Nur für manage_crm Capability oder Admins
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	// Nur für Users mit manage_crm Capability
	if ( ! user_can( $user->ID, 'manage_crm' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	global $wpdb;
	$table = WPsCRM_TABLE . 'agent_roles';
	$roles = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, role_name ASC" );
	
	$current_role = get_user_meta( $user->ID, '_crm_agent_role', true );
	
	?>
	<h3><?php _e( 'CRM Agent-Rolle', 'cpsmartcrm' ); ?></h3>
	<table class="form-table">
		<tr>
			<th>
				<label for="crm_agent_role"><?php _e( 'Agent-Rolle', 'cpsmartcrm' ); ?></label>
			</th>
			<td>
				<select name="crm_agent_role" id="crm_agent_role" class="regular-text">
					<option value=""><?php _e( '— Keine Rolle zugewiesen —', 'cpsmartcrm' ); ?></option>
					<?php foreach ( $roles as $role ) : ?>
						<option value="<?php echo esc_attr( $role->role_slug ); ?>"
								<?php selected( $current_role, $role->role_slug ); ?>>
							<?php echo esc_html( $role->role_name ); ?>
							<?php if ( $role->department ) : ?>
								(<?php echo esc_html( $role->department ); ?>)
							<?php endif; ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<p class="description">
					<?php _e( 'Wähle die Agent-Rolle für diesen Benutzer. Die Rolle bestimmt die Anzeige im Frontend-Dashboard und PM-Kommunikation.', 'cpsmartcrm' ); ?>
				</p>
				
				<?php if ( $current_role ) : ?>
					<?php
					$role_data = $wpdb->get_row( $wpdb->prepare(
						"SELECT * FROM $table WHERE role_slug = %s",
						$current_role
					) );
					?>
					<?php if ( $role_data ) : ?>
						<div style="margin-top:15px;padding:15px;background:#f0f0f1;border-left:4px solid #0073aa;">
							<p style="margin:0;">
								<strong><?php _e( 'Aktuelle Rolle:', 'cpsmartcrm' ); ?></strong>
								<?php echo esc_html( $role_data->role_name ); ?>
								<span class="dashicons <?php echo esc_attr( $role_data->icon ); ?>" 
									  style="font-size:20px;vertical-align:middle;"></span>
							</p>
							<?php if ( $role_data->department ) : ?>
								<p style="margin:5px 0 0;">
									<?php _e( 'Abteilung:', 'cpsmartcrm' ); ?>
									<?php echo esc_html( $role_data->department ); ?>
								</p>
							<?php endif; ?>
							<?php if ( $role_data->show_in_contact ) : ?>
								<p style="margin:5px 0 0;color:#00a32a;">
									<span class="dashicons dashicons-yes"></span>
									<?php _e( 'Wird als Kontakt-Button im Frontend angezeigt', 'cpsmartcrm' ); ?>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'wpscrm_show_user_agent_role_field' );
add_action( 'edit_user_profile', 'wpscrm_show_user_agent_role_field' );

/**
 * Speichert Agent-Rolle im User-Profil
 */
function wpscrm_save_user_agent_role_field( $user_id ) {
	// Permission Check
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	
	// Nur für manage_crm Users
	if ( ! user_can( $user_id, 'manage_crm' ) && ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	
	if ( isset( $_POST['crm_agent_role'] ) ) {
		$role_slug = sanitize_key( $_POST['crm_agent_role'] );
		
		if ( empty( $role_slug ) ) {
			delete_user_meta( $user_id, '_crm_agent_role' );
		} else {
			// Validiere dass die Rolle existiert
			global $wpdb;
			$table = WPsCRM_TABLE . 'agent_roles';
			$role_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE role_slug = %s",
				$role_slug
			) );
			
			if ( $role_exists ) {
				update_user_meta( $user_id, '_crm_agent_role', $role_slug );
			}
		}
	}
}
add_action( 'personal_options_update', 'wpscrm_save_user_agent_role_field' );
add_action( 'edit_user_profile_update', 'wpscrm_save_user_agent_role_field' );

/**
 * Zeigt Agent-Rolle in der User-Liste (Admin)
 */
function wpscrm_add_user_role_column( $columns ) {
	$columns['crm_agent_role'] = __( 'CRM-Rolle', 'cpsmartcrm' );
	return $columns;
}
add_filter( 'manage_users_columns', 'wpscrm_add_user_role_column' );

/**
 * Füllt Agent-Rolle Spalte in User-Liste
 */
function wpscrm_show_user_role_column_content( $value, $column_name, $user_id ) {
	if ( 'crm_agent_role' !== $column_name ) {
		return $value;
	}
	
	// Nur für manage_crm Users
	if ( ! user_can( $user_id, 'manage_crm' ) ) {
		return '—';
	}
	
	$role_slug = get_user_meta( $user_id, '_crm_agent_role', true );
	
	if ( empty( $role_slug ) ) {
		return '<span style="color:#999;">' . __( 'Nicht zugewiesen', 'cpsmartcrm' ) . '</span>';
	}
	
	global $wpdb;
	$table = WPsCRM_TABLE . 'agent_roles';
	$role = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM $table WHERE role_slug = %s",
		$role_slug
	) );
	
	if ( ! $role ) {
		return '<span style="color:#d63638;">' . __( 'Ungültige Rolle', 'cpsmartcrm' ) . '</span>';
	}
	
	return sprintf(
		'<span class="dashicons %s" style="font-size:16px;vertical-align:middle;margin-right:5px;"></span> <strong>%s</strong>',
		esc_attr( $role->icon ),
		esc_html( $role->role_name )
	);
}
add_filter( 'manage_users_custom_column', 'wpscrm_show_user_role_column_content', 10, 3 );

/**
 * Macht Agent-Rolle Spalte sortierbar
 */
function wpscrm_make_user_role_column_sortable( $columns ) {
	$columns['crm_agent_role'] = 'crm_agent_role';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'wpscrm_make_user_role_column_sortable' );

/**
 * Helper: Holt User-Display mit Rolle für PM-Context
 */
function wpscrm_get_user_with_role_display( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}
	
	$role_slug = get_user_meta( $user_id, '_crm_agent_role', true );
	$display = $user->display_name;
	
	if ( $role_slug ) {
		global $wpdb;
		$table = WPsCRM_TABLE . 'agent_roles';
		$role = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE role_slug = %s",
			$role_slug
		) );
		
		if ( $role ) {
			$display .= ' [' . $role->role_name . ']';
		}
	}
	
	return $display;
}
