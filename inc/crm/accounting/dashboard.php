<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

global $wpdb;

$d_table = WPsCRM_TABLE . 'documenti';
$invoice_type = 2; // 2 = Rechnung/Faktura

$counts = array(
    'open' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE tipo = %d AND pagato = 0", $invoice_type)),
    'paid_unbooked' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE tipo = %d AND pagato = 1 AND registrato = 0", $invoice_type)),
    'booked' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE tipo = %d AND registrato = 1", $invoice_type)),
);

$recent_invoices = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, oggetto, data, totale, pagato, registrato FROM {$d_table} WHERE tipo = %d ORDER BY data DESC LIMIT 8",
        $invoice_type
    )
);
?>
<div class="wrap">
    <h1><?php _e('Buchhaltung', 'cpsmartcrm'); ?></h1>
    <p><?php _e('Zentrale Übersicht für Einnahmen, Rechnungen und Buchungen.', 'cpsmartcrm'); ?></p>

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

    <div class="card" style="max-width: 1100px; padding: 16px;">
        <h2><?php _e('Letzte Rechnungen', 'cpsmartcrm'); ?></h2>
        <?php if (!empty($recent_invoices)) : ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Rechnung', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Betrag', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Zahlung', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Buchung', 'cpsmartcrm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_invoices as $invoice) : ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($invoice->data))); ?></td>
                            <td>#<?php echo esc_html($invoice->id); ?> – <?php echo esc_html($invoice->oggetto); ?></td>
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
            <a class="button button-primary" href="<?php echo admin_url('admin.php?page=smartcrm_settings'); ?>">
                <?php _e('Buchhaltungs-Einstellungen', 'cpsmartcrm'); ?>
            </a>
            <a class="button" href="<?php echo admin_url('admin.php?page=smart-crm&p=dokumente/list.php'); ?>">
                <?php _e('Zu den Rechnungen', 'cpsmartcrm'); ?>
            </a>
        </p>
    </div>

    <div class="card" style="max-width: 960px; padding: 16px; margin-top: 16px;">
        <h2><?php _e('Geplante Funktionen', 'cpsmartcrm'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php _e('Offene und bezahlte Rechnungen mit Buchungsstatus', 'cpsmartcrm'); ?></li>
            <li><?php _e('Buchungen erstellen/prüfen, CSV-Export (z.B. DATEV-ähnlich)', 'cpsmartcrm'); ?></li>
            <li><?php _e('Standard-Einstellungen für Steuer, Konten, Nummernkreise', 'cpsmartcrm'); ?></li>
        </ul>
    </div>
</div>
