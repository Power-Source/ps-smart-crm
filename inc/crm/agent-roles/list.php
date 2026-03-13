<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table = WPsCRM_TABLE . 'agent_roles';

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

$default_caps = array(
	'wp_role' => '',
	'can_view_accounting' => 0,
	'can_edit_accounting' => 0,
	'can_view_all_accounting' => 0,
	'can_view_documents' => 0,
	'can_edit_documents' => 0,
	'can_view_all_documents' => 0,
	'can_view_customers' => 0,
	'can_edit_customers' => 0,
);

$current_user_id = get_current_user_id();
$current_user_can_manage_chef = function_exists( 'wpscrm_can_manage_chef_role' )
	? wpscrm_can_manage_chef_role( $current_user_id )
	: ( 'chef' === get_user_meta( $current_user_id, '_crm_agent_role', true ) );

if ( isset( $_POST['action'], $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'crm_agent_roles' ) ) {
	switch ( sanitize_key( $_POST['action'] ) ) {
		case 'add':
				$new_role_slug = sanitize_key( $_POST['role_slug'] ?? '' );
				if ( 'chef' === $new_role_slug && ! $current_user_can_manage_chef ) {
					echo '<div class="alert alert-danger">' . esc_html__( 'Nur der aktuelle Chef darf die Chef-Rolle anlegen oder bearbeiten.', 'cpsmartcrm' ) . '</div>';
					break;
				}

			$caps = array(
					'wp_role' => ( 'chef' === $new_role_slug ) ? '' : sanitize_key( $_POST['wp_role'] ?? '' ),
				'can_view_accounting' => isset( $_POST['can_view_accounting'] ) ? 1 : 0,
				'can_edit_accounting' => isset( $_POST['can_edit_accounting'] ) ? 1 : 0,
				'can_view_all_accounting' => isset( $_POST['can_view_all_accounting'] ) ? 1 : 0,
				'can_view_documents' => isset( $_POST['can_view_documents'] ) ? 1 : 0,
				'can_edit_documents' => isset( $_POST['can_edit_documents'] ) ? 1 : 0,
				'can_view_all_documents' => isset( $_POST['can_view_all_documents'] ) ? 1 : 0,
				'can_view_customers' => isset( $_POST['can_view_customers'] ) ? 1 : 0,
				'can_edit_customers' => isset( $_POST['can_edit_customers'] ) ? 1 : 0,
			);

			$wpdb->insert(
				$table,
				array(
						'role_slug' => $new_role_slug,
					'role_name' => sanitize_text_field( $_POST['role_name'] ?? '' ),
					'display_name' => sanitize_text_field( $_POST['display_name'] ?? '' ),
					'department' => sanitize_text_field( $_POST['department'] ?? '' ),
					'icon' => sanitize_text_field( $_POST['icon'] ?? 'dashicons-businessman' ),
					'show_in_contact' => isset( $_POST['show_in_contact'] ) ? 1 : 0,
					'is_system_role' => isset( $_POST['is_system_role'] ) ? 1 : 0,
					'sort_order' => absint( $_POST['sort_order'] ?? 0 ),
					'capabilities' => wp_json_encode( $caps ),
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
			);

			echo '<div class="alert alert-success">' . esc_html__( 'Rolle erfolgreich hinzugefügt!', 'cpsmartcrm' ) . '</div>';
			break;

		case 'edit':
			$role_id = absint( $_POST['role_id'] ?? 0 );
			$role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $role_id ) );
			if ( $role ) {
					$updated_role_slug = sanitize_key( $_POST['role_slug'] ?? '' );
					$is_chef_role = ( 'chef' === $role->role_slug || 'chef' === $updated_role_slug );
					if ( $is_chef_role && ! $current_user_can_manage_chef ) {
						echo '<div class="alert alert-danger">' . esc_html__( 'Nur der aktuelle Chef darf die Chef-Rolle bearbeiten oder übergeben.', 'cpsmartcrm' ) . '</div>';
						break;
					}

				$caps = array(
						'wp_role' => $is_chef_role ? '' : sanitize_key( $_POST['wp_role'] ?? '' ),
					'can_view_accounting' => isset( $_POST['can_view_accounting'] ) ? 1 : 0,
					'can_edit_accounting' => isset( $_POST['can_edit_accounting'] ) ? 1 : 0,
					'can_view_all_accounting' => isset( $_POST['can_view_all_accounting'] ) ? 1 : 0,
					'can_view_documents' => isset( $_POST['can_view_documents'] ) ? 1 : 0,
					'can_edit_documents' => isset( $_POST['can_edit_documents'] ) ? 1 : 0,
					'can_view_all_documents' => isset( $_POST['can_view_all_documents'] ) ? 1 : 0,
					'can_view_customers' => isset( $_POST['can_view_customers'] ) ? 1 : 0,
					'can_edit_customers' => isset( $_POST['can_edit_customers'] ) ? 1 : 0,
				);

				$wpdb->update(
					$table,
					array(
							'role_slug' => $updated_role_slug,
						'role_name' => sanitize_text_field( $_POST['role_name'] ?? '' ),
						'display_name' => sanitize_text_field( $_POST['display_name'] ?? '' ),
						'department' => sanitize_text_field( $_POST['department'] ?? '' ),
						'icon' => sanitize_text_field( $_POST['icon'] ?? 'dashicons-businessman' ),
						'show_in_contact' => isset( $_POST['show_in_contact'] ) ? 1 : 0,
						'sort_order' => absint( $_POST['sort_order'] ?? 0 ),
						'capabilities' => wp_json_encode( $caps ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => $role_id ),
					array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ),
					array( '%d' )
				);

				echo '<div class="alert alert-success">' . esc_html__( 'Rolle erfolgreich aktualisiert!', 'cpsmartcrm' ) . '</div>';
			}
			break;

		case 'delete':
			$role_id = absint( $_POST['role_id'] ?? 0 );
			$role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $role_id ) );
			if ( $role && ! (int) $role->is_system_role ) {
				$wpdb->delete( $table, array( 'id' => $role_id ), array( '%d' ) );
				echo '<div class="alert alert-success">' . esc_html__( 'Rolle erfolgreich gelöscht!', 'cpsmartcrm' ) . '</div>';
			} else {
				echo '<div class="alert alert-danger">' . esc_html__( 'System-Rollen können nicht gelöscht werden!', 'cpsmartcrm' ) . '</div>';
			}
			break;

		case 'assign_users':
			$role_id = absint( $_POST['role_id'] ?? 0 );
			$role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $role_id ) );
			if ( $role ) {
				$selected_users = isset( $_POST['role_users'] ) ? array_values( array_unique( array_map( 'absint', (array) $_POST['role_users'] ) ) ) : array();

					if ( 'chef' === $role->role_slug && ! $current_user_can_manage_chef ) {
						echo '<div class="alert alert-danger">' . esc_html__( 'Nur der aktuelle Chef darf die Chef-Rolle übergeben.', 'cpsmartcrm' ) . '</div>';
						break;
					}

					if ( 'chef' === $role->role_slug && 1 !== count( $selected_users ) ) {
						echo '<div class="alert alert-danger">' . esc_html__( 'Für Chef muss genau ein Benutzer ausgewählt sein.', 'cpsmartcrm' ) . '</div>';
						break;
					}

				$all_users = get_users( array( 'fields' => 'ID' ) );

				foreach ( $all_users as $user_id ) {
					$current_role_slug = get_user_meta( $user_id, '_crm_agent_role', true );
					if ( $current_role_slug === $role->role_slug && ! in_array( $user_id, $selected_users, true ) ) {
						delete_user_meta( $user_id, '_crm_agent_role' );
						if ( function_exists( 'wpscrm_remove_role_permissions' ) ) {
							wpscrm_remove_role_permissions( $user_id, $role->role_slug );
						}
					}
				}

				foreach ( $selected_users as $user_id ) {
					update_user_meta( $user_id, '_crm_agent_role', $role->role_slug );
					if ( function_exists( 'wpscrm_apply_role_permissions' ) ) {
						wpscrm_apply_role_permissions( $user_id, $role->role_slug );
					}
				}

				echo '<div class="alert alert-success">' . esc_html__( 'Benutzerzuordnung gespeichert.', 'cpsmartcrm' ) . '</div>';
			}
			break;
	}
}

