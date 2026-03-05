<?php
/**
 * PS Smart Business - User Profile Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wpscrm_get_agent_role_by_slug( $role_slug ) {
	if ( empty( $role_slug ) ) {
		return null;
	}
	global $wpdb;
	$table = WPsCRM_TABLE . 'agent_roles';
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE role_slug = %s", $role_slug ) );
}

function wpscrm_get_chef_user_ids() {
	return get_users( array(
		'fields' => 'ID',
		'meta_key' => '_crm_agent_role',
		'meta_value' => 'chef',
	) );
}

function wpscrm_get_site_admin_user_id() {
	$admin_email = get_option( 'admin_email' );
	if ( empty( $admin_email ) ) {
		return 0;
	}

	$admin_user = get_user_by( 'email', $admin_email );
	if ( ! ( $admin_user instanceof WP_User ) ) {
		return 0;
	}

	return (int) $admin_user->ID;
}

function wpscrm_can_manage_chef_role( $actor_user_id = 0 ) {
	$actor_user_id = (int) $actor_user_id;
	if ( ! $actor_user_id ) {
		$actor_user_id = get_current_user_id();
	}
	if ( ! $actor_user_id ) {
		return false;
	}

	$current_role = get_user_meta( $actor_user_id, '_crm_agent_role', true );
	if ( 'chef' === $current_role ) {
		return true;
	}

	$chef_user_ids = wpscrm_get_chef_user_ids();
	if ( empty( $chef_user_ids ) ) {
		return (int) wpscrm_get_site_admin_user_id() === $actor_user_id;
	}

	return false;
}

function wpscrm_apply_role_permissions( $user_id, $role_slug ) {
	$role = wpscrm_get_agent_role_by_slug( $role_slug );
	if ( ! $role ) {
		return false;
	}

	// Chef darf nur einmal vergeben sein
	if ( 'chef' === $role_slug ) {
		$existing_chef_ids = get_users( array(
			'fields' => 'ID',
			'meta_key' => '_crm_agent_role',
			'meta_value' => 'chef',
		) );

		foreach ( $existing_chef_ids as $existing_chef_id ) {
			if ( (int) $existing_chef_id === (int) $user_id ) {
				continue;
			}

			if ( function_exists( 'wpscrm_remove_role_permissions' ) ) {
				wpscrm_remove_role_permissions( (int) $existing_chef_id, 'chef' );
			} else {
				delete_user_meta( (int) $existing_chef_id, '_crm_agent_role' );
			}
		}
	}

	$caps = json_decode( $role->capabilities, true );
	if ( ! is_array( $caps ) ) {
		$caps = array();
	}

	update_user_meta( $user_id, '_crm_role_permissions', $caps );
	update_user_meta( $user_id, '_crm_agent_role', $role_slug );

	$user = get_user_by( 'id', $user_id );
	if ( $user instanceof WP_User ) {
		if ( empty( $caps['wp_role'] ) ) {
			if ( ! user_can( $user_id, 'manage_crm' ) ) {
				$user->add_cap( 'manage_crm' );
			}
		} elseif ( get_role( $caps['wp_role'] ) ) {
			$user->set_role( $caps['wp_role'] );
			if ( ! user_can( $user_id, 'manage_crm' ) ) {
				$user->add_cap( 'manage_crm' );
			}
		}
	}

	do_action( 'wpscrm_role_assigned', $user_id, $role_slug, $caps );
	return true;
}

function wpscrm_remove_role_permissions( $user_id, $role_slug = '' ) {
	delete_user_meta( $user_id, '_crm_role_permissions' );
	if ( $role_slug ) {
		$current_role = get_user_meta( $user_id, '_crm_agent_role', true );
		if ( $current_role === $role_slug ) {
			delete_user_meta( $user_id, '_crm_agent_role' );
		}
	}
	return true;
}

function wpscrm_user_can( $user_id, $permission_key ) {
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	if ( ! user_can( $user_id, 'manage_crm' ) ) {
		return false;
	}
	$perms = get_user_meta( $user_id, '_crm_role_permissions', true );
	if ( ! is_array( $perms ) ) {
		return false;
	}
	return ! empty( $perms[ $permission_key ] );
}

function wpscrm_get_default_worktime_settings() {
	return array(
		'hourly_rate' => 0,
		'rate_type' => 'net',
		'billing_mode' => 'employee',
		'schedule' => array(
			'mo' => array( 'active' => 1, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 30 ),
			'tu' => array( 'active' => 1, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 30 ),
			'we' => array( 'active' => 1, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 30 ),
			'th' => array( 'active' => 1, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 30 ),
			'fr' => array( 'active' => 1, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 30 ),
			'sa' => array( 'active' => 0, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 0 ),
			'su' => array( 'active' => 0, 'start' => '09:00', 'end' => '17:00', 'break_minutes' => 0 ),
		),
		'last_sync_source' => '',
		'last_sync_at' => '',
	);
}

function wpscrm_get_user_worktime_settings( $user_id ) {
	$defaults = wpscrm_get_default_worktime_settings();
	$stored = get_user_meta( $user_id, '_crm_worktime_settings', true );
	if ( ! is_array( $stored ) ) {
		return $defaults;
	}

	$settings = wp_parse_args( $stored, $defaults );
	$settings['schedule'] = wp_parse_args( is_array( $settings['schedule'] ) ? $settings['schedule'] : array(), $defaults['schedule'] );

	foreach ( $defaults['schedule'] as $day_key => $day_defaults ) {
		$day_values = isset( $settings['schedule'][ $day_key ] ) && is_array( $settings['schedule'][ $day_key ] )
			? $settings['schedule'][ $day_key ]
			: array();
		$settings['schedule'][ $day_key ] = wp_parse_args( $day_values, $day_defaults );
	}

	return $settings;
}

function wpscrm_get_user_target_minutes_for_date( $user_id, $date_ymd ) {
	$timestamp = strtotime( $date_ymd . ' 00:00:00' );
	if ( ! $timestamp ) {
		return 0;
	}

	$weekday_map = array(
		1 => 'mo',
		2 => 'tu',
		3 => 'we',
		4 => 'th',
		5 => 'fr',
		6 => 'sa',
		7 => 'su',
	);

	$weekday_number = (int) date( 'N', $timestamp );
	$day_key = isset( $weekday_map[ $weekday_number ] ) ? $weekday_map[ $weekday_number ] : '';
	if ( empty( $day_key ) ) {
		return 0;
	}

	$settings = wpscrm_get_user_worktime_settings( $user_id );
	$day = isset( $settings['schedule'][ $day_key ] ) ? $settings['schedule'][ $day_key ] : array();
	if ( empty( $day ) || empty( $day['active'] ) ) {
		return 0;
	}

	$start = isset( $day['start'] ) ? sanitize_text_field( $day['start'] ) : '';
	$end = isset( $day['end'] ) ? sanitize_text_field( $day['end'] ) : '';
	if ( ! preg_match( '/^\d{2}:\d{2}$/', $start ) || ! preg_match( '/^\d{2}:\d{2}$/', $end ) ) {
		return 0;
	}

	$start_minutes = (int) substr( $start, 0, 2 ) * 60 + (int) substr( $start, 3, 2 );
	$end_minutes = (int) substr( $end, 0, 2 ) * 60 + (int) substr( $end, 3, 2 );
	$break_minutes = isset( $day['break_minutes'] ) ? max( 0, (int) $day['break_minutes'] ) : 0;

	$duration = $end_minutes - $start_minutes - $break_minutes;
	return max( 0, $duration );
}

/**
 * Get all active agents from the agents table
 * @return array Agent objects with role info
 */
