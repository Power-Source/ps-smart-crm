<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Agent-Rollen Liste
 * 
 * Backend-Verwaltung für flexible Agent-Rollen
 */

global $wpdb;
$table = WPsCRM_TABLE . 'agent_roles';

// Handle Actions (AJAX-ähnlich)
if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'crm_agent_roles' ) ) {
	
	switch ( $_POST['action'] ) {
		case 'add':
			$wpdb->insert(
				$table,
				array(
					'role_slug' => sanitize_key( $_POST['role_slug'] ),
					'role_name' => sanitize_text_field( $_POST['role_name'] ),
					'display_name' => sanitize_text_field( $_POST['display_name'] ),
					'department' => sanitize_text_field( $_POST['department'] ),
					'icon' => sanitize_text_field( $_POST['icon'] ),
					'show_in_contact' => isset( $_POST['show_in_contact'] ) ? 1 : 0,
					'is_system_role' => isset( $_POST['is_system_role'] ) ? 1 : 0,
					'sort_order' => absint( $_POST['sort_order'] ),
					'capabilities' => wp_json_encode( array() ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
			);
			
			echo '<div class="alert alert-success">' . __( 'Rolle erfolgreich hinzugefügt!', 'cpsmartcrm' ) . '</div>';
			break;
			
		case 'edit':
			$role_id = absint( $_POST['role_id'] );
			$existing_role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $role_id ) );
			
			$wpdb->update(
				$table,
				array(
					'role_slug' => sanitize_key( $_POST['role_slug'] ),
					'role_name' => sanitize_text_field( $_POST['role_name'] ),
					'display_name' => sanitize_text_field( $_POST['display_name'] ),
					'department' => sanitize_text_field( $_POST['department'] ),
					'icon' => sanitize_text_field( $_POST['icon'] ),
					'show_in_contact' => isset( $_POST['show_in_contact'] ) ? 1 : 0,
					// is_system_role kann nicht geändert werden
					'sort_order' => absint( $_POST['sort_order'] ),
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $role_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
				array( '%d' )
			);
			
			echo '<div class="alert alert-success">' . __( 'Rolle erfolgreich aktualisiert!', 'cpsmartcrm' ) . '</div>';
			break;
			
		case 'delete':
			$role_id = absint( $_POST['role_id'] );
			$existing_role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $role_id ) );
			
			if ( $existing_role && ! $existing_role->is_system_role ) {
				$wpdb->delete( $table, array( 'id' => $role_id ), array( '%d' ) );
				echo '<div class="alert alert-success">' . __( 'Rolle erfolgreich gelöscht!', 'cpsmartcrm' ) . '</div>';
			} else {
				echo '<div class="alert alert-danger">' . __( 'System-Rollen können nicht gelöscht werden!', 'cpsmartcrm' ) . '</div>';
			}
			break;
	
		case 'assign_users':
			$role_id = absint( $_POST['role_id'] );
			$selected_users = isset( $_POST['role_users'] ) ? array_map( 'absint', $_POST['role_users'] ) : array();
			
			// Alle Benutzer mit manage_crm finden
			$all_crm_users = get_users( array(
				'fields' => 'ID',
			) );
			
			// Rollen entfernen aus Benutzern, die nicht ausgewählt wurden
			foreach ( $all_crm_users as $user_id ) {
				$current_role = get_user_meta( $user_id, '_crm_agent_role', true );
				if ( $current_role === $edit_role->role_slug || get_user_meta( $user_id, '_crm_agent_role', true ) === $edit_role->role_slug ) {
					if ( ! in_array( $user_id, $selected_users ) ) {
						delete_user_meta( $user_id, '_crm_agent_role' );
					}
				}
			}
			
			// Ausgewählten Benutzern die Rolle hinzufügen
			foreach ( $selected_users as $user_id ) {
				update_user_meta( $user_id, '_crm_agent_role', $edit_role->role_slug );
			}
			
			echo '<div class="alert alert-success">' . __( 'Benutzer erfolgreich zugewiesen!', 'cpsmartcrm' ) . '</div>';
			break;
	}
}

// Fetch alle Rollen
$roles = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, role_name ASC" );

// Dashicons Liste für Icon-Picker
$dashicons = array(
	'dashicons-businessman' => 'Businessman',
	'dashicons-groups' => 'Groups',
	'dashicons-admin-users' => 'Users',
	'dashicons-admin-home' => 'Home Office',
	'dashicons-building' => 'Building',
	'dashicons-store' => 'Store',
	'dashicons-cart' => 'Cart',
	'dashicons-products' => 'Products',
	'dashicons-clipboard' => 'Clipboard',
	'dashicons-phone' => 'Phone',
	'dashicons-email' => 'Email',
	'dashicons-megaphone' => 'Megaphone',
	'dashicons-star-filled' => 'Star',
	'dashicons-admin-tools' => 'Tools',
);

