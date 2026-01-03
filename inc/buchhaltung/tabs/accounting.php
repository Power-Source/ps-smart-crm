<?php
/**
 * Hauptbuchhaltungs-Tab
 * 
 * Zentrale Verwaltung aller Einnahmen und Ausgaben
 * Steuerberechnung und Gewinn/Verlust-Rechnung
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

global $wpdb;

$d_table = WPsCRM_TABLE . 'documenti';
$k_table = WPsCRM_TABLE . 'kunde';
$i_table = WPsCRM_TABLE . 'incomes';
$e_table = WPsCRM_TABLE . 'expenses';

// Get current year and month
$current_year = (int) ($_GET['year'] ?? date('Y'));
$current_month = str_pad((int) ($_GET['month'] ?? date('m')), 2, '0', STR_PAD_LEFT);
$period_str = $current_year . '-' . $current_month;

// Get business settings
$is_kleinunternehmer = WPsCRM_is_kleinunternehmer();
$tax_rates = WPsCRM_get_tax_rates();
$acc_options = get_option('CRM_accounting_settings', array());

// Kategorien aus Einstellungen; fallback auf Defaults
$income_categories_raw = (isset($acc_options['income_categories']) && is_array($acc_options['income_categories'])) ? $acc_options['income_categories'] : array();
$expense_categories_raw = (isset($acc_options['expense_categories']) && is_array($acc_options['expense_categories'])) ? $acc_options['expense_categories'] : array();

$income_categories = array();
foreach ($income_categories_raw as $cat) {
    $cat = trim($cat);
    if ($cat !== '') {
        $income_categories[] = sanitize_text_field($cat);
    }
}
$income_categories = array_values(array_unique($income_categories));

$expense_categories = array();
foreach ($expense_categories_raw as $cat) {
    $cat = trim($cat);
    if ($cat !== '') {
        $expense_categories[] = sanitize_text_field($cat);
    }
}
$expense_categories = array_values(array_unique($expense_categories));

if (empty($income_categories)) {
    $income_categories = array('dienstleistung', 'produktverkauf', 'wartung', 'abo', 'sonstiges');
}

if (empty($expense_categories)) {
    $expense_categories = array('material', 'software', 'travel', 'office', 'marketing', 'other');
}

// Handle form submissions
$action_message = '';
$action_error = '';

// Handle new expense entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_expense_action')) {
        $action_error = __('Sicherheitsüberprüfung fehlgeschlagen.', 'cpsmartcrm');
    } else {
        $expense_date = sanitize_text_field($_POST['expense_date'] ?? date('Y-m-d'));
        $expense_category = sanitize_text_field($_POST['expense_category'] ?? '');
        $expense_description = sanitize_text_field($_POST['expense_description'] ?? '');
        $expense_amount = floatval($_POST['expense_amount'] ?? 0);
        $expense_tax_rate = floatval($_POST['expense_tax_rate'] ?? 0);

        if (empty($expense_category) || $expense_amount <= 0) {
            $action_error = __('Bitte füllen Sie alle erforderlichen Felder aus.', 'cpsmartcrm');
        } else {
            // Calculate tax
            $expense_tax = $expense_amount * ($expense_tax_rate / 100);
            $expense_gross = $expense_amount + $expense_tax;

            // Insert into custom expenses table
            $result = $wpdb->insert(
                WPsCRM_TABLE . 'expenses',
                array(
                    'data' => $expense_date,
                    'kategoria' => $expense_category,
                    'descrizione' => $expense_description,
                    'imponibile' => $expense_amount,
                    'aliquota' => $expense_tax_rate,
                    'imposta' => $expense_tax,
                    'totale' => $expense_gross,
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s')
            );

            if ($result) {
                $action_message = __('Ausgabe erfolgreich hinzugefügt.', 'cpsmartcrm');
            } else {
                $action_error = __('Fehler beim Speichern der Ausgabe.', 'cpsmartcrm');
            }
        }
    }
}

// Handle new manual income entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_income') {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_income_action')) {
        $action_error = __('Sicherheitsüberprüfung fehlgeschlagen.', 'cpsmartcrm');
    } else {
        $income_date = sanitize_text_field($_POST['income_date'] ?? date('Y-m-d'));
        $income_category = sanitize_text_field($_POST['income_category'] ?? '');
        $income_description = sanitize_text_field($_POST['income_description'] ?? '');
        $income_amount = floatval($_POST['income_amount'] ?? 0);
        $income_tax_rate = floatval($_POST['income_tax_rate'] ?? 0);

        if (empty($income_category) || $income_amount <= 0) {
            $action_error = __('Bitte füllen Sie alle erforderlichen Felder aus.', 'cpsmartcrm');
        } else {
            $income_tax = $income_amount * ($income_tax_rate / 100);
            $income_gross = $income_amount + $income_tax;

            $result = $wpdb->insert(
                $i_table,
                array(
                    'data' => $income_date,
                    'kategoria' => $income_category,
                    'descrizione' => $income_description,
                    'imponibile' => $income_amount,
                    'aliquota' => $income_tax_rate,
                    'imposta' => $income_tax,
                    'totale' => $income_gross,
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s')
            );

            if ($result) {
                $action_message = __('Einnahme erfolgreich hinzugefügt.', 'cpsmartcrm');
            } else {
                $action_error = __('Fehler beim Speichern der Einnahme.', 'cpsmartcrm');
            }
        }
    }
}

// Get available months (invoices, manual incomes, expenses)
$available_months = $wpdb->get_results(
    "SELECT DISTINCT period FROM (
        SELECT DATE_FORMAT(data, '%Y-%m') as period FROM {$d_table} WHERE tipo = 2
        UNION
        SELECT DATE_FORMAT(data, '%Y-%m') as period FROM {$i_table}
        UNION
        SELECT DATE_FORMAT(data, '%Y-%m') as period FROM {$e_table}
    ) p
    ORDER BY period DESC
    LIMIT 36"
);

// Calculate financial data for the selected period
$start_date = $current_year . '-' . $current_month . '-01';
$end_date = $current_year . '-' . $current_month . '-31';

// Get paid invoices data
$revenue_data = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(importo_netto), 0) as paid_net,
            COALESCE(SUM(iva), 0) as paid_tax
         FROM {$d_table}
         WHERE tipo = 2 AND pagato = 1 AND data BETWEEN %s AND %s",
        $start_date, $end_date
    )
);
if (!$revenue_data) {
    $revenue_data = (object)array('count' => 0, 'paid_net' => 0, 'paid_tax' => 0);
}

// Get manual incomes (from incomes table)
$manual_income_result = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COALESCE(SUM(imponibile), 0) as net,
            COALESCE(SUM(imposta), 0) as tax
         FROM {$i_table}
         WHERE data BETWEEN %s AND %s",
        $start_date, $end_date
    )
);
$manual_income_net = $manual_income_result ? $manual_income_result->net : 0;
$manual_income_tax = $manual_income_result ? $manual_income_result->tax : 0;

// Get expenses data
$expenses_data = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(imponibile), 0) as net,
            COALESCE(SUM(imposta), 0) as tax
         FROM {$e_table}
         WHERE data BETWEEN %s AND %s",
        $start_date, $end_date
    )
);
if (!$expenses_data) {
    $expenses_data = (object)array('count' => 0, 'net' => 0, 'tax' => 0);
}

// Calculate totals
$total_revenue_net = (float)$revenue_data->paid_net + (float)$manual_income_net;
$total_revenue_tax = (float)$revenue_data->paid_tax + (float)$manual_income_tax;
$total_revenue = $total_revenue_net + $total_revenue_tax;

$total_expenses_net = (float)$expenses_data->net;
$total_expenses_tax = (float)$expenses_data->tax;
$total_expenses = $total_expenses_net + $total_expenses_tax;

$profit_loss_net = $total_revenue_net - $total_expenses_net;

?>

<h2><?php _e('Buchhaltung', 'cpsmartcrm'); ?></h2>

<style>
.buch-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9999;
    overflow-y: auto;
    padding: 32px 12px;
}
.buch-modal-content {
    background: #fff;
    border-radius: 6px;
    max-width: 780px;
    width: 100%;
    margin: 0 auto;
    padding: 20px 24px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.22);
    position: relative;
}
.buch-modal-close {
    position: absolute;
    top: 10px;
    right: 10px;
    text-decoration: none;
}
</style>

<!-- Period Selection -->
<form method="get" action="" style="background: #f8f8f8; padding: 12px; border-radius: 4px; margin-bottom: 20px;">
    <input type="hidden" name="page" value="smart-crm" />
    <input type="hidden" name="p" value="buchhaltung/index.php" />
    <input type="hidden" name="accounting_tab" value="accounting" />

    <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: flex-end;">
        <div>
            <label for="year"><strong><?php _e('Zeitraum:', 'cpsmartcrm'); ?></strong></label>
            <select id="period" name="period" class="widefat" style="display: none;">
                <?php foreach ($available_months as $m) : ?>
                    <option value="<?php echo esc_attr($m->period); ?>" <?php selected($period_str, $m->period); ?>>
                        <?php 
                        $date = DateTime::createFromFormat('Y-m', $m->period);
                        echo esc_html($date->format('F Y'));
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="month" id="month_picker" value="<?php echo esc_attr($period_str); ?>" 
                   style="padding: 6px; border: 1px solid #ddd; border-radius: 4px; width: 100%;" />
        </div>
        <div style="text-align: right;">
            <button type="submit" class="button button-primary">
                <?php _e('Anzeigen', 'cpsmartcrm'); ?>
            </button>
        </div>
    </div>
</form>

<script>
document.getElementById('month_picker').addEventListener('change', function() {
    const parts = this.value.split('-');
    document.querySelector('input[name="year"]').value = parts[0];
    document.querySelector('input[name="month"]').value = parts[1];
    this.form.submit();
});
</script>

<!-- Messages -->
<?php if (!empty($action_message)) : ?>
    <div class="notice notice-success is-dismissible" style="margin-bottom: 20px;">
        <p><?php echo esc_html($action_message); ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($action_error)) : ?>
    <div class="notice notice-error is-dismissible" style="margin-bottom: 20px;">
        <p><?php echo esc_html($action_error); ?></p>
    </div>
<?php endif; ?>

<!-- Key Financial Metrics -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;">
    <div class="card" style="padding: 16px; border-left: 4px solid #0073aa;">
        <h3 style="margin-top: 0; font-size: 20px; color: #0073aa;">
            <?php echo esc_html(WPsCRM_format_currency($total_revenue_net)); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Einnahmen (Netto)', 'cpsmartcrm'); ?>
        </p>
    </div>

    <div class="card" style="padding: 16px; border-left: 4px solid #ff6b00;">
        <h3 style="margin-top: 0; font-size: 20px; color: #ff6b00;">
            <?php echo esc_html(WPsCRM_format_currency($total_expenses_net)); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Ausgaben (Netto)', 'cpsmartcrm'); ?>
        </p>
    </div>

    <div class="card" style="padding: 16px; border-left: 4px solid #046307;">
        <h3 style="margin-top: 0; font-size: 20px; color: #046307;">
            <?php echo esc_html(WPsCRM_format_currency($profit_loss_net)); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Gewinn (Netto)', 'cpsmartcrm'); ?>
        </p>
    </div>

    <div class="card" style="padding: 16px; border-left: 4px solid #dd3333;">
        <h3 style="margin-top: 0; font-size: 20px; color: #dd3333;">
            <?php echo !$is_kleinunternehmer ? esc_html(WPsCRM_format_currency($total_revenue_tax - $total_expenses_tax)) : __('–', 'cpsmartcrm'); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('USt.-Zahlung', 'cpsmartcrm'); ?>
        </p>
    </div>
</div>

<!-- Einnahmen & Ausgaben Übersicht -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

    <!-- Einnahmen Section -->
    <div class="card" style="padding: 20px;">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">💰</span> 
            <?php _e('Einnahmen', 'cpsmartcrm'); ?>
        </h3>

        <table style="width: 100%; margin-top: 12px; border-collapse: collapse;">
            <tr style="background: #f8f8f8; border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Bezahlte Rechnungen:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px; font-weight: bold;">
                    <?php echo esc_html($revenue_data->count ?? 0); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Netto-Summe Rechnungen:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($revenue_data->paid_net ?? 0)); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Manuelle Einnahmen (Netto):', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($manual_income_net)); ?>
                </td>
            </tr>
            <?php if (!$is_kleinunternehmer) : ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Umsatzsteuer:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($total_revenue_tax)); ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr style="background: #f8f8f8; border-bottom: 1px solid #ddd; font-weight: bold;">
                <td style="padding: 8px;"><?php _e('Brutto-Summe:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($total_revenue)); ?>
                </td>
            </tr>
        </table>

        <button type="button" class="button button-secondary" id="toggle_income_form" style="margin-top: 12px; width: 100%;">
            <?php _e('+ Manuelle Einnahme', 'cpsmartcrm'); ?>
        </button>
        <p style="margin-top: 8px; font-size: 12px; color: #666;">
            <?php _e('Rechnungen (bezahlt) + manuelle Einnahmen fließen in die Summen ein.', 'cpsmartcrm'); ?>
        </p>
    </div>

    <!-- Ausgaben Section -->
    <div class="card" style="padding: 20px;">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">📊</span> 
            <?php _e('Ausgaben', 'cpsmartcrm'); ?>
        </h3>

        <table style="width: 100%; margin-top: 12px; border-collapse: collapse;">
            <tr style="background: #f8f8f8; border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Erfasste Ausgaben:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px; font-weight: bold;">
                    <?php echo esc_html($expenses_data->count ?? 0); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Netto-Summe:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($total_expenses_net)); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Vorsteuer:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($total_expenses_tax)); ?>
                </td>
            </tr>
            <tr style="background: #f8f8f8; border-bottom: 1px solid #ddd; font-weight: bold;">
                <td style="padding: 8px;"><?php _e('Brutto-Summe:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($total_expenses)); ?>
                </td>
            </tr>
        </table>

        <button type="button" class="button button-secondary" id="toggle_expense_form" style="margin-top: 12px; width: 100%;">
            <?php _e('+ Neue Ausgabe', 'cpsmartcrm'); ?>
        </button>
    </div>
</div>

<!-- Transaction Details / Buchungen Übersicht -->
<div style="margin: 30px 0; background: #fff; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
    <h3 style="margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">📋</span> 
        <?php _e('Alle Buchungen', 'cpsmartcrm'); ?>
    </h3>

    <?php
    // Get all incomes and expenses for the period
    $incomes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, data, kategoria, descrizione, imponibile, aliquota, imposta, totale, 'income' as type 
             FROM {$i_table}
             WHERE data BETWEEN %s AND %s
             ORDER BY data DESC, id DESC",
            $start_date, $end_date
        )
    );

    $expenses = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, data, kategoria, descrizione, imponibile, aliquota, imposta, totale, 'expense' as type 
             FROM {$e_table}
             WHERE data BETWEEN %s AND %s
             ORDER BY data DESC, id DESC",
            $start_date, $end_date
        )
    );

    // Merge and sort chronologically
    $transactions = array_merge((array)$incomes, (array)$expenses);
    usort($transactions, function($a, $b) {
        $cmp = strtotime($b->data) - strtotime($a->data);
        return $cmp !== 0 ? $cmp : $b->id - $a->id;
    });
    ?>

    <?php if (empty($transactions)) : ?>
        <p style="color: #666; padding: 20px; text-align: center;">
            <?php _e('Keine Buchungen für diesen Zeitraum.', 'cpsmartcrm'); ?>
        </p>
    <?php else : ?>
        <div style="width: calc(100vw - 40px); max-width: 100%; overflow-y: auto; max-height: 900px; border: 1px solid #ddd; border-radius: 4px; margin-left: -20px; margin-right: -20px; padding: 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead style="position: sticky; top: 0; background: #f8f8f8; border-bottom: 2px solid #ddd; z-index: 10;">
                    <tr>
                        <th style="padding: 10px; text-align: left; font-weight: bold;"><?php _e('Datum', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: left; font-weight: bold;"><?php _e('Typ', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: left; font-weight: bold;"><?php _e('Kategorie', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: left; font-weight: bold;"><?php _e('Beschreibung', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: right; font-weight: bold;"><?php _e('Netto', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: right; font-weight: bold;"><?php _e('Steuersatz', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: right; font-weight: bold;"><?php _e('Steuer', 'cpsmartcrm'); ?></th>
                        <th style="padding: 10px; text-align: right; font-weight: bold;"><?php _e('Brutto', 'cpsmartcrm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t) : 
                        // Strip HTML tags to get plain text for description display
                        $description_text = wp_strip_all_tags($t->descrizione);
                        $description_text = mb_strimwidth($description_text, 0, 100, '...');
                    ?>
                        <tr style="border-bottom: 1px solid #eee; <?php echo $t->type === 'income' ? 'background: #f0f9f0;' : 'background: #f9f0f0;'; ?>"
                            data-date="<?php echo esc_attr($t->data); ?>" 
                            data-type="<?php echo esc_attr($t->type); ?>" 
                            data-totale="<?php echo esc_attr($t->totale); ?>">
                            <td style="padding: 8px; white-space: nowrap;">
                                <?php echo esc_html(date_i18n('d.m.Y', strtotime($t->data))); ?>
                            </td>
                            <td style="padding: 8px; white-space: nowrap;">
                                <?php if ($t->type === 'income') : ?>
                                    <span style="background: #46b450; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                        <?php _e('Einnahme', 'cpsmartcrm'); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="background: #d63638; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                        <?php _e('Ausgabe', 'cpsmartcrm'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px;">
                                <?php echo esc_html(ucfirst($t->kategoria)); ?>
                            </td>
                            <td style="padding: 8px; max-width: 300px;">
                                <?php 
                                // If description contains HTML links, display them (MarketPress orders, etc.)
                                if (strpos($t->descrizione, '<a') !== false) {
                                    echo wp_kses_post($t->descrizione);
                                } else {
                                    // Otherwise show plain text, truncated
                                    echo esc_html($description_text);
                                }
                                ?>
                            </td>
                            <td style="padding: 8px; text-align: right; white-space: nowrap;">
                                <?php echo esc_html(WPsCRM_format_currency($t->imponibile)); ?>
                            </td>
                            <td style="padding: 8px; text-align: right; white-space: nowrap;">
                                <?php echo esc_html($t->aliquota); ?>%
                            </td>
                            <td style="padding: 8px; text-align: right; white-space: nowrap;">
                                <?php echo esc_html(WPsCRM_format_currency($t->imposta)); ?>
                            </td>
                            <td style="padding: 8px; text-align: right; white-space: nowrap; font-weight: bold;">
                                <?php echo esc_html(WPsCRM_format_currency($t->totale)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            <p style="font-size: 12px; color: #666; margin-bottom: 15px;">
                <?php _e('Insgesamt', 'cpsmartcrm'); ?>: 
                <strong><?php echo esc_html(count($transactions)); ?> <?php _e('Buchungen', 'cpsmartcrm'); ?></strong> 
                (<?php echo count($incomes); ?> <?php _e('Einnahmen', 'cpsmartcrm'); ?> / 
                <?php echo count($expenses); ?> <?php _e('Ausgaben', 'cpsmartcrm'); ?>)
            </p>

            <!-- Period Filter & Quick Stats -->
            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 20px; align-items: start;">
                <div>
                    <label for="transaction_period_filter" style="display: block; margin-bottom: 8px; font-weight: bold; font-size: 13px;">
                        <?php _e('Zeitraum für Auswertung:', 'cpsmartcrm'); ?>
                    </label>
                    <select id="transaction_period_filter" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="month"><?php _e('Aktueller Monat', 'cpsmartcrm'); ?></option>
                        <option value="week"><?php _e('Aktuelle Woche', 'cpsmartcrm'); ?></option>
                        <option value="day"><?php _e('Heute', 'cpsmartcrm'); ?></option>
                        <option value="year"><?php _e('Dieses Jahr', 'cpsmartcrm'); ?></option>
                        <option value="all"><?php _e('Alle Zeiträume', 'cpsmartcrm'); ?></option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                    <div style="background: #f0f9f0; padding: 12px; border-radius: 4px; border-left: 4px solid #46b450;">
                        <div style="font-size: 11px; color: #666; margin-bottom: 4px;">
                            <?php _e('Einnahmen', 'cpsmartcrm'); ?>
                        </div>
                        <div style="font-size: 18px; font-weight: bold; color: #46b450;" class="trans_filter_income">
                            <?php 
                            $income_sum = array_reduce($incomes, function($carry, $item) {
                                return $carry + $item->totale;
                            }, 0);
                            echo esc_html(WPsCRM_format_currency($income_sum));
                            ?>
                        </div>
                    </div>

                    <div style="background: #f9f0f0; padding: 12px; border-radius: 4px; border-left: 4px solid #d63638;">
                        <div style="font-size: 11px; color: #666; margin-bottom: 4px;">
                            <?php _e('Ausgaben', 'cpsmartcrm'); ?>
                        </div>
                        <div style="font-size: 18px; font-weight: bold; color: #d63638;" class="trans_filter_expenses">
                            <?php 
                            $expense_sum = array_reduce($expenses, function($carry, $item) {
                                return $carry + $item->totale;
                            }, 0);
                            echo esc_html(WPsCRM_format_currency($expense_sum));
                            ?>
                        </div>
                    </div>

                    <div style="background: #f0f7ff; padding: 12px; border-radius: 4px; border-left: 4px solid #0073aa;">
                        <div style="font-size: 11px; color: #666; margin-bottom: 4px;">
                            <?php _e('Differenz', 'cpsmartcrm'); ?>
                        </div>
                        <div style="font-size: 18px; font-weight: bold; color: #0073aa;" class="trans_filter_diff">
                            <?php 
                            $diff = $income_sum - $expense_sum;
                            echo esc_html(WPsCRM_format_currency($diff));
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        (function() {
            const filterSelect = document.getElementById('transaction_period_filter');
            if (!filterSelect) return;

            function formatCurrency(value) {
                return value.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
            }

            function calculateTotals() {
                const period = filterSelect.value;
                const today = new Date();
                let startDate = new Date();

                // Calculate start date based on period
                switch(period) {
                    case 'day':
                        startDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                        break;
                    case 'week':
                        const day = today.getDay() || 7; // Sunday = 7
                        const diff = today.getDate() - day + 1;
                        startDate = new Date(today.getFullYear(), today.getMonth(), diff);
                        break;
                    case 'month':
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        break;
                    case 'year':
                        startDate = new Date(today.getFullYear(), 0, 1);
                        break;
                    case 'all':
                        startDate = new Date(1970, 0, 1);
                        break;
                }

                let incomeTotal = 0;
                let expenseTotal = 0;

                // Get all rows
                const rows = document.querySelectorAll('tbody tr[data-date]');
                rows.forEach(function(row) {
                    const dateStr = row.getAttribute('data-date'); // YYYY-MM-DD format
                    const type = row.getAttribute('data-type');
                    const totale = parseFloat(row.getAttribute('data-totale')) || 0;

                    // Parse date
                    const rowDate = new Date(dateStr + 'T00:00:00');

                    // Check if within range
                    if (rowDate >= startDate) {
                        if (type === 'income') {
                            incomeTotal += totale;
                        } else {
                            expenseTotal += totale;
                        }
                    }
                });

                const diff = incomeTotal - expenseTotal;

                // Update display
                const incomeEl = document.querySelector('.trans_filter_income');
                const expenseEl = document.querySelector('.trans_filter_expenses');
                const diffEl = document.querySelector('.trans_filter_diff');

                if (incomeEl) incomeEl.textContent = formatCurrency(incomeTotal);
                if (expenseEl) expenseEl.textContent = formatCurrency(expenseTotal);
                if (diffEl) diffEl.textContent = formatCurrency(diff);
            }

            // Initial calculation
            calculateTotals();

            // Listen to changes
            filterSelect.addEventListener('change', calculateTotals);
        })();
        </script>
    <?php endif; ?>
</div>

<!-- New Income Form Modal -->
<div id="income_modal" class="buch-modal-overlay" style="display: none;">
    <div class="buch-modal-content">
        <a href="#" class="buch-modal-close" data-close-modal="income_modal">&times;</a>
        <h3><?php _e('Manuelle Einnahme erfassen', 'cpsmartcrm'); ?></h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add_income" />
            <?php wp_nonce_field('add_income_action', 'nonce'); ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label for="income_date"><strong><?php _e('Datum:', 'cpsmartcrm'); ?></strong></label>
                    <input type="date" id="income_date" name="income_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" 
                           required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div>
                    <label for="income_category"><strong><?php _e('Kategorie:', 'cpsmartcrm'); ?></strong></label>
                    <select id="income_category" name="income_category" required 
                            style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value=""><?php _e('Wählen...', 'cpsmartcrm'); ?></option>
                        <?php foreach ($income_categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="grid-column: 1/-1;">
                    <label for="income_description"><strong><?php _e('Beschreibung:', 'cpsmartcrm'); ?></strong></label>
                    <input type="text" id="income_description" name="income_description" 
                           style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div>
                    <label for="income_amount"><strong><?php _e('Betrag (netto):', 'cpsmartcrm'); ?></strong></label>
                    <input type="number" id="income_amount" name="income_amount" step="0.01" min="0" 
                           required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div>
                    <label for="income_tax_rate"><strong><?php _e('Steuersatz:', 'cpsmartcrm'); ?></strong></label>
                    <select id="income_tax_rate" name="income_tax_rate" 
                            style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="0">0%</option>
                        <option value="7">7%</option>
                        <option value="19" selected>19%</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 12px; display: flex; gap: 8px;">
                <button type="submit" class="button button-primary">
                    <?php _e('Einnahme speichern', 'cpsmartcrm'); ?>
                </button>
                <button type="button" class="button button-secondary" data-close-modal="income_modal">
                    <?php _e('Abbrechen', 'cpsmartcrm'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- New Expense Form Modal -->
<div id="expense_modal" class="buch-modal-overlay" style="display: none;">
    <div class="buch-modal-content">
        <a href="#" class="buch-modal-close" data-close-modal="expense_modal">&times;</a>
        <h3><?php _e('Neue Ausgabe erfassen', 'cpsmartcrm'); ?></h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="add_expense" />
            <?php wp_nonce_field('add_expense_action', 'nonce'); ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label for="expense_date"><strong><?php _e('Datum:', 'cpsmartcrm'); ?></strong></label>
                    <input type="date" id="expense_date" name="expense_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" 
                           required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div>
                    <label for="expense_category"><strong><?php _e('Kategorie:', 'cpsmartcrm'); ?></strong></label>
                    <select id="expense_category" name="expense_category" required 
                            style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value=""><?php _e('Wählen...', 'cpsmartcrm'); ?></option>
                        <?php foreach ($expense_categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="grid-column: 1/-1;">
                    <label for="expense_description"><strong><?php _e('Beschreibung:', 'cpsmartcrm'); ?></strong></label>
                    <input type="text" id="expense_description" name="expense_description" 
                           style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div>
                    <label for="expense_amount"><strong><?php _e('Betrag (netto):', 'cpsmartcrm'); ?></strong></label>
                    <input type="number" id="expense_amount" name="expense_amount" step="0.01" min="0" 
                           required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>

                <div>
                    <label for="expense_tax_rate"><strong><?php _e('Steuersatz:', 'cpsmartcrm'); ?></strong></label>
                    <select id="expense_tax_rate" name="expense_tax_rate" 
                            style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="0">0%</option>
                        <option value="7">7%</option>
                        <option value="19" selected>19%</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 12px; display: flex; gap: 8px;">
                <button type="submit" class="button button-primary">
                    <?php _e('Ausgabe speichern', 'cpsmartcrm'); ?>
                </button>
                <button type="button" class="button button-secondary" data-close-modal="expense_modal">
                    <?php _e('Abbrechen', 'cpsmartcrm'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Invoices List for Current Month -->
<?php if (!empty($invoices_list)) : ?>
<div class="card" style="padding: 20px; margin-bottom: 20px;">
    <h3><?php _e('Rechnungen diesen Monat', 'cpsmartcrm'); ?></h3>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
        <thead>
            <tr style="background: #f0f0f0;">
                <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                <th><?php _e('Rechnungsnr.', 'cpsmartcrm'); ?></th>
                <th><?php _e('Kunde', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 120px;"><?php _e('Netto', 'cpsmartcrm'); ?></th>
                <?php if (!$is_kleinunternehmer) : ?>
                <th style="text-align: right; width: 100px;"><?php _e('USt.', 'cpsmartcrm'); ?></th>
                <?php endif; ?>
                <th style="text-align: right; width: 120px;"><?php _e('Gesamt', 'cpsmartcrm'); ?></th>
                <th style="text-align: center; width: 80px;"><?php _e('Status', 'cpsmartcrm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices_list as $invoice) : ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($invoice->data))); ?></td>
                    <td><strong><?php echo esc_html($invoice->numero); ?></strong></td>
                    <td>
                        <?php 
                        $cust_name = !empty($invoice->firmenname) 
                            ? $invoice->firmenname 
                            : $invoice->name . ' ' . $invoice->nachname;
                        echo esc_html($cust_name);
                        ?>
                    </td>
                    <td style="text-align: right;"><?php echo esc_html(WPsCRM_format_currency($invoice->totale_imponibile)); ?></td>
                    <?php if (!$is_kleinunternehmer) : ?>
                    <td style="text-align: right;"><?php echo esc_html(WPsCRM_format_currency($invoice->totale_imposta)); ?></td>
                    <?php endif; ?>
                    <td style="text-align: right;"><strong><?php echo esc_html(WPsCRM_format_currency($invoice->totale)); ?></strong></td>
                    <td style="text-align: center;">
                        <span style="<?php echo ($invoice->pagato == 1) ? 'color: green;' : 'color: orange;'; ?>">
                            <?php echo ($invoice->pagato == 1) ? '✓ Bezahlt' : '⏳ Offen'; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Manual Incomes List for Current Month -->
<?php if (!empty($incomes_list)) : ?>
<div class="card" style="padding: 20px; margin-bottom: 20px;">
    <h3><?php _e('Manuelle Einnahmen diesen Monat', 'cpsmartcrm'); ?></h3>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
        <thead>
            <tr style="background: #f0f0f0;">
                <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                <th><?php _e('Kategorie', 'cpsmartcrm'); ?></th>
                <th><?php _e('Beschreibung', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 120px;"><?php _e('Netto', 'cpsmartcrm'); ?></th>
                <?php if (!$is_kleinunternehmer) : ?>
                <th style="text-align: right; width: 100px;"><?php _e('USt.', 'cpsmartcrm'); ?></th>
                <?php endif; ?>
                <th style="text-align: right; width: 120px;"><?php _e('Gesamt', 'cpsmartcrm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($incomes_list as $income) : ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($income->data))); ?></td>
                    <td><?php echo esc_html($income->kategoria); ?></td>
                    <td><?php echo esc_html($income->descrizione); ?></td>
                    <td style="text-align: right;"><?php echo esc_html(WPsCRM_format_currency($income->imponibile)); ?></td>
                    <?php if (!$is_kleinunternehmer) : ?>
                    <td style="text-align: right;"><?php echo esc_html(WPsCRM_format_currency($income->imposta)); ?></td>
                    <?php endif; ?>
                    <td style="text-align: right;"><strong><?php echo esc_html(WPsCRM_format_currency($income->totale)); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Expenses List for Current Month -->
<?php if (!empty($expenses_list)) : ?>
<div class="card" style="padding: 20px;">
    <h3><?php _e('Erfasste Ausgaben diesen Monat', 'cpsmartcrm'); ?></h3>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
        <thead>
            <tr style="background: #f0f0f0;">
                <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                <th><?php _e('Kategorie', 'cpsmartcrm'); ?></th>
                <th><?php _e('Beschreibung', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 120px;"><?php _e('Netto', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 100px;"><?php _e('Steuersatz', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 100px;"><?php _e('Vorsteuer', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 120px;"><?php _e('Gesamt', 'cpsmartcrm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses_list as $expense) : ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($expense->data))); ?></td>
                    <td><?php echo esc_html($expense->kategoria); ?></td>
                    <td><?php echo esc_html($expense->descrizione); ?></td>
                    <td style="text-align: right;"><?php echo esc_html(WPsCRM_format_currency($expense->imponibile)); ?></td>
                    <td style="text-align: right;"><?php echo esc_html($expense->aliquota); ?>%</td>
                    <td style="text-align: right;"><?php echo esc_html(WPsCRM_format_currency($expense->imposta)); ?></td>
                    <td style="text-align: right;"><strong><?php echo esc_html(WPsCRM_format_currency($expense->totale)); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
function buchShowModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'block';
}
function buchHideModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

document.getElementById('toggle_income_form').addEventListener('click', function() {
    buchShowModal('income_modal');
});

document.getElementById('toggle_expense_form').addEventListener('click', function() {
    buchShowModal('expense_modal');
});

document.querySelectorAll('[data-close-modal]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        buchHideModal(btn.getAttribute('data-close-modal'));
    });
});

document.querySelectorAll('.buch-modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            buchHideModal(overlay.id);
        }
    });
});
</script>