function wpscrm_get_all_agents( $status = 'active' ) {
	global $wpdb;
	
	$agents_table = WPsCRM_TABLE . 'agents';
	$roles_table = WPsCRM_TABLE . 'agent_roles';
	
	$query = "
		SELECT 
			a.id as agent_id,
			a.user_id,
			a.role_id,
			a.status,
			r.role_slug,
			r.role_name,
			r.display_name as role_display_name,
			u.ID,
			u.user_login,
			u.user_email,
			u.display_name
		FROM $agents_table a
		INNER JOIN $roles_table r ON a.role_id = r.id
		INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
	";
	
	if ( $status ) {
		$query .= $wpdb->prepare( " WHERE a.status = %s", $status );
	}
	
	$query .= " ORDER BY r.sort_order ASC, u.display_name ASC";
	
	return $wpdb->get_results( $query );
}

/**
 * Get agent by user_id
 */
function wpscrm_get_agent_by_user_id( $user_id ) {
	global $wpdb;
	
	$agents_table = WPsCRM_TABLE . 'agents';
	$roles_table = WPsCRM_TABLE . 'agent_roles';
	
	$query = $wpdb->prepare(
		"
		SELECT 
			a.*,
			r.role_slug,
			r.role_name,
			r.display_name as role_display_name
		FROM $agents_table a
		LEFT JOIN $roles_table r ON a.role_id = r.id
		WHERE a.user_id = %d
		",
		$user_id
	);
	
	return $wpdb->get_row( $query );
}