// Edit Mode
$edit_role = null;
if ( isset( $_GET['edit'] ) ) {
	$edit_role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint( $_GET['edit'] ) ) );
}

?>

<style>
.crm-roles-container {
	max-width: 1200px;
	margin: 20px auto;
}
.crm-roles-form {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccc;
	border-radius: 4px;
	margin-bottom: 30px;
}
.crm-roles-table {
	background: #fff;
	border: 1px solid #ccc;
}
.crm-roles-table table {
	width: 100%;
	border-collapse: collapse;
}
.crm-roles-table th,
.crm-roles-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid #e5e5e5;
}
.crm-roles-table th {
	background: #f7f7f7;
	font-weight: 600;
}
.crm-roles-table .icon-preview {
	font-size: 24px;
	color: #0073aa;
}
.crm-roles-table .badge {
	display: inline-block;
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
}
.crm-roles-table .badge-system {
	background: #d63638;
	color: #fff;
}
.crm-roles-table .badge-contact {
	background: #00a32a;
	color: #fff;
}
.form-row {
	margin-bottom: 15px;
}
.form-row label {
	display: block;
	font-weight: 600;
	margin-bottom: 5px;
}
.form-row input[type="text"],
.form-row input[type="number"],
.form-row select {
	width: 100%;
	padding: 8px;
	border: 1px solid #ccc;
	border-radius: 3px;
}
.form-row-inline {
	display: flex;
	align-items: center;
	gap: 10px;
}
.btn-actions {
	display: flex;
	gap: 5px;
}
</style>

