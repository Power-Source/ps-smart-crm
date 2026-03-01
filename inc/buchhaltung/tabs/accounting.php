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

$wpscrm_user_id = get_current_user_id();
$wpscrm_is_admin = current_user_can('manage_options');
$can_view_accounting = $wpscrm_is_admin || !function_exists('wpscrm_user_can') || wpscrm_user_can($wpscrm_user_id, 'can_view_accounting');
$can_edit_accounting = $wpscrm_is_admin || !function_exists('wpscrm_user_can') || wpscrm_user_can($wpscrm_user_id, 'can_edit_accounting');
$can_view_all_accounting = $wpscrm_is_admin || !function_exists('wpscrm_user_can') || wpscrm_user_can($wpscrm_user_id, 'can_view_all_accounting');
$is_own_accounting_scope = !$can_view_all_accounting;
$can_manage_manual_accounting = $can_edit_accounting;

if (!$can_view_accounting) {
	wp_die(__('Du hast keine Berechtigung für diesen Buchhaltungsbereich.', 'cpsmartcrm'));
}

global $wpdb;

$d_table = WPsCRM_TABLE . 'documenti';
$k_table = WPsCRM_TABLE . 'kunde';
$i_table = WPsCRM_TABLE . 'incomes';
$e_table = WPsCRM_TABLE . 'expenses';

// ===== NEUE LOGIK: Intelligente Zeitraum-Filterung =====

$period_preset = isset($_GET['period']) ? sanitize_key($_GET['period']) : 'this_month';
$custom_from = isset($_GET['custom_from']) ? sanitize_text_field($_GET['custom_from']) : '';
$custom_to = isset($_GET['custom_to']) ? sanitize_text_field($_GET['custom_to']) : '';
$show_comparison = isset($_GET['compare']) && $_GET['compare'] === '1';
$compare_type = isset($_GET['compare_type']) ? sanitize_key($_GET['compare_type']) : 'previous';

// Berechne aktuelle Periode
$current_period = wpscrm_calculate_accounting_period($period_preset, $custom_from, $custom_to);
$period_from = $current_period['from'];
$period_to = $current_period['to'];
$period_label = $current_period['label'];

// Berechne Vergleichsperiode (wenn aktiviert)
$comparison_period = null;
$comparison_data = null;
if ($show_comparison) {
    $comparison_period = wpscrm_calculate_comparison_period($period_preset, $compare_type);
    $comparison_data = wpscrm_get_accounting_period_data(
        $comparison_period['from'],
        $comparison_period['to'],
        $is_own_accounting_scope ? $wpscrm_user_id : null
    );
}

// Get current period data
$current_data = wpscrm_get_accounting_period_data(
    $period_from,
    $period_to,
    $is_own_accounting_scope ? $wpscrm_user_id : null
);

// Alte Variablen für Compatibility (werden aus den neuen Daten gefüllt)
$period_str = substr($period_from, 0, 7);
$current_year = (int)substr($period_from, 0, 4);
$current_month = (int)substr($period_from, 5, 2);

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
    if (!$can_manage_manual_accounting) {
        $action_error = __('Du hast keine Berechtigung zum Bearbeiten der Buchhaltung.', 'cpsmartcrm');
    } elseif (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_expense_action')) {
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
                    'created_by' => $wpscrm_user_id,
                ),
                array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d')
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
    if (!$can_manage_manual_accounting) {
        $action_error = __('Du hast keine Berechtigung zum Bearbeiten der Buchhaltung.', 'cpsmartcrm');
    } elseif (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_income_action')) {
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
                    'created_by' => $wpscrm_user_id,
                ),
                array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s', '%d')
            );

            if ($result) {
                $action_message = __('Einnahme erfolgreich hinzugefügt.', 'cpsmartcrm');
            } else {
                $action_error = __('Fehler beim Speichern der Einnahme.', 'cpsmartcrm');
            }
        }
    }
}

// Scope-Filter für Rechnungen (eigene vs alle)
$invoice_owner_filter = '';
$invoice_owner_filter_alias = '';
$invoice_owner_params = array();
if ($is_own_accounting_scope) {
    $invoice_owner_filter = " AND (fk_utenti_age = %d OR fk_utenti_ins = %d)";
    $invoice_owner_filter_alias = " AND (d.fk_utenti_age = %d OR d.fk_utenti_ins = %d)";
    $invoice_owner_params = array($wpscrm_user_id, $wpscrm_user_id);
}