/**
 * Create new agent
 */
function wpscrm_create_agent( $user_id, $role_id, $status = 'active' ) {
	global $wpdb;
	
	// Check if user is already an agent
	$existing = wpscrm_get_agent_by_user_id( $user_id );
	if ( $existing ) {
		return new WP_Error( 'agent_exists', __( 'User ist bereits ein Agent.', 'cpsmartcrm' ) );
	}
	
	// Verify role exists
	$role = $wpdb->get_row( $wpdb->prepare(
		"SELECT id FROM " . WPsCRM_TABLE . "agent_roles WHERE id = %d",
		$role_id
	) );
	
	if ( ! $role ) {
		return new WP_Error( 'role_not_found', __( 'Rolle nicht gefunden.', 'cpsmartcrm' ) );
	}
	
	$agents_table = WPsCRM_TABLE . 'agents';
	$result = $wpdb->insert(
		$agents_table,
		array(
			'user_id' => (int) $user_id,
			'role_id' => (int) $role_id,
			'status' => $status,
			'joined_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s' )
	);
	
	if ( ! $result ) {
		return new WP_Error( 'insert_failed', __( 'Fehler beim Erstellen des Agenten.', 'cpsmartcrm' ) );
	}
	
	return $wpdb->insert_id;
}

/**
 * Update agent
 */
function wpscrm_update_agent( $agent_id, $data ) {
	global $wpdb;
	
	$agents_table = WPsCRM_TABLE . 'agents';
	
	$update_data = array();
	$format = array();
	
	if ( isset( $data['role_id'] ) ) {
		$update_data['role_id'] = (int) $data['role_id'];
		$format[] = '%d';
	}
	
	if ( isset( $data['status'] ) && in_array( $data['status'], array( 'active', 'inactive', 'archived' ), true ) ) {
		$update_data['status'] = $data['status'];
		$format[] = '%s';
	}
	
	if ( empty( $update_data ) ) {
		return false;
	}
	
	$update_data['updated_at'] = current_time( 'mysql' );
	$format[] = '%s';
	
	return $wpdb->update(
		$agents_table,
		$update_data,
		array( 'id' => (int) $agent_id ),
		$format,
		array( '%d' )
	);
}

/**
 * Delete agent (move to archived status)
 */
