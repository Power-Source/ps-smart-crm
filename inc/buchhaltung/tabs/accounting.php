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
