<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Zeiterfassung Liste
 * 
 * Backend-Überwachung aller Zeiteinträge
 */

global $wpdb;
$table = WPsCRM_TABLE . 'timetracking';
$current_user = wp_get_current_user();

// Filter-Parameter
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : date( 'Y-m-01' );
$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : date( 'Y-m-d' );
$user_filter = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
$customer_filter = isset( $_GET['customer_id'] ) ? intval( $_GET['customer_id'] ) : 0;
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

?>

<style>
.timetracking-filters {
	background: #f9fafb;
	padding: 20px;
	border-radius: 4px;
	margin-bottom: 20px;
	border: 1px solid #e5e5e5;
}
.filter-row {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin-bottom: 15px;
}
.filter-row label {
	display: block;
	font-weight: 600;
	margin-bottom: 5px;
	color: #374151;
	font-size: 13px;
}
.filter-row input,
.filter-row select {
	width: 100%;
	padding: 8px;
	border: 1px solid #d1d5db;
	border-radius: 4px;
	font-size: 14px;
}
.timetracking-table {
	background: #fff;
	border: 1px solid #e5e5e5;
	border-radius: 4px;
	overflow: hidden;
}
.timetracking-table table {
	width: 100%;
	border-collapse: collapse;
}
.timetracking-table th {
	background: #f3f4f6;
	padding: 12px;
	text-align: left;
	font-weight: 600;
	color: #374151;
	border-bottom: 2px solid #e5e5e5;
	font-size: 13px;
}
.timetracking-table td {
	padding: 12px;
	border-bottom: 1px solid #f3f4f6;
	font-size: 13px;
}
.timetracking-table tr:hover {
	background: #f9fafb;
}
.badge {
	display: inline-block;
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}
.badge-completed {
	background: #d1fae5;
	color: #065f46;
}
.badge-running {
	background: #dbeafe;
	color: #0c4a6e;
}
.badge-paused {
	background: #fef3c7;
	color: #78350f;
}
.amount-positive {
	color: #00a32a;
	font-weight: 600;
}
.duration {
	font-variant-numeric: tabular-nums;
	font-family: monospace;
}
</style>

<h2><?php _e( 'Zeiterfassung', 'cpsmartcrm' ); ?></h2>

<div class="timetracking-filters">
	<form method="get">
		<input type="hidden" name="page" value="smart-crm" />
		<input type="hidden" name="p" value="timetracking/list.php" />
		
		<div class="filter-row">
			
			<div>
				<label for="user_filter"><?php _e( 'Agent', 'cpsmartcrm' ); ?></label>
				<select id="user_filter" name="user_id">
					<option value=""><?php _e( '— Alle Agents —', 'cpsmartcrm' ); ?></option>
					<?php
					$users = get_users( array(
						'meta_query' => array(
							array(
								'key' => 'wp_capabilities',
								'value' => 'manage_crm',
								'compare' => 'LIKE',
							),
						),
					) );
					foreach ( $users as $user ) {
						$role_slug = get_user_meta( $user->ID, '_crm_agent_role', true );
						$role_label = $role_slug ? " [$role_slug]" : '';
						echo '<option value="' . $user->ID . '" ' . selected( $user_filter, $user->ID ) . '>' . 
							 esc_html( $user->display_name ) . $role_label . '</option>';
					}
					?>
				</select>
			</div>
			
			<div>
				<label for="customer_filter"><?php _e( 'Kunde', 'cpsmartcrm' ); ?></label>
				<select id="customer_filter" name="customer_id">
					<option value=""><?php _e( '— Alle Kunden —', 'cpsmartcrm' ); ?></option>
					<?php
					$customers = $wpdb->get_results( 
						"SELECT ID_kunde, name, nachname FROM " . WPsCRM_TABLE . "kunde WHERE eliminato = 0 ORDER BY name ASC LIMIT 100"
					);
					foreach ( $customers as $customer ) {
						echo '<option value="' . $customer->ID_kunde . '" ' . selected( $customer_filter, $customer->ID_kunde ) . '>' . 
							 esc_html( $customer->name . ' ' . $customer->nachname ) . '</option>';
					}
					?>
				</select>
			</div>
			
			<div>
				<label for="status_filter"><?php _e( 'Status', 'cpsmartcrm' ); ?></label>
				<select id="status_filter" name="status">
					<option value=""><?php _e( '— Alle Status —', 'cpsmartcrm' ); ?></option>
					<option value="running" <?php selected( $status_filter, 'running' ); ?>><?php _e( 'Läuft', 'cpsmartcrm' ); ?></option>
					<option value="paused" <?php selected( $status_filter, 'paused' ); ?>><?php _e( 'Pausiert', 'cpsmartcrm' ); ?></option>
					<option value="completed" <?php selected( $status_filter, 'completed' ); ?>><?php _e( 'Abgeschlossen', 'cpsmartcrm' ); ?></option>
				</select>
			</div>
			
			<div>
				<label for="date_from"><?php _e( 'Von', 'cpsmartcrm' ); ?></label>
				<input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
			</div>
			
			<div>
				<label for="date_to"><?php _e( 'Bis', 'cpsmartcrm' ); ?></label>
				<input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
			</div>
			
		</div>
		
		<button type="submit" class="button button-primary"><?php _e( 'Filtern', 'cpsmartcrm' ); ?></button>
		<a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=timetracking/list.php' ); ?>" class="button"><?php _e( 'Zurücksetzen', 'cpsmartcrm' ); ?></a>
	</form>
