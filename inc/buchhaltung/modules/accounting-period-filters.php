<?php
/**
 * Modul: Intelligente Zeitraum-Filter für Buchhaltung
 * 
 * Bietet flexible Periodenfiltration mit vordefinierten Optionen,
 * Datepickern und Vergleichsfunktionalität
 */

if (!defined('ABSPATH')) exit;

/**
 * Gibt verfügbare Zeitraum-Voreinstellungen zurück
 * 
 * @return array Array mit Schlüssel: Optionscode, Wert: Label
 */
function wpscrm_get_accounting_period_presets() {
    $today = new DateTime();
    
    return array(
        'this_week' => array(
            'label' => __('Diese Woche', 'cpsmartcrm'),
            'icon' => '📅',
        ),
        'this_month' => array(
            'label' => __('Dieser Monat', 'cpsmartcrm'),
            'icon' => '📗',
        ),
        'last_month' => array(
            'label' => __('Letzter Monat', 'cpsmartcrm'),
            'icon' => '📙',
        ),
        'this_quarter' => array(
            'label' => __('Dieses Quartal', 'cpsmartcrm'),
            'icon' => '📊',
        ),
        'this_year' => array(
            'label' => __('Dieses Jahr', 'cpsmartcrm'),
            'icon' => '📈',
        ),
        'last_year' => array(
            'label' => __('Letztes Jahr', 'cpsmartcrm'),
            'icon' => '📉',
        ),
        'custom' => array(
            'label' => __('Benutzerdef. Zeitraum', 'cpsmartcrm'),
            'icon' => '🖱️',
        ),
    );
}

/**
 * Rechnet einen Zeitraum-Code in Start- und Enddatum um
 * 
 * @param string $preset Preset-Code
 * @param string $custom_from Optional: Benutzerdef. Startdatum (Y-m-d)
 * @param string $custom_to Optional: Benutzerdef. Enddatum (Y-m-d)
 * 
 * @return array ['from' => Datum, 'to' => Datum, 'label' => Beschreibung]
 */
function wpscrm_calculate_accounting_period($preset, $custom_from = null, $custom_to = null) {
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $from = clone $today;
    $to = clone $today;
    $label = '';
    
    switch ($preset) {
        case 'this_week':
            // Montag dieser Woche bis heute
            $week_start = clone $today;
            $week_start->modify('Monday this week');
            $from = $week_start;
            $to = $today;
            $label = sprintf(__('Woche vom %s bis %s', 'cpsmartcrm'), $from->format('d.m.Y'), $to->format('d.m.Y'));
            break;
            
        case 'this_month':
            // 1. bis heute diesen Monat
            $month_start = clone $today;
            $month_start->modify('first day of this month');
            $from = $month_start;
            $to = $today;
            $label = $from->format('F Y');
            break;
            
        case 'last_month':
            // Ganzer letzter Monat
            $last_month = clone $today;
            $last_month->modify('first day of last month');
            $last_month_end = clone $last_month;
            $last_month_end->modify('last day of this month');
            $from = $last_month;
            $to = $last_month_end;
            $label = $from->format('F Y');
            break;
            
        case 'this_quarter':
            // Dieses Quartal
            $month = (int)$today->format('m');
            $quarter_start_month = (floor(($month - 1) / 3) * 3) + 1;
            $quarter_start = clone $today;
            $quarter_start->setDate($today->format('Y'), $quarter_start_month, 1);
            $from = $quarter_start;
            $to = $today;
            $quarter_num = ceil($month / 3);
            $label = sprintf(__('Q%d %s', 'cpsmartcrm'), $quarter_num, $today->format('Y'));
            break;
            
        case 'this_year':
            // 1. Jan bis heute dieses Jahr
            $year_start = clone $today;
            $year_start->setDate($today->format('Y'), 1, 1);
            $from = $year_start;
            $to = $today;
            $label = $today->format('Y');
            break;
            
        case 'last_year':
            // Ganzes letztes Jahr
            $last_year = clone $today;
            $last_year->setDate($today->format('Y') - 1, 1, 1);
            $last_year_end = clone $last_year;
            $last_year_end->setDate($today->format('Y') - 1, 12, 31);
            $from = $last_year;
            $to = $last_year_end;
            $label = $last_year->format('Y');
            break;
            
        case 'custom':
            // Benutzerdefiniert
            if ($custom_from && $custom_to) {
                try {
                    $from = DateTime::createFromFormat('Y-m-d', $custom_from);
                    $to = DateTime::createFromFormat('Y-m-d', $custom_to);
                    if (!$from || !$to) {
                        throw new Exception('Invalid date');
                    }
                    $label = sprintf(__('%s bis %s', 'cpsmartcrm'), $from->format('d.m.Y'), $to->format('d.m.Y'));
                } catch (Exception $e) {
                    // Fallback auf diesen Monat bei ungültigem Datum
                    $from->modify('first day of this month');
                    $to = clone $today;
                    $label = $from->format('F Y');
                }
            }
            break;
            
        default:
            // Fallback: Dieser Monat
            $from->modify('first day of this month');
            $to = clone $today;
            $label = $from->format('F Y');
    }
    
    return array(
        'from' => $from->format('Y-m-d'),
        'to' => $to->format('Y-m-d'),
        'label' => $label,
        'from_obj' => $from,
        'to_obj' => $to,
    );
}

