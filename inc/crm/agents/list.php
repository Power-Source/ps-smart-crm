<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$current_user_id = get_current_user_id();

if ( ! current_user_can( 'manage_crm' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Zugriff verweigert.', 'cpsmartcrm' ) );
}

$can_manage_agents = current_user_can( 'manage_options' ) || ( function_exists( 'wpscrm_can_manage_chef_role' ) && wpscrm_can_manage_chef_role( $current_user_id ) );

$notices = array();

// Handle CRUD actions
if ( isset( $_POST['crm_agents_action'], $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'crm_agents_admin' ) ) {
	$action = sanitize_key( $_POST['crm_agents_action'] );
	
	if ( ! $can_manage_agents ) {
		$notices[] = array( 'type' => 'error', 'text' => __( 'Du darfst Agenten nicht verwalten.', 'cpsmartcrm' ) );
	} elseif ( 'create' === $action ) {
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$role_id = isset( $_POST['role_id'] ) ? absint( $_POST['role_id'] ) : 0;
		
		if ( ! $user_id ||  ! $role_id ) {
			$notices[] = array( 'type' => 'error', 'text' => __( 'Benutzer und Rolle sind erforderlich.', 'cpsmartcrm' ) );
		} else {
			$result = function_exists( 'wpscrm_create_agent' )
				? wpscrm_create_agent( $user_id, $role_id, 'active' )
				: null;
			
			if ( is_wp_error( $result ) ) {
				$notices[] = array( 'type' => 'error', 'text' => $result->get_error_message() );
			} elseif ( $result ) {
				$notices[] = array( 'type' => 'success', 'text' => __( 'Agent erfolgreich erstellt.', 'cpsmartcrm' ) );
			}
		}
	} elseif ( 'update' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( $_POST['agent_id'] ) : 0;
		$role_id = isset( $_POST['role_id'] ) ? absint( $_POST['role_id'] ) : 0;
		$status = isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'active', 'inactive', 'archived' ), true )
			? sanitize_key( $_POST['status'] )
			: 'active';
		
		if ( ! $agent_id || ! $role_id ) {
			$notices[] = array( 'type' => 'error', 'text' => __( 'Agent ID und Rolle sind erforderlich.', 'cpsmartcrm' ) );
		} else {
			$result = function_exists( 'wpscrm_update_agent' )
				? wpscrm_update_agent( $agent_id, array( 'role_id' => $role_id, 'status' => $status ) )
				: false;
			
			if ( $result !== false ) {
				$notices[] = array( 'type' => 'success', 'text' => __( 'Agent aktualisiert.', 'cpsmartcrm' ) );
			} else {
				$notices[] = array( 'type' => 'error', 'text' => __( 'Fehler beim Aktualisieren.', 'cpsmartcrm' ) );
			}
		}
	} elseif ( 'delete' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( $_POST['agent_id'] ) : 0;
		
		if ( ! $agent_id ) {
			$notices[] = array( 'type' => 'error', 'text' => __( 'Agent nicht gefunden.', 'cpsmartcrm' ) );
		} else {
			$result = function_exists( 'wpscrm_delete_agent' )
				? wpscrm_delete_agent( $agent_id )
				: false;
			
			if ( $result ) {
				$notices[] = array( 'type' => 'success', 'text' => __( 'Agent archiviert.', 'cpsmartcrm' ) );
			}
		}
	}
}

// Get data
$agents_table = WPsCRM_TABLE . 'agents';
$roles_table = WPsCRM_TABLE . 'agent_roles';

$agents = function_exists( 'wpscrm_get_all_agents' )
	? wpscrm_get_all_agents( 'active' )
	: array();

$all_roles = $wpdb->get_results( "SELECT * FROM $roles_table ORDER BY sort_order ASC, role_name ASC" );

// Get available users (not yet agents)
$available_users_query = "
	SELECT u.ID, u.display_name, u.user_email
	FROM {$wpdb->users} u
	LEFT JOIN $agents_table a ON u.ID = a.user_id
	WHERE a.id IS NULL
	ORDER BY u.display_name ASC
";
$available_users = $wpdb->get_results( $available_users_query );
?>