$roles = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, role_name ASC" );

$edit_role = null;
if ( isset( $_GET['edit'] ) ) {
	$edit_role = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint( $_GET['edit'] ) ) );
}

$edit_caps = $default_caps;
if ( $edit_role && ! empty( $edit_role->capabilities ) ) {
	$decoded = json_decode( $edit_role->capabilities, true );
	if ( is_array( $decoded ) ) {
		$edit_caps = wp_parse_args( $decoded, $default_caps );
	}
}

$users_assigned_to_edit = array();
if ( $edit_role ) {
	$all_users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
	foreach ( $all_users as $user ) {
		if ( get_user_meta( $user->ID, '_crm_agent_role', true ) === $edit_role->role_slug ) {
			$users_assigned_to_edit[] = $user;
		}
	}
}

$is_editing_chef = $edit_role && 'chef' === $edit_role->role_slug;
$chef_edit_locked = $is_editing_chef && ! $current_user_can_manage_chef;
$show_role_form = (bool) $edit_role;
?>

<style>
.crm-roles-container { max-width: 1300px; margin: 20px auto; }
.crm-roles-form, .crm-roles-table, .role-users-box { background: #fff; border: 1px solid #dcdcde; border-radius: 4px; margin-bottom: 24px; }
.crm-roles-form { padding: 20px; }
.crm-roles-table table { width: 100%; border-collapse: collapse; }
.crm-roles-table th, .crm-roles-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f1; vertical-align: top; }
.crm-roles-table th { background: #f6f7f7; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(300px, 1fr)); gap: 16px; }
.form-row label { font-weight: 600; display: block; margin-bottom: 6px; }
.form-row input[type="text"], .form-row input[type="number"], .form-row select { width: 100%; }
.form-row small { color: #666; }
.full-row { grid-column: 1 / -1; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; margin-right: 6px; }
.badge-system { background: #d63638; color: #fff; }
.badge-contact { background: #00a32a; color: #fff; }
.permissions-grid { display: grid; grid-template-columns: repeat(3, minmax(220px, 1fr)); gap: 14px; background: #f6f7f7; padding: 12px; border: 1px solid #e3e5e8; }
.permissions-help { margin-bottom: 10px; padding: 10px 12px; border: 1px solid #dcdcde; border-radius: 4px; background: #fff; }
.permissions-help p { margin: 0 0 8px; color: #2c3338; }
.permissions-help ul { margin: 0; padding-left: 18px; color: #50575e; }
.permissions-help li { margin: 3px 0; }
.role-users-box { padding: 18px; }
.role-users-box ul { margin: 8px 0 0; padding-left: 20px; }
.inline-list { display: inline-flex; flex-wrap: wrap; gap: 6px; }
.inline-pill { display: inline-block; background: #eef4ff; padding: 2px 8px; border-radius: 999px; font-size: 12px; }
.role-form-toggle-wrap { margin-bottom: 12px; }
</style>

<div class="crm-roles-container">
	<?php if ( $edit_role ) : ?>
		<h2><?php esc_html_e( 'Rolle bearbeiten', 'cpsmartcrm' ); ?></h2>
	<?php else : ?>
		<div class="role-form-toggle-wrap">
			<button type="button" class="button button-primary" id="toggle-role-form" aria-expanded="false" aria-controls="crm-role-form-wrapper">
				<?php esc_html_e( 'Neue Agent-Rolle erstellen', 'cpsmartcrm' ); ?>
			</button>
		</div>
	<?php endif; ?>

	<?php if ( $edit_role ) : ?>
		<div class="role-users-box" id="role-users">
			<strong><?php esc_html_e( 'Aktuell zugeordnete Benutzer:', 'cpsmartcrm' ); ?></strong>
			<?php if ( empty( $users_assigned_to_edit ) ) : ?>
				<p style="margin-top:8px;color:#666;"><?php esc_html_e( 'Dieser Rolle ist aktuell kein Benutzer zugeordnet.', 'cpsmartcrm' ); ?></p>
			<?php else : ?>
				<ul>
					<?php foreach ( $users_assigned_to_edit as $assigned_user ) : ?>
						<li><?php echo esc_html( $assigned_user->display_name . ' (' . $assigned_user->user_email . ')' ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="crm-roles-form" id="crm-role-form-wrapper"<?php echo $show_role_form ? '' : ' style="display:none;"'; ?>>
		<?php if ( ! $edit_role ) : ?>
			<h2><?php esc_html_e( 'Neue Agent-Rolle erstellen', 'cpsmartcrm' ); ?></h2>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'crm_agent_roles' ); ?>
			<input type="hidden" name="action" value="<?php echo $edit_role ? 'edit' : 'add'; ?>" />
			<?php if ( $edit_role ) : ?>
				<input type="hidden" name="role_id" value="<?php echo esc_attr( $edit_role->id ); ?>" />
			<?php endif; ?>

			<div class="form-grid">
				<?php if ( $chef_edit_locked ) : ?>
					<div class="form-row full-row">
						<div class="notice notice-warning inline" style="margin:0;">
							<p><?php esc_html_e( 'Die Chef-Rolle darf nur vom aktuellen Chef bearbeitet und übergeben werden.', 'cpsmartcrm' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
				<div class="form-row">
					<label><?php esc_html_e( 'Rollen-Slug (eindeutig)', 'cpsmartcrm' ); ?> *</label>
					<input type="text" name="role_slug" pattern="[a-z0-9_-]+" required value="<?php echo esc_attr( $edit_role ? $edit_role->role_slug : '' ); ?>" <?php echo ( $edit_role && (int) $edit_role->is_system_role ) ? 'readonly' : ''; ?> />
				</div>
				<div class="form-row">
					<label><?php esc_html_e( 'Rollen-Name', 'cpsmartcrm' ); ?> *</label>
					<input type="text" name="role_name" required value="<?php echo esc_attr( $edit_role ? $edit_role->role_name : '' ); ?>" />
				</div>

				<div class="form-row">
					<label><?php esc_html_e( 'Anzeigename (Frontend)', 'cpsmartcrm' ); ?></label>
					<input type="text" name="display_name" value="<?php echo esc_attr( $edit_role ? $edit_role->display_name : '' ); ?>" />
				</div>
				<div class="form-row">
					<label><?php esc_html_e( 'Abteilung', 'cpsmartcrm' ); ?></label>
					<input type="text" name="department" value="<?php echo esc_attr( $edit_role ? $edit_role->department : '' ); ?>" />
				</div>

				<div class="form-row">
					<label><?php esc_html_e( 'Icon (Dashicons)', 'cpsmartcrm' ); ?></label>
					<select name="icon" id="role-icon-select">
						<?php foreach ( $dashicons as $icon_class => $icon_name ) : ?>
							<option value="<?php echo esc_attr( $icon_class ); ?>" <?php selected( $edit_role ? $edit_role->icon : '', $icon_class ); ?>><?php echo esc_html( $icon_name ); ?></option>
						<?php endforeach; ?>
					</select>
					<div id="icon-preview" style="margin-top:8px;font-size:26px;"></div>
				</div>
				<div class="form-row">
					<label><?php esc_html_e( 'Sortierreihenfolge', 'cpsmartcrm' ); ?></label>
					<input type="number" name="sort_order" min="0" max="999" value="<?php echo esc_attr( $edit_role ? $edit_role->sort_order : 50 ); ?>" />
				</div>

				<div class="form-row">
					<label><?php esc_html_e( 'WordPress-Rolle (optional)', 'cpsmartcrm' ); ?></label>
					<select name="wp_role" <?php disabled( $is_editing_chef ); ?>>
						<option value=""><?php esc_html_e( '— Keine zusätzliche WP-Rolle —', 'cpsmartcrm' ); ?></option>
						<?php
						global $wp_roles;
						foreach ( $wp_roles->roles as $role_key => $role_info ) {
							echo '<option value="' . esc_attr( $role_key ) . '" ' . selected( $edit_caps['wp_role'], $role_key, false ) . '>' . esc_html( $role_info['name'] ) . '</option>';
						}
						?>
					</select>
					<?php if ( $is_editing_chef ) : ?>
						<small><?php esc_html_e( 'Chef betrifft nur CRM-Rechte, keine WP-Rollenzuweisung.', 'cpsmartcrm' ); ?></small>
					<?php else : ?>
						<small><?php esc_html_e( 'Wird bei Benutzerzuweisung automatisch gesetzt.', 'cpsmartcrm' ); ?></small>
					<?php endif; ?>
				</div>

				<div class="form-row full-row">
					<label><?php esc_html_e( 'Sichtbarkeit & Typ', 'cpsmartcrm' ); ?></label>
					<label><input type="checkbox" name="show_in_contact" value="1" <?php checked( $edit_role ? $edit_role->show_in_contact : 0, 1 ); ?> /> <?php esc_html_e( 'Im Frontend als Kontakt-Button anzeigen', 'cpsmartcrm' ); ?></label>
					<?php if ( ! $edit_role ) : ?>
						&nbsp;&nbsp;&nbsp;
						<label><input type="checkbox" name="is_system_role" value="1" /> <?php esc_html_e( 'System-Rolle (nicht löschbar)', 'cpsmartcrm' ); ?></label>
					<?php endif; ?>
				</div>

				<div class="form-row full-row">
					<label><?php esc_html_e( 'Rechte', 'cpsmartcrm' ); ?></label>
					<div class="permissions-help">
						<p><strong><?php esc_html_e( 'Kurz erklärt:', 'cpsmartcrm' ); ?></strong> <?php esc_html_e( 'So greifen die Häkchen:', 'cpsmartcrm' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Anzeigen = nur lesen, nichts ändern.', 'cpsmartcrm' ); ?></li>
							<li><?php esc_html_e( 'Bearbeiten = erstellen, ändern, löschen (je nach Bereich).', 'cpsmartcrm' ); ?></li>
							<li><?php esc_html_e( 'Alle Datensätze sehen = nicht nur eigene, sondern alles.', 'cpsmartcrm' ); ?></li>
							<li><?php esc_html_e( 'Ohne Alle-Datensätze siehst du nur deine eigenen Einträge.', 'cpsmartcrm' ); ?></li>
						</ul>
					</div>
					<div class="permissions-grid">
						<div>
							<strong><?php esc_html_e( 'Buchhaltung', 'cpsmartcrm' ); ?></strong><br>
							<label><input type="checkbox" name="can_view_accounting" value="1" <?php checked( $edit_caps['can_view_accounting'], 1 ); ?> /> <?php esc_html_e( 'Anzeigen', 'cpsmartcrm' ); ?></label><br>
							<label><input type="checkbox" name="can_edit_accounting" value="1" <?php checked( $edit_caps['can_edit_accounting'], 1 ); ?> /> <?php esc_html_e( 'Bearbeiten', 'cpsmartcrm' ); ?></label>
							<br><label><input type="checkbox" name="can_view_all_accounting" value="1" <?php checked( $edit_caps['can_view_all_accounting'], 1 ); ?> /> <?php esc_html_e( 'Alle Datensätze sehen', 'cpsmartcrm' ); ?></label>
						</div>
						<div>
							<strong><?php esc_html_e( 'Dokumente', 'cpsmartcrm' ); ?></strong><br>
							<label><input type="checkbox" name="can_view_documents" value="1" <?php checked( $edit_caps['can_view_documents'], 1 ); ?> /> <?php esc_html_e( 'Anzeigen', 'cpsmartcrm' ); ?></label><br>
							<label><input type="checkbox" name="can_edit_documents" value="1" <?php checked( $edit_caps['can_edit_documents'], 1 ); ?> /> <?php esc_html_e( 'Bearbeiten/Löschen', 'cpsmartcrm' ); ?></label>
							<br><label><input type="checkbox" name="can_view_all_documents" value="1" <?php checked( $edit_caps['can_view_all_documents'], 1 ); ?> /> <?php esc_html_e( 'Alle Datensätze sehen', 'cpsmartcrm' ); ?></label>
						</div>
						<div>
							<strong><?php esc_html_e( 'Kunden', 'cpsmartcrm' ); ?></strong><br>
							<label><input type="checkbox" name="can_view_customers" value="1" <?php checked( $edit_caps['can_view_customers'], 1 ); ?> /> <?php esc_html_e( 'Anzeigen', 'cpsmartcrm' ); ?></label><br>
							<label><input type="checkbox" name="can_edit_customers" value="1" <?php checked( $edit_caps['can_edit_customers'], 1 ); ?> /> <?php esc_html_e( 'Bearbeiten/Löschen', 'cpsmartcrm' ); ?></label>
						</div>
					</div>
				</div>

				<div class="form-row full-row">
					<?php if ( ! $chef_edit_locked ) : ?>
						<button type="submit" class="button button-primary"><?php echo $edit_role ? esc_html__( 'Rolle aktualisieren', 'cpsmartcrm' ) : esc_html__( 'Rolle erstellen', 'cpsmartcrm' ); ?></button>
					<?php endif; ?>
					<?php if ( $edit_role ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-crm&p=agent-roles/list.php' ) ); ?>" class="button"><?php esc_html_e( 'Abbrechen', 'cpsmartcrm' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</form>
	</div>

	<?php if ( $edit_role && ! $chef_edit_locked ) : ?>
		<div class="role-users-box">
			<h3 style="margin-top:0;"><?php esc_html_e( 'Benutzer dieser Rolle verwalten', 'cpsmartcrm' ); ?></h3>
			<?php if ( $is_editing_chef ) : ?>
				<p style="margin:8px 0 12px;color:#8a6d00;"><strong><?php esc_html_e( 'Hinweis:', 'cpsmartcrm' ); ?></strong> <?php esc_html_e( 'Wenn du einen anderen Benutzer als Chef speicherst, verliert der bisherige Chef diese Rolle sofort.', 'cpsmartcrm' ); ?></p>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'crm_agent_roles' ); ?>
				<input type="hidden" name="action" value="assign_users" />
				<input type="hidden" name="role_id" value="<?php echo esc_attr( $edit_role->id ); ?>" />
				<div style="max-height:280px; overflow:auto; border:1px solid #e3e5e8; padding:12px; background:#f9f9f9;">
					<?php
					$all_users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
					if ( empty( $all_users ) ) {
						echo '<p>' . esc_html__( 'Keine Benutzer vorhanden.', 'cpsmartcrm' ) . '</p>';
					} else {
						foreach ( $all_users as $user ) {
							$current_role_slug = get_user_meta( $user->ID, '_crm_agent_role', true );
							$checked = ( $current_role_slug === $edit_role->role_slug );
							echo '<label style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-bottom:1px solid #ececec;">';
							echo '<span><input type="checkbox" name="role_users[]" value="' . esc_attr( $user->ID ) . '" ' . checked( $checked, true, false ) . '> <strong>' . esc_html( $user->display_name ) . '</strong> <small>(' . esc_html( $user->user_email ) . ')</small></span>';
							if ( $current_role_slug && ! $checked ) {
								echo '<span style="color:#996800;">' . esc_html__( 'hat Rolle:', 'cpsmartcrm' ) . ' ' . esc_html( $current_role_slug ) . '</span>';
							}
							echo '</label>';
						}
					}
					?>
				</div>
				<p style="margin-top:12px;"><button type="submit" class="button button-primary"><?php esc_html_e( 'Benutzer speichern', 'cpsmartcrm' ); ?></button></p>
			</form>
		</div>
	<?php elseif ( $edit_role && $is_editing_chef ) : ?>
		<div class="role-users-box">
			<p style="margin:0;color:#b32d2e;"><?php esc_html_e( 'Nur der aktuelle Chef darf die Chef-Rolle an andere Benutzer übergeben.', 'cpsmartcrm' ); ?></p>
		</div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Vorhandene Rollen', 'cpsmartcrm' ); ?></h2>
	<div class="crm-roles-table">
		<table>
			<thead>
			<tr>
				<th><?php esc_html_e( 'Icon', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Rolle', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Abteilung', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Benutzer', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Rechte', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Eigenschaften', 'cpsmartcrm' ); ?></th>
				<th><?php esc_html_e( 'Aktionen', 'cpsmartcrm' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( empty( $roles ) ) : ?>
				<tr><td colspan="8" style="text-align:center;padding:26px;"><?php esc_html_e( 'Keine Rollen gefunden.', 'cpsmartcrm' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $roles as $role ) : ?>
					<?php
					$role_users = array();
					$all_users_for_count = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ) ) );
					foreach ( $all_users_for_count as $user ) {
						if ( get_user_meta( $user->ID, '_crm_agent_role', true ) === $role->role_slug ) {
							$role_users[ $user->ID ] = array(
								'display_name' => $user->display_name,
								'user_email' => $user->user_email,
							);
						}
					}
					$caps = wp_parse_args( json_decode( $role->capabilities, true ), $default_caps );
					?>
					<tr>
						<td><span class="dashicons <?php echo esc_attr( $role->icon ); ?>"></span></td>
						<td><strong><?php echo esc_html( $role->role_name ); ?></strong></td>
						<td><code><?php echo esc_html( $role->role_slug ); ?></code></td>
						<td><?php echo esc_html( $role->department ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-crm&p=agent-roles/list.php&edit=' . $role->id ) ); ?>#role-users"><strong><?php echo (int) count( $role_users ); ?></strong> <?php esc_html_e( 'Benutzer', 'cpsmartcrm' ); ?></a>
							<?php if ( ! empty( $role_users ) ) : ?>
								<div class="inline-list" style="margin-top:4px;">
									<?php foreach ( $role_users as $role_user ) : ?>
										<span class="inline-pill"><?php echo esc_html( $role_user['display_name'] . ' (' . $role_user['user_email'] . ')' ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $caps['wp_role'] ) ) : ?><span class="inline-pill">WP: <?php echo esc_html( $caps['wp_role'] ); ?></span><?php endif; ?>
							<?php if ( ! empty( $caps['can_view_accounting'] ) || ! empty( $caps['can_edit_accounting'] ) ) : ?><span class="inline-pill">💼 <?php echo ! empty( $caps['can_edit_accounting'] ) ? 'Edit' : 'View'; ?></span><?php endif; ?>
							<?php if ( ! empty( $caps['can_view_all_accounting'] ) ) : ?><span class="inline-pill">💼 All</span><?php endif; ?>
							<?php if ( ! empty( $caps['can_view_documents'] ) || ! empty( $caps['can_edit_documents'] ) ) : ?><span class="inline-pill">📄 <?php echo ! empty( $caps['can_edit_documents'] ) ? 'Edit' : 'View'; ?></span><?php endif; ?>
							<?php if ( ! empty( $caps['can_view_all_documents'] ) ) : ?><span class="inline-pill">📄 All</span><?php endif; ?>
							<?php if ( ! empty( $caps['can_view_customers'] ) || ! empty( $caps['can_edit_customers'] ) ) : ?><span class="inline-pill">👥 <?php echo ! empty( $caps['can_edit_customers'] ) ? 'Edit' : 'View'; ?></span><?php endif; ?>
						</td>
						<td>
							<?php if ( (int) $role->is_system_role ) : ?><span class="badge badge-system"><?php esc_html_e( 'System', 'cpsmartcrm' ); ?></span><?php endif; ?>
							<?php if ( (int) $role->show_in_contact ) : ?><span class="badge badge-contact"><?php esc_html_e( 'Kontakt', 'cpsmartcrm' ); ?></span><?php endif; ?>
						</td>
						<td>
							<?php if ( 'chef' !== $role->role_slug || $current_user_can_manage_chef ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-crm&p=agent-roles/list.php&edit=' . $role->id ) ); ?>" class="button button-small"><?php esc_html_e( 'Bearbeiten', 'cpsmartcrm' ); ?></a>
							<?php else : ?>
								<button type="button" class="button button-small" disabled><?php esc_html_e( 'Nur Chef', 'cpsmartcrm' ); ?></button>
							<?php endif; ?>
							<?php if ( ! (int) $role->is_system_role ) : ?>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Wirklich löschen?', 'cpsmartcrm' ) ); ?>');">
									<?php wp_nonce_field( 'crm_agent_roles' ); ?>
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="role_id" value="<?php echo esc_attr( $role->id ); ?>">
									<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Löschen', 'cpsmartcrm' ); ?></button>
								</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<script>
jQuery(function($) {
	function updateIconPreview() {
		var selectedIcon = $('#role-icon-select').val();
		$('#icon-preview').html('<span class="dashicons ' + selectedIcon + '"></span>');
	}

	var $toggleBtn = $('#toggle-role-form');
	var $formWrapper = $('#crm-role-form-wrapper');
	if ($toggleBtn.length && $formWrapper.length) {
		$toggleBtn.on('click', function() {
			var isVisible = $formWrapper.is(':visible');
			$formWrapper.slideToggle(150);
			$toggleBtn.attr('aria-expanded', isVisible ? 'false' : 'true');
			$toggleBtn.text(isVisible ? '<?php echo esc_js( __( 'Neue Agent-Rolle erstellen', 'cpsmartcrm' ) ); ?>' : '<?php echo esc_js( __( 'Formular schließen', 'cpsmartcrm' ) ); ?>');
		});
	}

	$('#role-icon-select').on('change', updateIconPreview);
	updateIconPreview();
});
</script>