/**
 * Gibt die Vergleichsperiode basierend auf der aktuellen Periode
 * 
 * @param string $preset Aktueller Zeitraum-Code
 * @param string $compare_type Vergleichstyp: 'previous', 'year_ago', etc.
 * 
 * @return array wie wpscrm_calculate_accounting_period()
 */
function wpscrm_calculate_comparison_period($preset, $compare_type = 'previous') {
    $current = wpscrm_calculate_accounting_period($preset);
    $from = DateTime::createFromFormat('Y-m-d', $current['from']);
    $to = DateTime::createFromFormat('Y-m-d', $current['to']);
    
    $label = '';
    
    if ($compare_type === 'previous') {
        // Gleiche Duration, aber einen Schritt vorher
        $duration = $from->diff($to)->days;
        $new_to = clone $from;
        $new_to->modify('-1 day');
        $new_from = clone $new_to;
        $new_from->modify("-{$duration} days");
        
        $label = sprintf(__('Vergleich: %s bis %s', 'cpsmartcrm'), $new_from->format('d.m.Y'), $new_to->format('d.m.Y'));
    } elseif ($compare_type === 'year_ago') {
        // Gleichzeitraum im letzten Jahr
        $new_from = clone $from;
        $new_from->modify('-1 year');
        $new_to = clone $to;
        $new_to->modify('-1 year');
        
        $label = sprintf(__('Vgl. Vorjahr: %s bis %s', 'cpsmartcrm'), $new_from->format('d.m.Y'), $new_to->format('d.m.Y'));
    }
    
    return array(
        'from' => $new_from->format('Y-m-d'),
        'to' => $new_to->format('Y-m-d'),
        'label' => $label,
        'from_obj' => $new_from,
        'to_obj' => $new_to,
    );
}

/**
 * Holt Buchhaltungsdaten für einen Zeitraum
 * 
 * @param string $from Startdatum (Y-m-d)
 * @param string $to Enddatum (Y-m-d)
 * @param int|null $user_id Optional: Nur Daten des Benutzers
 * 
 * @return array 
 */