<style>
.agents-container { max-width: 1200px; margin: 20px auto; }
.agent-section { background: #fff; border: 1px solid #dcdcde; border-radius: 4px; margin-bottom: 20px; padding: 20px; }
.agent-section h3 { margin-top: 0; }
.agent-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 12px; }
.agent-form label { font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px; }
.agent-form input, .agent-form select { width: 100%; }
.agent-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.agent-table th, .agent-table td { padding: 10px 12px; border-bottom: 1px solid #f0f2f5; text-align: left; }
.agent-table th { background: #f8fafc; font-weight: 600; }
.status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #fff3cd; color: #856404; }
.status-archived { background: #f8d7da; color: #721c24; }
.action-btn { padding: 4px 8px; margin: 0 2px; font-size: 11px; cursor: pointer; }
.notice-inline { padding: 10px 12px; border-radius: 4px; margin-bottom: 10px; }
.notice-success { background: #ecfdf3; border: 1px solid #b7ebc6; color: #0f5132; }
.notice-error { background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; }
</style>

<div class="agents-container">
	<h2><?php esc_html_e( 'Agenten-Verwaltung', 'cpsmartcrm' ); ?></h2>

	<?php foreach ( $notices as $notice ) : ?>
		<div class="notice-inline <?php echo 'success' === $notice['type'] ? 'notice-success' : 'notice-error'; ?>">
			<?php echo esc_html( $notice['text'] ); ?>
		</div>
	<?php endforeach; ?>

	<?php if ( $can_manage_agents ) : ?>
		<!-- Neuen Agent hinzufügen -->
		<div class="agent-section">
			<h3><?php esc_html_e( 'Neuen Agent registrieren', 'cpsmartcrm' ); ?></h3>
			<form method="post">
				<?php wp_nonce_field( 'crm_agents_admin' ); ?>
				<input type="hidden" name="crm_agents_action" value="create" />
				
				<div class="agent-form">
					<div>
						<label><?php esc_html_e( 'Benutzer', 'cpsmartcrm' ); ?> *</label>
						<select name="user_id" required>
							<option value="">-- <?php esc_html_e( 'Bitte wählen', 'cpsmartcrm' ); ?> --</option>
							<?php foreach ( $available_users as $user ) : ?>
								<option value="<?php echo esc_attr( $user->ID ); ?>">
									<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div>
						<label><?php esc_html_e( 'Rolle', 'cpsmartcrm' ); ?> *</label>
						<select name="role_id" required>
							<option value="">-- <?php esc_html_e( 'Bitte wählen', 'cpsmartcrm' ); ?> --</option>
							<?php foreach ( $all_roles as $role ) : ?>
								<option value="<?php echo esc_attr( $role->id ); ?>">
									<?php echo esc_html( $role->role_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div style="display: flex; align-items: flex-end;">
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Agent erstellen', 'cpsmartcrm' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>

		<!-- Agenten-Tabelle -->
		<div class="agent-section">
			<h3><?php esc_html_e( 'Aktive Agenten', 'cpsmartcrm' ); ?></h3>
			
			<?php if ( empty( $agents ) ) : ?>
				<p><?php esc_html_e( 'Keine Agenten registriert.', 'cpsmartcrm' ); ?></p>
			<?php else : ?>
				<table class="agent-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'E-Mail', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Rolle', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Status', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Beigetreten', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Aktionen', 'cpsmartcrm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $agents as $agent ) : ?>
							<tr>
								<td><?php echo esc_html( $agent->display_name ); ?></td>
								<td><?php echo esc_html( $agent->user_email ); ?></td>
								<td><?php echo esc_html( $agent->role_name ); ?></td>
								<td>
									<span class="status-badge status-<?php echo esc_attr( $agent->status ); ?>">
										<?php echo esc_html( ucfirst( $agent->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( substr( $agent->joined_at, 0, 10 ) ); ?></td>
								<td>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'crm_agents_admin' ); ?>
										<input type="hidden" name="crm_agents_action" value="update" />
										<input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent->agent_id ); ?>" />
										<input type="hidden" name="role_id" value="<?php echo esc_attr( $agent->role_id ); ?>" />
										<select name="status" onchange="this.form.submit();">
											<option value="active" <?php selected( $agent->status, 'active' ); ?>>
												<?php esc_html_e( 'Aktiv', 'cpsmartcrm' ); ?>
											</option>
											<option value="inactive" <?php selected( $agent->status, 'inactive' ); ?>>
												<?php esc_html_e( 'Inaktiv', 'cpsmartcrm' ); ?>
											</option>
											<option value="archived" <?php selected( $agent->status, 'archived' ); ?>>
												<?php esc_html_e( 'Archiviert', 'cpsmartcrm' ); ?>
											</option>
										</select>
									</form>
									
									<form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_html_e( 'Agent archivieren?', 'cpsmartcrm' ); ?>');">
										<?php wp_nonce_field( 'crm_agents_admin' ); ?>
										<input type="hidden" name="crm_agents_action" value="delete" />
										<input type="hidden" name="agent_id" value="<?php echo esc_attr( $agent->agent_id ); ?>" />
										<button type="submit" class="button button-small action-btn">
											<?php esc_html_e( 'Löschen', 'cpsmartcrm' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="agent-section notice notice-warning inline">
			<p><?php esc_html_e( 'Du hast keine Berechtigung zur Agent-Verwaltung.', 'cpsmartcrm' ); ?></p>
		</div>
	<?php endif; ?>
</div>