</div>

<div class="timetracking-table">
	<table>
		<thead>
			<tr>
				<th><?php _e( 'Agent', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Projekt', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Kunde', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Von', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Bis', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Dauer', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Betrag', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Status', 'cpsmartcrm' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$where = array( "deleted = 0" );
			$values = array();
			
			if ( $user_filter ) {
				$where[] = "user_id = %d";
				$values[] = $user_filter;
			} elseif ( ! current_user_can( 'manage_options' ) ) {
				// Non-admin können nur ihre eigenen Einträge sehen
				$where[] = "user_id = %d";
				$values[] = $current_user->ID;
			}
			
			if ( $customer_filter ) {
				$where[] = "fk_kunde = %d";
				$values[] = $customer_filter;
			}
			
			if ( $status_filter ) {
				$where[] = "status = %s";
				$values[] = $status_filter;
			}
			
			if ( $date_from ) {
				$where[] = "DATE(start_time) >= %s";
				$values[] = $date_from;
			}
			
			if ( $date_to ) {
				$where[] = "DATE(start_time) <= %s";
				$values[] = $date_to;
			}
			
			$query = "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY start_time DESC LIMIT 200";
			
			if ( ! empty( $values ) ) {
				$entries = $wpdb->get_results( $wpdb->prepare( $query, $values ) );
			} else {
				$entries = $wpdb->get_results( $query );
			}
			
			if ( empty( $entries ) ) {
				?>
				<tr>
					<td colspan="8" style="text-align:center;padding:40px;color:#999;">
						<?php _e( 'Keine Einträge gefunden.', 'cpsmartcrm' ); ?>
					</td>
				</tr>
				<?php
			} else {
				$total_amount = 0;
				$total_minutes = 0;
				
				foreach ( $entries as $entry ) {
					$user = get_userdata( $entry->user_id );
					
					$customer_name = '—';
					if ( $entry->fk_kunde ) {
						$kunde = $wpdb->get_row( $wpdb->prepare(
							"SELECT name, nachname FROM " . WPsCRM_TABLE . "kunde WHERE ID_kunde = %d",
							$entry->fk_kunde
						) );
						if ( $kunde ) {
							$customer_name = $kunde->name . ' ' . $kunde->nachname;
						}
					}
					
					$hours = floor( $entry->duration_minutes / 60 );
					$mins = $entry->duration_minutes % 60;
					$duration = sprintf( '%02d:%02d', $hours, $mins );
					
					$start_time = $entry->start_time ? date_i18n( 'd.m.Y H:i', strtotime( $entry->start_time ) ) : '—';
					$end_time = $entry->end_time ? date_i18n( 'd.m.Y H:i', strtotime( $entry->end_time ) ) : '—';
					
					$status_badge_class = 'badge-' . $entry->status;
					
					if ( 'completed' === $entry->status ) {
						$total_amount += floatval( $entry->total_amount );
						$total_minutes += intval( $entry->duration_minutes );
					}
					
					?>
					<tr>
						<td><strong><?php echo esc_html( $user->display_name ); ?></strong></td>
						<td><?php echo esc_html( $entry->project_name ?: '—' ); ?></td>
						<td><?php echo esc_html( $customer_name ); ?></td>
						<td><small><?php echo $start_time; ?></small></td>
						<td><small><?php echo $end_time; ?></small></td>
						<td><span class="duration"><?php echo $duration; ?></span></td>
						<td>
							<?php 
							if ( $entry->is_billable && $entry->total_amount > 0 ) {
								echo '<span class="amount-positive">€ ' . number_format( $entry->total_amount, 2, ',', '.' ) . '</span>';
							} else {
								echo '—';
							}
							?>
						</td>
						<td>
							<span class="badge <?php echo esc_attr( $status_badge_class ); ?>">
								<?php 
								switch ( $entry->status ) {
									case 'running':
										_e( 'Läuft', 'cpsmartcrm' );
										break;
									case 'paused':
										_e( 'Pausiert', 'cpsmartcrm' );
										break;
									case 'completed':
										_e( 'Fertig', 'cpsmartcrm' );
										break;
									default:
										echo ucfirst( $entry->status );
								}
								?>
							</span>
						</td>
					</tr>
					<?php
				}
				
				// Summary row
				if ( ! empty( $entries ) ) {
					?>
					<tr style="background:#f3f4f6;font-weight:600;border-top:2px solid #e5e5e5;">
						<td colspan="5" style="text-align:right;"><?php _e( 'Summen:', 'cpsmartcrm' ); ?></td>
						<td>
							<?php 
							$total_hours = floor( $total_minutes / 60 );
							$total_mins = $total_minutes % 60;
							echo sprintf( '%02d:%02d', $total_hours, $total_mins );
							?>
						</td>
						<td class="amount-positive">
							€ <?php echo number_format( $total_amount, 2, ',', '.' ); ?>
						</td>
						<td></td>
					</tr>
					<?php
				}
			}
			?>
		</tbody>
	</table>
</div>

<p style="margin-top:20px;color:#666;font-size:12px;">
	<?php _e( 'Hinweis: Es werden maximal 200 Einträge angezeigt. Nutze die Filter zum Eingrenzen.', 'cpsmartcrm' ); ?>
</p>