$manual_owner_filter = '';
$manual_owner_params = array();
if ($is_own_accounting_scope) {
    $manual_owner_filter = ' AND created_by = %d';
    $manual_owner_params = array($wpscrm_user_id);
}

// Get available months
if ($is_own_accounting_scope) {
    $available_months = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT period FROM (
                SELECT DATE_FORMAT(data, '%%Y-%%m') as period FROM {$d_table} WHERE tipo = 2 {$invoice_owner_filter}
                UNION
                SELECT DATE_FORMAT(data, '%%Y-%%m') as period FROM {$i_table} WHERE created_by = %d
                UNION
                SELECT DATE_FORMAT(data, '%%Y-%%m') as period FROM {$e_table} WHERE created_by = %d
            ) p
            ORDER BY period DESC
            LIMIT 36",
            ...array_merge($invoice_owner_params, array($wpscrm_user_id, $wpscrm_user_id))
        )
    );
} else {
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
}

// Calculate revenue for current period (CRM invoices only)
$revenue_query = "SELECT 
        COUNT(*) as count,
        COALESCE(SUM(totale_imponibile), 0) as paid_net,
        COALESCE(SUM(totale_imposta), 0) as paid_tax,
        COALESCE(SUM(totale), 0) as paid_total
     FROM {$d_table}
     WHERE tipo = 2 AND pagato = 1 AND DATE_FORMAT(data, '%%Y-%%m') = %s{$invoice_owner_filter}";
$revenue_params = array_merge(array($period_str), $invoice_owner_params);
$revenue_data = $wpdb->get_row($wpdb->prepare($revenue_query, ...$revenue_params));

// Calculate manual incomes for current period
$manual_income_data = $wpdb->get_row($wpdb->prepare(
    "SELECT 
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM {$i_table}
     WHERE DATE_FORMAT(data, '%%Y-%%m') = %s{$manual_owner_filter}",
    ...array_merge(array($period_str), $manual_owner_params)
));

$income_by_source = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        kategoria as source,
        COUNT(*) as count,
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM {$i_table}
     WHERE DATE_FORMAT(data, '%%Y-%%m') = %s{$manual_owner_filter}
     GROUP BY kategoria
     ORDER BY total DESC",
    ...array_merge(array($period_str), $manual_owner_params)
), ARRAY_A);

// Calculate income breakdown by source (for revenue sources box)

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
     WHERE DATE_FORMAT(data, '%%Y-%%m') = %s{$manual_owner_filter}",
    ...array_merge(array($period_str), $manual_owner_params)
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
    "SELECT * FROM {$i_table} WHERE DATE_FORMAT(data, '%%Y-%%m') = %s{$manual_owner_filter} ORDER BY data DESC",
    ...array_merge(array($period_str), $manual_owner_params)
));

$expenses_list = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$e_table} WHERE DATE_FORMAT(data, '%%Y-%%m') = %s{$manual_owner_filter} ORDER BY data DESC",
    ...array_merge(array($period_str), $manual_owner_params)
));

// Get invoices for current period
$invoice_list_query = "SELECT d.*, k.firmenname, k.name, k.nachname 
     FROM {$d_table} d
     LEFT JOIN {$k_table} k ON d.fk_kunde = k.ID_kunde
     WHERE d.tipo = 2 AND d.pagato = 1 AND DATE_FORMAT(d.data, '%%Y-%%m') = %s{$invoice_owner_filter_alias}
     ORDER BY d.data DESC";
$invoice_list_params = array_merge(array($period_str), $invoice_owner_params);
$invoices_list = $wpdb->get_results($wpdb->prepare($invoice_list_query, ...$invoice_list_params));

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

