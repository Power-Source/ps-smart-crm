<?php
/**
 * Buchhaltungs-Buchungen Tab
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

global $wpdb;

$d_table = WPsCRM_TABLE . 'documenti';
$invoice_type = 2;

// Parse parameters
$page = max(1, (int) ($_GET['paged'] ?? 1));
$status = sanitize_text_field($_GET['status'] ?? '');
$date_from = sanitize_text_field($_GET['date_from'] ?? '');
$date_to = sanitize_text_field($_GET['date_to'] ?? '');
$search = sanitize_text_field($_GET['search'] ?? '');
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Build WHERE clause
$where_parts = array("tipo = %d");
$where_values = array($invoice_type);

if ($status === 'open') {
    $where_parts[] = 'pagato = 0';
} elseif ($status === 'paid_unbooked') {
    $where_parts[] = 'pagato = 1 AND registrato = 0';
} elseif ($status === 'booked') {
    $where_parts[] = 'registrato = 1';
}

if (!empty($date_from)) {
    $date_from_obj = date_create_from_format(get_option('date_format'), $date_from);
    if ($date_from_obj) {
        $where_parts[] = "data >= %s";
        $where_values[] = $date_from_obj->format('Y-m-d');
    }
}

if (!empty($date_to)) {
    $date_to_obj = date_create_from_format(get_option('date_format'), $date_to);
    if ($date_to_obj) {
        $where_parts[] = "data <= %s";
        $where_values[] = $date_to_obj->format('Y-m-d');
    }
}

if (!empty($search)) {
    $where_parts[] = "(oggetto LIKE %s OR id LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_term;
    $where_values[] = $search_term;
}

$where_clause = implode(' AND ', $where_parts);

// Count total
$count_query = $wpdb->prepare("SELECT COUNT(*) FROM {$d_table} WHERE {$where_clause}", ...$where_values);
$total_items = (int) $wpdb->get_var($count_query);
$total_pages = ceil($total_items / $items_per_page);

// Get bookings
$bookings_query = $wpdb->prepare(
    "SELECT id, data, oggetto, totale_imponibile, totale_imposta, totale, pagato, registrato FROM {$d_table} 
     WHERE {$where_clause} ORDER BY data DESC LIMIT %d OFFSET %d",
    ...[...$where_values, $items_per_page, $offset]
);
$bookings = $wpdb->get_results($bookings_query);

// Handle bulk mark as booked
if (isset($_POST['mark_booked']) && check_admin_referer('bookings_bulk_action')) {
    $ids = array_map('intval', $_POST['booking_ids'] ?? array());
    if (!empty($ids)) {
        foreach ($ids as $id) {
            $wpdb->update($d_table, array('registrato' => 1), array('id' => $id), array('%d'), array('%d'));
        }
        echo '<div class="notice notice-success"><p>' . sprintf(__('%d Buchung(en) als gebucht markiert.', 'cpsmartcrm'), count($ids)) . '</p></div>';
    }
}

// Handle CSV export
if (isset($_POST['export_csv']) && check_admin_referer('bookings_bulk_action')) {
    $ids = array_map('intval', $_POST['booking_ids'] ?? array());
    if (!empty($ids)) {
        $acc_options = get_option('CRM_accounting_settings', array());
        $currency = $acc_options['currency'] ?? 'EUR';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=buchungen_' . date('Y-m-d_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Rechnungs-ID', 'Datum', 'Betreff', 'Netto', 'Steuern', 'Gesamt', 'Status', 'Währung'), ';');
        
        foreach ($ids as $id) {
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT id, data, oggetto, totale_imponibile, totale_imposta, totale, pagato, registrato FROM {$d_table} WHERE id = %d",
                $id
            ));
            
            if ($booking) {
                $status_label = $booking->registrato ? 'Gebucht' : ($booking->pagato ? 'Zu buchen' : 'Offen');
                fputcsv($output, array(
                    '#' . $booking->id,
                    date_i18n(get_option('date_format'), strtotime($booking->data)),
                    $booking->oggetto,
                    number_format($booking->totale_imponibile, 2, ',', '.'),
                    number_format($booking->totale_imposta, 2, ',', '.'),
                    number_format($booking->totale, 2, ',', '.'),
                    $status_label,
                    $currency
                ), ';');
            }
        }
        
        fclose($output);
        exit;
    }
}
?>

<h2><?php _e('Rechnungen filtern und buchen', 'cpsmartcrm'); ?></h2>

<form method="get" action="" style="background: #f8f8f8; padding: 16px; border-radius: 4px; margin-bottom: 16px;">
    <input type="hidden" name="page" value="smart-crm" />
    <input type="hidden" name="p" value="buchhaltung/index.php" />
    <input type="hidden" name="accounting_tab" value="bookings" />

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
            <label for="status"><?php _e('Status:', 'cpsmartcrm'); ?></label>
            <select id="status" name="status" class="widefat">
                <option value=""><?php _e('Alle', 'cpsmartcrm'); ?></option>
                <option value="open" <?php selected($status, 'open'); ?>><?php _e('Offen', 'cpsmartcrm'); ?></option>
                <option value="paid_unbooked" <?php selected($status, 'paid_unbooked'); ?>><?php _e('Zu buchen', 'cpsmartcrm'); ?></option>
                <option value="booked" <?php selected($status, 'booked'); ?>><?php _e('Gebucht', 'cpsmartcrm'); ?></option>
            </select>
        </div>

        <div>
            <label for="date_from"><?php _e('Von Datum:', 'cpsmartcrm'); ?></label>
            <input type="text" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php echo esc_attr(get_option('date_format')); ?>" class="widefat" />
        </div>

        <div>
            <label for="date_to"><?php _e('Bis Datum:', 'cpsmartcrm'); ?></label>
            <input type="text" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php echo esc_attr(get_option('date_format')); ?>" class="widefat" />
        </div>

        <div>
            <label for="search"><?php _e('Suche (ID/Betreff):', 'cpsmartcrm'); ?></label>
            <input type="text" id="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Rechnungs-ID oder Betreff', 'cpsmartcrm'); ?>" class="widefat" />
        </div>
    </div>

    <button type="submit" class="button button-primary"><?php _e('Filtern', 'cpsmartcrm'); ?></button>
    <?php if (!empty($status) || !empty($date_from) || !empty($date_to) || !empty($search)) : ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=bookings')); ?>" class="button">
            <?php _e('Zurücksetzen', 'cpsmartcrm'); ?>
        </a>
    <?php endif; ?>
</form>

<?php if (!empty($bookings)) : ?>
    <form method="post" action="">
        <?php wp_nonce_field('bookings_bulk_action'); ?>

        <div style="margin-bottom: 12px; padding: 12px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffc107;">
            <label>
                <input type="checkbox" id="select-all-bookings" />
                <strong><?php _e('Alle auf dieser Seite auswählen', 'cpsmartcrm'); ?></strong>
            </label>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="select-all-bookings-header" /></th>
                    <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Rechnungs-ID / Betreff', 'cpsmartcrm'); ?></th>
                    <th style="text-align: right;"><?php _e('Netto', 'cpsmartcrm'); ?></th>
                    <th style="text-align: right;"><?php _e('Steuern', 'cpsmartcrm'); ?></th>
                    <th style="text-align: right;"><?php _e('Gesamt', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Zahlung', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Buchung', 'cpsmartcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking) : ?>
                    <tr>
                        <td><input type="checkbox" name="booking_ids[]" value="<?php echo esc_attr($booking->id); ?>" class="booking-checkbox" /></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking->data))); ?></td>
                        <td><strong>#<?php echo esc_html($booking->id); ?></strong><br /><small><?php echo esc_html($booking->oggetto); ?></small></td>
                        <td style="text-align: right;"><?php echo esc_html(number_format_i18n((float) $booking->totale_imponibile, 2)); ?></td>
                        <td style="text-align: right;"><?php echo esc_html(number_format_i18n((float) $booking->totale_imposta, 2)); ?></td>
                        <td style="text-align: right;"><strong><?php echo esc_html(number_format_i18n((float) $booking->totale, 2)); ?></strong></td>
                        <td><?php echo ((int)$booking->pagato === 1) ? '<span style="background:#46b450;color:#fff;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Bezahlt', 'cpsmartcrm') . '</span>' : '<span style="background:#d63638;color:#fff;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Offen', 'cpsmartcrm') . '</span>'; ?></td>
                        <td><?php 
                            if ((int)$booking->registrato === 1) {
                                echo '<span style="background:#46b450;color:#fff;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Gebucht', 'cpsmartcrm') . '</span>';
                            } elseif ((int)$booking->pagato === 1) {
                                echo '<span style="background:#ffb900;color:#1d2327;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Zu buchen', 'cpsmartcrm') . '</span>';
                            } else {
                                echo '<span style="background:#f0f0f1;color:#1d2327;padding:3px 8px;border-radius:3px;display:inline-block;font-size:12px;">' . __('Nicht fällig', 'cpsmartcrm') . '</span>';
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 16px; padding: 12px; background: #f0f0f0; border-radius: 4px;">
            <button type="submit" name="mark_booked" value="1" class="button button-primary">
                <?php _e('✓ Markierte als gebucht', 'cpsmartcrm'); ?>
            </button>
            <button type="submit" name="export_csv" value="1" class="button button-secondary" style="margin-left: 8px;">
                <?php _e('⬇ CSV exportieren', 'cpsmartcrm'); ?>
            </button>
        </div>
    </form>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(__('%d Einträge', 'cpsmartcrm'), $total_items); ?></span>
                <span class="pagination-links">
                    <?php if ($page > 1) : ?>
                        <a class="prev-page button" href="<?php echo esc_url(add_query_arg(array('paged' => 1))); ?>">«</a>
                        <a class="prev-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $page - 1))); ?>">‹</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) : ?>
                        <?php if ($i == $page) : ?>
                            <span class="page-numbers current"><span aria-current="page"><?php echo $i; ?></span></span>
                        <?php else : ?>
                            <a class="page-numbers" href="<?php echo esc_url(add_query_arg(array('paged' => $i))); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages) : ?>
                        <a class="next-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $page + 1))); ?>">›</a>
                        <a class="next-page button" href="<?php echo esc_url(add_query_arg(array('paged' => $total_pages))); ?>">»</a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
<?php else : ?>
    <div style="background: #f0f0f0; padding: 20px; border-radius: 4px; text-align: center;">
        <p><?php _e('Keine Buchungen mit den aktuellen Filtern gefunden.', 'cpsmartcrm'); ?></p>
    </div>
<?php endif; ?>

<script>
jQuery(function($) {
    $('#select-all-bookings, #select-all-bookings-header').on('change', function() {
        const isChecked = this.checked;
        $('.booking-checkbox').prop('checked', isChecked);
    });

    $('.booking-checkbox').on('change', function() {
        const totalCheckboxes = $('.booking-checkbox').length;
        const checkedCheckboxes = $('.booking-checkbox:checked').length;
        $('#select-all-bookings, #select-all-bookings-header').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
});
</script>
