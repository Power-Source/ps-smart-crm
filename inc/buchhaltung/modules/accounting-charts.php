<?php
/**
 * Modul: Visualisierungen und Charts für Buchhaltung
 * 
 * Bietet Charting-Funktionen mit Chart.js für Trends,
 * Vergleiche und historische Entwicklungen
 */

if (!defined('ABSPATH')) exit;

/**
 * Holt die letzten 12 Monate mit Einnahmen/Ausgaben
 * 
 * @param int|null $user_id Optional: Nur Daten des Benutzers
 * 
 * @return array Monatliche Daten für die letzten 12 Monate
 */
function wpscrm_get_last_12_months_data($user_id = null) {
    global $wpdb;
    
    $d_table = WPsCRM_TABLE . 'documenti';
    $i_table = WPsCRM_TABLE . 'incomes';
    $e_table = WPsCRM_TABLE . 'expenses';
    
    $months = array();
    $today = new DateTime();
    
    // Generiere 12 Monate rückwärts
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $today;
        $date->modify("-{$i} months");
        $date->setDate($date->format('Y'), $date->format('m'), 1);
        
        $year = $date->format('Y');
        $month = $date->format('m');
        $month_key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        $month_label = $date->format('M');
        
        // Einnahmen diesen Monat
        $owner_filter = $user_id ? " AND (created_by = %d OR fk_utenti_age = %d)" : "";
        $owner_params = $user_id ? array($user_id, $user_id) : array();
        
        $income_query = "SELECT COALESCE(SUM(totale), 0) as total FROM (
            SELECT totale FROM {$d_table} WHERE tipo = 2 AND pagato = 1 AND DATE_FORMAT(data, '%Y-%m') = %s {$owner_filter}
            UNION ALL
            SELECT totale FROM {$i_table} WHERE DATE_FORMAT(data, '%Y-%m') = %s " . ($user_id ? "AND created_by = %d" : "") . "
        ) combined";
        
        $income_params = array($month_key);
        if ($user_id) $income_params[] = $user_id;
        $income_params[] = $month_key;
        if ($user_id) $income_params[] = $user_id;
        
        $income = $wpdb->get_var($wpdb->prepare($income_query, ...$income_params));
        
        // Ausgaben diesen Monat
        $expense_query = "SELECT COALESCE(SUM(totale), 0) as total FROM {$e_table} 
                         WHERE DATE_FORMAT(data, '%Y-%m') = %s" . ($user_id ? " AND created_by = %d" : "");
        $expense_params = array($month_key);
        if ($user_id) $expense_params[] = $user_id;
        
        $expenses = $wpdb->get_var($wpdb->prepare($expense_query, ...$expense_params));
        
        $months[$month_key] = array(
            'label' => $month_label,
            'income' => (float)$income,
            'expenses' => (float)$expenses,
            'profit' => ((float)$income) - ((float)$expenses),
        );
    }
    
    return $months;
}

/**
 * Generiert Chart.js Config für Trend-Graph
 * 
 * @param array $data Daten (z.B. aus wpscrm_get_last_12_months_data)
 * @param string $type 'line' oder 'bar'
 * 
 * @return array Chart-Config für Chart.js
 */
function wpscrm_generate_trend_chart_config($data, $type = 'line') {
    $labels = array();
    $income_data = array();
    $expense_data = array();
    $profit_data = array();
    
    foreach ($data as $entry) {
        $labels[] = $entry['label'];
        $income_data[] = $entry['income'];
        $expense_data[] = $entry['expenses'];
        $profit_data[] = $entry['profit'];
    }
    
    return array(
        'type' => $type,
        'data' => array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => __('Einnahmen', 'cpsmartcrm'),
                    'data' => $income_data,
                    'borderColor' => 'rgb(75, 192, 75)',
                    'backgroundColor' => 'rgba(75, 192, 75, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                ),
                array(
                    'label' => __('Ausgaben', 'cpsmartcrm'),
                    'data' => $expense_data,
                    'borderColor' => 'rgb(255, 107, 0)',
                    'backgroundColor' => 'rgba(255, 107, 0, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                ),
                array(
                    'label' => __('Gewinn', 'cpsmartcrm'),
                    'data' => $profit_data,
                    'borderColor' => 'rgb(33, 150, 243)',
                    'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 2,
                ),
            ),
        ),
        'options' => array(
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => array(
                'legend' => array(
                    'position' => 'top',
                ),
                'title' => array(
                    'display' => false,
                ),
            ),
            'scales' => array(
                'y' => array(
                    'beginAtZero' => true,
                    'ticks' => array(
                        'callback' => 'function(value) { return value.toLocaleString("de-DE", {style: "currency", currency: "EUR"}); }',
                    ),
                ),
            ),
        ),
    );
}

