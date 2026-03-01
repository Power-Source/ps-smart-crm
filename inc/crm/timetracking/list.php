<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table = WPsCRM_TABLE . 'timetracking';
$current_user = wp_get_current_user();
$current_user_id = (int) $current_user->ID;

if ( ! current_user_can( 'manage_crm' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Zugriff verweigert.', 'cpsmartcrm' ) );
}

if ( ! function_exists( 'wpscrm_tt_format_minutes' ) ) {
	function wpscrm_tt_format_minutes( $minutes ) {
		$minutes = (int) $minutes;
		$sign = $minutes < 0 ? '-' : '';
		$minutes = abs( $minutes );
		$hours = floor( $minutes / 60 );
		$mins = $minutes % 60;
		return $sign . sprintf( '%02d:%02d', $hours, $mins );
	}
}

if ( ! function_exists( 'wpscrm_tt_float_hours' ) ) {
	function wpscrm_tt_float_hours( $minutes ) {
		return round( ( (int) $minutes ) / 60, 2 );
	}
}

$default_work_settings = function_exists( 'wpscrm_get_default_worktime_settings' )
	? wpscrm_get_default_worktime_settings()
	: array(
		'hourly_rate' => 0,
		'rate_type' => 'net',
		'billing_mode' => 'employee',
		'schedule' => array(),
		'last_sync_source' => '',
		'last_sync_at' => '',
	);

$get_user_work_settings = function( $user_id ) use ( $default_work_settings ) {
	if ( function_exists( 'wpscrm_get_user_worktime_settings' ) ) {
		return wpscrm_get_user_worktime_settings( $user_id );
	}
	$stored = get_user_meta( $user_id, '_crm_worktime_settings', true );
	if ( ! is_array( $stored ) ) {
		return $default_work_settings;
	}
	return wp_parse_args( $stored, $default_work_settings );
};

$day_map = array(
	'mo' => __( 'Montag', 'cpsmartcrm' ),
	'tu' => __( 'Dienstag', 'cpsmartcrm' ),
	'we' => __( 'Mittwoch', 'cpsmartcrm' ),
	'th' => __( 'Donnerstag', 'cpsmartcrm' ),
	'fr' => __( 'Freitag', 'cpsmartcrm' ),
	'sa' => __( 'Samstag', 'cpsmartcrm' ),
	'su' => __( 'Sonntag', 'cpsmartcrm' ),
);

$can_manage_all_worktimes = current_user_can( 'manage_options' ) || ( function_exists( 'wpscrm_can_manage_chef_role' ) && wpscrm_can_manage_chef_role( $current_user_id ) );

$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : date( 'Y-m-01' );
$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : date( 'Y-m-d' );
$user_filter = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
$customer_filter = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;
$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$edit_user_id = isset( $_REQUEST['edit_user_id'] ) ? absint( $_REQUEST['edit_user_id'] ) : 0;

$crm_users = get_users( array(
	'meta_query' => array(
		array(
			'key' => '_crm_agent_role',
			'compare' => 'EXISTS',
		),
	),
	'orderby' => 'display_name',
	'order' => 'ASC',
) );

$crm_user_ids = wp_list_pluck( $crm_users, 'ID' );
if ( ! $edit_user_id ) {
	$edit_user_id = $user_filter ? $user_filter : $current_user_id;
}
if ( ! in_array( $edit_user_id, $crm_user_ids, true ) && ! current_user_can( 'manage_options' ) ) {
	$edit_user_id = $current_user_id;
}

if ( ! current_user_can( 'manage_options' ) && ! $can_manage_all_worktimes ) {
	$user_filter = $current_user_id;
}

$notices = array();
if ( isset( $_POST['crm_timetracking_action'], $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'crm_timetracking_admin' ) ) {
	$action = sanitize_key( $_POST['crm_timetracking_action'] );
	$post_user_id = isset( $_POST['edit_user_id'] ) ? absint( $_POST['edit_user_id'] ) : $edit_user_id;
	$can_edit_target = $can_manage_all_worktimes || $post_user_id === $current_user_id;

	if ( ! $can_edit_target ) {
		$notices[] = array( 'type' => 'error', 'text' => __( 'Du darfst diese Mitarbeiterdaten nicht bearbeiten.', 'cpsmartcrm' ) );
	} elseif ( 'save_worktime_settings' === $action ) {
		$settings = $get_user_work_settings( $post_user_id );
		$settings['hourly_rate'] = isset( $_POST['crm_hourly_rate'] ) ? max( 0, (float) $_POST['crm_hourly_rate'] ) : 0;
		$settings['rate_type'] = ( isset( $_POST['crm_rate_type'] ) && in_array( $_POST['crm_rate_type'], array( 'net', 'gross' ), true ) ) ? sanitize_key( $_POST['crm_rate_type'] ) : 'net';
		$settings['billing_mode'] = ( isset( $_POST['crm_billing_mode'] ) && in_array( $_POST['crm_billing_mode'], array( 'employee', 'contractor' ), true ) ) ? sanitize_key( $_POST['crm_billing_mode'] ) : 'employee';

		$incoming_schedule = isset( $_POST['crm_schedule'] ) && is_array( $_POST['crm_schedule'] ) ? $_POST['crm_schedule'] : array();
		$settings['schedule'] = array();
		foreach ( $day_map as $day_key => $day_label ) {
			$defaults = isset( $default_work_settings['schedule'][ $day_key ] ) ? $default_work_settings['schedule'][ $day_key ] : array(
				'active' => 0,
				'start' => '09:00',
				'end' => '17:00',
				'break_minutes' => 0,
			);
			$incoming_day = isset( $incoming_schedule[ $day_key ] ) && is_array( $incoming_schedule[ $day_key ] ) ? $incoming_schedule[ $day_key ] : array();
			$start = isset( $incoming_day['start'] ) && preg_match( '/^\d{2}:\d{2}$/', $incoming_day['start'] ) ? $incoming_day['start'] : $defaults['start'];
			$end = isset( $incoming_day['end'] ) && preg_match( '/^\d{2}:\d{2}$/', $incoming_day['end'] ) ? $incoming_day['end'] : $defaults['end'];
			$settings['schedule'][ $day_key ] = array(
				'active' => isset( $incoming_day['active'] ) ? 1 : 0,
				'start' => $start,
				'end' => $end,
				'break_minutes' => isset( $incoming_day['break_minutes'] ) ? max( 0, min( 600, (int) $incoming_day['break_minutes'] ) ) : (int) $defaults['break_minutes'],
			);
		}

		update_user_meta( $post_user_id, '_crm_worktime_settings', $settings );
		$edit_user_id = $post_user_id;
		$notices[] = array( 'type' => 'success', 'text' => __( 'Sollzeiten und Stundensatz gespeichert.', 'cpsmartcrm' ) );
	} elseif ( 'sync_from_terminmanager' === $action ) {
		if ( ! function_exists( 'appointments_get_worker_working_hours' ) ) {
			$notices[] = array( 'type' => 'error', 'text' => __( 'Terminmanager-Sync nicht verfügbar (Funktion fehlt).', 'cpsmartcrm' ) );
		} else {
			$worker_hours = appointments_get_worker_working_hours( 'open', $post_user_id, 0 );
			if ( ! $worker_hours || empty( $worker_hours->hours ) || ! is_array( $worker_hours->hours ) ) {
				$notices[] = array( 'type' => 'error', 'text' => __( 'Keine Terminmanager-Arbeitszeiten für diesen Agent gefunden.', 'cpsmartcrm' ) );
			} else {
				$weekday_map_tm = array(
					'Monday' => 'mo',
					'Tuesday' => 'tu',
					'Wednesday' => 'we',
					'Thursday' => 'th',
					'Friday' => 'fr',
					'Saturday' => 'sa',
					'Sunday' => 'su',
				);
				$settings = $get_user_work_settings( $post_user_id );
				foreach ( $worker_hours->hours as $weekday_name => $day_values ) {
					if ( empty( $weekday_map_tm[ $weekday_name ] ) ) {
						continue;
					}
					$day_key = $weekday_map_tm[ $weekday_name ];
					if ( ! isset( $settings['schedule'][ $day_key ] ) || ! is_array( $settings['schedule'][ $day_key ] ) ) {
						$settings['schedule'][ $day_key ] = $default_work_settings['schedule'][ $day_key ];
					}
					$settings['schedule'][ $day_key ]['active'] = ( isset( $day_values['active'] ) && 'yes' === $day_values['active'] ) ? 1 : 0;
					if ( isset( $day_values['start'] ) && preg_match( '/^\d{2}:\d{2}$/', $day_values['start'] ) ) {
						$settings['schedule'][ $day_key ]['start'] = $day_values['start'];
					}
					if ( isset( $day_values['end'] ) && preg_match( '/^\d{2}:\d{2}$/', $day_values['end'] ) ) {
						$settings['schedule'][ $day_key ]['end'] = $day_values['end'];
					}
				}
				$settings['last_sync_source'] = 'terminmanager';
				$settings['last_sync_at'] = current_time( 'mysql' );
				update_user_meta( $post_user_id, '_crm_worktime_settings', $settings );
				$edit_user_id = $post_user_id;
				$notices[] = array( 'type' => 'success', 'text' => __( 'Arbeitszeiten aus Terminmanager synchronisiert.', 'cpsmartcrm' ) );
			}
		}
	}
}

$edit_user = get_userdata( $edit_user_id );
$edit_settings = $edit_user ? $get_user_work_settings( $edit_user_id ) : $default_work_settings;
$effective_rate = isset( $edit_settings['hourly_rate'] ) ? (float) $edit_settings['hourly_rate'] : 0;

$where = array( 'deleted = 0' );
$values = array();
if ( $user_filter ) {
	$where[] = 'user_id = %d';
	$values[] = $user_filter;
} elseif ( ! current_user_can( 'manage_options' ) && ! $can_manage_all_worktimes ) {
	$where[] = 'user_id = %d';
	$values[] = $current_user_id;
}
if ( $customer_filter ) {
	$where[] = 'fk_kunde = %d';
	$values[] = $customer_filter;
}
if ( $status_filter ) {
	$where[] = 'status = %s';
	$values[] = $status_filter;
}
if ( $date_from ) {
	$where[] = 'DATE(start_time) >= %s';
	$values[] = $date_from;
}
if ( $date_to ) {
	$where[] = 'DATE(start_time) <= %s';
	$values[] = $date_to;
}

$query = "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_time DESC LIMIT 500';
$entries = ! empty( $values ) ? $wpdb->get_results( $wpdb->prepare( $query, $values ) ) : $wpdb->get_results( $query );

$stats_where = array( 'deleted = 0', "status = 'completed'" );
$stats_values = array();
if ( $user_filter ) {
	$stats_where[] = 'user_id = %d';
	$stats_values[] = $user_filter;
} elseif ( ! current_user_can( 'manage_options' ) && ! $can_manage_all_worktimes ) {
	$stats_where[] = 'user_id = %d';
	$stats_values[] = $current_user_id;
}
if ( $customer_filter ) {
	$stats_where[] = 'fk_kunde = %d';
	$stats_values[] = $customer_filter;
}
if ( $date_from ) {
	$stats_where[] = 'DATE(start_time) >= %s';
	$stats_values[] = $date_from;
}
if ( $date_to ) {
	$stats_where[] = 'DATE(start_time) <= %s';
	$stats_values[] = $date_to;
}

$stats_query = "SELECT user_id, DATE(start_time) AS work_date, SUM(duration_minutes) AS total_minutes, SUM(CASE WHEN is_billable = 1 THEN total_amount ELSE 0 END) AS total_amount FROM $table WHERE " . implode( ' AND ', $stats_where ) . ' GROUP BY user_id, DATE(start_time)';
$daily_rows = ! empty( $stats_values ) ? $wpdb->get_results( $wpdb->prepare( $stats_query, $stats_values ) ) : $wpdb->get_results( $stats_query );

$total_actual_minutes = 0;
$total_target_minutes = 0;
$total_billable_amount = 0.0;
$total_external_amount = 0.0;
$per_user_stats = array();

foreach ( $daily_rows as $daily_row ) {
	$user_id = (int) $daily_row->user_id;
	$actual_minutes = (int) $daily_row->total_minutes;
	$billable_amount = (float) $daily_row->total_amount;
	$target_minutes = function_exists( 'wpscrm_get_user_target_minutes_for_date' )
		? wpscrm_get_user_target_minutes_for_date( $user_id, $daily_row->work_date )
		: 0;

	if ( ! isset( $per_user_stats[ $user_id ] ) ) {
		$user_settings = $get_user_work_settings( $user_id );
		$per_user_stats[ $user_id ] = array(
			'actual_minutes' => 0,
			'target_minutes' => 0,
			'billable_amount' => 0,
			'hourly_rate' => isset( $user_settings['hourly_rate'] ) ? (float) $user_settings['hourly_rate'] : 0,
			'rate_type' => isset( $user_settings['rate_type'] ) ? $user_settings['rate_type'] : 'net',
			'billing_mode' => isset( $user_settings['billing_mode'] ) ? $user_settings['billing_mode'] : 'employee',
		);
	}

	$per_user_stats[ $user_id ]['actual_minutes'] += $actual_minutes;
	$per_user_stats[ $user_id ]['target_minutes'] += $target_minutes;
	$per_user_stats[ $user_id ]['billable_amount'] += $billable_amount;

	$total_actual_minutes += $actual_minutes;
	$total_target_minutes += $target_minutes;
	$total_billable_amount += $billable_amount;
}

foreach ( $per_user_stats as $user_id => $stats ) {
	if ( 'contractor' !== $stats['billing_mode'] ) {
		continue;
	}
	$total_external_amount += wpscrm_tt_float_hours( $stats['actual_minutes'] ) * $stats['hourly_rate'];
}

$saldo_minutes = $total_actual_minutes - $total_target_minutes;
$customers = $wpdb->get_results( "SELECT ID_kunde, name, nachname FROM " . WPsCRM_TABLE . "kunde WHERE eliminato = 0 ORDER BY name ASC LIMIT 200" );
?>

<style>
.timetracking-filters,
.timetracking-settings,
.timetracking-stats,
.timetracking-table,
.timetracking-user-summary {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	margin-bottom: 20px;
}
.timetracking-filters,
.timetracking-settings,
.timetracking-user-summary { padding: 18px; }
.filter-row {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 12px;
	margin-bottom: 10px;
}
.filter-row label { display:block; font-weight:600; margin-bottom:4px; font-size:12px; color:#374151; }
.filter-row input, .filter-row select { width:100%; }
.stats-grid {
	display:grid;
	grid-template-columns: repeat(4, minmax(160px,1fr));
	gap:12px;
	padding:16px;
}
.stat-card { background:#f9fafb; border:1px solid #eef2f7; border-radius:6px; padding:12px; }
.stat-label { font-size:12px; color:#6b7280; margin-bottom:6px; }
.stat-value { font-size:20px; font-weight:700; color:#111827; }
.stat-value.positive { color:#00a32a; }
.stat-value.negative { color:#b32d2e; }
.timetracking-table table, .timetracking-user-summary table { width:100%; border-collapse: collapse; }
.timetracking-table th, .timetracking-table td, .timetracking-user-summary th, .timetracking-user-summary td {
	padding:10px 12px;
	border-bottom:1px solid #f0f2f5;
	font-size:13px;
	text-align:left;
}
.timetracking-table th, .timetracking-user-summary th { background:#f8fafc; font-weight:600; }
.duration { font-family: ui-monospace, Menlo, Consolas, monospace; font-variant-numeric: tabular-nums; }
.badge { display:inline-block; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; }
.badge-completed { background:#dcfce7; color:#166534; }
.badge-running { background:#dbeafe; color:#1d4ed8; }
.badge-paused { background:#fef9c3; color:#a16207; }
.schedule-table { width:100%; border-collapse: collapse; margin-top:10px; }
.schedule-table th, .schedule-table td { border-bottom:1px solid #f3f4f6; padding:6px 8px; text-align:left; }
.notice-inline { padding:10px 12px; border-radius:4px; margin-bottom:10px; }
.notice-success { background:#ecfdf3; border:1px solid #b7ebc6; color:#0f5132; }
.notice-error { background:#fef2f2; border:1px solid #fecaca; color:#7f1d1d; }
.meta-note { color:#6b7280; font-size:12px; margin-top:6px; }
</style>

<h2><?php _e( 'Zeiterfassung Übersicht', 'cpsmartcrm' ); ?></h2>

<?php foreach ( $notices as $notice ) : ?>
	<div class="notice-inline <?php echo 'success' === $notice['type'] ? 'notice-success' : 'notice-error'; ?>">
		<?php echo esc_html( $notice['text'] ); ?>
	</div>
<?php endforeach; ?>

<div class="timetracking-stats">
	<div class="stats-grid">
		<div class="stat-card">
			<div class="stat-label"><?php _e( 'Erfasste Stunden', 'cpsmartcrm' ); ?></div>
			<div class="stat-value"><?php echo esc_html( wpscrm_tt_format_minutes( $total_actual_minutes ) ); ?></div>
		</div>
		<div class="stat-card">
			<div class="stat-label"><?php _e( 'Soll-Stunden', 'cpsmartcrm' ); ?></div>
			<div class="stat-value"><?php echo esc_html( wpscrm_tt_format_minutes( $total_target_minutes ) ); ?></div>
		</div>
		<div class="stat-card">
			<div class="stat-label"><?php _e( 'Über-/Unterstunden', 'cpsmartcrm' ); ?></div>
			<div class="stat-value <?php echo $saldo_minutes >= 0 ? 'positive' : 'negative'; ?>"><?php echo esc_html( wpscrm_tt_format_minutes( $saldo_minutes ) ); ?></div>
		</div>
		<div class="stat-card">
			<div class="stat-label"><?php _e( 'Abrechenbar (Timer)', 'cpsmartcrm' ); ?></div>
			<div class="stat-value positive">€ <?php echo esc_html( number_format( $total_billable_amount, 2, ',', '.' ) ); ?></div>
			<div class="meta-note"><?php _e( 'Externe Kalkulation (Rate):', 'cpsmartcrm' ); ?> € <?php echo esc_html( number_format( $total_external_amount, 2, ',', '.' ) ); ?></div>
		</div>
	</div>
</div>

<div class="timetracking-settings">
	<h3 style="margin-top:0;"><?php _e( 'Mitarbeiter bearbeiten (Sollzeiten & Satz)', 'cpsmartcrm' ); ?></h3>
	<form method="post">
		<?php wp_nonce_field( 'crm_timetracking_admin' ); ?>
		<input type="hidden" name="crm_timetracking_action" value="save_worktime_settings" />
		<div class="filter-row">
			<div>
				<label for="edit_user_id"><?php _e( 'Agent / Dienstleister', 'cpsmartcrm' ); ?></label>
				<select id="edit_user_id" name="edit_user_id" <?php disabled( ! $can_manage_all_worktimes ); ?>>
					<?php foreach ( $crm_users as $crm_user ) : ?>
						<?php 
							$user_role_slug = get_user_meta( $crm_user->ID, '_crm_agent_role', true );
							$user_role_name = '';
							if ( $user_role_slug && function_exists( 'wpscrm_get_agent_role_by_slug' ) ) {
								$role_obj = wpscrm_get_agent_role_by_slug( $user_role_slug );
								$user_role_name = $role_obj ? $role_obj->role_name : '';
							}
							$display_text = $user_role_name ? $user_role_name : $crm_user->display_name;
						?>
						<option value="<?php echo esc_attr( $crm_user->ID ); ?>" <?php selected( $edit_user_id, $crm_user->ID ); ?>><?php echo esc_html( $display_text ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label><?php _e( 'Stundensatz', 'cpsmartcrm' ); ?></label>
				<input type="number" min="0" step="0.01" name="crm_hourly_rate" value="<?php echo esc_attr( $effective_rate ); ?>" />
			</div>
			<div>
				<label><?php _e( 'Satz-Typ', 'cpsmartcrm' ); ?></label>
				<select name="crm_rate_type">
					<option value="net" <?php selected( $edit_settings['rate_type'], 'net' ); ?>><?php _e( 'Netto', 'cpsmartcrm' ); ?></option>
					<option value="gross" <?php selected( $edit_settings['rate_type'], 'gross' ); ?>><?php _e( 'Brutto', 'cpsmartcrm' ); ?></option>
				</select>
			</div>
			<div>
				<label><?php _e( 'Modus', 'cpsmartcrm' ); ?></label>
				<select name="crm_billing_mode">
					<option value="employee" <?php selected( $edit_settings['billing_mode'], 'employee' ); ?>><?php _e( 'Mitarbeiter', 'cpsmartcrm' ); ?></option>
					<option value="contractor" <?php selected( $edit_settings['billing_mode'], 'contractor' ); ?>><?php _e( 'Externer Dienstleister', 'cpsmartcrm' ); ?></option>
				</select>
			</div>
		</div>

		<table class="schedule-table">
			<thead>
				<tr>
					<th><?php _e( 'Tag', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Aktiv', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Von', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Bis', 'cpsmartcrm' ); ?></th>
					<th><?php _e( 'Pause gesamt (Min.)', 'cpsmartcrm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $day_map as $day_key => $label ) : ?>
					<?php $day = isset( $edit_settings['schedule'][ $day_key ] ) ? $edit_settings['schedule'][ $day_key ] : $default_work_settings['schedule'][ $day_key]; ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><input type="checkbox" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][active]" value="1" <?php checked( ! empty( $day['active'] ) ); ?> /></td>
						<td><input type="time" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][start]" value="<?php echo esc_attr( $day['start'] ); ?>" /></td>
						<td><input type="time" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][end]" value="<?php echo esc_attr( $day['end'] ); ?>" /></td>
						<td><input type="number" min="0" max="600" step="1" name="crm_schedule[<?php echo esc_attr( $day_key ); ?>][break_minutes]" value="<?php echo esc_attr( (int) $day['break_minutes'] ); ?>" /></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( ! empty( $edit_settings['last_sync_source'] ) ) : ?>
			<p class="meta-note"><?php echo esc_html( sprintf( __( 'Zuletzt synchronisiert: %s (%s)', 'cpsmartcrm' ), $edit_settings['last_sync_source'], $edit_settings['last_sync_at'] ) ); ?></p>
		<?php endif; ?>
		<p style="margin-top:12px;">
			<button type="submit" class="button button-primary"><?php _e( 'Sollzeiten speichern', 'cpsmartcrm' ); ?></button>
		</p>
	</form>

	<?php if ( function_exists( 'appointments_get_worker_working_hours' ) ) : ?>
		<form method="post" style="margin-top:10px;">
			<?php wp_nonce_field( 'crm_timetracking_admin' ); ?>
			<input type="hidden" name="crm_timetracking_action" value="sync_from_terminmanager" />
			<input type="hidden" name="edit_user_id" value="<?php echo esc_attr( $edit_user_id ); ?>" />
			<button type="submit" class="button"><?php _e( 'Sollzeiten aus Terminmanager syncen', 'cpsmartcrm' ); ?></button>
			<span class="meta-note"><?php _e( 'Falls der Agent dort als Dienstleister gepflegt ist.', 'cpsmartcrm' ); ?></span>
		</form>
	<?php endif; ?>
</div>

<div class="timetracking-filters">
	<form method="get">
		<input type="hidden" name="page" value="smart-crm" />
		<input type="hidden" name="p" value="timetracking/list.php" />
		<div class="filter-row">
			<div>
				<label for="user_filter"><?php _e( 'Agent', 'cpsmartcrm' ); ?></label>
				<select id="user_filter" name="user_id">
					<option value=""><?php _e( '— Alle Agents —', 'cpsmartcrm' ); ?></option>
					<?php foreach ( $crm_users as $crm_user ) : ?>
						<?php $role_slug = get_user_meta( $crm_user->ID, '_crm_agent_role', true ); ?>
						<option value="<?php echo esc_attr( $crm_user->ID ); ?>" <?php selected( $user_filter, $crm_user->ID ); ?>><?php echo esc_html( $crm_user->display_name . ( $role_slug ? ' [' . $role_slug . ']' : '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div>
				<label for="customer_filter"><?php _e( 'Kunde', 'cpsmartcrm' ); ?></label>
				<select id="customer_filter" name="customer_id">
					<option value=""><?php _e( '— Alle Kunden —', 'cpsmartcrm' ); ?></option>
					<?php foreach ( $customers as $customer ) : ?>
						<option value="<?php echo esc_attr( $customer->ID_kunde ); ?>" <?php selected( $customer_filter, $customer->ID_kunde ); ?>><?php echo esc_html( trim( $customer->name . ' ' . $customer->nachname ) ); ?></option>
					<?php endforeach; ?>
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
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-crm&p=timetracking/list.php' ) ); ?>" class="button"><?php _e( 'Zurücksetzen', 'cpsmartcrm' ); ?></a>
	</form>
</div>

<div class="timetracking-user-summary">
	<h3 style="margin-top:0;"><?php _e( 'Über-/Unterstunden nach Agent', 'cpsmartcrm' ); ?></h3>
	<table>
		<thead>
			<tr>
				<th><?php _e( 'Agent', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Modus', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Satz', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Ist', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Soll', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Saldo', 'cpsmartcrm' ); ?></th>
				<th><?php _e( 'Kalkulation', 'cpsmartcrm' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $per_user_stats ) ) : ?>
				<tr><td colspan="7" style="text-align:center;color:#6b7280;padding:20px;"><?php _e( 'Keine abgeschlossenen Zeiteinträge im gewählten Zeitraum.', 'cpsmartcrm' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $per_user_stats as $user_id => $stats ) : ?>
					<?php
					$user_obj = get_userdata( $user_id );
					$saldo = (int) $stats['actual_minutes'] - (int) $stats['target_minutes'];
					$calc_amount = wpscrm_tt_float_hours( $stats['actual_minutes'] ) * (float) $stats['hourly_rate'];
					?>
					<tr>
						<td><?php echo esc_html( $user_obj ? $user_obj->display_name : '#' . $user_id ); ?></td>
						<td><?php echo esc_html( 'contractor' === $stats['billing_mode'] ? __( 'Extern', 'cpsmartcrm' ) : __( 'Mitarbeiter', 'cpsmartcrm' ) ); ?></td>
						<td>€ <?php echo esc_html( number_format( (float) $stats['hourly_rate'], 2, ',', '.' ) ); ?> (<?php echo esc_html( 'gross' === $stats['rate_type'] ? __( 'brutto', 'cpsmartcrm' ) : __( 'netto', 'cpsmartcrm' ) ); ?>)</td>
						<td class="duration"><?php echo esc_html( wpscrm_tt_format_minutes( $stats['actual_minutes'] ) ); ?></td>
						<td class="duration"><?php echo esc_html( wpscrm_tt_format_minutes( $stats['target_minutes'] ) ); ?></td>
						<td class="duration" style="font-weight:600;color:<?php echo $saldo >= 0 ? '#15803d' : '#b91c1c'; ?>;"><?php echo esc_html( wpscrm_tt_format_minutes( $saldo ) ); ?></td>
						<td>€ <?php echo esc_html( number_format( $calc_amount, 2, ',', '.' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
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
			<?php if ( empty( $entries ) ) : ?>
				<tr><td colspan="8" style="text-align:center;color:#6b7280;padding:26px;"><?php _e( 'Keine Einträge gefunden.', 'cpsmartcrm' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $entries as $entry ) : ?>
					<?php
					$user = get_userdata( $entry->user_id );
					$customer_name = '—';
					if ( $entry->fk_kunde ) {
						$kunde = $wpdb->get_row( $wpdb->prepare( "SELECT name, nachname FROM " . WPsCRM_TABLE . "kunde WHERE ID_kunde = %d", $entry->fk_kunde ) );
						if ( $kunde ) {
							$customer_name = trim( $kunde->name . ' ' . $kunde->nachname );
						}
					}
					$status_badge_class = 'badge-' . $entry->status;
					?>
					<tr>
						<td><strong><?php echo esc_html( $user ? $user->display_name : '#' . $entry->user_id ); ?></strong></td>
						<td><?php echo esc_html( $entry->project_name ? $entry->project_name : '—' ); ?></td>
						<td><?php echo esc_html( $customer_name ); ?></td>
						<td><small><?php echo esc_html( $entry->start_time ? date_i18n( 'd.m.Y H:i', strtotime( $entry->start_time ) ) : '—' ); ?></small></td>
						<td><small><?php echo esc_html( $entry->end_time ? date_i18n( 'd.m.Y H:i', strtotime( $entry->end_time ) ) : '—' ); ?></small></td>
						<td><span class="duration"><?php echo esc_html( wpscrm_tt_format_minutes( $entry->duration_minutes ) ); ?></span></td>
						<td><?php echo ( $entry->is_billable && $entry->total_amount > 0 ) ? '<strong style="color:#166534;">€ ' . esc_html( number_format( (float) $entry->total_amount, 2, ',', '.' ) ) . '</strong>' : '—'; ?></td>
						<td><span class="badge <?php echo esc_attr( $status_badge_class ); ?>"><?php echo esc_html( 'running' === $entry->status ? __( 'Läuft', 'cpsmartcrm' ) : ( 'paused' === $entry->status ? __( 'Pausiert', 'cpsmartcrm' ) : __( 'Fertig', 'cpsmartcrm' ) ) ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<p class="meta-note"><?php _e( 'Hinweis: Es werden maximal 500 Einträge angezeigt. Überstunden berechnen sich aus Ist minus Soll im gewählten Zeitraum.', 'cpsmartcrm' ); ?></p>
