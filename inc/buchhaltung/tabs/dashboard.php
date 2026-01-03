<?php
/**
 * Buchhaltungs-Dashboard Tab
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

global $wpdb;

$d_table = WPsCRM_TABLE . 'documenti';
$invoice_type = 2; // 2 = Rechnung/Faktura
$bus_options = get_option('CRM_business_settings', array());
$is_kleinunternehmer = WPsCRM_is_kleinunternehmer();

$counts = array(
    'open' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE tipo = %d AND pagato = 0", $invoice_type)),
    'paid_unbooked' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE tipo = %d AND pagato = 1 AND registrato = 0", $invoice_type)),
    'booked' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE tipo = %d AND registrato = 1", $invoice_type)),
);

// Get accounting summary for paid invoices
$summary = WPsCRM_get_accounting_summary(array('date_from' => date('Y-01-01'), 'date_to' => date('Y-12-31')));

$recent_invoices = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, oggetto, data, totale, totale_imponibile, totale_imposta, pagato, registrato FROM {$d_table} WHERE tipo = %d ORDER BY data DESC LIMIT 8",
        $invoice_type
    )
);
?>

<p><?php _e('Zentrale Übersicht für Einnahmen, Rechnungen und Buchungen.', 'cpsmartcrm'); ?></p>

<?php if ($is_kleinunternehmer) : ?>
    <div style="background: #fff3cd; padding: 12px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #ffc107;">
        <strong>ℹ️ <?php _e('Status: Kleinunternehmer nach §19 UStG', 'cpsmartcrm'); ?></strong><br />
        <small><?php _e('Sie sind nicht zur Umsatzsteuererhebung berechtigt. Auf Rechnungen muss angegeben werden: „Kleine Lieferer nach §19 UStG".', 'cpsmartcrm'); ?></small>
    </div>
<?php else : ?>
    <div style="background: #e7f3ff; padding: 12px; border-radius: 4px; margin-bottom: 16px; border: 1px solid #0073aa;">
        <strong>ℹ️ <?php _e('Status: Unternehmer mit Umsatzsteuer', 'cpsmartcrm'); ?></strong><br />
        <small><?php _e('Umsatzsteuer-ID: ', 'cpsmartcrm'); ?><?php echo esc_html($bus_options['business_ustid'] ?? '—'); ?></small>
    </div>
<?php endif; ?>

<div style="display: flex; gap: 16px; flex-wrap: wrap; margin: 16px 0;">
    <div class="card" style="min-width: 220px; padding: 16px;">
        <h2 style="margin-top: 0;"><?php _e('Offene Rechnungen', 'cpsmartcrm'); ?></h2>
        <p style="font-size: 28px; margin: 0;"><?php echo esc_html($counts['open']); ?></p>
        <p style="margin: 4px 0 0; color: #666;"><?php _e('noch nicht bezahlt', 'cpsmartcrm'); ?></p>
    </div>

    <div class="card" style="min-width: 220px; padding: 16px;">
        <h2 style="margin-top: 0;"><?php _e('Zu buchen', 'cpsmartcrm'); ?></h2>
        <p style="font-size: 28px; margin: 0;"><?php echo esc_html($counts['paid_unbooked']); ?></p>
        <p style="margin: 4px 0 0; color: #666;"><?php _e('bezahlt, aber noch nicht gebucht', 'cpsmartcrm'); ?></p>
    </div>

    <div class="card" style="min-width: 220px; padding: 16px;">
        <h2 style="margin-top: 0;"><?php _e('Gebuchte Rechnungen', 'cpsmartcrm'); ?></h2>
        <p style="font-size: 28px; margin: 0;"><?php echo esc_html($counts['booked']); ?></p>
        <p style="margin: 4px 0 0; color: #666;"><?php _e('bereits verbucht', 'cpsmartcrm'); ?></p>
    </div>
</div>

<?php if ($summary['total'] > 0) : ?>
    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin: 16px 0;">
        <div class="card" style="min-width: 220px; padding: 16px; background: #f0f8ff; border-left: 4px solid #0073aa;">
            <h3 style="margin-top: 0; color: #0073aa;"><?php _e('Jahresumsatz (bezahlt)', 'cpsmartcrm'); ?></h3>
            <p style="font-size: 24px; margin: 0; color: #0073aa;">
                <?php echo esc_html(number_format_i18n($summary['total'], 2)); ?>
            </p>
            <small style="color: #666;">Netto: <?php echo esc_html(number_format_i18n($summary['total_net'], 2)); ?></small>
        </div>

        <?php if (!$is_kleinunternehmer && $summary['total_tax'] > 0) : ?>
            <div class="card" style="min-width: 220px; padding: 16px; background: #fff3cd; border-left: 4px solid #ff9800;">
                <h3 style="margin-top: 0; color: #ff6b00;"><?php _e('Fällige Umsatzsteuer', 'cpsmartcrm'); ?></h3>
                <p style="font-size: 24px; margin: 0; color: #ff6b00;">
                    <?php echo esc_html(number_format_i18n($summary['total_tax'], 2)); ?>
                </p>
                <small style="color: #666;"><?php _e('aus bezahlten Rechnungen (dieses Jahr)', 'cpsmartcrm'); ?></small>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 1100px; padding: 16px;">
    <h2><?php _e('Letzte Rechnungen', 'cpsmartcrm'); ?></h2>
    <?php if (!empty($recent_invoices)) : ?>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
            <thead>
                <tr>
                    <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Rechnung', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Netto', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Steuern', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Gesamt', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Zahlung', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Buchung', 'cpsmartcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_invoices as $invoice) : ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice->data))); ?></td>
                        <td><strong>#<?php echo esc_html($invoice->id); ?></strong><br /><small><?php echo esc_html($invoice->oggetto); ?></small></td>
                        <td><?php echo esc_html(number_format_i18n((float) $invoice->totale_imponibile, 2)); ?></td>
                        <td>
                            <?php if (!$is_kleinunternehmer) : ?>
                                <?php echo esc_html(number_format_i18n((float) $invoice->totale_imposta, 2)); ?>
                            <?php else : ?>
                                <small><?php _e('(keine USt.)', 'cpsmartcrm'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(number_format_i18n((float) $invoice->totale, 2)); ?></td>
                        <td>
                            <?php if ((int) $invoice->pagato === 1) : ?>
                                <span class="label label-success" style="background:#46b450;color:#fff;padding:2px 6px;border-radius:3px;display:inline-block;">
                                    <?php _e('Bezahlt', 'cpsmartcrm'); ?>
                                </span>
                            <?php else : ?>
                                <span class="label label-warning" style="background:#d63638;color:#fff;padding:2px 6px;border-radius:3px;display:inline-block;">
                                    <?php _e('Offen', 'cpsmartcrm'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $invoice->registrato === 1) : ?>
                                <span class="label label-success" style="background:#46b450;color:#fff;padding:2px 6px;border-radius:3px;display:inline-block;">
                                    <?php _e('Gebucht', 'cpsmartcrm'); ?>
                                </span>
                            <?php elseif ((int) $invoice->pagato === 1) : ?>
                                <span class="label" style="background:#ffb900;color:#1d2327;padding:2px 6px;border-radius:3px;display:inline-block;">
                                    <?php _e('Zu buchen', 'cpsmartcrm'); ?>
                                </span>
                            <?php else : ?>
                                <span class="label" style="background:#f0f0f1;color:#1d2327;padding:2px 6px;border-radius:3px;display:inline-block;">
                                    <?php _e('Nicht fällig', 'cpsmartcrm'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p><?php _e('Keine Rechnungen gefunden.', 'cpsmartcrm'); ?></p>
    <?php endif; ?>

    <p style="margin-top: 16px; display: flex; gap: 8px;">
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=bookings')); ?>">
            <?php _e('Zur Buchungsverwaltung', 'cpsmartcrm'); ?>
        </a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=settings')); ?>">
            <?php _e('Zu den Einstellungen', 'cpsmartcrm'); ?>
        </a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&p=dokumente/list.php')); ?>">
            <?php _e('Rechnungen', 'cpsmartcrm'); ?>
        </a>
    </p>
</div>