/**
 * Generiert Chart.js Config für Kategorien-Verteilung
 * 
 * @param array $categories Array mit Kategorien und Werten
 * 
 * @return array Chart-Config für Doughnut-Chart
 */
function wpscrm_generate_category_chart_config($categories) {
    $labels = array();
    $values = array();
    $colors = array(
        'rgba(75, 192, 75, 0.7)',
        'rgba(255, 107, 0, 0.7)',
        'rgba(33, 150, 243, 0.7)',
        'rgba(156, 39, 176, 0.7)',
        'rgba(255, 193, 7, 0.7)',
        'rgba(76, 175, 80, 0.7)',
        'rgba(244, 67, 54, 0.7)',
        'rgba(63, 81, 181, 0.7)',
    );
    
    $color_idx = 0;
    foreach ($categories as $label => $value) {
        $labels[] = $label;
        $values[] = (float)$value;
        if (!isset($colors[$color_idx])) {
            $colors[$color_idx] = 'rgba(' . rand(50, 200) . ', ' . rand(50, 200) . ', ' . rand(50, 200) . ', 0.7)';
        }
        $color_idx++;
    }
    
    return array(
        'type' => 'doughnut',
        'data' => array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values)),
                    'borderColor' => '#fff',
                    'borderWidth' => 2,
                ),
            ),
        ),
        'options' => array(
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => array(
                'legend' => array(
                    'position' => 'right',
                ),
            ),
        ),
    );
}

/**
 * Holt Chart.js Library und initialisiert sie
 * 
 * @return void
 */
function wpscrm_enqueue_chart_js() {
    if (!wp_script_is('chart-js', 'registered')) {
        wp_register_script(
            'chart-js',
            WPsCRM_URL . 'js/chart.umd.min.js',
            array(),
            '3.9.1',
            true
        );
    }
    wp_enqueue_script('chart-js');
}

/**
 * Rendert einen Chart mit Chart.js als HTML Canvas
 * 
 * @param string $canvas_id ID des Canvas-Elements
 * @param array $config Chart-Konfiguration
 * 
 * @return string HTML inkl. Script
 */
function wpscrm_render_chart($canvas_id, $config) {
    wpscrm_enqueue_chart_js();
    
    $config_json = json_encode($config);
    
    return sprintf(
        '<canvas id="%s" style="max-height: 400px;"></canvas>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx%s = document.getElementById("%s").getContext("2d");
            const config%s = %s;
            // Callback-Funktionen für Formatter
            if (config%s.options.scales && config%s.options.scales.y && config%s.options.scales.y.ticks && config%s.options.scales.y.ticks.callback) {
                config%s.options.scales.y.ticks.callback = %s;
            }
            new Chart(ctx%s, config%s);
        });
        </script>',
        esc_attr($canvas_id),
        esc_attr($canvas_id), esc_attr($canvas_id),
        esc_attr($canvas_id), $config_json,
        esc_attr($canvas_id), esc_attr($canvas_id), esc_attr($canvas_id), esc_attr($canvas_id),
        esc_attr($canvas_id),
        'function(value) { return value.toLocaleString("de-DE", {style: "currency", currency: "EUR"}); }',
        esc_attr($canvas_id), esc_attr($canvas_id)
    );
}
?>