<!-- Period Selection (REDESIGNED) -->
<div style="background: #f8f8f8; padding: 16px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #ddd;">
    <form method="get" action="">
        <input type="hidden" name="page" value="smart-crm" />
        <input type="hidden" name="p" value="buchhaltung/index.php" />
        <input type="hidden" name="accounting_tab" value="accounting" />

        <h3 style="margin-top: 0; margin-bottom: 16px;"><?php _e('Zeitraum & Vergleich', 'cpsmartcrm'); ?></h3>

        <!-- Zeitdpicker Buttons / Voreinstellungen -->
        <div style="margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap;">
            <?php foreach (wpscrm_get_accounting_period_presets() as $preset_key => $preset_info) : ?>
                <button type="button" 
                        class="button <?php echo $period_preset === $preset_key ? 'button-primary' : ''; ?>" 
                        onclick="setPreset('<?php echo esc_attr($preset_key); ?>')"
                        style="<?php echo $period_preset === $preset_key ? 'font-weight: bold;' : ''; ?>">
                    <?php echo esc_html($preset_info['icon'] . ' ' . $preset_info['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Custom Date Range (nur sichtbar wenn "Benutzerdef. Zeitraum" aktiv) -->
        <div id="custom_range_section" style="display: <?php echo $period_preset === 'custom' ? 'block' : 'none'; ?>; margin-bottom: 16px; padding: 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: flex-end;">
                <div>
                    <label><strong><?php _e('Von:', 'cpsmartcrm'); ?></strong></label>
                    <input type="date" name="custom_from" value="<?php echo esc_attr($custom_from ?: $period_from); ?>" 
                           style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>
                <div>
                    <label><strong><?php _e('Bis:', 'cpsmartcrm'); ?></strong></label>
                    <input type="date" name="custom_to" value="<?php echo esc_attr($custom_to ?: $period_to); ?>" 
                           style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;" />
                </div>
                <button type="submit" class="button button-primary"><?php _e('Anzeigen', 'cpsmartcrm'); ?></button>
            </div>
        </div>

        <!-- Comparison Options -->
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 12px; align-items: center; padding-top: 12px; border-top: 1px solid #ddd;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="compare" value="1" <?php checked($show_comparison, true); ?> 
                       onchange="document.querySelector('form').submit();" />
                <strong><?php _e('Mit Vorperiode vergleichen', 'cpsmartcrm'); ?></strong>
            </label>

            <?php if ($show_comparison) : ?>
                <select name="compare_type" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;" onchange="document.querySelector('form').submit();">
                    <option value="previous" <?php selected($compare_type, 'previous'); ?>>
                        <?php _e('Vergleich: vorherige Periode', 'cpsmartcrm'); ?>
                    </option>
                    <option value="year_ago" <?php selected($compare_type, 'year_ago'); ?>>
                        <?php _e('Vergleich: gleichzeitraum Vorjahr', 'cpsmartcrm'); ?>
                    </option>
                </select>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function setPreset(preset) {
    const form = document.querySelector('form');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'period';
    input.value = preset;
    
    // Hidden fields für custom bei Bedarf
    document.querySelector('input[name="custom_from"]').parentElement.parentElement.style.display = 
        preset === 'custom' ? 'block' : 'none';
    
    form.appendChild(input);
    form.submit();
}
</script>

<!-- PDF Export Button -->
<div style="text-align: right; margin-bottom: 16px;">
    <?php echo wpscrm_get_pdf_export_button($period_from, $period_to, sprintf(__('Buchhaltungs-Report %s', 'cpsmartcrm'), $period_label)); ?>
</div>

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

<!-- LIVEPROGNOSE SEKTION (DIE MAGIE) -->
<?php 
// Ruhle die Liveprognose (unbilled timetracking) innerhalb des selektierten Zeitraums
$tt_summary = function_exists('wpscrm_get_timetracking_summary')
    ? wpscrm_get_timetracking_summary($period_from, $period_to)
    : array();

$unbilled_hours = 0;
$unbilled_amount = 0;
foreach ($tt_summary as $agent_row) {
    $unbilled_hours += round($agent_row->total_minutes / 60, 2);
    
    $billing_info = function_exists('wpscrm_get_agent_billing_info')
        ? wpscrm_get_agent_billing_info($agent_row->user_id)
        : (object) array('hourly_rate' => 0, 'rate_type' => 'net');
    
    $agent_hours = round($agent_row->total_minutes / 60, 2);
    $agent_amount = ('gross' === $billing_info->rate_type)
        ? $agent_hours * $billing_info->hourly_rate
        : $agent_hours * $billing_info->hourly_rate * 1.19;
    
    $unbilled_amount += $agent_amount;
}

// Get pending billing drafts
$billing_table = WPsCRM_TABLE . 'billing_drafts';
$draft_count = 0;
$drafts_total = 0;

if ($wpdb->get_var("SHOW TABLES LIKE '$billing_table'")) {
    $draft_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $billing_table WHERE status = 'draft'");
    $drafts_total = (float)$wpdb->get_var("SELECT COALESCE(SUM(amount_gross), 0) FROM $billing_table WHERE status = 'draft'");
}
?>

<?php if (count($tt_summary) > 0 || $draft_count > 0) : ?>
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 20px; margin-bottom: 20px; color: #fff;">
    <h3 style="margin-top: 0; margin-bottom: 16px; font-size: 18px;">
        ⚡ <?php esc_html_e('Liveprognose: Unbilled Revenue', 'cpsmartcrm'); ?>
    </h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
            <div style="font-size: 12px; opacity: 0.9; margin-bottom: 4px;">
                <?php esc_html_e('Offene Arbeitszeiten', 'cpsmartcrm'); ?>
            </div>
            <div style="font-size: 24px; font-weight: bold;">
                <?php echo esc_html(round($unbilled_hours, 2)); ?> h
            </div>
            <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">
                <?php esc_html_e('noch nicht erfasst', 'cpsmartcrm'); ?>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
            <div style="font-size: 12px; opacity: 0.9; margin-bottom: 4px;">
                <?php esc_html_e('Geschätzter Betrag', 'cpsmartcrm'); ?>
            </div>
            <div style="font-size: 24px; font-weight: bold;">
                € <?php echo esc_html(number_format($unbilled_amount, 2, ',', '.')); ?>
            </div>
            <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">
                <?php esc_html_e('brutto', 'cpsmartcrm'); ?>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
            <div style="font-size: 12px; opacity: 0.9; margin-bottom: 4px;">
                <?php esc_html_e('Abrechnungsentwürfe', 'cpsmartcrm'); ?>
            </div>
            <div style="font-size: 24px; font-weight: bold;">
                <?php echo esc_html($draft_count); ?> (€ <?php echo esc_html(number_format($drafts_total, 2, ',', '.')); ?>)
            </div>
            <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">
                <?php printf(esc_html__('ausstehende Buchung', 'cpsmartcrm')); ?>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.2);">
        <a href="admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=abrechnung" 
           style="color: #fff; text-decoration: none; font-weight: bold; display: inline-flex; gap: 6px; align-items: center;">
            → <?php esc_html_e('Zur Abrechnung & Verwaltung', 'cpsmartcrm'); ?>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Vergleichssektion (wenn aktiv) -->
<?php if ($show_comparison && $comparison_data) : ?>
    <?php $comparison = wpscrm_compare_accounting_periods($current_data, $comparison_data); ?>
    <div style="background: #f0f8ff; border: 2px solid #0073aa; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
        <h4 style="margin-top: 0;"><?php _e('📊 Vergleich mit Vorperiode', 'cpsmartcrm'); ?></h4>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
            <div style="padding: 12px; background: #fff; border-radius: 4px; border-left: 4px solid #0073aa;">
                <small style="color: #666;"><?php _e('Einnahmen-Veränderung', 'cpsmartcrm'); ?></small><br />
                <strong><?php echo wpscrm_format_percentage_change(
                    $comparison['changes']['income']['percent'],
                    $comparison['changes']['income']['direction']
                ); ?></strong><br />
                <small style="color: #999;">
                    <?php echo esc_html(WPsCRM_format_currency($comparison['changes']['income']['absolute'])); ?>
                </small>
            </div>
            
            <div style="padding: 12px; background: #fff; border-radius: 4px; border-left: 4px solid #ff6b00;">
                <small style="color: #666;"><?php _e('Ausgaben-Veränderung', 'cpsmartcrm'); ?></small><br />
                <strong><?php echo wpscrm_format_percentage_change(
                    $comparison['changes']['expenses']['percent'],
                    $comparison['changes']['expenses']['direction']
                ); ?></strong><br />
                <small style="color: #999;">
                    <?php echo esc_html(WPsCRM_format_currency($comparison['changes']['expenses']['absolute'])); ?>
                </small>
            </div>
            
            <div style="padding: 12px; background: #fff; border-radius: 4px; border-left: 4px solid #46b450;">
                <small style="color: #666;"><?php _e('Gewinn-Veränderung', 'cpsmartcrm'); ?></small><br />
                <strong><?php echo wpscrm_format_percentage_change(
                    $comparison['changes']['profit']['percent'],
                    $comparison['changes']['profit']['direction']
                ); ?></strong><br />
                <small style="color: #999;">
                    <?php echo esc_html(WPsCRM_format_currency($comparison['changes']['profit']['absolute'])); ?>
                </small>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Mapping der neuen Daten zu alten Variablen für Compatibility -->
<?php
// Aus $current_data extrahieren
$total_invoices_count = $current_data['income']['invoices_count'];
$total_revenue_net = $current_data['income']['total_net'];
$total_revenue_tax = $current_data['income']['total_tax'];
$total_revenue = $current_data['income']['total_gross'];
$total_expenses_net = $current_data['expenses']['net'];
$total_expenses_tax = $current_data['expenses']['tax'];
$total_expenses = $current_data['expenses']['gross'];
$profit_loss_net = $current_data['summary']['profit_net'];
$profit_loss_tax = $total_revenue_tax - $total_expenses_tax;
$profit_loss = $current_data['summary']['profit_gross'];

// Expenses data
$expenses_data = (object) array(
    'count' => $current_data['expenses']['count'],
    'net' => $current_data['expenses']['net'],
    'tax' => $current_data['expenses']['tax'],
    'gross' => $current_data['expenses']['gross'],
);

// Income by source (aus Kategorien)
$global_wpdb = $wpdb;
$income_by_source = $global_wpdb->get_results($global_wpdb->prepare(
    "SELECT 
        kategoria as source,
        COUNT(*) as count,
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM " . WPsCRM_TABLE . "incomes
     WHERE data BETWEEN %s AND %s
     GROUP BY kategoria
     ORDER BY total DESC",
    $period_from,
    $period_to
), ARRAY_A);

// Rechnungsquelle Übersicht (vereinfacht)
$sources_breakdown = array(
    'CRM' => array(
        'count' => $current_data['income']['invoices_count'],
        'net' => $current_data['income']['invoices_net'],
        'total' => $current_data['income']['invoices_gross'],
    ),
);
?>

<?php if ($is_own_accounting_scope) : ?>
    <div class="notice notice-warning" style="margin-bottom: 20px;">
        <p><?php _e('Eingeschränkter Modus aktiv: Du siehst nur eigene Datensätze (Rechnungen + manuelle Buchungen).', 'cpsmartcrm'); ?></p>
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

        <?php if ($can_manage_manual_accounting) : ?>
            <button type="button" class="button" id="toggle_income_form" style="margin-top: 12px; width: 100%; padding: 8px;">
                + <?php _e('Manuelle Einnahme', 'cpsmartcrm'); ?>
            </button>
            
            <!-- Income Form - Inline in Container -->
            <div id="income_form_container" style="max-height: 0; overflow: hidden; transition: max-height 0.4s ease;">
                <form id="add_income_form" method="post" action="" style="background: #f0f7ff; padding: 16px; margin-top: 12px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="hidden" name="action" value="add_income" />
                    <input type="hidden" name="period_from" value="<?php echo esc_attr($period_from); ?>" />
                    <input type="hidden" name="period_to" value="<?php echo esc_attr($period_to); ?>" />
                    <?php wp_nonce_field('add_income_action', 'nonce'); ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label for="income_date"><small><?php _e('Datum', 'cpsmartcrm'); ?></small></label>
                            <input type="date" id="income_date" name="income_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" 
                                   required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" />
                        </div>
                        <div>
                            <label for="income_category"><small><?php _e('Kategorie', 'cpsmartcrm'); ?></small></label>
                            <select id="income_category" name="income_category" required 
                                    style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                                <option value=""><?php _e('Wählen...', 'cpsmartcrm'); ?></option>
                                <?php foreach ($income_categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="grid-column: 1/-1;">
                            <label for="income_description"><small><?php _e('Beschreibung', 'cpsmartcrm'); ?></small></label>
                            <input type="text" id="income_description" name="income_description" 
                                   style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" />
                        </div>
                        <div>
                            <label for="income_amount"><small><?php _e('Betrag (netto)', 'cpsmartcrm'); ?></small></label>
                            <input type="text" id="income_amount" name="income_amount" placeholder="90,45" 
                                   required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" />
                        </div>
                        <div>
                            <label for="income_tax_rate"><small><?php _e('Steuersatz', 'cpsmartcrm'); ?></small></label>
                            <select id="income_tax_rate" name="income_tax_rate" 
                                    style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                                <option value="0">0%</option>
                                <option value="7">7%</option>
                                <option value="19" selected>19%</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="button button-primary" style="font-size: 13px; padding: 6px 12px;">
                            ✓ <?php _e('Buchen', 'cpsmartcrm'); ?>
                        </button>
                        <button type="button" class="button" onclick="toggleIncomeForm();" style="font-size: 13px; padding: 6px 12px;">
                            ✕ <?php _e('Stornieren', 'cpsmartcrm'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
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

        <?php if ($can_manage_manual_accounting) : ?>
            <button type="button" class="button" id="toggle_expense_form" style="margin-top: 12px; width: 100%; padding: 8px;">
                + <?php _e('Neue Ausgabe', 'cpsmartcrm'); ?>
            </button>
            
            <!-- Expense Form - Inline in Container -->
            <div id="expense_form_container" style="max-height: 0; overflow: hidden; transition: max-height 0.4s ease;">
                <form id="add_expense_form" method="post" action="" style="background: #fff5f0; padding: 16px; margin-top: 12px; border: 1px solid #ddd; border-radius: 4px;">
                    <input type="hidden" name="action" value="add_expense" />
                    <input type="hidden" name="period_from" value="<?php echo esc_attr($period_from); ?>" />
                    <input type="hidden" name="period_to" value="<?php echo esc_attr($period_to); ?>" />
                    <?php wp_nonce_field('add_expense_action', 'nonce'); ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label for="expense_date"><small><?php _e('Datum', 'cpsmartcrm'); ?></small></label>
                            <input type="date" id="expense_date" name="expense_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" 
                                   required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" />
                        </div>
                        <div>
                            <label for="expense_category"><small><?php _e('Kategorie', 'cpsmartcrm'); ?></small></label>
                            <select id="expense_category" name="expense_category" required 
                                    style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                                <option value=""><?php _e('Wählen...', 'cpsmartcrm'); ?></option>
                                <?php foreach ($expense_categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="grid-column: 1/-1;">
                            <label for="expense_description"><small><?php _e('Beschreibung', 'cpsmartcrm'); ?></small></label>
                            <input type="text" id="expense_description" name="expense_description" 
                                   style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" />
                        </div>
                        <div>
                            <label for="expense_amount"><small><?php _e('Betrag (netto)', 'cpsmartcrm'); ?></small></label>
                            <input type="text" id="expense_amount" name="expense_amount" placeholder="90,45" 
                                   required style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;" />
                        </div>
                        <div>
                            <label for="expense_tax_rate"><small><?php _e('Steuersatz', 'cpsmartcrm'); ?></small></label>
                            <select id="expense_tax_rate" name="expense_tax_rate" 
                                    style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">
                                <option value="0">0%</option>
                                <option value="7">7%</option>
                                <option value="19" selected>19%</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="button button-primary" style="font-size: 13px; padding: 6px 12px;">
                            ✓ <?php _e('Buchen', 'cpsmartcrm'); ?>
                        </button>
                        <button type="button" class="button" onclick="toggleExpenseForm();" style="font-size: 13px; padding: 6px 12px;">
                            ✕ <?php _e('Stornieren', 'cpsmartcrm'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
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
</div></div>

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
// Stelle sicher, dass ajaxurl verfügbar ist
if (typeof ajaxurl === 'undefined') {
    ajaxurl = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
}

// Toggle Inline Forms mit Smooth Height Animation
function toggleIncomeForm() {
    const container = document.getElementById('income_form_container');
    const btn = document.getElementById('toggle_income_form');
    const form = container.querySelector('form');
    
    if (container.style.maxHeight && container.style.maxHeight !== '0px') {
        // Collapse
        container.style.maxHeight = '0px';
        btn.innerHTML = '+ <?php _e('Manuelle Einnahme', 'cpsmartcrm'); ?>';
    } else {
        // Expand
        container.style.maxHeight = (form.scrollHeight + 20) + 'px';
        btn.innerHTML = '- <?php _e('Formular ausblenden', 'cpsmartcrm'); ?>';
    }
}

function toggleExpenseForm() {
    const container = document.getElementById('expense_form_container');
    const btn = document.getElementById('toggle_expense_form');
    const form = container.querySelector('form');
    
    if (container.style.maxHeight && container.style.maxHeight !== '0px') {
        // Collapse
        container.style.maxHeight = '0px';
        btn.innerHTML = '+ <?php _e('Neue Ausgabe', 'cpsmartcrm'); ?>';
    } else {
        // Expand
        container.style.maxHeight = (form.scrollHeight + 20) + 'px';
        btn.innerHTML = '- <?php _e('Formular ausblenden', 'cpsmartcrm'); ?>';
    }
}

// Button Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const incomeBtn = document.getElementById('toggle_income_form');
    const expenseBtn = document.getElementById('toggle_expense_form');
    
    if (incomeBtn) {
        incomeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleIncomeForm();
        });
    }
    
    if (expenseBtn) {
        expenseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleExpenseForm();
        });
    }
});

