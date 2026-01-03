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
    $acc_options['booking_date_auto'] = (int) ($_POST['booking_date_auto'] ?? 0);
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
        array('label' => __('Regulär (19%)', 'cpsmartcrm'), 'percentage' => 19, 'account' => '1600'),
        array('label' => __('Ermäßigt (7%)', 'cpsmartcrm'), 'percentage' => 7, 'account' => '1601'),
        array('label' => __('Null (0%)', 'cpsmartcrm'), 'percentage' => 0, 'account' => '1602'),
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
            <?php _e('Definiere die Steuersätze, die in Deinen Rechnungen verwendet werden. Für Kleinunternehmer sind diese informativ.', 'cpsmartcrm'); ?>
        </p>

        <table class="widefat" style="margin: 12px 0;">
            <thead>
                <tr style="background: #f8f8f8;">
                    <th style="padding: 10px;">Bezeichnung</th>
                    <th style="padding: 10px; width: 120px;">Prozentsatz (%)</th>
                    <th style="padding: 10px; width: 150px;">Konto (SKR04)</th>
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

        <hr style="margin: 20px 0;" />

        <h3><?php _e('Buchungsoptionen', 'cpsmartcrm'); ?></h3>

        <div style="margin: 16px 0;">
            <label>
                <input type="checkbox" name="booking_date_auto" value="1" <?php checked($acc_options['booking_date_auto'] ?? 0, 1); ?> />
                <?php _e('Automatisch als gebucht markieren, wenn Rechnung bezahlt wird', 'cpsmartcrm'); ?>
            </label>
        </div>

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
