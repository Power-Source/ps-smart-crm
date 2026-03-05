<?php
/**
 * PS Smart Business - Debug & Statusprüfung
 * Shortcode: [crm_debug_status]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug-Shortcode für Statusüberprüfung
 */
function wpscrm_debug_status_shortcode( $atts ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '<div style="background:#fee;padding:20px;border-radius:4px;">Nur Admin sichtbar</div>';
	}
	
	ob_start();
	?>
	<div style="background:#fff;border:2px solid #667eea;padding:20px;border-radius:8px;max-width:800px;margin:20px auto;font-family:monospace;font-size:13px;">
		
		<h2 style="color:#667eea;margin-top:0;">🔍 CRM System Status</h2>
		
		<div style="background:#f9fafb;padding:15px;border-radius:4px;margin-bottom:15px;">
			
			<?php
			// 1. PM Plugin prüfen
			$pm_active = class_exists( 'MMessaging' ) && function_exists( 'mm_display_contact_button' );
			echo '<p><strong>Private Messaging Plugin:</strong> ';
			echo $pm_active ? '<span style="color:#00a32a;">✓ AKTIV</span>' : '<span style="color:#d63638;">✗ INAKTIV</span>';
			echo '</p>';
			
			// 2. DB Tabellen prüfen
			global $wpdb;
			$tables_needed = array(
				'agent_roles' => WPsCRM_TABLE . 'agent_roles',
				'timetracking' => WPsCRM_TABLE . 'timetracking',
				'kunde' => WPsCRM_TABLE . 'kunde',
			);
			
			echo '<p><strong>Datenbank-Tabellen:</strong></p><ul>';
			foreach ( $tables_needed as $name => $table ) {
				$exists = $wpdb->get_var( "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$wpdb->dbname}' AND TABLE_NAME='{$table}'" );
				echo '<li>' . $table . ': ' . ( $exists ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#d63638;">✗</span>' ) . '</li>';
			}
			echo '</ul>';
			
			// 3. Klassenladen prüfen
			echo '<p><strong>Klassen geladen:</strong></p><ul>';
			echo '<li>WPsCRM_PM_Integration: ' . ( class_exists( 'WPsCRM_PM_Integration' ) ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#d63638;">✗</span>' ) . '</li>';
			echo '<li>WPsCRM_Timetracking: ' . ( class_exists( 'WPsCRM_Timetracking' ) ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#d63638;">✗</span>' ) . '</li>';
			echo '</ul>';
			
			// 4. Shortcodes prüfen
			global $shortcode_tags;
			echo '<p><strong>Shortcodes registriert:</strong></p><ul>';
			echo '<li>crm_agent_dashboard: ' . ( isset( $shortcode_tags['crm_agent_dashboard'] ) ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#d63638;">✗</span>' ) . '</li>';
			echo '<li>crm_timer: ' . ( isset( $shortcode_tags['crm_timer'] ) ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#d63638;">✗</span>' ) . '</li>';
			echo '</ul>';
			
			// 5. Agent-Rollen in DB
			$roles_count = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPsCRM_TABLE . "agent_roles" );
			echo '<p><strong>Agent-Rollen in Datenbank:</strong> ' . intval( $roles_count ) . '</p>';
			
			if ( $roles_count > 0 ) {
				echo '<p style="font-size:11px;color:#666;"><strong>Rollen:</strong><br>';
				$roles = $wpdb->get_results( "SELECT role_name, department FROM " . WPsCRM_TABLE . "agent_roles ORDER BY sort_order ASC" );
				foreach ( $roles as $role ) {
					echo '• ' . $role->role_name . ( $role->department ? ' (' . $role->department . ')' : '' ) . '<br>';
				}
				echo '</p>';
			}
			
			// 6. Users mit manage_crm
			$crm_users = count_users();
			$users_with_crm = $wpdb->get_results( 
				"SELECT COUNT(*) as cnt FROM $wpdb->users WHERE ID IN (
					SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value LIKE '%manage_crm%'
				)"
			);
			
			echo '<p><strong>User mit manage_crm Capability:</strong> ';
			if ( ! empty( $users_with_crm ) && isset( $users_with_crm[0]->cnt ) ) {
				echo intval( $users_with_crm[0]->cnt );
			}
			echo '</p>';
			
			// 7. Aktuelle User
			$current_user = wp_get_current_user();
			echo '<p><strong>Aktueller User:</strong> ' . $current_user->user_login . '</p>';
			echo '<p><strong>Fähigkeiten:</strong> ';
			if ( current_user_can( 'manage_options' ) ) {
				echo 'Admin • ';
			}
			if ( current_user_can( 'manage_crm' ) ) {
				echo 'CRM-Agent • ';
				$my_role = get_user_meta( $current_user->ID, '_crm_agent_role', true );
				echo 'Rolle: ' . ( $my_role ?: 'Nicht zugewiesen' );
			}
			echo '</p>';
			
		?>
		</div>
		
		<h3 style="color:#667eea;">📋 Nächste Schritte:</h3>
		<ol style="line-height:1.8;">
			<li><strong>Agent-Rollen konfigurieren:</strong> Siehe <a href="<?php echo admin_url('admin.php?page=smart-crm&p=agent-roles/list.php'); ?>" style="color:#667eea;text-decoration:underline;">Dienstprogramme → Agent-Rollen</a></li>
			<li><strong>Agents zuweisen:</strong> <a href="<?php echo admin_url('users.php'); ?>" style="color:#667eea;text-decoration:underline;">Benutzer</a> bearbeiten → CRM Agent-Rolle wählen</li>
			<li><strong>Frontend-Dashboard:</strong> Shortcode <code>[crm_agent_dashboard]</code> auf einer Seite einfügen</li>
			<li><strong>Zeiterfassung:</strong> Shortcode <code>[crm_timer]</code> auf einer Dashboard-Seite einfügen</li>
		</ol>
		
		<h3 style="color:#667eea;">🧪 Test-Links:</h3>
		<ul style="line-height:2;">
			<li><a href="<?php echo admin_url('users.php'); ?>" style="color:#667eea;text-decoration:underline;">→ Benutzer verwalten</a></li>
			<li><a href="<?php echo admin_url('admin.php?page=smart-crm&p=agent-roles/list.php'); ?>" style="color:#667eea;text-decoration:underline;">→ Agent-Rollen verwalten</a></li>
			<li><a href="<?php echo site_url('/'); ?>" style="color:#667eea;text-decoration:underline;">→ Zur Website</a></li>
		</ul>
		
	</div>
	<?php
	
	return ob_get_clean();
}
add_shortcode( 'crm_debug_status', 'wpscrm_debug_status_shortcode' );
