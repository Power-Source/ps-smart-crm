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

// Calculate revenue for current period (CRM invoices only)
$revenue_data = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        COUNT(*) as count,
        COALESCE(SUM(totale_imponibile), 0) as paid_net,
        COALESCE(SUM(totale_imposta), 0) as paid_tax,
        COALESCE(SUM(totale), 0) as paid_total
     FROM {$d_table}
     WHERE tipo = 2 AND pagato = 1 AND DATE_FORMAT(data, '%%Y-%%m') = %s",
    $period_str
));

// Calculate manual incomes for current period
$manual_income_data = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM {$i_table}
     WHERE DATE_FORMAT(data, '%%Y-%%m') = %s",
    $period_str
));

// Calculate income breakdown by source (for revenue sources box)
// Only show integration sources (eCommerce, etc.), not CRM internal categories
$income_by_source = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        kategoria as source,
        COUNT(*) as count,
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM {$i_table}
     WHERE DATE_FORMAT(data, '%%Y-%%m') = %s
     GROUP BY kategoria
     ORDER BY total DESC",
    $period_str
), ARRAY_A);

// Prepare source breakdown - only include integration sources
$sources_breakdown = array();
$sources_breakdown['PS Smart CRM'] = array(
    'count' => (int)($revenue_data->count ?? 0),
    'net' => (float)($revenue_data->paid_net ?? 0),
    'tax' => (float)($revenue_data->paid_tax ?? 0),
    'total' => (float)($revenue_data->paid_total ?? 0)
);

// Map integration category names to display labels
$integration_sources = array(
    'eCommerce' => 'MarketPress',
    'BlogHosting' => 'PS-Bloghosting'
);

foreach ($income_by_source as $source) {
    $source_key = $source['source'];
    // Map to integration name or use source name if not mapped
    $display_name = isset($integration_sources[$source_key]) ? $integration_sources[$source_key] : $source_key;
    
    // Only add if it's a known integration source (skip internal CRM categories like abo, wartung)
    if (isset($integration_sources[$source_key]) || !in_array($source_key, array('abo', 'wartung', 'dienstleistung', 'produktverkauf'))) {
        $sources_breakdown[$display_name] = array(
            'count' => (int)$source['count'],
            'net' => (float)$source['net'],
            'tax' => (float)$source['tax'],
            'total' => (float)$source['total']
        );
    }
}

$manual_income_net = (float)($manual_income_data->net ?? 0);
$manual_income_tax = (float)($manual_income_data->tax ?? 0);
$manual_income_total = (float)($manual_income_data->total ?? 0);

// Total invoices count includes CRM + all income sources
$total_invoices_count = (int)($revenue_data->count ?? 0);
foreach ($income_by_source as $source) {
    $total_invoices_count += (int)$source['count'];
}

// Calculate total revenue (invoices + manual incomes)
$total_revenue_net = (float)($revenue_data->paid_net ?? 0) + $manual_income_net;
$total_revenue_tax = (float)($revenue_data->paid_tax ?? 0) + $manual_income_tax;
$total_revenue = (float)($revenue_data->paid_total ?? 0) + $manual_income_total;

// Calculate expenses for current period
$expenses_data = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        COUNT(*) as count,
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM {$e_table}
     WHERE DATE_FORMAT(data, '%%Y-%%m') = %s",
    $period_str
));

$total_expenses_net = (float)($expenses_data->net ?? 0);
$total_expenses_tax = (float)($expenses_data->tax ?? 0);
$total_expenses = (float)($expenses_data->total ?? 0);

// Calculate profit/loss
$profit_loss_net = $total_revenue_net - $total_expenses_net;
$profit_loss_tax = $total_revenue_tax - $total_expenses_tax;
$profit_loss = $total_revenue - $total_expenses;

// Get lists of incomes and expenses for current period
$incomes_list = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$i_table} WHERE DATE_FORMAT(data, '%%Y-%%m') = %s ORDER BY data DESC",
    $period_str
));

$expenses_list = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$e_table} WHERE DATE_FORMAT(data, '%%Y-%%m') = %s ORDER BY data DESC",
    $period_str
));