function wpscrm_delete_agent( $agent_id ) {
	global $wpdb;
	
	$agents_table = WPsCRM_TABLE . 'agents';
	
	return $wpdb->update(
		$agents_table,
		array( 'status' => 'archived', 'updated_at' => current_time( 'mysql' ) ),
		array( 'id' => (int) $agent_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);
}

function wpscrm_show_user_agent_role_field( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table = WPsCRM_TABLE . 'agent_roles';
	$roles = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, role_name ASC" );
	$current_role = get_user_meta( $user->ID, '_crm_agent_role', true );
	$work_settings = wpscrm_get_user_worktime_settings( $user->ID );
	$day_labels = array(
		'mo' => __( 'Montag', 'cpsmartcrm' ),
		'tu' => __( 'Dienstag', 'cpsmartcrm' ),
		'we' => __( 'Mittwoch', 'cpsmartcrm' ),
		'th' => __( 'Donnerstag', 'cpsmartcrm' ),
		'fr' => __( 'Freitag', 'cpsmartcrm' ),
		'sa' => __( 'Samstag', 'cpsmartcrm' ),
		'su' => __( 'Sonntag', 'cpsmartcrm' ),
	);
	?>
	<h3><?php esc_html_e( 'CRM Agent-Rolle', 'cpsmartcrm' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="crm_agent_role"><?php esc_html_e( 'Agent-Rolle', 'cpsmartcrm' ); ?></label></th>
			<td>
				<select name="crm_agent_role" id="crm_agent_role" class="regular-text">
					<option value=""><?php esc_html_e( '— Keine Rolle zugewiesen —', 'cpsmartcrm' ); ?></option>
					<?php foreach ( $roles as $role ) : ?>
						<option value="<?php echo esc_attr( $role->role_slug ); ?>" <?php selected( $current_role, $role->role_slug ); ?>>
							<?php echo esc_html( $role->role_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Diese Rolle steuert CRM-Berechtigungen, Kontaktanzeige und optionale WP-Rollenzuweisung.', 'cpsmartcrm' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Stundensatz', 'cpsmartcrm' ); ?></th>
			<td>
				<input type="number" step="0.01" min="0" name="crm_hourly_rate" value="<?php echo esc_attr( $work_settings['hourly_rate'] ); ?>" class="regular-text" />
				<select name="crm_rate_type">
					<option value="net" <?php selected( $work_settings['rate_type'], 'net' ); ?>><?php esc_html_e( 'Netto', 'cpsmartcrm' ); ?></option>
					<option value="gross" <?php selected( $work_settings['rate_type'], 'gross' ); ?>><?php esc_html_e( 'Brutto', 'cpsmartcrm' ); ?></option>
				</select>
				<select name="crm_billing_mode">
					<option value="employee" <?php selected( $work_settings['billing_mode'], 'employee' ); ?>><?php esc_html_e( 'Mitarbeiter', 'cpsmartcrm' ); ?></option>
					<option value="contractor" <?php selected( $work_settings['billing_mode'], 'contractor' ); ?>><?php esc_html_e( 'Externer Dienstleister', 'cpsmartcrm' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Für interne Kalkulation und Abrechnung externer Agents.', 'cpsmartcrm' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Wöchentliche Sollzeiten', 'cpsmartcrm' ); ?></th>
			<td>
				<table style="border-collapse:collapse;min-width:760px;">
					<thead>
						<tr>
							<th style="text-align:left;padding:4px 8px;"><?php esc_html_e( 'Tag', 'cpsmartcrm' ); ?></th>
							<th style="text-align:left;padding:4px 8px;"><?php esc_html_e( 'Aktiv', 'cpsmartcrm' ); ?></th>
							<th style="text-align:left;padding:4px 8px;"><?php esc_html_e( 'Von', 'cpsmartcrm' ); ?></th>
							<th style="text-align:left;padding:4px 8px;"><?php esc_html_e( 'Bis', 'cpsmartcrm' ); ?></th>
							<th style="text-align:left;padding:4px 8px;"><?php esc_html_e( 'Pause gesamt (Min.)', 'cpsmartcrm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $day_labels as $day_key => $day_label ) : ?>
							<?php $day_values = $work_settings['schedule'][ $day_key ]; ?>
							<tr>
								<td style="padding:4px 8px;"><?php echo esc_html( $day_label ); ?></td>
								<td style="padding:4px 8px;"><input type="checkbox" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][active]" value="1" <?php checked( ! empty( $day_values['active'] ) ); ?> /></td>
								<td style="padding:4px 8px;"><input type="time" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][start]" value="<?php echo esc_attr( $day_values['start'] ); ?>" /></td>
								<td style="padding:4px 8px;"><input type="time" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][end]" value="<?php echo esc_attr( $day_values['end'] ); ?>" /></td>
								<td style="padding:4px 8px;"><input type="number" min="0" max="600" step="1" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][break_minutes]" value="<?php echo esc_attr( $day_values['break_minutes'] ); ?>" /></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description"><?php esc_html_e( 'Diese Sollzeiten werden in der CRM-Zeiterfassungsübersicht für Über-/Unterstunden verwendet.', 'cpsmartcrm' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'wpscrm_show_user_agent_role_field' );
add_action( 'edit_user_profile', 'wpscrm_show_user_agent_role_field' );