<div class="crm-roles-container">
	
	<h2>
		<span class="dashicons dashicons-groups" style="font-size:28px;"></span>
		<?php echo $edit_role ? __( 'Rolle bearbeiten', 'cpsmartcrm' ) : __( 'Neue Agent-Rolle erstellen', 'cpsmartcrm' ); ?>
	</h2>
	
	<div class="crm-roles-form">
		<form method="post" action="">
			<?php wp_nonce_field( 'crm_agent_roles' ); ?>
	.role-info-box {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white;
		padding: 20px;
		border-radius: 4px;
		margin-bottom: 20px;
		display: none;
	}
	.role-info-box.show {
		display: block;
	}
	.role-info-box h4 {
		margin: 0 0 10px 0;
		font-size: 16px;
	}
	.role-info-box ul {
		margin: 0;
		padding-left: 20px;
	}
	.role-info-box li {
		margin: 5px 0;
		font-size: 14px;
	}
	.role-info-box .no-users {
		font-style: italic;
		opacity: 0.9;
	}
			<input type="hidden" name="action" value="<?php echo $edit_role ? 'edit' : 'add'; ?>" />
			<?php if ( $edit_role ) : ?>
				<input type="hidden" name="role_id" value="<?php echo esc_attr( $edit_role->id ); ?>" />
		<div class="role-info-box show">
			<h4><?php _e( '🔗 Derzeit zugeordnet zu:', 'cpsmartcrm' ); ?></h4>
			<?php
			$all_users = get_users();
			$role_users = array();
			foreach ( $all_users as $user ) {
				$user_role = get_user_meta( $user->ID, '_crm_agent_role', true );
				if ( $user_role === $edit_role->role_slug ) {
					$role_users[] = $user->display_name . ' (' . $user->user_email . ')';
				}
			}
		
			if ( empty( $role_users ) ) {
				echo '<p class="no-users">' . __( 'Keine Benutzer zugeordnet', 'cpsmartcrm' ) . '</p>';
			} else {
				echo '<ul><li>' . implode( '</li><li>', $role_users ) . '</li></ul>';
			}
			?>
		</div>
			<?php endif; ?>
			
			<div class="form-row">
				<label><?php _e( 'Rollen-Slug (eindeutig)', 'cpsmartcrm' ); ?> *</label>
				<input type="text" name="role_slug" required 
					   value="<?php echo $edit_role ? esc_attr( $edit_role->role_slug ) : ''; ?>"
					   pattern="[a-z0-9_-]+" 
					   placeholder="chef, buero, vertrieb, lager..."
					   <?php echo ( $edit_role && $edit_role->is_system_role ) ? 'readonly' : ''; ?> />
				<small><?php _e( 'Nur Kleinbuchstaben, Zahlen, Unterstriche und Bindestriche', 'cpsmartcrm' ); ?></small>
			</div>
			
			<div class="form-row">
				<label><?php _e( 'Rollen-Name (intern)', 'cpsmartcrm' ); ?> *</label>
				<input type="text" name="role_name" required 
					   value="<?php echo $edit_role ? esc_attr( $edit_role->role_name ) : ''; ?>"
					   placeholder="Chef, Büro, Vertrieb..." />
			</div>
			
			<div class="form-row">
				<label><?php _e( 'Anzeigename (Frontend)', 'cpsmartcrm' ); ?></label>
				<input type="text" name="display_name" 
					   value="<?php echo $edit_role ? esc_attr( $edit_role->display_name ) : ''; ?>"
					   placeholder="Chef kontaktieren, Büro kontaktieren..." />
			</div>
			
			<div class="form-row">
				<label><?php _e( 'Abteilung', 'cpsmartcrm' ); ?></label>
				<input type="text" name="department" 
					   value="<?php echo $edit_role ? esc_attr( $edit_role->department ) : ''; ?>"
					   placeholder="Verwaltung, Verkauf, Lager..." />
			</div>
			
			<div class="form-row">
				<label><?php _e( 'Icon (Dashicons)', 'cpsmartcrm' ); ?></label>
				<select name="icon" id="role-icon-select">
					<?php foreach ( $dashicons as $icon_class => $icon_name ) : ?>
						<option value="<?php echo esc_attr( $icon_class ); ?>"
								<?php selected( $edit_role ? $edit_role->icon : '', $icon_class ); ?>>
							<?php echo esc_html( $icon_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div id="icon-preview" style="margin-top:10px;font-size:32px;"></div>
			</div>
			
			<div class="form-row">
				<label><?php _e( 'Sortierreihenfolge', 'cpsmartcrm' ); ?></label>
				<input type="number" name="sort_order" value="<?php echo $edit_role ? esc_attr( $edit_role->sort_order ) : 50; ?>" min="0" max="999" />
				<small><?php _e( 'Niedrigere Zahlen erscheinen zuerst', 'cpsmartcrm' ); ?></small>
			</div>
			
			<div class="form-row form-row-inline">
				<label>
					<input type="checkbox" name="show_in_contact" value="1" 
						   <?php checked( $edit_role ? $edit_role->show_in_contact : 0, 1 ); ?> />
					<?php _e( 'Im Frontend als Kontakt-Button anzeigen', 'cpsmartcrm' ); ?>
				</label>
			</div>
			
			<?php if ( ! $edit_role ) : ?>
				<div class="form-row form-row-inline">
					<label>
						<input type="checkbox" name="is_system_role" value="1" />
						<?php _e( 'System-Rolle (kann nicht gelöscht werden)', 'cpsmartcrm' ); ?>
					</label>
				</div>
			<?php endif; ?>
			
			<div class="form-row">
				<button type="submit" class="button button-primary">
					<?php echo $edit_role ? __( 'Rolle aktualisieren', 'cpsmartcrm' ) : __( 'Rolle erstellen', 'cpsmartcrm' ); ?>
				</button>
				<?php if ( $edit_role ) : ?>
					<a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=agent-roles/list.php' ); ?>" class="button">
						<?php _e( 'Abbrechen', 'cpsmartcrm' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>
	</div>
	
	<?php if ( $edit_role ) : ?>
		<div class="crm-role-users" id="role-users">
			<h3><?php _e( 'Benutzer dieser Rolle', 'cpsmartcrm' ); ?></h3>
			
			<form method="post">
				<?php wp_nonce_field( 'crm_agent_roles' ); ?>
				<input type="hidden" name="action" value="assign_users" />
				<input type="hidden" name="role_id" value="<?php echo esc_attr( $edit_role->id ); ?>" />
				
				<div style="background:#f9fafb;padding:15px;border-radius:4px;max-height:300px;overflow-y:auto;border:1px solid #e5e5e5;">
					<?php
					$all_users = get_users( array(
						'orderby' => 'display_name',
						'order' => 'ASC',
					) );
					
					if ( empty( $all_users ) ) {
						echo '<p style="color:#999;">' . __( 'Keine Benutzer vorhanden.', 'cpsmartcrm' ) . '</p>';
					} else {
						foreach ( $all_users as $user ) {
							$user_role = get_user_meta( $user->ID, '_crm_agent_role', true );
							$is_checked = $user_role === $edit_role->role_slug;
							?>
							<div style="margin-bottom:8px;">
								<label style="display:flex;align-items:center;cursor:pointer;padding:8px;border-radius:3px;transition:background 0.2s;">
									<input type="checkbox" name="role_users[]" value="<?php echo esc_attr( $user->ID ); ?>" 
										   <?php checked( $is_checked, true ); ?> 
										   style="margin-right:10px;" />
									<strong><?php echo esc_html( $user->display_name ); ?></strong>
									<span style="margin-left:auto;font-size:12px;color:#999;">
										(<?php echo esc_html( $user->user_email ); ?>)
										<?php if ( $user_role && $user_role !== $edit_role->role_slug ) : ?>
											<span style="color:#f59e0b;font-weight:600;margin-left:10px;">
												<?php _e( 'hat Rolle: ', 'cpsmartcrm' ); echo esc_html( $user_role ); ?>
											</span>
										<?php endif; ?>
									</span>
								</label>
							</div>
							<?php
						}
					}
					?>
				</div>
				
				<button type="submit" class="button button-primary" style="margin-top:15px;">
					<?php _e( 'Benutzer speichern', 'cpsmartcrm' ); ?>
				</button>
			</form>
		</div>
		
		<style>
			.crm-role-users {
				background: #fff;
				padding: 20px;
				border-radius: 4px;
				border: 1px solid #e5e5e5;
				margin-bottom: 30px;
				margin-top: 30px;
			}
			.crm-role-users h3 {
				margin-top: 0;
				margin-bottom: 15px;
				color: #374151;
			}
		</style>
	<?php endif; ?>
	
	<h2><?php _e( 'Vorhandene Rollen', 'cpsmartcrm' ); ?></h2>
	
	<div class="crm-roles-table">
		<table>
			<thead>
				<tr>
					<th><?php _e( 'Icon', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Rolle', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Slug', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Abteilung', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Anzeigename', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Reihenfolge', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Benutzer', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Eigenschaften', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Aktionen', 'cpsmartcrm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $roles ) ) : ?>
					<tr>
						<td colspan="9" style="text-align:center;padding:30px;">
							<?php _e( 'Keine Rollen gefunden. Erstelle deine erste Rolle!', 'cpsmartcrm' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $roles as $role ) : ?>
						<?php
						// Zähle Benutzer mit dieser Rolle
						$users_with_role = count_users();
						$role_user_count = 0;
						$all_users = get_users();
						foreach ( $all_users as $user ) {
							$user_role = get_user_meta( $user->ID, '_crm_agent_role', true );
							if ( $user_role === $role->role_slug ) {
								$role_user_count++;
							}
						}
						?>
						<tr>
							<td>
								<span class="icon-preview dashicons <?php echo esc_attr( $role->icon ); ?>"></span>
							</td>
							<td><strong><?php echo esc_html( $role->role_name ); ?></strong></td>
							<td><code><?php echo esc_html( $role->role_slug ); ?></code></td>
							<td><?php echo esc_html( $role->department ); ?></td>
							<td><?php echo esc_html( $role->display_name ); ?></td>
							<td><?php echo esc_html( $role->sort_order ); ?></td>
							<td>
								<a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=agent-roles/list.php&edit=' . $role->id ); ?>#role-users" 
								   style="color:#667eea;text-decoration:none;font-weight:600;">
									<?php echo $role_user_count; ?> 
									<?php echo $role_user_count === 1 ? __( 'Benutzer', 'cpsmartcrm' ) : __( 'Benutzer', 'cpsmartcrm' ); ?>
								</a>
							</td>
							<td>
								<?php if ( $role->is_system_role ) : ?>
									<span class="badge badge-system"><?php _e( 'System', 'cpsmartcrm' ); ?></span>
								<?php endif; ?>
								<?php if ( $role->show_in_contact ) : ?>
									<span class="badge badge-contact"><?php _e( 'Kontakt', 'cpsmartcrm' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<div class="btn-actions">
									<a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=agent-roles/list.php&edit=' . $role->id ); ?>" 
									   class="button button-small">
										<?php _e( 'Bearbeiten', 'cpsmartcrm' ); ?>
									</a>
									<?php if ( ! $role->is_system_role ) : ?>
										<form method="post" style="display:inline;" 
											  onsubmit="return confirm('<?php _e( 'Wirklich löschen?', 'cpsmartcrm' ); ?>');">
											<?php wp_nonce_field( 'crm_agent_roles' ); ?>
											<input type="hidden" name="action" value="delete" />
											<input type="hidden" name="role_id" value="<?php echo esc_attr( $role->id ); ?>" />
											<button type="submit" class="button button-small button-link-delete">
												<?php _e( 'Löschen', 'cpsmartcrm' ); ?>
											</button>
										</form>
									<?php endif; ?>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	
</div>

<script>
jQuery(document).ready(function($) {
	// Icon Preview
	function updateIconPreview() {
		var selectedIcon = $('#role-icon-select').val();
		$('#icon-preview').html('<span class="dashicons ' + selectedIcon + '"></span>');
	}
	
	$('#role-icon-select').on('change', updateIconPreview);
	updateIconPreview(); // Initial
});
</script>