// Get invoices for current period
$invoices_list = $wpdb->get_results($wpdb->prepare(
    "SELECT d.*, k.firmenname, k.name, k.nachname 
     FROM {$d_table} d
     LEFT JOIN {$k_table} k ON d.id_cliente = k.id
     WHERE d.tipo = 2 AND d.pagato = 1 AND DATE_FORMAT(d.data, '%%Y-%%m') = %s 
     ORDER BY d.data DESC",
    $period_str
));

// Combine all transactions into one array
$all_transactions = array();

// Add invoices
foreach ($invoices_list as $invoice) {
    $all_transactions[] = array(
        'date' => $invoice->data,
        'type' => 'invoice',
        'category' => __('Rechnung', 'cpsmartcrm'),
        'description' => $invoice->numero . ' - ' . (!empty($invoice->firmenname) ? $invoice->firmenname : $invoice->name . ' ' . $invoice->nachname),
        'net' => (float)$invoice->totale_imponibile,
        'tax' => (float)$invoice->totale_imposta,
        'total' => (float)$invoice->totale,
        'is_income' => true
    );
}

// Add manual incomes
foreach ($incomes_list as $income) {
    $all_transactions[] = array(
        'date' => $income->data,
        'type' => 'manual_income',
        'category' => $income->kategoria,
        'description' => $income->descrizione,
        'net' => (float)$income->imponibile,
        'tax' => (float)$income->imposta,
        'total' => (float)$income->totale,
        'is_income' => true
    );
}

// Add expenses
foreach ($expenses_list as $expense) {
    $all_transactions[] = array(
        'date' => $expense->data,
        'type' => 'expense',
        'category' => $expense->kategoria,
        'description' => $expense->descrizione,
        'net' => (float)$expense->imponibile,
        'tax' => (float)$expense->imposta,
        'total' => (float)$expense->totale,
        'is_income' => false
    );
}