function wpscrm_save_user_agent_role_field( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( ! isset( $_POST['crm_agent_role'] ) ) {
		return false;
	}

	$role_slug = sanitize_key( $_POST['crm_agent_role'] );
	$current_target_role = get_user_meta( $user_id, '_crm_agent_role', true );
	if ( ( 'chef' === $role_slug || 'chef' === $current_target_role ) && ! wpscrm_can_manage_chef_role( get_current_user_id() ) ) {
		return false;
	}

	$defaults = wpscrm_get_default_worktime_settings();
	$settings = wpscrm_get_user_worktime_settings( $user_id );
	$settings['hourly_rate'] = isset( $_POST['crm_hourly_rate'] ) ? max( 0, (float) $_POST['crm_hourly_rate'] ) : 0;
	$settings['rate_type'] = ( isset( $_POST['crm_rate_type'] ) && in_array( $_POST['crm_rate_type'], array( 'net', 'gross' ), true ) ) ? sanitize_key( $_POST['crm_rate_type'] ) : 'net';
	$settings['billing_mode'] = ( isset( $_POST['crm_billing_mode'] ) && in_array( $_POST['crm_billing_mode'], array( 'employee', 'contractor' ), true ) ) ? sanitize_key( $_POST['crm_billing_mode'] ) : 'employee';

	$incoming_schedule = isset( $_POST['crm_schedule'] ) && is_array( $_POST['crm_schedule'] ) ? $_POST['crm_schedule'] : array();
	$settings['schedule'] = array();
	foreach ( $defaults['schedule'] as $day_key => $day_defaults ) {
		$incoming_day = isset( $incoming_schedule[ $day_key ] ) && is_array( $incoming_schedule[ $day_key ] ) ? $incoming_schedule[ $day_key ] : array();
		$start = isset( $incoming_day['start'] ) && preg_match( '/^\d{2}:\d{2}$/', $incoming_day['start'] ) ? $incoming_day['start'] : $day_defaults['start'];
		$end = isset( $incoming_day['end'] ) && preg_match( '/^\d{2}:\d{2}$/', $incoming_day['end'] ) ? $incoming_day['end'] : $day_defaults['end'];
		$settings['schedule'][ $day_key ] = array(
			'active' => isset( $incoming_day['active'] ) ? 1 : 0,
			'start' => $start,
			'end' => $end,
			'break_minutes' => isset( $incoming_day['break_minutes'] ) ? max( 0, min( 600, (int) $incoming_day['break_minutes'] ) ) : (int) $day_defaults['break_minutes'],
		);
	}

	update_user_meta( $user_id, '_crm_worktime_settings', $settings );

	if ( empty( $role_slug ) ) {
		wpscrm_remove_role_permissions( $user_id );
		return true;
	}

	$role_exists = wpscrm_get_agent_role_by_slug( $role_slug );
	if ( $role_exists ) {
		return wpscrm_apply_role_permissions( $user_id, $role_slug );
	}
	return false;
}
add_action( 'personal_options_update', 'wpscrm_save_user_agent_role_field' );
add_action( 'edit_user_profile_update', 'wpscrm_save_user_agent_role_field' );

function wpscrm_add_user_role_column( $columns ) {
	$columns['crm_agent_role'] = __( 'CRM-Rolle', 'cpsmartcrm' );
	return $columns;
}
add_filter( 'manage_users_columns', 'wpscrm_add_user_role_column' );

function wpscrm_show_user_role_column_content( $value, $column_name, $user_id ) {
	if ( 'crm_agent_role' !== $column_name ) {
		return $value;
	}
	$role_slug = get_user_meta( $user_id, '_crm_agent_role', true );
	if ( empty( $role_slug ) ) {
		return '<span style="color:#999;">' . esc_html__( 'Nicht zugewiesen', 'cpsmartcrm' ) . '</span>';
	}
	$role = wpscrm_get_agent_role_by_slug( $role_slug );
	if ( ! $role ) {
		return '<span style="color:#d63638;">' . esc_html__( 'Ungültige Rolle', 'cpsmartcrm' ) . '</span>';
	}
	return sprintf(
		'<span class="dashicons %s" style="font-size:16px;vertical-align:middle;margin-right:5px;"></span> <strong>%s</strong>',
		esc_attr( $role->icon ),
		esc_html( $role->role_name )
	);
}
add_filter( 'manage_users_custom_column', 'wpscrm_show_user_role_column_content', 10, 3 );

function wpscrm_make_user_role_column_sortable( $columns ) {
	$columns['crm_agent_role'] = 'crm_agent_role';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'wpscrm_make_user_role_column_sortable' );

function wpscrm_get_user_with_role_display( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}
	$role_slug = get_user_meta( $user_id, '_crm_agent_role', true );
	$display = $user->display_name;
	if ( $role_slug ) {
		$role = wpscrm_get_agent_role_by_slug( $role_slug );
		if ( $role ) {
			$display .= ' [' . $role->role_name . ']';
		}
	}
	return $display;
}