// ===== AJAX Handler für Einnahme/Ausgabe Forms =====

// Income Form AJAX Handler
document.addEventListener('DOMContentLoaded', function() {
    const incomeForm = document.getElementById('add_income_form');
    console.log('🔍 Income Form gefunden:', incomeForm ? '✓' : '✗');
    console.log('🔍 ajaxurl verfügbar:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'NICHT VERFÜGBAR');
    
    if (incomeForm) {
        incomeForm.addEventListener('submit', function(e) {
            console.log('📝 Income Form Submitted!');
            e.preventDefault();
            
            try {
                // Validate amount input
                const amountInput = this.querySelector('#income_amount');
                console.log('📍 Amount Input gefunden:', amountInput ? '✓' : '✗');
                
                if (!amountInput) {
                    showAjaxNotice('❌ FEHLER: Betrag-Feld nicht gefunden!', 'error');
                    return;
                }
                
                const amountValue = amountInput.value.trim();
                console.log('💰 Amount Value:', amountValue);
                
                // Basic validation
                if (!amountValue) {
                    showAjaxNotice('❌ Bitte geben Sie einen Betrag ein!', 'error');
                    return;
                }
                
                // Try to parse amount with both separators
                const normalizedAmount = parseFloat(amountValue.replace('.', '').replace(',', '.'));
                console.log('💯 Normalized Amount:', normalizedAmount);
                
                if (isNaN(normalizedAmount) || normalizedAmount <= 0) {
                    showAjaxNotice('❌ Ungültiger Betrag! Bitte verwenden Sie Komma (,) oder Punkt (.) als Dezimaltrenner', 'error');
                    return;
                }
                
                const formData = new FormData(this);
                formData.append('action', 'wpscrm_add_income');
                
                console.log('📋 FormData Keys:', Array.from(formData.keys()));
                console.log('✉️ Nonce:', formData.get('nonce'));
                console.log('🌐 AJAX URL:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'UNDEFINED');
                
                const submitBtn = this.querySelector('button[type="submit"]');
                if (!submitBtn) {
                    showAjaxNotice('❌ FEHLER: Submit-Button nicht gefunden!', 'error');
                    return;
                }
                
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '⏳ Speichert...';
                submitBtn.disabled = true;
                
                if (typeof ajaxurl === 'undefined') {
                    showAjaxNotice('❌ KRITISCHER FEHLER: ajaxurl ist nicht definiert!', 'error');
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    return;
                }
                
                console.log('🚀 Starte AJAX-Anfrage zu:', ajaxurl);
                
                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('📡 Response Status:', response.status, response.statusText);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Server Response:', data);
                    if (data.success) {
                        updateIncomeMetrics(data.data);
                        showAjaxNotice('✓ Einnahme erfolgreich gespeichert!', 'success');
                        incomeForm.reset();
                        toggleIncomeForm();
                        setTimeout(() => {
                            const metricsSection = document.querySelector('[style*="display: grid"]');
                            if (metricsSection) metricsSection.scrollIntoView({ behavior: 'smooth' });
                        }, 300);
                    } else {
                        console.error('❌ Server Fehler:', data.data);
                        showAjaxNotice('❌ ' + (data.data?.message || 'Fehler beim Speichern'), 'error');
                    }
                })
                .catch(error => {
                    console.error('🔴 AJAX Fehler Details:', error);
                    showAjaxNotice('🔴 ' + error.message, 'error');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
            } catch (err) {
                console.error('🔴 JavaScript Fehler:', err);
                showAjaxNotice('🔴 JavaScript Fehler: ' + err.message, 'error');
            }
        });
    }
});

// Expense Form AJAX Handler
const expenseForm = document.getElementById('add_expense_form');
if (expenseForm) {
    expenseForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate amount input
        const amountInput = this.querySelector('#expense_amount');
        const amountValue = amountInput.value.trim();
        
        // Basic validation
        if (!amountValue) {
            showAjaxNotice('❌ Bitte geben Sie einen Betrag ein!', 'error');
            return;
        }
        
        // Try to parse amount with both separators
        const normalizedAmount = parseFloat(amountValue.replace('.', '').replace(',', '.'));
        if (isNaN(normalizedAmount) || normalizedAmount <= 0) {
            showAjaxNotice('❌ Ungültiger Betrag! Bitte verwenden Sie Komma (,) oder Punkt (.) als Dezimaltrenner', 'error');
            return;
        }
        
        const formData = new FormData(this);
        formData.append('action', 'wpscrm_add_expense');
        
        console.log('📋 Expense Form DEBUG - FormData Keys:', Array.from(formData.keys()));
        console.log('📋 Expense Form DEBUG - FormData Werte:', {
            amount: formData.get('expense_amount'),
            normalizedAmount: normalizedAmount,
            taxRate: formData.get('expense_tax_rate'),
            category: formData.get('expense_category'),
            date: formData.get('expense_date'),
            nonce: formData.get('nonce') ? '✓ vorhanden' : '✗ FEHLT!'
        });
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '⏳ Speichert...';
        submitBtn.disabled = true;
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Response Status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('✅ Server Response:', data);
            if (data.success) {
                updateExpenseMetrics(data.data);
                showAjaxNotice('✓ Ausgabe erfolgreich gespeichert!', 'success');
                expenseForm.reset();
                toggleExpenseForm();
                setTimeout(() => {
                    const metricsSection = document.querySelector('[style*="display: grid"]');
                    if (metricsSection) metricsSection.scrollIntoView({ behavior: 'smooth' });
                }, 300);
            } else {
                console.error('❌ Fehler:', data.data);
                showAjaxNotice('❌ ' + (data.data?.message || 'Fehler beim Speichern'), 'error');
            }
        })
        .catch(error => {
            console.error('🔴 AJAX Fehler:', error);
            showAjaxNotice('🔴 Kritischer Fehler: ' + error.message, 'error');
        })
        .finally(() => {
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        });
    });
}

