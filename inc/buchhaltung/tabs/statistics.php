<?php
/**
 * Buchhaltungs-Statistik/Reports Tab
 * 
 * Umsatzanalyse, Trends, Durchschnittsmetriken
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

global $wpdb;

$d_table = WPsCRM_TABLE . 'documenti';
$c_table = WPsCRM_TABLE . 'kunde';
$i_table = WPsCRM_TABLE . 'incomes';
$e_table = WPsCRM_TABLE . 'expenses';
$invoice_type = 2;
$acc_options = get_option('CRM_accounting_settings', array());

// Parse filter parameters
$year = (int) ($_GET['year'] ?? date('Y'));
$month = sanitize_text_field($_GET['month'] ?? '');
$tax_rate_filter = sanitize_text_field($_GET['tax_rate'] ?? '');
$customer_filter = (int) ($_GET['customer'] ?? 0);

// Date range
$year_start = $year . '-01-01';
$year_end = $year . '-12-31';

// Build WHERE clause for invoices
$where_parts = array("tipo = %d", "pagato = %d"); // Only paid invoices
$where_values = array($invoice_type, 1);

if (!empty($month)) {
    $where_parts[] = "DATE_FORMAT(data, '%Y-%m') = %s";
    $where_values[] = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
} else {
    $where_parts[] = "data >= %s AND data <= %s";
    $where_values[] = $year_start;
    $where_values[] = $year_end;
}

if ($customer_filter > 0) {
    $where_parts[] = "fk_kunde = %d";
    $where_values[] = $customer_filter;
}

$where_clause = implode(' AND ', $where_parts);

// Get yearly summary - kombiniere Rechnungen + manuelle Einnahmen/Ausgaben
// Rechnungen
$invoice_summary = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as invoice_count,
            COALESCE(SUM(totale_imponibile), 0) as total_net,
            COALESCE(SUM(totale_imposta), 0) as total_tax,
            COALESCE(SUM(totale), 0) as total,
            COALESCE(AVG(totale), 0) as avg_invoice_value
         FROM {$d_table}
         WHERE {$where_clause}",
        ...$where_values
    )
);

// Manuelle Einnahmen
$income_where = "data >= %s AND data <= %s";
$income_where_values = array($year_start, $year_end);
if (!empty($month)) {
    $income_where = "DATE_FORMAT(data, '%Y-%m') = %s";
    $income_where_values = array($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT));
}

$income_summary = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(imponibile), 0) as total,
            COALESCE(SUM(imposta), 0) as tax
         FROM {$i_table}
         WHERE {$income_where}",
        ...$income_where_values
    )
);

// Manuelle Ausgaben
$expense_summary = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(imponibile), 0) as total,
            COALESCE(SUM(imposta), 0) as tax
         FROM {$e_table}
         WHERE {$income_where}",
        ...$income_where_values
    )
);

// Kombiniere alles zu einer Summary
$total_transactions = ($invoice_summary->invoice_count ?? 0) + ($income_summary->count ?? 0) + ($expense_summary->count ?? 0);
$total_income_net = ($invoice_summary->total_net ?? 0) + ($income_summary->total ?? 0);
$total_expense_net = ($expense_summary->total ?? 0);
$total_income_tax = ($invoice_summary->total_tax ?? 0) + ($income_summary->tax ?? 0);
$total_expense_tax = ($expense_summary->tax ?? 0);

$yearly_summary = (object) array(
    'invoice_count' => $total_transactions,
    'total_net' => $total_income_net - $total_expense_net,
    'total_tax' => $total_income_tax - $total_expense_tax,  // Einnahmen-USt minus Ausgaben-USt
    'total' => ($total_income_net + $total_income_tax) - ($total_expense_net + $total_expense_tax),
    'avg_invoice_value' => $total_transactions > 0 ? (($total_income_net - $total_expense_net) / $total_transactions) : 0
);

// Ensure we have defaults if query returns nothing
if (!$yearly_summary) {
    $yearly_summary = (object) array(
        'invoice_count' => 0,
        'total_net' => 0,
        'total_tax' => 0,
        'total' => 0,
        'avg_invoice_value' => 0
    );
}

// Monthly data for chart
$monthly_data = $wpdb->get_results(
    "SELECT 
        DATE_FORMAT(data, '%m') as month,
        DATE_FORMAT(data, '%b') as month_label,
        COUNT(*) as count,
        COALESCE(SUM(totale_imponibile), 0) as net,
        COALESCE(SUM(totale_imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as total
     FROM {$d_table}
     WHERE tipo = $invoice_type AND pagato = 1 AND data >= '$year_start' AND data <= '$year_end'
     GROUP BY DATE_FORMAT(data, '%Y-%m')
     ORDER BY month ASC"
);

// Einnahmen/Ausgaben Chart-Daten (Netto) für gewähltes Jahr
$chart_periods = array();
for ($m = 1; $m <= 12; $m++) {
    $chart_periods[] = sprintf('%04d-%02d', $year, $m);
}

$income_net_by_period = array_fill_keys($chart_periods, 0);
$expense_net_by_period = array_fill_keys($chart_periods, 0);

$invoice_rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE_FORMAT(data, '%Y-%m') as period, COALESCE(SUM(totale_imponibile), 0) as net
         FROM {$d_table}
         WHERE tipo = %d AND pagato = 1 AND data >= %s AND data <= %s
         GROUP BY DATE_FORMAT(data, '%Y-%m')",
        $invoice_type, $year_start, $year_end
    )
);
foreach ($invoice_rows as $row) {
    if (isset($income_net_by_period[$row->period])) {
        $income_net_by_period[$row->period] += (float) $row->net;
    }
}

$manual_income_rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE_FORMAT(data, '%Y-%m') as period, COALESCE(SUM(imponibile), 0) as net
         FROM {$i_table}
         WHERE data >= %s AND data <= %s
         GROUP BY DATE_FORMAT(data, '%Y-%m')",
        $year_start, $year_end
    )
);
foreach ($manual_income_rows as $row) {
    if (isset($income_net_by_period[$row->period])) {
        $income_net_by_period[$row->period] += (float) $row->net;
    }
}

$expense_rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE_FORMAT(data, '%Y-%m') as period, COALESCE(SUM(imponibile), 0) as net
         FROM {$e_table}
         WHERE data >= %s AND data <= %s
         GROUP BY DATE_FORMAT(data, '%Y-%m')",
        $year_start, $year_end
    )
);
foreach ($expense_rows as $row) {
    if (isset($expense_net_by_period[$row->period])) {
        $expense_net_by_period[$row->period] += (float) $row->net;
    }
}

$chart_labels = array();
$chart_income_values = array();
$chart_expense_values = array();
foreach ($chart_periods as $p) {
    $dt = DateTime::createFromFormat('Y-m', $p);
    $chart_labels[] = $dt ? $dt->format('M') : $p;
    $chart_income_values[] = round($income_net_by_period[$p] ?? 0, 2);
    $chart_expense_values[] = round($expense_net_by_period[$p] ?? 0, 2);
}

$chart_js_url = plugins_url('js/chart.umd.min.js', dirname(__FILE__, 4) . '/ps-smart-crm.php');
$chart_currency = $acc_options['currency'] ?? 'EUR';

// Get top customers
$top_customers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT 
            c.ID_kunde,
            c.firmenname,
            c.name,
            c.nachname,
            COUNT(d.id) as invoice_count,
            COALESCE(SUM(d.totale), 0) as total
         FROM {$d_table} d
         LEFT JOIN {$c_table} c ON d.fk_kunde = c.ID_kunde
         WHERE d.tipo = %d AND d.pagato = 1 AND d.data >= %s AND d.data <= %s
         GROUP BY d.fk_kunde
         ORDER BY total DESC
         LIMIT 10",
        $invoice_type, $year_start, $year_end
    )
);

// Get tax breakdown
$tax_breakdown = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT 
            ROUND(totale_imposta / NULLIF(totale_imponibile, 0) * 100, 2) as tax_percent,
            COUNT(*) as count,
            COALESCE(SUM(totale_imponibile), 0) as net,
            COALESCE(SUM(totale_imposta), 0) as tax
         FROM {$d_table}
         WHERE tipo = %d AND pagato = 1 AND data >= %s AND data <= %s
         GROUP BY ROUND(totale_imposta / NULLIF(totale_imponibile, 0) * 100, 2)
         ORDER BY tax_percent DESC",
        $invoice_type, $year_start, $year_end
    )
);

// Available years for filter - sammeln aus ALLEN Tabellen (Rechnungen, Einnahmen, Ausgaben)
$years_from_invoices = $wpdb->get_col("SELECT DISTINCT YEAR(data) as year FROM {$d_table} WHERE tipo = {$invoice_type} ORDER BY year DESC");
$years_from_income = $wpdb->get_col("SELECT DISTINCT YEAR(data) as year FROM {$i_table} ORDER BY year DESC");
$years_from_expenses = $wpdb->get_col("SELECT DISTINCT YEAR(data) as year FROM {$e_table} ORDER BY year DESC");

// Zusammenfassen und deduplizieren
$all_years = array_unique(array_merge(
    (array)$years_from_invoices,
    (array)$years_from_income,
    (array)$years_from_expenses
));
rsort($all_years); // Nach absteigend sortieren

// Fallback: wenn gar keine Jahre, zumindest aktuelles Jahr anzeigen
$available_years = !empty($all_years) ? $all_years : array(date('Y'));

// Available customers for filter
$available_customers = $wpdb->get_results(
    "SELECT ID_kunde as id, 
            IF(firmenname != '', firmenname, CONCAT(name, ' ', nachname)) as name
     FROM {$c_table}
     WHERE eliminato = 0
     ORDER BY name ASC"
);

// DEBUG: Zeige an ob Daten existieren
$test_invoices = $wpdb->get_var("SELECT COUNT(*) FROM {$d_table} WHERE tipo = 2");
$test_income = $wpdb->get_var("SELECT COUNT(*) FROM {$i_table}");
$test_expenses = $wpdb->get_var("SELECT COUNT(*) FROM {$e_table}");
$has_any_data = ($test_invoices > 0 || $test_income > 0 || $test_expenses > 0);

?>


<h2><?php _e('Statistik & Reports', 'cpsmartcrm'); ?></h2>

<?php if (!$has_any_data): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 16px; border-radius: 4px; margin-bottom: 20px;">
        <strong style="color: #856404;">⚠️ <?php _e('Keine Buchhaltungsdaten', 'cpsmartcrm'); ?></strong><br>
        <p style="margin: 8px 0 0 0; color: #856404; font-size: 13px;">
            <?php _e('Es wurden noch keine Rechnungen, Einnahmen oder Ausgaben erfasst. Gehen Sie zum Tab "Belege" um Transaktionen zu erstellen.', 'cpsmartcrm'); ?>
        </p>
    </div>
<?php endif; ?>

<form method="get" action="" style="background: #f8f8f8; padding: 16px; border-radius: 4px; margin-bottom: 20px;">
    <input type="hidden" name="page" value="smart-crm" />
    <input type="hidden" name="p" value="buchhaltung/index.php" />
    <input type="hidden" name="accounting_tab" value="statistics" />

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
        <div>
            <label for="year"><strong><?php _e('Jahr:', 'cpsmartcrm'); ?></strong></label>
            <select id="year" name="year" class="widefat">
                <?php foreach ($available_years as $available_year) : ?>
                    <option value="<?php echo esc_attr($available_year); ?>" <?php selected($year, $available_year); ?>>
                        <?php echo esc_html($available_year); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="month"><strong><?php _e('Monat:', 'cpsmartcrm'); ?></strong></label>
            <select id="month" name="month" class="widefat">
                <option value=""><?php _e('Alle', 'cpsmartcrm'); ?></option>
                <option value="01" <?php selected($month, '01'); ?>>Januar</option>
                <option value="02" <?php selected($month, '02'); ?>>Februar</option>
                <option value="03" <?php selected($month, '03'); ?>>März</option>
                <option value="04" <?php selected($month, '04'); ?>>April</option>
                <option value="05" <?php selected($month, '05'); ?>>Mai</option>
                <option value="06" <?php selected($month, '06'); ?>>Juni</option>
                <option value="07" <?php selected($month, '07'); ?>>Juli</option>
                <option value="08" <?php selected($month, '08'); ?>>August</option>
                <option value="09" <?php selected($month, '09'); ?>>September</option>
                <option value="10" <?php selected($month, '10'); ?>>Oktober</option>
                <option value="11" <?php selected($month, '11'); ?>>November</option>
                <option value="12" <?php selected($month, '12'); ?>>Dezember</option>
            </select>
        </div>

        <div>
            <label for="customer_filter"><strong><?php _e('Kunde:', 'cpsmartcrm'); ?></strong></label>
            <select id="customer_filter" name="customer" class="widefat">
                <option value="0"><?php _e('Alle', 'cpsmartcrm'); ?></option>
                <?php foreach ($available_customers as $cust) : ?>
                    <option value="<?php echo esc_attr($cust->id); ?>" <?php selected($customer_filter, $cust->id); ?>>
                        <?php echo esc_html($cust->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: flex-end;">
            <button type="submit" class="button button-primary" style="width: 100%;">
                <?php _e('Filtern', 'cpsmartcrm'); ?>
            </button>
        </div>
    </div>
</form>

<!-- Key Metrics -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px;">
    <div class="card" style="padding: 16px; text-align: center;">
        <h3 style="margin-top: 0; font-size: 28px; color: #0073aa;">
            <?php echo esc_html($yearly_summary->invoice_count ?? 0); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Rechnungen', 'cpsmartcrm'); ?>
        </p>
    </div>

    <div class="card" style="padding: 16px; text-align: center;">
        <h3 style="margin-top: 0; font-size: 24px; color: #0073aa;">
            <?php echo esc_html(number_format_i18n((float)$yearly_summary->total_net, 2)); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Netto-Umsatz', 'cpsmartcrm'); ?>
        </p>
    </div>

    <div class="card" style="padding: 16px; text-align: center;">
        <h3 style="margin-top: 0; font-size: 24px; color: #ff6b00;">
            <?php echo esc_html(number_format_i18n((float)$yearly_summary->total_tax, 2)); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Umsatzsteuer', 'cpsmartcrm'); ?>
        </p>
    </div>

    <div class="card" style="padding: 16px; text-align: center;">
        <h3 style="margin-top: 0; font-size: 24px; color: #0073aa;">
            <?php echo esc_html(number_format_i18n((float)($yearly_summary->avg_invoice_value ?? 0), 2)); ?>
        </h3>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php _e('Ø Rechnungsbetrag', 'cpsmartcrm'); ?>
        </p>
    </div>
</div>

<!-- Monthly Chart: Einnahmen vs. Ausgaben (Netto) -->
<div class="card" style="padding: 20px; margin-bottom: 20px;">
    <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">📈</span>
        <?php _e('Verlauf Einnahmen vs. Ausgaben (Netto)', 'cpsmartcrm'); ?>
    </h3>
    <div style="height: 320px;">
        <canvas id="stats_income_expense_chart"></canvas>
    </div>
    <small style="color: #666;">
        <?php _e('Zeigt monatliche Entwicklung für gewähltes Jahr', 'cpsmartcrm'); ?>
    </small>
</div>

<script src="<?php echo esc_url($chart_js_url); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('stats_income_expense_chart');
    if (!ctx || typeof Chart === 'undefined') return;

    const labels = <?php echo wp_json_encode($chart_labels); ?>;
    const incomeData = <?php echo wp_json_encode($chart_income_values); ?>;
    const expenseData = <?php echo wp_json_encode($chart_expense_values); ?>;
    const currency = '<?php echo esc_js($chart_currency); ?>';

    const formatMoney = (value) => {
        try {
            return new Intl.NumberFormat('de-DE', { style: 'currency', currency }).format(value);
        } catch (e) {
            return value;
        }
    };

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: '<?php echo esc_js(__('Einnahmen (netto)', 'cpsmartcrm')); ?>',
                    data: incomeData,
                    borderColor: 'rgb(0, 115, 170)',
                    backgroundColor: 'rgba(0, 115, 170, 0.16)',
                    tension: 0.25,
                    fill: true,
                    pointRadius: 3
                },
                {
                    label: '<?php echo esc_js(__('Ausgaben (netto)', 'cpsmartcrm')); ?>',
                    data: expenseData,
                    borderColor: 'rgb(221, 51, 51)',
                    backgroundColor: 'rgba(221, 51, 51, 0.16)',
                    tension: 0.25,
                    fill: true,
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) { return formatMoney(value); }
                    },
                    beginAtZero: true
                }
            },
            plugins: {
                legend: { display: true },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const val = ctx.parsed.y ?? 0;
                            return ctx.dataset.label + ': ' + formatMoney(val);
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Top Customers -->
<div class="card" style="padding: 20px; margin-bottom: 20px;">
    <h3><?php _e('Top 10 Kunden', 'cpsmartcrm'); ?></h3>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
        <thead>
            <tr style="background: #f0f0f0;">
                <th><?php _e('Kunde', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 100px;"><?php _e('Rechnungen', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 150px;"><?php _e('Umsatz', 'cpsmartcrm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($top_customers)) : ?>
                <?php foreach ($top_customers as $customer) : ?>
                    <tr>
                        <td>
                            <?php 
                            $cust_name = !empty($customer->firmenname) 
                                ? $customer->firmenname 
                                : $customer->name . ' ' . $customer->nachname;
                            echo esc_html($cust_name);
                            ?>
                        </td>
                        <td style="text-align: right;"><?php echo esc_html($customer->invoice_count); ?></td>
                        <td style="text-align: right;"><strong><?php echo esc_html(number_format_i18n((float)$customer->total, 2)); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="3" style="text-align: center; color: #999;"><?php _e('Keine Daten gefunden', 'cpsmartcrm'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Tax Breakdown -->
<div class="card" style="padding: 20px;">
    <h3><?php _e('Steuersätze Übersicht', 'cpsmartcrm'); ?></h3>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 12px;">
        <thead>
            <tr style="background: #f0f0f0;">
                <th style="width: 120px;"><?php _e('Steuersatz', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 100px;"><?php _e('Rechnungen', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 150px;"><?php _e('Netto-Umsatz', 'cpsmartcrm'); ?></th>
                <th style="text-align: right; width: 150px;"><?php _e('Umsatzsteuer', 'cpsmartcrm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tax_breakdown)) : ?>
                <?php foreach ($tax_breakdown as $tax) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($tax->tax_percent); ?>%</strong></td>
                        <td style="text-align: right;"><?php echo esc_html($tax->count); ?></td>
                        <td style="text-align: right;"><?php echo esc_html(number_format_i18n((float)$tax->net, 2)); ?></td>
                        <td style="text-align: right;"><strong><?php echo esc_html(number_format_i18n((float)$tax->tax, 2)); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="4" style="text-align: center; color: #999;">><?php _e('Keine Daten gefunden', 'cpsmartcrm'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
