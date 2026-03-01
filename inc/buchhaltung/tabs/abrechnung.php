<?php
/**
 * Abrechnung (Zeiterfassung) Tab
 * 
 * Verwaltet Abrechnungen von erfassten Arbeitszeiten
 * - Zeigt unbezahlte Stunden pro Agent
 * - Erstellt Abrechnungsentwürfe
 * - Überführt in tatsächliche Einnahme-Buchungen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

if ( ! current_user_can( 'manage_crm' ) ) {
	wp_die( esc_html__( 'Zugriff verweigert.', 'cpsmartcrm' ) );
}

$current_user_id = get_current_user_id();
$notices = array();

// Handle CRUD actions
if ( isset( $_POST['abrechnung_action'], $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'abrechnung_admin' ) ) {
	$action = sanitize_key( $_POST['abrechnung_action'] );
	
	if ( 'create_draft' === $action ) {
		$agent_id = isset( $_POST['agent_id'] ) ? absint( $_POST['agent_id'] ) : 0;
		if ( $agent_id > 0 && function_exists( 'wpscrm_create_billing_draft' ) ) {
			$result = wpscrm_create_billing_draft( $agent_id );
			if ( is_wp_error( $result ) ) {
				$notices[] = array( 'type' => 'error', 'text' => $result->get_error_message() );
			} else {
				$notices[] = array( 'type' => 'success', 'text' => sprintf( __( 'Abrechnungsentwurf #%d erstellt.', 'cpsmartcrm' ), $result ) );
			}
		}
	} elseif ( 'bill_draft' === $action ) {
		$draft_id = isset( $_POST['draft_id'] ) ? absint( $_POST['draft_id'] ) : 0;
		if ( $draft_id > 0 && function_exists( 'wpscrm_convert_draft_to_income' ) ) {
			$result = wpscrm_convert_draft_to_income( $draft_id );
			if ( is_wp_error( $result ) ) {
				$notices[] = array( 'type' => 'error', 'text' => $result->get_error_message() );
			} else {
				$notices[] = array( 'type' => 'success', 'text' => sprintf( __( 'Entwurf #%d gebucht. Einnahmeposten #%d erstellt.', 'cpsmartcrm' ), $draft_id, $result ) );
			}
		}
	} elseif ( 'cancel_draft' === $action ) {
		$draft_id = isset( $_POST['draft_id'] ) ? absint( $_POST['draft_id'] ) : 0;
		if ( $draft_id > 0 ) {
			$wpdb->update(
				WPsCRM_TABLE . 'billing_drafts',
				array( 'status' => 'cancelled' ),
				array( 'id' => $draft_id ),
				array( '%s' ),
				array( '%d' )
			);
			$notices[] = array( 'type' => 'success', 'text' => __( 'Entwurf gelöscht.', 'cpsmartcrm' ) );
		}
	}
}

// Get filter parameters
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : date( 'Y-m-01' );
$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : date( 'Y-m-d' );

// Get summary of unbilled timetracking
$tt_summary = function_exists( 'wpscrm_get_timetracking_summary' )
	? wpscrm_get_timetracking_summary( $date_from, $date_to )
	: array();

// Get all drafts
$billing_table = WPsCRM_TABLE . 'billing_drafts';
$draft_drafts = $wpdb->get_results( "SELECT * FROM $billing_table WHERE status = 'draft' ORDER BY created_at DESC" );
$draft_billed = $wpdb->get_results( "SELECT * FROM $billing_table WHERE status = 'billed' ORDER BY billed_at DESC LIMIT 50" );

?>

<style>
.abrechnung-container { max-width: 1200px; margin: 20px auto; }
.abrechnung-section { background: #fff; border: 1px solid #dcdcde; border-radius: 4px; margin-bottom: 20px; }
.section-header { background: #f8fafc; padding: 16px; border-bottom: 1px solid #dcdcde; font-weight: 600; }
.section-content { padding: 20px; }
.filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 16px; }
.filter-row label { font-weight: 600; display: block; margin-bottom: 4px; font-size: 12px; }
.filter-row input, .filter-row select { width: 100%; }
.summary-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
.summary-table th, .summary-table td { padding: 10px 12px; border-bottom: 1px solid #f0f2f5; text-align: left; }
.summary-table th { background: #f8fafc; font-weight: 600; }
.status-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
.status-draft { background: #e3f2fd; color: #1565c0; }
.status-billed { background: #d4edda; color: #155724; }
.amount { font-weight: 600; text-align: right; }
.notice-inline { padding: 10px 12px; border-radius: 4px; margin-bottom: 10px; }
.notice-success { background: #ecfdf3; border: 1px solid #b7ebc6; color: #0f5132; }
.notice-error { background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; }
.action-btn { padding: 4px 8px; margin: 0 2px; font-size: 11px; cursor: pointer; }
.flex-row { display: flex; gap: 8px; align-items: center; }
.info-box { background: #f0f7ff; border-left: 4px solid #2196F3; padding: 12px; margin-bottom: 16px; border-radius: 3px; }
.info-label { font-weight: 600; color: #1565c0; }
</style>

<div class="abrechnung-container">
	<h2><?php esc_html_e( 'Abrechnung (Zeiterfassung)', 'cpsmartcrm' ); ?></h2>

	<?php foreach ( $notices as $notice ) : ?>
		<div class="notice-inline <?php echo 'success' === $notice['type'] ? 'notice-success' : 'notice-error'; ?>">
			<?php echo esc_html( $notice['text'] ); ?>
		</div>
	<?php endforeach; ?>

	<!-- Unbilled Timetracking Summary -->
	<div class="abrechnung-section">
		<div class="section-header">
			<?php esc_html_e( 'Offene Arbeitszeiten (noch nicht abgerechnet)', 'cpsmartcrm' ); ?>
		</div>
		<div class="section-content">
			<form method="get" style="display: flex; gap: 12px; margin-bottom: 16px;">
				<input type="hidden" name="page" value="smart-crm" />
				<input type="hidden" name="p" value="buchhaltung/index.php" />
				<input type="hidden" name="accounting_tab" value="abrechnung" />
				
				<div style="flex: 1;">
					<label><?php esc_html_e( 'Von', 'cpsmartcrm' ); ?></label>
					<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
				</div>
				
				<div style="flex: 1;">
					<label><?php esc_html_e( 'Bis', 'cpsmartcrm' ); ?></label>
					<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
				</div>
				
				<div style="flex: 0 0 auto; display: flex; align-items: flex-end;">
					<button type="submit" class="button"><?php esc_html_e( 'Filtern', 'cpsmartcrm' ); ?></button>
				</div>
			</form>

			<?php if ( empty( $tt_summary ) ) : ?>
				<p style="color: #666; font-style: italic;">
					<?php esc_html_e( 'Keine offenen Arbeitszeiten in diesem Zeitraum gefunden.', 'cpsmartcrm' ); ?>
				</p>
			<?php else : ?>
				<table class="summary-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Agent', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Einträge', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Stunden', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Geschätzter Betrag', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Aktion', 'cpsmartcrm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php 
						$total_hours_all = 0;
						foreach ( $tt_summary as $row ) : 
							$hours = round( $row->total_minutes / 60, 2 );
							$total_hours_all += $hours;
							
							// Get agent info for rate calculation
							$billing_info = function_exists( 'wpscrm_get_agent_billing_info' )
								? wpscrm_get_agent_billing_info( $row->user_id )
								: (object) array( 'hourly_rate' => 0, 'rate_type' => 'net' );
							
							$amount_gross = ( 'gross' === $billing_info->rate_type )
								? $hours * $billing_info->hourly_rate
								: $hours * $billing_info->hourly_rate * 1.19;
						?>
							<tr>
								<td><?php echo esc_html( $row->display_name ); ?></td>
								<td style="text-align: right;"><?php echo esc_html( $row->entry_count ); ?></td>
								<td style="text-align: right;"><?php echo esc_html( $hours ); ?></td>
								<td class="amount">€ <?php echo esc_html( number_format( $amount_gross, 2, ',', '.' ) ); ?></td>
								<td>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'abrechnung_admin' ); ?>
										<input type="hidden" name="abrechnung_action" value="create_draft" />
										<input type="hidden" name="agent_id" value="<?php echo esc_attr( $row->agent_id ); ?>" />
										<button type="submit" class="button button-small action-btn">
											<?php esc_html_e( 'Entwurf erstellen', 'cpsmartcrm' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						<tr style="background: #f8fafc; font-weight: 600;">
							<td colspan="2"><?php esc_html_e( 'SUMME', 'cpsmartcrm' ); ?></td>
							<td style="text-align: right;"><?php echo esc_html( round( $total_hours_all, 2 ) ); ?> h</td>
							<td colspan="2"></td>
						</tr>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Billing Drafts (Entwürfe) -->
	<div class="abrechnung-section">
		<div class="section-header">
			<?php esc_html_e( 'Abrechnungsentwürfe (noch nicht gebucht)', 'cpsmartcrm' ); ?>
		</div>
		<div class="section-content">
			<?php if ( empty( $draft_drafts ) ) : ?>
				<p style="color: #666; font-style: italic;">
					<?php esc_html_e( 'Keine offenen Entwürfe.', 'cpsmartcrm' ); ?>
				</p>
			<?php else : ?>
				<div class="info-box">
					<span class="info-label">ℹ️ Info:</span>
					Diese Entwürfe sind noch nicht in der Buchhaltung gebucht. Klicke "Buchen", um sie als Einnahmeposten zu registrieren.
				</div>
				<table class="summary-table">
					<thead>
						<tr>
							<th>#</th>
							<th><?php esc_html_e( 'Agent', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Stunden', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Satz', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Brutto', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Erstellt', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Aktion', 'cpsmartcrm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $draft_drafts as $draft ) : 
							$user = get_user_by( 'id', $draft->user_id );
							$hours = round( $draft->total_minutes / 60, 2 );
						?>
							<tr>
								<td><?php echo esc_html( $draft->id ); ?></td>
								<td><?php echo esc_html( $user ? $user->display_name : __( '(Gelöschter User)', 'cpsmartcrm' ) ); ?></td>
								<td style="text-align: right;"><?php echo esc_html( $hours ); ?></td>
								<td style="text-align: right;">€ <?php echo esc_html( number_format( $draft->hourly_rate, 2, ',', '.' ) ); ?></td>
								<td class="amount">€ <?php echo esc_html( number_format( $draft->amount_gross, 2, ',', '.' ) ); ?></td>
								<td><?php echo esc_html( substr( $draft->created_at, 0, 10 ) ); ?></td>
								<td>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'abrechnung_admin' ); ?>
										<input type="hidden" name="abrechnung_action" value="bill_draft" />
										<input type="hidden" name="draft_id" value="<?php echo esc_attr( $draft->id ); ?>" />
										<button type="submit" class="button button-small button-primary action-btn">
											<?php esc_html_e( '✓ Buchen', 'cpsmartcrm' ); ?>
										</button>
									</form>
									
									<form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_html_e( 'Entwurf gelöscht?', 'cpsmartcrm' ); ?>');">
										<?php wp_nonce_field( 'abrechnung_admin' ); ?>
										<input type="hidden" name="abrechnung_action" value="cancel_draft" />
										<input type="hidden" name="draft_id" value="<?php echo esc_attr( $draft->id ); ?>" />
										<button type="submit" class="button button-small action-btn">
											<?php esc_html_e( '✕ Ablehnen', 'cpsmartcrm' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Billed History -->
	<div class="abrechnung-section">
		<div class="section-header">
			<?php esc_html_e( 'Gebuchte Abrechnungen (letzte 50)', 'cpsmartcrm' ); ?>
		</div>
		<div class="section-content">
			<?php if ( empty( $draft_billed ) ) : ?>
				<p style="color: #666; font-style: italic;">
					<?php esc_html_e( 'Noch keine Abrechnungen gebucht.', 'cpsmartcrm' ); ?>
				</p>
			<?php else : ?>
				<table class="summary-table">
					<thead>
						<tr>
							<th>#</th>
							<th><?php esc_html_e( 'Agent', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Stunden', 'cpsmartcrm' ); ?></th>
							<th style="text-align: right;"><?php esc_html_e( 'Betrag', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Gebucht am', 'cpsmartcrm' ); ?></th>
							<th><?php esc_html_e( 'Einnahmeposten', 'cpsmartcrm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $draft_billed as $billed ) : 
							$user = get_user_by( 'id', $billed->user_id );
							$hours = round( $billed->total_minutes / 60, 2 );
						?>
							<tr>
								<td><?php echo esc_html( $billed->id ); ?></td>
								<td><?php echo esc_html( $user ? $user->display_name : __( '(Gelöschter User)', 'cpsmartcrm' ) ); ?></td>
								<td style="text-align: right;"><?php echo esc_html( $hours ); ?></td>
								<td class="amount">€ <?php echo esc_html( number_format( $billed->amount_gross, 2, ',', '.' ) ); ?></td>
								<td><?php echo esc_html( substr( $billed->billed_at ?? '', 0, 10 ) ); ?></td>
								<td>
									<?php if ( $billed->income_entry_id ) : ?>
										<a href="admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=accounting&income_id=<?php echo esc_attr( $billed->income_entry_id ); ?>">
											#<?php echo esc_html( $billed->income_entry_id ); ?>
										</a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>
