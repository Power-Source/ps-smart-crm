<?php
/**
 * PS Smart CRM - Frontend Agent Dashboard
 * 
 * Shortcode: [crm_agent_dashboard]
 * 
 * Dashboard für CRM-Agents mit:
 * - PM Inbox Integration
 * - Rollen-basierte Kontakt-Buttons
 * - Kunden-Übersicht
 * - Zeiterfassung (kommend)
 * 
 * @package PS-Smart-CRM
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: CRM Agent Dashboard
 */
function wpscrm_agent_dashboard_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'show_inbox' => 'yes',
		'show_contacts' => 'yes',
		'show_customers' => 'yes',
		'show_timetracking' => 'no',
	), $atts );
	
	// Nur für eingeloggte CRM-Agents
	if ( ! is_user_logged_in() ) {
		return '<div class="crm-dashboard-notice">' . __( 'Bitte melde dich an.', 'cpsmartcrm' ) . '</div>';
	}
	
	$current_user_id = get_current_user_id();
	
	if ( ! user_can( $current_user_id, 'manage_crm' ) ) {
		return '<div class="crm-dashboard-notice">' . __( 'Zugriff verweigert.', 'cpsmartcrm' ) . '</div>';
	}
	
	$pm = wpscrm_pm();
	$user_role_slug = get_user_meta( $current_user_id, '_crm_agent_role', true );
	
	global $wpdb;
	$roles_table = WPsCRM_TABLE . 'agent_roles';
	$user_role = null;
	
	if ( $user_role_slug ) {
		$user_role = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $roles_table WHERE role_slug = %s",
			$user_role_slug
		) );
	}
	
	ob_start();
	?>
	
	<style>
	.crm-agent-dashboard {
		max-width: 1200px;
		margin: 30px auto;
	}
	.crm-dashboard-header {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: #fff;
		padding: 30px;
		border-radius: 8px;
		margin-bottom: 30px;
		box-shadow: 0 4px 6px rgba(0,0,0,0.1);
	}
	.crm-dashboard-header h1 {
		margin: 0 0 10px;
		font-size: 28px;
		color: #fff;
	}
	.crm-dashboard-header .user-role {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		background: rgba(255,255,255,0.2);
		padding: 8px 15px;
		border-radius: 20px;
		font-size: 14px;
		font-weight: 600;
	}
	.crm-dashboard-widgets {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
		gap: 20px;
		margin-bottom: 30px;
	}
	.crm-widget {
		background: #fff;
		border: 1px solid #e5e5e5;
		border-radius: 8px;
		padding: 20px;
		box-shadow: 0 2px 4px rgba(0,0,0,0.05);
	}
	.crm-widget h3 {
		margin: 0 0 15px;
		font-size: 18px;
		display: flex;
		align-items: center;
		gap: 10px;
		padding-bottom: 10px;
		border-bottom: 2px solid #f0f0f1;
	}
	.crm-widget h3 .dashicons {
		font-size: 24px;
		color: #667eea;
	}
	.crm-contact-buttons {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}
	.crm-contact-btn {
		display: flex;
		align-items: center;
		gap: 12px;
		padding: 12px 15px;
		background: #f8f9fa;
		border: 2px solid #e5e5e5;
		border-radius: 6px;
		text-decoration: none;
		color: #333;
		transition: all 0.3s ease;
	}
	.crm-contact-btn:hover {
		background: #667eea;
		border-color: #667eea;
		color: #fff;
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
	}
	.crm-contact-btn .dashicons {
		font-size: 28px;
		color: #667eea;
		transition: color 0.3s ease;
	}
	.crm-contact-btn:hover .dashicons {
		color: #fff;
	}
	.crm-contact-btn .btn-content {
		flex: 1;
	}
	.crm-contact-btn .btn-title {
		font-weight: 600;
		font-size: 15px;
		display: block;
	}
	.crm-contact-btn .btn-meta {
		font-size: 12px;
		color: #666;
		display: block;
		margin-top: 3px;
	}
	.crm-contact-btn:hover .btn-meta {
		color: rgba(255,255,255,0.8);
	}
	.crm-customers-list {
		max-height: 300px;
		overflow-y: auto;
	}
	.crm-customer-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 10px;
		border-bottom: 1px solid #f0f0f1;
	}
	.crm-customer-item:last-child {
		border-bottom: none;
	}
	.crm-customer-name {
		font-weight: 600;
		color: #333;
	}
	.crm-customer-email {
		font-size: 12px;
		color: #666;
	}
	.crm-customer-actions {
		display: flex;
		gap: 5px;
	}
	.crm-inbox-link {
		display: inline-block;
		margin-top: 15px;
		padding: 10px 20px;
		background: #667eea;
		color: #fff;
		text-decoration: none;
		border-radius: 4px;
		text-align: center;
		transition: background 0.3s ease;
	}
	.crm-inbox-link:hover {
		background: #764ba2;
		color: #fff;
	}
	.crm-stat-box {
		background: #f8f9fa;
		padding: 15px;
		border-radius: 6px;
		text-align: center;
		margin-bottom: 15px;
	}
	.crm-stat-value {
		font-size: 32px;
		font-weight: 700;
		color: #667eea;
		display: block;
	}
	.crm-stat-label {
		font-size: 13px;
		color: #666;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	.crm-dashboard-notice {
		background: #fff3cd;
		border: 1px solid #ffc107;
		color: #856404;
		padding: 15px;
		border-radius: 4px;
		text-align: center;
	}
	</style>
	
	<div class="crm-agent-dashboard">
		
		<!-- Header -->
		<div class="crm-dashboard-header">
			<h1>
				<?php
				printf(
					__( 'Willkommen, %s!', 'cpsmartcrm' ),
					wp_get_current_user()->display_name
				);
				?>
			</h1>
			<?php if ( $user_role ) : ?>
				<div class="user-role">
					<span class="dashicons <?php echo esc_attr( $user_role->icon ); ?>"></span>
					<span><?php echo esc_html( $user_role->role_name ); ?></span>
					<?php if ( $user_role->department ) : ?>
						<span> • <?php echo esc_html( $user_role->department ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- Widgets Grid -->
		<div class="crm-dashboard-widgets">
			
			<!-- PM Inbox Widget -->
			<?php if ( 'yes' === $atts['show_inbox'] && $pm->is_pm_active() ) : ?>
				<div class="crm-widget">
					<h3>
						<span class="dashicons dashicons-email"></span>
						<?php _e( 'Nachrichten', 'cpsmartcrm' ); ?>
					</h3>
					
					<?php
					// Hole PM-Stats wenn möglich
					$inbox_count = 0;
					if ( class_exists( 'MM_Conversation_Model' ) ) {
						global $wpdb;
						$pm_table = $wpdb->prefix . 'private_messages';
						$inbox_count = $wpdb->get_var( $wpdb->prepare(
							"SELECT COUNT(*) FROM $pm_table WHERE recipient = %d AND is_read = 0",
							$current_user_id
						) );
					}
					?>
					
					<div class="crm-stat-box">
						<span class="crm-stat-value"><?php echo intval( $inbox_count ); ?></span>
						<span class="crm-stat-label"><?php _e( 'Ungelesene Nachrichten', 'cpsmartcrm' ); ?></span>
					</div>
					
					<a href="<?php echo esc_url( $pm->get_pm_inbox_url() ); ?>" class="crm-inbox-link">
						<?php _e( 'Zur Inbox', 'cpsmartcrm' ); ?> →
					</a>
				</div>
			<?php endif; ?>
			
			<!-- Contact Buttons Widget -->
			<?php if ( 'yes' === $atts['show_contacts'] ) : ?>
				<div class="crm-widget">
					<h3>
						<span class="dashicons dashicons-groups"></span>
						<?php _e( 'Team kontaktieren', 'cpsmartcrm' ); ?>
					</h3>
					
					<div class="crm-contact-buttons">
						<?php
						$contact_roles = $pm->get_contact_roles();
						
						if ( empty( $contact_roles ) ) {
							echo '<p style="color:#999;text-align:center;padding:20px 0;">' . 
								 __( 'Keine Kontakt-Rollen konfiguriert.', 'cpsmartcrm' ) . 
								 '</p>';
						} else {
							foreach ( $contact_roles as $role ) {
								// Finde Agents mit dieser Rolle
								$agents_with_role = $pm->get_agents_by_role( $role->role_slug );
								
								if ( empty( $agents_with_role ) ) {
									continue; // Skip wenn keine Agents mit dieser Rolle
								}
								
								// Nimm den ersten Agent für den Kontakt
								$first_agent = $agents_with_role[0];
								
								// Eigene Rolle nicht anzeigen
								if ( $first_agent['ID'] === $current_user_id && count( $agents_with_role ) === 1 ) {
									continue;
								}
								
								// Wenn nur eigene ID, nimm nächsten Agent
								if ( $first_agent['ID'] === $current_user_id && count( $agents_with_role ) > 1 ) {
									$first_agent = $agents_with_role[1];
								}
								
								$contact_url = '#';
								if ( $pm->is_pm_active() && function_exists( 'mm_get_conversation_url' ) ) {
									$contact_url = mm_get_conversation_url( $first_agent['ID'] );
								}
								
								?>
								<a href="<?php echo esc_url( $contact_url ); ?>" class="crm-contact-btn">
									<span class="dashicons <?php echo esc_attr( $role->icon ); ?>"></span>
									<div class="btn-content">
										<span class="btn-title">
											<?php echo esc_html( $role->display_name ? $role->display_name : $role->role_name ); ?>
										</span>
										<span class="btn-meta">
											<?php
											printf(
												_n(
													'%d Agent verfügbar',
													'%d Agents verfügbar',
													count( $agents_with_role ),
													'cpsmartcrm'
												),
												count( $agents_with_role )
											);
											?>
										</span>
									</div>
								</a>
								<?php
							}
						}
						?>
					</div>
				</div>
			<?php endif; ?>
			
			<!-- Assigned Customers Widget -->
			<?php if ( 'yes' === $atts['show_customers'] ) : ?>
				<div class="crm-widget">
					<h3>
						<span class="dashicons dashicons-id"></span>
						<?php _e( 'Meine Kunden', 'cpsmartcrm' ); ?>
					</h3>
					
					<?php
					$customers = $pm->get_agent_customers( $current_user_id );
					?>
					
					<div class="crm-stat-box">
						<span class="crm-stat-value"><?php echo count( $customers ); ?></span>
						<span class="crm-stat-label"><?php _e( 'Zugewiesene Kunden', 'cpsmartcrm' ); ?></span>
					</div>
					
					<?php if ( ! empty( $customers ) ) : ?>
						<div class="crm-customers-list">
							<?php foreach ( array_slice( $customers, 0, 5 ) as $customer ) : ?>
								<div class="crm-customer-item">
									<div>
										<div class="crm-customer-name">
											<?php echo esc_html( $customer->name . ' ' . $customer->nachname ); ?>
										</div>
										<?php if ( $customer->email ) : ?>
											<div class="crm-customer-email">
												<?php echo esc_html( $customer->email ); ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
							
							<?php if ( count( $customers ) > 5 ) : ?>
								<div style="text-align:center;padding-top:10px;">
									<a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=kunde/list.php' ); ?>">
										<?php printf( __( '+ %d weitere Kunden anzeigen', 'cpsmartcrm' ), count( $customers ) - 5 ); ?>
									</a>
								</div>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<p style="color:#999;text-align:center;padding:20px 0;">
							<?php _e( 'Keine Kunden zugewiesen.', 'cpsmartcrm' ); ?>
						</p>
					<?php endif; ?>
					
					<a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=kunde/list.php' ); ?>" class="crm-inbox-link">
						<?php _e( 'Alle Kunden ansehen', 'cpsmartcrm' ); ?> →
					</a>
				</div>
			<?php endif; ?>
			
		</div>
		
	</div>
	
	<?php
	return ob_get_clean();
}
add_shortcode( 'crm_agent_dashboard', 'wpscrm_agent_dashboard_shortcode' );