// Update Income Metrics Animations
function updateIncomeMetrics(data) {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        const text = card.textContent;
        if (text.includes('Einnahmen') && text.includes('Netto')) {
            const valueEl = card.querySelector('h3');
            if (valueEl) {
                valueEl.style.transition = 'color 0.3s ease';
                valueEl.style.color = '#ff9800';
                valueEl.innerHTML = data.income_total;
                setTimeout(() => valueEl.style.color = '#0073aa', 300);
            }
        }
        if (text.includes('Gewinn') && text.includes('Netto')) {
            const valueEl = card.querySelector('h3');
            if (valueEl) {
                valueEl.style.transition = 'color 0.3s ease';
                valueEl.innerHTML = data.profit_net;
            }
        }
    });
}

// Update Expense Metrics Animations
function updateExpenseMetrics(data) {
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        const text = card.textContent;
        if (text.includes('Ausgaben') && text.includes('Netto')) {
            const valueEl = card.querySelector('h3');
            if (valueEl) {
                valueEl.style.transition = 'color 0.3s ease';
                valueEl.style.color = '#ff9800';
                valueEl.innerHTML = data.expenses_total;
                setTimeout(() => valueEl.style.color = '#ff6b00', 300);
            }
        }
        if (text.includes('Gewinn') && text.includes('Netto')) {
            const valueEl = card.querySelector('h3');
            if (valueEl) {
                valueEl.style.transition = 'color 0.3s ease';
                valueEl.innerHTML = data.profit_net;
            }
        }
    });
}

// Show AJAX Notice
function showAjaxNotice(message, type) {
    const notice = document.createElement('div');
    notice.className = 'notice notice-' + type + ' is-dismissible';
    notice.innerHTML = '<p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
    notice.style.cssText = 'animation: slideIn 0.3s ease; margin-bottom: 20px;';
    
    // Insert at top of page
    const mainContent = document.querySelector('[style*="max-width"]') || document.body;
    mainContent.insertBefore(notice, mainContent.firstChild);
    
    // Auto-remove after 4s
    setTimeout(() => {
        notice.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notice.remove(), 300);
    }, 4000);
    
    // Dismiss button
    notice.querySelector('.notice-dismiss').addEventListener('click', () => {
        notice.remove();
    });
}

// Add CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateY(0); opacity: 1; }
        to { transform: translateY(-20px); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
