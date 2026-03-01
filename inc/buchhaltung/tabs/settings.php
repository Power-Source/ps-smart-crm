<?php
/**
 * Buchhaltungs-Einstellungen Tab
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

$acc_options = get_option('CRM_accounting_settings', array());
$bus_options = get_option('CRM_business_settings', array());

// Check if MarketPress is active
$marketpress_active = class_exists('MP_Order') && class_exists('MP_Cart');
$marketpress_integration_enabled = false;
$sync_message = '';
$sync_error = '';

if ($marketpress_active) {
    $integration_options = get_option('CRM_accounting_integrations', array());
    $marketpress_integration_enabled = !empty($integration_options['marketpress']['sync_enabled']);
}

// Handle manual MarketPress sync
if (isset($_POST['sync_marketpress_orders']) && check_admin_referer('sync_marketpress_nonce')) {
    if ($marketpress_active && $marketpress_integration_enabled) {
        global $wpdb;
        
        $args = array(
            'post_type' => 'mp_order',
            'post_status' => 'order_paid',
            'posts_per_page' => -1, // Alle bezahlten Bestellungen
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wpscrm_income_ids',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_wpscrm_income_ids',
                    'value' => '',
                    'compare' => '=',
                ),
            ),
            'orderby' => 'date',
            'order' => 'ASC',
        );

        $query = new WP_Query($args);
        $synced_count = 0;
        
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $order = new MP_Order($post->ID);
                $order_total = (float) $order->get_meta('mp_order_total', 0);
                
                if ($order_total > 0) {
                    WPsCRM_mp_sync_paid_order($order);
                    $synced_count++;
                }
            }
            
            $sync_message = sprintf(__('%d Bestellungen wurden erfolgreich synchronisiert.', 'cpsmartcrm'), $synced_count);
        } else {
            $sync_message = __('Keine fehlenden Bestellungen gefunden.', 'cpsmartcrm');
        }
    } else {
        $sync_error = __('MarketPress Integration ist nicht aktiviert.', 'cpsmartcrm');
    }
}

// Check if form submitted
if (isset($_POST['save_accounting_settings']) && check_admin_referer('accounting_settings_nonce')) {
    $tax_rates = array();
    if (!empty($_POST['tax_rates']) && is_array($_POST['tax_rates'])) {
        foreach ($_POST['tax_rates'] as $rate) {
            if (!empty($rate['label']) && isset($rate['percentage'])) {
                $tax_rates[] = array(
                    'label' => sanitize_text_field($rate['label']),
                    'percentage' => (float) $rate['percentage'],
                    'account' => sanitize_text_field($rate['account'] ?? '')
                );
            }
        }
    }

    $income_categories = array();
    if (!empty($_POST['income_categories']) && is_array($_POST['income_categories'])) {
        foreach ($_POST['income_categories'] as $cat) {
            $cat = trim($cat);
            if ($cat !== '') {
                $income_categories[] = substr(sanitize_text_field($cat), 0, 20);
            }
        }
    }

    $expense_categories = array();
    if (!empty($_POST['expense_categories']) && is_array($_POST['expense_categories'])) {
        foreach ($_POST['expense_categories'] as $cat) {
            $cat = trim($cat);
            if ($cat !== '') {
                $expense_categories[] = substr(sanitize_text_field($cat), 0, 20);
            }
        }
    }

    $acc_options['tax_rates'] = $tax_rates;
    $acc_options['booking_number_prefix'] = sanitize_text_field($_POST['booking_number_prefix'] ?? 'B');
    $acc_options['booking_number_start'] = (int) ($_POST['booking_number_start'] ?? 1000);
    $acc_options['currency'] = sanitize_text_field($_POST['currency'] ?? 'EUR');
    $acc_options['income_categories'] = array_values(array_unique($income_categories));
    $acc_options['expense_categories'] = array_values(array_unique($expense_categories));
    $acc_options['notes'] = wp_kses_post($_POST['notes'] ?? '');

    update_option('CRM_accounting_settings', $acc_options);
    echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert.', 'cpsmartcrm') . '</p></div>';
}

if (empty($acc_options['tax_rates'])) {
    $acc_options['tax_rates'] = array(
        array('label' => __('Regulär (19%)', 'cpsmartcrm'), 'percentage' => 19, 'account' => '1776'),
        array('label' => __('Ermäßigt (7%)', 'cpsmartcrm'), 'percentage' => 7, 'account' => '1771'),
        array('label' => __('Null (0%)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1000'),
        array('label' => __('Reverse Charge §13b', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1407'),
        array('label' => __('Innergemeinschaftl. (IGE)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1404'),
    );
}

// Defaults for categories if none saved yet
if (empty($acc_options['income_categories'])) {
    $acc_options['income_categories'] = array('dienstleistung', 'produktverkauf', 'wartung', 'abo', 'sonstiges');
}

if (empty($acc_options['expense_categories'])) {
    $acc_options['expense_categories'] = array('material', 'software', 'travel', 'office', 'marketing', 'other');
}

$is_kleinunternehmer = isset($bus_options['crm_kleinunternehmer']) && $bus_options['crm_kleinunternehmer'] == 1;
?>

<h2><?php _e('Buchhaltungs-Einstellungen', 'cpsmartcrm'); ?></h2>

<!-- Sync Messages -->
<?php if (!empty($sync_message)) : ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($sync_message); ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($sync_error)) : ?>
    <div class="notice notice-error is-dismissible">
        <p><?php echo esc_html($sync_error); ?></p>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 1100px; padding: 20px;">
    <h3><?php _e('Grundkonfiguration', 'cpsmartcrm'); ?></h3>
    
    <div style="background: #f0f0f0; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
        <?php if ($is_kleinunternehmer) : ?>
            <p><strong><?php _e('Status: Kleinunternehmer nach §19 UStG', 'cpsmartcrm'); ?></strong></p>
            <p style="color: #666; font-size: 13px;">
                <?php _e('Sie sind als Kleinunternehmer registriert. Sie sind nicht zur Umsatzsteuererhebung berechtigt, müssen aber auf Rechnungen angeben „Kleine Lieferer nach §19 UStG".', 'cpsmartcrm'); ?>
            </p>
        <?php else : ?>
            <p><strong><?php _e('Status: Unternehmer mit Umsatzsteuer-ID', 'cpsmartcrm'); ?></strong></p>
            <p style="color: #666; font-size: 13px;">
                <?php _e('Sie sind zur Umsatzsteuererhebung berechtigt. Umsatzsteuer-ID: ' . esc_html($bus_options['business_ustid'] ?? '—'), 'cpsmartcrm'); ?>
            </p>
        <?php endif; ?>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('accounting_settings_nonce'); ?>
        
        <h3><?php _e('Steuersätze', 'cpsmartcrm'); ?></h3>
        <p style="color: #666; font-size: 13px;">
            <?php _e('Definiere die Steuersätze nach deutschem Recht. SKR04-Konten für korrekte Buchführung.', 'cpsmartcrm'); ?>
            <br>
            <strong><?php _e('Wichtig:', 'cpsmartcrm'); ?></strong> 
            <?php _e('Für Kleinunternehmer sind diese informativ - sie können keine Umsatzsteuer erheben.', 'cpsmartcrm'); ?>
        </p>
        
        <?php if ($is_kleinunternehmer) : ?>
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                <p style="margin: 0; color: #856404; font-size: 13px;">
                    <strong>ℹ️ <?php _e('Aktuell: Kleinunternehmer', 'cpsmartcrm'); ?></strong><br>
                    <?php _e('Steuersätze sind auf 0% begrenzt, da Sie keine Umsatzsteuer berechnen dürfen.', 'cpsmartcrm'); ?>
                </p>
            </div>
        <?php endif; ?>

        <table class="widefat" style="margin: 12px 0;">
            <thead>
                <tr style="background: #f8f8f8;">
                    <th style="padding: 10px;"><?php _e('Bezeichnung', 'cpsmartcrm'); ?></th>
                    <th style="padding: 10px; width: 120px;"><?php _e('Prozentsatz (%)', 'cpsmartcrm'); ?></th>
                    <th style="padding: 10px; width: 150px;"><?php _e('Konto (SKR04)', 'cpsmartcrm'); ?></th>
                    <th style="padding: 10px; width: 50px;"></th>
                </tr>
            </thead>
            <tbody id="tax_rates_tbody">
                <?php foreach ($acc_options['tax_rates'] as $idx => $rate) : ?>
                    <tr>
                        <td style="padding: 8px;">
                            <input type="text" name="tax_rates[<?php echo $idx; ?>][label]" value="<?php echo esc_attr($rate['label']); ?>" class="widefat" />
                        </td>
                        <td style="padding: 8px;">
                            <input type="number" name="tax_rates[<?php echo $idx; ?>][percentage]" value="<?php echo esc_attr($rate['percentage']); ?>" step="0.01" min="0" max="100" class="widefat" />
                        </td>
                        <td style="padding: 8px;">
                            <input type="text" name="tax_rates[<?php echo $idx; ?>][account]" value="<?php echo esc_attr($rate['account']); ?>" placeholder="z.B. 1600" class="widefat" />
                        </td>
                        <td style="padding: 8px;">
                            <button type="button" class="button button-small remove-tax-row" data-index="<?php echo $idx; ?>">
                                <?php _e('Entfernen', 'cpsmartcrm'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p>
            <button type="button" id="add_tax_rate" class="button button-secondary">
                <?php _e('+ Steuersatz hinzufügen', 'cpsmartcrm'); ?>
            </button>
        </p>

        <!-- SKR04 Referenz Box -->
        <div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                📚 <?php _e('SKR04 Kontenrahmen - Wichtige Konten', 'cpsmartcrm'); ?>
            </h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 12px;">
                <div>
                    <strong><?php _e('Umsatzsteuer (Einnahmen):', 'cpsmartcrm'); ?></strong><br>
                    • 1776 - USt 19%<br>
                    • 1771 - USt 7% (ermäßigt)<br>
                    • 1773 - USt 5,5% (Land-/Forstwirtschaft)<br>
                    • 1000 - Kasse/Bank (0%)
                </div>
                <div>
                    <strong><?php _e('Vorsteuer (Ausgaben):', 'cpsmartcrm'); ?></strong><br>
                    • 1406 - VSt 19%<br>
                    • 1401 - VSt 7% (ermäßigt)<br>
                    • 1404 - VSt innergemeinschaftl. Erwerb<br>
                    • 1407 - VSt bei Reverse Charge (§13b)
                </div>
                <div>
                    <strong><?php _e('Erlöskonten:', 'cpsmartcrm'); ?></strong><br>
                    • 4400 - Erlöse 19%<br>
                    • 4300 - Erlöse 7%<br>
                    • 4120 - Erlöse im Ausland (0%)
                </div>
                <div>
                    <strong><?php _e('Sonderregelungen:', 'cpsmartcrm'); ?></strong><br>
                    • 1783 - Steuerfreie innergemeinschaftl. Lieferungen<br>
                    • 1787 - Reverse Charge (§13b UStG)<br>
                    • 1789 - Kleinunternehmer (§19 UStG)
                </div>
            </div>
            <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">
                <?php _e('Hinweis: Kontenrahmen sollten mit Ihrem Steuerberater abgestimmt werden.', 'cpsmartcrm'); ?>
            </p>
        </div>

        <hr style="margin: 20px 0;" />

        <h3><?php _e('Buchungsoptionen', 'cpsmartcrm'); ?></h3>

        <div style="margin: 16px 0;">
            <label for="booking_number_prefix"><?php _e('Buchungsnummer-Präfix:', 'cpsmartcrm'); ?></label>
            <input type="text" id="booking_number_prefix" name="booking_number_prefix" value="<?php echo esc_attr($acc_options['booking_number_prefix'] ?? 'B'); ?>" maxlength="10" class="small-text" />
            <p style="color: #666; font-size: 12px;">z.B. B-1000, B-1001, ...</p>
        </div>

        <div style="margin: 16px 0;">
            <label for="booking_number_start"><?php _e('Nächste Buchungsnummer beginnt bei:', 'cpsmartcrm'); ?></label>
            <input type="number" id="booking_number_start" name="booking_number_start" value="<?php echo esc_attr($acc_options['booking_number_start'] ?? 1000); ?>" min="1" class="small-text" />
        </div>

        <div style="margin: 16px 0;">
            <label for="currency"><?php _e('Währung:', 'cpsmartcrm'); ?></label>
            <select id="currency" name="currency" class="small-text">
                <option value="EUR" <?php selected($acc_options['currency'] ?? 'EUR', 'EUR'); ?>>EUR (€)</option>
                <option value="USD" <?php selected($acc_options['currency'] ?? 'EUR', 'USD'); ?>>USD ($)</option>
                <option value="GBP" <?php selected($acc_options['currency'] ?? 'EUR', 'GBP'); ?>>GBP (£)</option>
            </select>
        </div>

        <hr style="margin: 20px 0;" />

        <h3><?php _e('Kategorien für Einnahmen & Ausgaben', 'cpsmartcrm'); ?></h3>
        <p style="color: #666; font-size: 13px;">
            <?php _e('Eine Zeile pro Begriff, max. 20 Zeichen. Per + kannst du weitere Zeilen hinzufügen.', 'cpsmartcrm'); ?>
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4 style="margin-top: 0;"><?php _e('Einnahmen-Kategorien', 'cpsmartcrm'); ?></h4>
                <div id="income_categories_wrap" style="display: flex; flex-direction: column; gap: 6px;">
                    <?php foreach ($acc_options['income_categories'] as $idx => $cat) : ?>
                        <div class="cat-row" style="display: flex; gap: 6px; align-items: center;">
                            <input type="text" name="income_categories[]" value="<?php echo esc_attr($cat); ?>" maxlength="20" class="widefat" />
                            <button type="button" class="button button-small remove-income-category" aria-label="<?php _e('Kategorie entfernen', 'cpsmartcrm'); ?>">&minus;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-small" id="add_income_category" style="margin-top: 6px;">
                    <?php _e('+ Kategorie', 'cpsmartcrm'); ?>
                </button>
            </div>

            <div>
                <h4 style="margin-top: 0;"><?php _e('Ausgaben-Kategorien', 'cpsmartcrm'); ?></h4>
                <div id="expense_categories_wrap" style="display: flex; flex-direction: column; gap: 6px;">
                    <?php foreach ($acc_options['expense_categories'] as $idx => $cat) : ?>
                        <div class="cat-row" style="display: flex; gap: 6px; align-items: center;">
                            <input type="text" name="expense_categories[]" value="<?php echo esc_attr($cat); ?>" maxlength="20" class="widefat" />
                            <button type="button" class="button button-small remove-expense-category" aria-label="<?php _e('Kategorie entfernen', 'cpsmartcrm'); ?>">&minus;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-small" id="add_expense_category" style="margin-top: 6px;">
                    <?php _e('+ Kategorie', 'cpsmartcrm'); ?>
                </button>
            </div>
        </div>

        <hr style="margin: 20px 0;" />

        <h3><?php _e('Notizen', 'cpsmartcrm'); ?></h3>
        <textarea name="notes" rows="4" class="widefat" placeholder="<?php _e('z.B. Hinweise für Buchführung, Steuerberaterkontakt, etc.', 'cpsmartcrm'); ?>"><?php echo esc_textarea($acc_options['notes'] ?? ''); ?></textarea>

        <hr style="margin: 20px 0;" />

        <input type="hidden" name="save_accounting_settings" value="1" />
        <button type="submit" class="button button-primary">
            <?php _e('Einstellungen speichern', 'cpsmartcrm'); ?>
        </button>
    </form>
</div>

<script>
jQuery(function($) {
    let taxRateCount = <?php echo count($acc_options['tax_rates']); ?>;

    $('#add_tax_rate').on('click', function(e) {
        e.preventDefault();
        const html = `
            <tr>
                <td style="padding: 8px;">
                    <input type="text" name="tax_rates[${taxRateCount}][label]" value="" class="widefat" placeholder="z.B. Regulär" />
                </td>
                <td style="padding: 8px;">
                    <input type="number" name="tax_rates[${taxRateCount}][percentage]" value="19" step="0.01" min="0" max="100" class="widefat" />
                </td>
                <td style="padding: 8px;">
                    <input type="text" name="tax_rates[${taxRateCount}][account]" value="" placeholder="z.B. 1600" class="widefat" />
                </td>
                <td style="padding: 8px;">
                    <button type="button" class="button button-small remove-tax-row" data-index="${taxRateCount}">
                        <?php _e('Entfernen', 'cpsmartcrm'); ?>
                    </button>
                </td>
            </tr>
        `;
        $('#tax_rates_tbody').append(html);
        taxRateCount++;
    });

    $(document).on('click', '.remove-tax-row', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });

    $('#add_income_category').on('click', function(e) {
        e.preventDefault();
        $('#income_categories_wrap').append('<div class="cat-row" style="display: flex; gap: 6px; align-items: center;"><input type="text" name="income_categories[]" value="" maxlength="20" class="widefat" /><button type="button" class="button button-small remove-income-category" aria-label="<?php _e('Kategorie entfernen', 'cpsmartcrm'); ?>">&minus;</button></div>');
    });

    $('#add_expense_category').on('click', function(e) {
        e.preventDefault();
        $('#expense_categories_wrap').append('<div class="cat-row" style="display: flex; gap: 6px; align-items: center;"><input type="text" name="expense_categories[]" value="" maxlength="20" class="widefat" /><button type="button" class="button button-small remove-expense-category" aria-label="<?php _e('Kategorie entfernen', 'cpsmartcrm'); ?>">&minus;</button></div>');
    });

    $(document).on('click', '.remove-income-category', function(e) {
        e.preventDefault();
        const wrap = $('#income_categories_wrap');
        $(this).closest('.cat-row').remove();
        if (wrap.children().length === 0) {
            wrap.append('<div class="cat-row" style="display: flex; gap: 6px; align-items: center;"><input type="text" name="income_categories[]" value="" maxlength="20" class="widefat" /><button type="button" class="button button-small remove-income-category" aria-label="<?php _e('Kategorie entfernen', 'cpsmartcrm'); ?>">&minus;</button></div>');
        }
    });

    $(document).on('click', '.remove-expense-category', function(e) {
        e.preventDefault();
        const wrap = $('#expense_categories_wrap');
        $(this).closest('.cat-row').remove();
        if (wrap.children().length === 0) {
            wrap.append('<div class="cat-row" style="display: flex; gap: 6px; align-items: center;"><input type="text" name="expense_categories[]" value="" maxlength="20" class="widefat" /><button type="button" class="button button-small remove-expense-category" aria-label="<?php _e('Kategorie entfernen', 'cpsmartcrm'); ?>">&minus;</button></div>');
        }
    });
});
</script>

<!-- MarketPress Sync Section -->
<?php if ($marketpress_active && $marketpress_integration_enabled) : ?>
<div class="card" style="max-width: 1100px; padding: 20px; margin-top: 20px; background: #f0f8ff; border-left: 4px solid #0073aa;">
    <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
        <span class="dashicons dashicons-update"></span>
        <?php _e('MarketPress Synchronisation', 'cpsmartcrm'); ?>
    </h3>
    <p style="color: #666;">
        <?php _e('Prüfen Sie alle bezahlten MarketPress-Bestellungen und fügen Sie fehlende automatisch in die Buchhaltung ein.', 'cpsmartcrm'); ?>
    </p>
    <form method="post" action="" onsubmit="return confirm('<?php esc_attr_e('Möchten Sie wirklich alle fehlenden bezahlten Bestellungen synchronisieren?', 'cpsmartcrm'); ?>');">
        <?php wp_nonce_field('sync_marketpress_nonce'); ?>
        <input type="hidden" name="sync_marketpress_orders" value="1" />
        <button type="submit" class="button button-primary">
            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 4px;"></span>
            <?php _e('Jetzt synchronisieren', 'cpsmartcrm'); ?>
        </button>
        <p style="font-size: 12px; color: #666; margin-top: 8px;">
            <?php _e('Hinweis: Bereits erfasste Bestellungen werden übersprungen (keine Duplikate).', 'cpsmartcrm'); ?>
        </p>
    </form>
</div>
<?php endif; ?>
