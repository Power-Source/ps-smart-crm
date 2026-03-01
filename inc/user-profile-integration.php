<?php
/**
 * PS Smart CRM - User Profile Integration
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

function wpscrm_apply_role_permissions( $user_id, $role_slug ) {
	$role = wpscrm_get_agent_role_by_slug( $role_slug );
	if ( ! $role ) {
		return false;
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

function wpscrm_show_user_agent_role_field( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;
	$table = WPsCRM_TABLE . 'agent_roles';
	$roles = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, role_name ASC" );
	$current_role = get_user_meta( $user->ID, '_crm_agent_role', true );
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
