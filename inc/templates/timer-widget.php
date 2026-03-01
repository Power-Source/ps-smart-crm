<?php
/**
 * Timer Widget Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id = get_current_user_id();
$timetracker = wpscrm_timetracking();
$active_timer = $timetracker->get_active_timer( $user_id );
$pm = wpscrm_pm();
$customers = $pm->get_agent_customers( $user_id );
?>

<style>
.wpscrm-timer-widget {
	max-width: 500px;
	background: #fff;
	border: 2px solid #667eea;
	border-radius: 10px;
	padding: 25px;
	box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}
.timer-display {
	text-align: center;
	margin-bottom: 25px;
}
.timer-clock {
	font-size: 48px;
	font-weight: 700;
	color: #667eea;
	font-variant-numeric: tabular-nums;
	letter-spacing: 2px;
	margin: 15px 0;
}
.timer-status {
	display: inline-block;
	padding: 6px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}
.timer-status.running {
	background: #dcfce7;
	color: #166534;
}
.timer-status.paused {
	background: #fef3c7;
	color: #854d0e;
}
.timer-status.stopped {
	background: #f3f4f6;
	color: #6b7280;
}
.timer-form {
	margin bottom: 20px;
}
.timer-form .form-group {
	margin-bottom: 15px;
}
.timer-form label {
	display: block;
	font-size: 13px;
	font-weight: 600;
	color: #374151;
	margin-bottom: 5px;
}
.timer-form input[type="text"],
.timer-form textarea,
.timer-form select,
.timer-form input[type="number"] {
	width: 100%;
	padding: 10px;
	border: 1px solid #d1d5db;
	border-radius: 6px;
	font-size: 14px;
	transition: border-color 0.2s;
}
.timer-form input:focus,
.timer-form textarea:focus,
.timer-form select:focus {
	outline: none;
	border-color: #667eea;
	box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.timer-form textarea {
	resize: vertical;
	min-height: 60px;
}
.timer-controls {
	display: flex;
	gap: 10px;
	margin-top: 20px;
}
.timer-btn {
	flex: 1;
	padding: 12px 20px;
	border: none;
	border-radius: 6px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}
.timer-btn-start {
	background: #667eea;
	color: #fff;
}
.timer-btn-start:hover {
	background: #5568d3;
	transform: translateY(-1px);
	box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
}
.timer-btn-stop {
	background: #ef4444;
	color: #fff;
}
.timer-btn-stop:hover {
	background: #dc2626;
}
.timer-btn-pause {
	background: #f59e0b;
	color: #fff;
}
.timer-btn-pause:hover {
	background: #d97706;
}
.timer-info {
	background: #f9fafb;
	padding: 15px;
	border-radius: 6px;
	margin-top: 20px;
	font-size: 13px;
	color: #6b7280;
}
.timer-info strong {
	color: #374151;
}
.timer-history {
	margin-top: 30px;
	padding-top: 20px;
	border-top: 2px solid #f3f4f6;
}
.timer-history h4 {
	font-size: 16px;
	font-weight: 600;
	color: #374151;
	margin-bottom: 15px;
}
.history-entry {
	background: #f9fafb;
	padding: 12px;
	border-radius: 6px;
	margin-bottom: 10px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.history-entry-date {
	font-size: 12px;
	color: #6b7280;
}
.history-entry-duration {
	font-weight: 600;
	color: #667eea;
}
.checkbox-wrapper {
	display: flex;
	align-items: center;
	gap: 8px;
}
.checkbox-wrapper input[type="checkbox"] {
	width: auto;
	margin: 0;
}
</style>

<div class="wpscrm-timer-widget" id="wpscrm-timer-widget">
	
	<div class="timer-display">
		<h3 style="margin:0 0 10px;color:#374151;font-size:20px;">⏱️ Zeiterfassung</h3>
		
		<div class="timer-clock" id="timer-clock">
			00:00:00
		</div>
		
		<span class="timer-status stopped" id="timer-status">
			<?php _e( 'Bereit', 'cpsmartcrm' ); ?>
		</span>
	</div>
	
	<div class="timer-form" id="timer-form" style="<?php echo $active_timer ? 'display:none;' : ''; ?>">
		
		<div class="form-group">
			<label><?php _e( 'Kunde (optional)', 'cpsmartcrm' ); ?></label>
			<select id="timer-customer" name="customer_id">
				<option value=""><?php _e( '— Kein Kunde —', 'cpsmartcrm' ); ?></option>
				<?php foreach ( $customers as $customer ) : ?>
					<option value="<?php echo esc_attr( $customer->ID_kunde ); ?>">
						<?php echo esc_html( $customer->name . ' ' . $customer->nachname ); ?>
						<?php if ( $customer->firmenname ) : ?>
							(<?php echo esc_html( $customer->firmenname ); ?>)
						<?php endif; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div class="form-group">
			<label><?php _e( 'Projekt', 'cpsmartcrm' ); ?></label>
			<input type="text" id="timer-project" name="project_name" placeholder="<?php esc_attr_e( 'Projektname', 'cpsmartcrm' ); ?>" />
		</div>
		
		<div class="form-group">
			<label><?php _e( 'Aufgabe', 'cpsmartcrm' ); ?></label>
			<textarea id="timer-task" name="task_description" placeholder="<?php esc_attr_e( 'Was machst du gerade?', 'cpsmartcrm' ); ?>"></textarea>
		</div>
		
		<div class="form-group">
			<label><?php _e( 'Stundensatz (€)', 'cpsmartcrm' ); ?></label>
			<input type="number" id="timer-rate" name="hourly_rate" value="0" min="0" step="0.01" />
		</div>
		
		<div class="form-group">
			<div class="checkbox-wrapper">
				<input type="checkbox" id="timer-billable" name="is_billable" value="1" checked />
				<label for="timer-billable" style="margin:0;">
					<?php _e( 'Abrechenbar', 'cpsmartcrm' ); ?>
				</label>
			</div>
		</div>
		
	</div>
	
	<div class="timer-controls">
		<button type="button" class="timer-btn timer-btn-start" id="btn-start" onclick="wpsCRMTimerWidget.start()">
			▶ <?php _e( 'Start', 'cpsmartcrm' ); ?>
		</button>
		<button type="button" class="timer-btn timer-btn-pause" id="btn-pause" onclick="wpsCRMTimerWidget.pause()" style="display:none;">
			⏸ <?php _e( 'Pause', 'cpsmartcrm' ); ?>
		</button>
		<button type="button" class="timer-btn timer-btn-stop" id="btn-stop" onclick="wpsCRMTimerWidget.stop()" style="display:none;">
			⏹ <?php _e( 'Stop', 'cpsmartcrm' ); ?>
		</button>
	</div>
	
	<div class="timer-info" id="timer-info" style="display:none;">
		<strong><?php _e( 'Kunde:', 'cpsmartcrm' ); ?></strong> <span id="info-customer">—</span><br>
		<strong><?php _e( 'Projekt:', 'cpsmartcrm' ); ?></strong> <span id="info-project">—</span><br>
		<strong><?php _e( 'Aufgabe:', 'cpsmartcrm' ); ?></strong> <span id="info-task">—</span>
	</div>
	
	<?php if ( 'yes' === $atts['show_history'] ) : ?>
		<div class="timer-history">
			<h4><?php _e( 'Letzte Einträge', 'cpsmartcrm' ); ?></h4>
			<?php
			$recent_entries = $timetracker->get_user_entries( $user_id, array(
				'status' => 'completed',
				'limit' => 5,
			) );
			?>
			<?php if ( empty( $recent_entries ) ) : ?>
				<p style="color:#9ca3af;text-align:center;padding:20px 0;">
					<?php _e( 'Noch keine Einträge vorhanden.', 'cpsmartcrm' ); ?>
				</p>
			<?php else : ?>
				<?php foreach ( $recent_entries as $entry ) : ?>
					<div class="history-entry">
						<div>
							<div><strong><?php echo esc_html( $entry->project_name ?: __( 'Ohne Projekt', 'cpsmartcrm' ) ); ?></strong></div>
							<div class="history-entry-date">
								<?php echo date_i18n( 'd.m.Y H:i', strtotime( $entry->start_time ) ); ?>
							</div>
						</div>
						<div class="history-entry-duration">
							<?php
							$hours = floor( $entry->duration_minutes / 60 );
							$mins = $entry->duration_minutes % 60;
							printf( '%02d:%02d', $hours, $mins );
							?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
</div>

<script>
// Init when widget loads
jQuery(document).ready(function() {
	if (typeof wpsCRMTimerWidget !== 'undefined') {
		wpsCRMTimerWidget.init();
	}
});
</script>