function wpscrm_get_accounting_period_data($from, $to, $user_id = null) {
    global $wpdb;
    
    $d_table = WPsCRM_TABLE . 'documenti';
    $k_table = WPsCRM_TABLE . 'kunde';
    $i_table = WPsCRM_TABLE . 'incomes';
    $e_table = WPsCRM_TABLE . 'expenses';
    
    $params = array();
    $owner_filter = '';
    $owner_params = array();
    
    if ($user_id) {
        $owner_filter = " AND (created_by = %d OR fk_utenti_age = %d)";
        $owner_params = array($user_id, $user_id);
    }
    
    // Einnahmen aus Rechnungen
    $query_invoices = "SELECT 
        COUNT(*) as count,
        COALESCE(SUM(totale_imponibile), 0) as net,
        COALESCE(SUM(totale_imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as gross
    FROM {$d_table}
    WHERE tipo = 2 AND pagato = 1 AND data BETWEEN %s AND %s {$owner_filter}";
    
    $invoice_params = array_merge(array($from, $to), $owner_params);
    $invoices = $wpdb->get_row($wpdb->prepare($query_invoices, ...$invoice_params));
    
    // Manuelle Einnahmen
    $query_manual_income = "SELECT 
        COUNT(*) as count,
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as gross
    FROM {$i_table}
    WHERE data BETWEEN %s AND %s {$owner_filter}";
    
    $manual_income_params = array_merge(array($from, $to), $owner_params);
    $manual_income = $wpdb->get_row($wpdb->prepare($query_manual_income, ...$manual_income_params));
    
    // Ausgaben
    $query_expenses = "SELECT 
        COUNT(*) as count,
        COALESCE(SUM(imponibile), 0) as net,
        COALESCE(SUM(imposta), 0) as tax,
        COALESCE(SUM(totale), 0) as gross
    FROM {$e_table}
    WHERE data BETWEEN %s AND %s {$owner_filter}";
    
    $expense_params = array_merge(array($from, $to), $owner_params);
    $expenses = $wpdb->get_row($wpdb->prepare($query_expenses, ...$expense_params));
    
    // Aggregation
    $total_income_gross = ((float)$invoices->gross ?? 0) + ((float)$manual_income->gross ?? 0);
    $total_income_net = ((float)$invoices->net ?? 0) + ((float)$manual_income->net ?? 0);
    $total_income_tax = ((float)$invoices->tax ?? 0) + ((float)$manual_income->tax ?? 0);
    
    $total_expenses_gross = ((float)$expenses->gross ?? 0);
    $total_expenses_net = ((float)$expenses->net ?? 0);
    $total_expenses_tax = ((float)$expenses->tax ?? 0);
    
    $profit_net = $total_income_net - $total_expenses_net;
    $profit_gross = $total_income_gross - $total_expenses_gross;
    
    return array(
        'from' => $from,
        'to' => $to,
        'income' => array(
            'invoices_count' => (int)$invoices->count,
            'invoices_net' => (float)$invoices->net,
            'invoices_tax' => (float)$invoices->tax,
            'invoices_gross' => (float)$invoices->gross,
            'manual_count' => (int)$manual_income->count,
            'manual_net' => (float)$manual_income->net,
            'manual_tax' => (float)$manual_income->tax,
            'manual_gross' => (float)$manual_income->gross,
            'total_count' => ((int)$invoices->count ?? 0) + ((int)$manual_income->count ?? 0),
            'total_net' => $total_income_net,
            'total_tax' => $total_income_tax,
            'total_gross' => $total_income_gross,
        ),
        'expenses' => array(
            'count' => (int)$expenses->count,
            'net' => (float)$expenses->net,
            'tax' => (float)$expenses->tax,
            'gross' => (float)$expenses->gross,
        ),
        'summary' => array(
            'profit_net' => $profit_net,
            'profit_gross' => $profit_gross,
        ),
    );
}

/**
 * Vergleicht zwei Perioden und gibt prozentuale Änderungen
 * 
 * @param array $current Periode-Daten (aus wpscrm_get_accounting_period_data)
 * @param array $comparison Vergleichsperiode-Daten
 * 
 * @return array ['income_change' => %, 'expenses_change' => %, 'profit_change' => %, ...]
 */
function wpscrm_compare_accounting_periods($current, $comparison) {
    $result = array(
        'current' => $current,
        'comparison' => $comparison,
        'changes' => array(),
    );
    
    // Prozentuale Veränderungen berechnen
    if ($comparison['income']['total_gross'] > 0) {
        $income_change = (($current['income']['total_gross'] - $comparison['income']['total_gross']) / $comparison['income']['total_gross']) * 100;
    } else {
        $income_change = $current['income']['total_gross'] > 0 ? 100 : 0;
    }
    
    if ($comparison['expenses']['gross'] > 0) {
        $expense_change = (($current['expenses']['gross'] - $comparison['expenses']['gross']) / $comparison['expenses']['gross']) * 100;
    } else {
        $expense_change = $current['expenses']['gross'] > 0 ? 100 : 0;
    }
    
    if ($comparison['summary']['profit_gross'] != 0) {
        $profit_change = (($current['summary']['profit_gross'] - $comparison['summary']['profit_gross']) / abs($comparison['summary']['profit_gross'])) * 100;
    } else {
        $profit_change = $current['summary']['profit_gross'] != 0 ? 100 : 0;
    }
    
    $result['changes'] = array(
        'income' => array(
            'percent' => $income_change,
            'absolute' => $current['income']['total_gross'] - $comparison['income']['total_gross'],
            'direction' => $income_change >= 0 ? 'up' : 'down',
        ),
        'expenses' => array(
            'percent' => $expense_change,
            'absolute' => $current['expenses']['gross'] - $comparison['expenses']['gross'],
            'direction' => $expense_change <= 0 ? 'down' : 'up', // Down ist gut bei Ausgaben
        ),
        'profit' => array(
            'percent' => $profit_change,
            'absolute' => $current['summary']['profit_gross'] - $comparison['summary']['profit_gross'],
            'direction' => $profit_change >= 0 ? 'up' : 'down',
        ),
    );
    
    return $result;
}

/**
 * Formatiert einen Prozentänderungs-HTML-String
 * 
 * @param float $percent Prozent
 * @param string $direction 'up' oder 'down'
 * 
 * @return string HTML
 */
function wpscrm_format_percentage_change($percent, $direction) {
    $color = ($direction === 'up') ? '#ff6b00' : '#46b450';
    $icon = ($direction === 'up') ? '↑' : '↓';
    $sign = ($percent >= 0) ? '+' : '';
    
    return sprintf(
        '<span style="color: %s; font-weight: bold;">%s%s%%</span>',
        esc_attr($color),
        esc_html($sign),
        esc_html(number_format($percent, 1, ',', '.'))
    );
}
?>
