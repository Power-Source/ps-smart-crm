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

// Get current year and month
$current_year = (int) ($_GET['year'] ?? date('Y'));
$current_month = str_pad((int) ($_GET['month'] ?? date('m')), 2, '0', STR_PAD_LEFT);
$period_str = $current_year . '-' . $current_month;

// Get business settings
$is_kleinunternehmer = WPsCRM_is_kleinunternehmer();
$tax_rates = WPsCRM_get_tax_rates();

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

// Get revenue data for current month
$revenue_data = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(CASE WHEN pagato = 1 THEN totale_imponibile ELSE 0 END), 0) as paid_net,
            COALESCE(SUM(CASE WHEN pagato = 1 THEN totale_imposta ELSE 0 END), 0) as paid_tax,
            COALESCE(SUM(CASE WHEN pagato = 1 THEN totale ELSE 0 END), 0) as paid_total,
            COALESCE(SUM(CASE WHEN pagato = 0 THEN totale_imponibile ELSE 0 END), 0) as unpaid_net,
            COALESCE(SUM(CASE WHEN pagato = 0 THEN totale_imposta ELSE 0 END), 0) as unpaid_tax,
            COALESCE(SUM(CASE WHEN pagato = 0 THEN totale ELSE 0 END), 0) as unpaid_total
         FROM {$d_table}
         WHERE tipo = 2 AND DATE_FORMAT(data, '%Y-%m') = %s",
        $period_str
    )
);

// Get expenses for current month
$expenses_data = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(imponibile), 0) as total_net,
            COALESCE(SUM(imposta), 0) as total_tax,
            COALESCE(SUM(totale), 0) as total
         FROM " . WPsCRM_TABLE . "expenses
         WHERE DATE_FORMAT(data, '%Y-%m') = %s",
        $period_str
    )
);

// Get manual incomes for current month
$incomes_data = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COUNT(*) as count,
            COALESCE(SUM(imponibile), 0) as total_net,
            COALESCE(SUM(imposta), 0) as total_tax,
            COALESCE(SUM(totale), 0) as total
         FROM {$i_table}
         WHERE DATE_FORMAT(data, '%Y-%m') = %s",
        $period_str
    )
);

// Get detailed expenses list
$expenses_list = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM " . WPsCRM_TABLE . "expenses
         WHERE DATE_FORMAT(data, '%Y-%m') = %s
         ORDER BY data DESC",
        $period_str
    )
);

// Get manual incomes list
$incomes_list = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$i_table}
         WHERE DATE_FORMAT(data, '%Y-%m') = %s
         ORDER BY data DESC",
        $period_str
    )
);

// Get invoices for detail view
$invoices_list = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT d.*, k.firmenname, k.name, k.nachname
         FROM {$d_table} d
         LEFT JOIN {$k_table} k ON d.fk_kunde = k.ID_kunde
         WHERE d.tipo = 2 AND DATE_FORMAT(d.data, '%Y-%m') = %s
         ORDER BY d.data DESC",
        $period_str
    )
);

// Calculate totals
$manual_income_net = $incomes_data->total_net ?? 0;
$manual_income_tax = $incomes_data->total_tax ?? 0;
$manual_income_total = $incomes_data->total ?? 0;

$total_revenue_net = ($revenue_data->paid_net ?? 0) + $manual_income_net;
$total_revenue_tax = ($revenue_data->paid_tax ?? 0) + $manual_income_tax;
$total_revenue = ($revenue_data->paid_total ?? 0) + $manual_income_total;
$total_expenses_net = $expenses_data->total_net ?? 0;
$total_expenses_tax = $expenses_data->total_tax ?? 0;
$total_expenses = $expenses_data->total ?? 0;

// Calculate profit/loss
$profit_loss_net = $total_revenue_net - $total_expenses_net;
$profit_loss_gross = $total_revenue - $total_expenses;

// Get available months
$available_months = $wpdb->get_results(
    "SELECT DISTINCT period FROM (
        SELECT DATE_FORMAT(data, '%Y-%m') as period FROM {$d_table} WHERE tipo = 2
        UNION
        SELECT DATE_FORMAT(data, '%Y-%m') as period FROM {$i_table}
        UNION
        SELECT DATE_FORMAT(data, '%Y-%m') as period FROM " . WPsCRM_TABLE . "expenses
    ) p
    ORDER BY period DESC
    LIMIT 36"
);

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

        <button class="button button-secondary" id="toggle_income_form" style="margin-top: 12px; width: 100%;">
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

        <button class="button button-secondary" id="toggle_expense_form" style="margin-top: 12px; width: 100%;">
            <?php _e('+ Neue Ausgabe', 'cpsmartcrm'); ?>
        </button>
    </div>
</div>

<!-- New Income Form (Hidden by default) -->
<div id="income_form_container" class="card" style="padding: 20px; margin-bottom: 20px; display: none;">
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
                    <option value="dienstleistung"><?php _e('Dienstleistung', 'cpsmartcrm'); ?></option>
                    <option value="produktverkauf"><?php _e('Produktverkauf', 'cpsmartcrm'); ?></option>
                    <option value="wartung"><?php _e('Wartung/Service', 'cpsmartcrm'); ?></option>
                    <option value="abo"><?php _e('Abos/Lizenzen', 'cpsmartcrm'); ?></option>
                    <option value="sonstiges"><?php _e('Sonstiges', 'cpsmartcrm'); ?></option>
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
            <button type="button" class="button button-secondary" id="cancel_income_form">
                <?php _e('Abbrechen', 'cpsmartcrm'); ?>
            </button>
        </div>
    </form>
</div>

<!-- New Expense Form (Hidden by default) -->
<div id="expense_form_container" class="card" style="padding: 20px; margin-bottom: 20px; display: none;">
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
                    <option value="material"><?php _e('Material/Waren', 'cpsmartcrm'); ?></option>
                    <option value="software"><?php _e('Software/Lizenzen', 'cpsmartcrm'); ?></option>
                    <option value="travel"><?php _e('Reisen/Mobilität', 'cpsmartcrm'); ?></option>
                    <option value="office"><?php _e('Büro/Miete', 'cpsmartcrm'); ?></option>
                    <option value="marketing"><?php _e('Marketing', 'cpsmartcrm'); ?></option>
                    <option value="other"><?php _e('Sonstiges', 'cpsmartcrm'); ?></option>
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
            <button type="button" class="button button-secondary" id="cancel_expense_form">
                <?php _e('Abbrechen', 'cpsmartcrm'); ?>
            </button>
        </div>
    </form>
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
// Toggle expense form
document.getElementById('toggle_expense_form').addEventListener('click', function() {
    const el = document.getElementById('expense_form_container');
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
});

document.getElementById('cancel_expense_form').addEventListener('click', function() {
    document.getElementById('expense_form_container').style.display = 'none';
});

// Toggle income form
document.getElementById('toggle_income_form').addEventListener('click', function() {
    const el = document.getElementById('income_form_container');
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
});

document.getElementById('cancel_income_form').addEventListener('click', function() {
    document.getElementById('income_form_container').style.display = 'none';
});
</script>