// Sort by date (newest first)
usort($all_transactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

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
<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">

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
                    <?php echo esc_html($total_invoices_count); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php _e('Netto-Summe Rechnungen:', 'cpsmartcrm'); ?></td>
                <td style="text-align: right; padding: 8px;">
                    <?php echo esc_html(WPsCRM_format_currency($total_revenue_net)); ?>
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
            <?php _e('Alle Einnahmequellen werden summiert.', 'cpsmartcrm'); ?>
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

    <!-- Revenue Sources Breakdown -->
    <div class="card" style="padding: 20px;">
        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">📈</span> 
            <?php _e('Einnahmequellen', 'cpsmartcrm'); ?>
        </h3>

        <table style="width: 100%; margin-top: 12px; border-collapse: collapse; font-size: 13px;">
            <?php 
            // Display each revenue source
            $source_labels = array(
                'CRM' => __('Rechnungen CRM', 'cpsmartcrm'),
                'eCommerce' => __('Rechnungen MarketPress', 'cpsmartcrm'),
                'BlogHosting' => __('Rechnungen PS-Bloghosting', 'cpsmartcrm')
            );
            
            foreach ($sources_breakdown as $source_key => $source_data) :
                if ($source_data['total'] > 0) : // Only show sources with revenue
                    $label = isset($source_labels[$source_key]) ? $source_labels[$source_key] : $source_key;
            ?>
            <tr style="border-bottom: 1px solid #e8e8e8;">
                <td style="padding: 6px;">
                    <strong><?php echo esc_html($label); ?>:</strong>
                </td>
                <td style="text-align: right; padding: 6px; font-family: monospace;">
                    <?php echo esc_html(WPsCRM_format_currency($source_data['total'])); ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #e8e8e8;">
                <td style="padding: 6px 6px 6px 20px; font-size: 12px; color: #666;">
                    <?php echo esc_html($source_data['count']); ?> <?php _e('Rechnung(en)', 'cpsmartcrm'); ?>
                </td>
                <td style="text-align: right; padding: 6px; font-size: 12px; color: #666; font-family: monospace;">
                    <?php echo esc_html(WPsCRM_format_currency($source_data['net'])); ?> <?php _e('netto', 'cpsmartcrm'); ?>
                </td>
            </tr>
            <?php 
                endif;
            endforeach; 
            
            // Show message if no sources have data
            if (empty(array_filter($sources_breakdown, function($s) { return $s['total'] > 0; }))) :
            ?>
            <tr>
                <td colspan="2" style="padding: 20px; text-align: center; color: #999; font-style: italic;">
                    <?php _e('Keine Einnahmen in diesem Zeitraum', 'cpsmartcrm'); ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <p style="margin-top: 12px; margin-bottom: 0; font-size: 11px; color: #999; font-style: italic;">
            <?php _e('Übersicht nach Quelle/Integration', 'cpsmartcrm'); ?>
        </p>
    </div>
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

<!-- Alle Transaktionen (Einnahmen & Ausgaben) chronologisch -->
<style>
/* Transaction List Styling - Independent from other components */
.crm-transaction-list-container {
    width: 100%;
    margin: 20px 0;
}

.crm-transaction-list-wrapper {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.crm-transaction-list-header {
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 16px;
    border-bottom: 2px solid #0073aa;
}

.crm-transaction-list-title {
    font-size: 20px;
    font-weight: 600;
    color: #23282d;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.crm-transaction-table-container {
    max-height: 900px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}

.crm-transaction-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.crm-transaction-table thead {
    position: sticky;
    top: 0;
    background: #f8f8f8;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.crm-transaction-table thead th {
    padding: 14px 12px;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #555;
    border-bottom: 2px solid #ddd;
}

.crm-transaction-table tbody tr {
    transition: background-color 0.15s ease;
}

.crm-transaction-table tbody tr:hover {
    background-color: rgba(0, 115, 170, 0.03) !important;
}

.crm-transaction-table tbody td {
    padding: 12px;
    border-bottom: 1px solid #e8e8e8;
}

.crm-transaction-row-income {
    background: #e8f5e9;
}

.crm-transaction-row-expense {
    background: #ffebee;
}

.crm-transaction-type-icon {
    font-size: 18px;
    font-weight: bold;
}

.crm-transaction-summary {
    margin-top: 24px;
    padding: 24px;
    background: linear-gradient(135deg, #f8f8f8 0%, #ffffff 100%);
    border-radius: 6px;
    border-left: 5px solid #0073aa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.crm-transaction-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    text-align: center;
}

.crm-transaction-summary-item {
    padding: 12px;
    border-radius: 4px;
    transition: transform 0.2s ease;
}

.crm-transaction-summary-item:hover {
    transform: translateY(-2px);
}

.crm-transaction-summary-label {
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.crm-transaction-summary-value {
    font-size: 26px;
    font-weight: 700;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

.crm-transaction-summary-profit {
    border-left: 2px solid #ddd;
    padding-left: 24px;
}

.crm-transaction-empty {
    padding: 60px 40px;
    text-align: center;
    color: #999;
    background: #fafafa;
    border-radius: 6px;
    border: 2px dashed #ddd;
}

.crm-transaction-empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.crm-transaction-empty-text {
    font-size: 16px;
    margin: 0;
}
</style>

<?php if (!empty($all_transactions)) : ?>
<div class="crm-transaction-list-container">
    <div class="crm-transaction-list-wrapper">
        <h3 class="crm-transaction-list-header">
            <span class="crm-transaction-list-title">
                <span style="font-size: 24px;">📋</span>
                <?php _e('Alle Einnahmen & Ausgaben', 'cpsmartcrm'); ?>
            </span>
        </h3>
        
        <div class="crm-transaction-table-container">
            <table class="crm-transaction-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;"><?php _e('Datum', 'cpsmartcrm'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php _e('Typ', 'cpsmartcrm'); ?></th>
                        <th style="width: 150px;"><?php _e('Kategorie', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Beschreibung', 'cpsmartcrm'); ?></th>
                        <th style="width: 120px; text-align: right;"><?php _e('Netto', 'cpsmartcrm'); ?></th>
                        <?php if (!$is_kleinunternehmer) : ?>
                        <th style="width: 100px; text-align: right;"><?php _e('USt./Vorst.', 'cpsmartcrm'); ?></th>
                        <?php endif; ?>
                        <th style="width: 140px; text-align: right;"><?php _e('Betrag', 'cpsmartcrm'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php _e('Belege', 'cpsmartcrm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $running_balance = 0;
                    foreach ($all_transactions as $trans) : 
                        $is_income = $trans['is_income'];
                        $amount = $trans['total'];
                        $running_balance += $is_income ? $amount : -$amount;
                        
                        $row_class = $is_income ? 'crm-transaction-row-income' : 'crm-transaction-row-expense';
                        
                        // Get attached belege for this transaction
                        $belege = array();
                        if (isset($trans['transaction_id'])) {
                            $type = isset($trans['invoice_id']) ? 'invoice' : (isset($trans['income_id']) ? 'income' : 'expense');
                            $trans_id = isset($trans['invoice_id']) ? $trans['invoice_id'] : (isset($trans['income_id']) ? $trans['income_id'] : $trans['expense_id']);
                            $belege = WPsCRM_get_beleg_by_transaction($type, $trans_id);
                        }
                    ?>
                    <tr class="<?php echo esc_attr($row_class); ?>">
                        <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($trans['date']))); ?></td>
                        <td style="text-align: center;">
                            <?php if ($is_income) : ?>
                                <span class="crm-transaction-type-icon" style="color: #2e7d32;">+</span>
                            <?php else : ?>
                                <span class="crm-transaction-type-icon" style="color: #c62828;">−</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo esc_html($trans['category']); ?></strong></td>
                        <td><?php echo wp_kses_post($trans['description']); ?></td>
                        <td style="text-align: right; font-family: monospace;">
                            <?php echo esc_html(WPsCRM_format_currency($trans['net'])); ?>
                        </td>
                        <?php if (!$is_kleinunternehmer) : ?>
                        <td style="text-align: right; font-family: monospace;">
                            <?php echo esc_html(WPsCRM_format_currency($trans['tax'])); ?>
                        </td>
                        <?php endif; ?>
                        <td style="text-align: right; font-family: monospace;">
                            <strong style="color: <?php echo $is_income ? '#2e7d32' : '#c62828'; ?>;">
                                <?php echo $is_income ? '+' : '−'; ?> <?php echo esc_html(WPsCRM_format_currency($trans['total'])); ?>
                            </strong>
                        </td>
                        <td style="text-align: center;">
                            <?php if (!empty($belege)) : ?>
                                <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">
                                    📎 <?php echo count($belege); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #999; font-size: 12px;">−</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Transaction Summary Footer -->
        <div class="crm-transaction-summary">
            <div class="crm-transaction-summary-grid">
                <div class="crm-transaction-summary-item">
                    <div class="crm-transaction-summary-label">
                        <?php _e('Einnahmen (Netto)', 'cpsmartcrm'); ?>
                    </div>
                    <div class="crm-transaction-summary-value" style="color: #2e7d32;">
                        <?php echo esc_html(WPsCRM_format_currency($total_revenue_net)); ?>
                    </div>
                </div>
                <div class="crm-transaction-summary-item">
                    <div class="crm-transaction-summary-label">
                        <?php _e('Ausgaben (Netto)', 'cpsmartcrm'); ?>
                    </div>
                    <div class="crm-transaction-summary-value" style="color: #c62828;">
                        <?php echo esc_html(WPsCRM_format_currency($total_expenses_net)); ?>
                    </div>
                </div>
                <div class="crm-transaction-summary-item crm-transaction-summary-profit">
                    <div class="crm-transaction-summary-label">
                        <?php _e('Gewinn / Verlust', 'cpsmartcrm'); ?>
                    </div>
                    <div class="crm-transaction-summary-value" style="color: <?php echo $profit_loss_net >= 0 ? '#2e7d32' : '#c62828'; ?>;">
                        <?php echo esc_html(WPsCRM_format_currency($profit_loss_net)); ?>
                    </div>
                </div>
                <div class="crm-transaction-summary-item">
                    <div class="crm-transaction-summary-label">
                        <?php _e('USt.-Zahllast', 'cpsmartcrm'); ?>
                    </div>
                    <div class="crm-transaction-summary-value" style="color: #1976d2;">
                        <?php echo !$is_kleinunternehmer ? esc_html(WPsCRM_format_currency($total_revenue_tax - $total_expenses_tax)) : '−'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else : ?>
<div class="crm-transaction-list-container">
    <div class="crm-transaction-empty">
        <div class="crm-transaction-empty-icon">📋</div>
        <p class="crm-transaction-empty-text">
            <?php _e('Keine Transaktionen im ausgewählten Zeitraum vorhanden.', 'cpsmartcrm'); ?>
        </p>
    </div>
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
