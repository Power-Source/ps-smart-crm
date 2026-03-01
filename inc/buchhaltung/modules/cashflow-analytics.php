<?php
/**
 * Modul: Cashflow-Prognose und Analyse
 * 
 * Berechnet zukünftige Cashflows basierend auf
 * historischen Daten, Trends und offenen Abrechnungen
 */

if (!defined('ABSPATH')) exit;

/**
 * Prognostiziert Cashflow für die nächsten Monate
 * 
 * @param int $months_ahead Wie viele Monate in die Zukunft
 * @param int|null $user_id Optional: Nur Daten des Benutzers
 * 
 * @return array Monatliche Cashflow-Prognosen
 */
function wpscrm_forecast_cashflow($months_ahead = 3, $user_id = null) {
    global $wpdb;
    
    // Historische Daten sammeln (letzte 6 Monate als Basis)
    $historical = wpscrm_get_last_12_months_data($user_id);
    $recent_6_months = array_slice($historical, -6, 6);
    
    // Durchschnittliche monatliche Income/Expense berechnen
    $avg_income = 0;
    $avg_expenses = 0;
    $month_count = count($recent_6_months);
    
    if ($month_count > 0) {
        foreach ($recent_6_months as $month) {
            $avg_income += $month['income'];
            $avg_expenses += $month['expenses'];
        }
        $avg_income = $avg_income / $month_count;
        $avg_expenses = $avg_expenses / $month_count;
    }
    
    // Unbilled Revenue (Zeiterfassung) hinzufügen
    $unbilled_revenue = 0;
    if (function_exists('wpscrm_get_timetracking_summary')) {
        $today = new DateTime();
        $month_end = clone $today;
        $month_end->setDate($today->format('Y'), $today->format('m'), 1);
        $month_end->modify('last day of this month');
        
        $tt_summary = wpscrm_get_timetracking_summary($today->format('Y-m-01'), $month_end->format('Y-m-d'));
        
        foreach ($tt_summary as $agent) {
            $billing_info = function_exists('wpscrm_get_agent_billing_info')
                ? wpscrm_get_agent_billing_info($agent->user_id)
                : (object) array('hourly_rate' => 0, 'rate_type' => 'net');
            
            $hours = $agent->total_minutes / 60;
            $amount = ('gross' === $billing_info->rate_type)
                ? $hours * $billing_info->hourly_rate
                : $hours * $billing_info->hourly_rate * 1.19;
            
            $unbilled_revenue += $amount;
        }
    }
    
    // Ausstehende Abrechnungsentwürfe
    $pending_drafts = 0;
    $billing_table = WPsCRM_TABLE . 'billing_drafts';
    $pending_drafts = (float)$wpdb->get_var("SELECT COALESCE(SUM(amount_gross), 0) FROM $billing_table WHERE status = 'draft'");
    
    // Prognose generieren
    $forecast = array();
    $today = new DateTime();
    
    for ($i = 1; $i <= $months_ahead; $i++) {
        $date = clone $today;
        $date->setDate($date->format('Y'), $date->format('m'), 1);
        $date->modify("+{$i} months");
        
        $year = $date->format('Y');
        $month = $date->format('m');
        $month_key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Gewöhnlicher monatlicher Cashflow + Trend
        $trend_factor = 1.0; // Könnte später mit Trend berechnet werden
        
        $forecast[$month_key] = array(
            'label' => $date->format('M.y'),
            'income' => $avg_income * $trend_factor,
            'expenses' => $avg_expenses,
            'profit' => ($avg_income * $trend_factor) - $avg_expenses,
            'special_income' => $i === 1 ? $unbilled_revenue : 0,
            'special_events' => $i === 1 ? $pending_drafts : 0,
            'total_cash_in' => ($avg_income * $trend_factor) + ($i === 1 ? $unbilled_revenue + $pending_drafts : 0),
        );
    }
    
    return array(
        'forecast' => $forecast,
        'avg_monthly_income' => $avg_income,
        'avg_monthly_expenses' => $avg_expenses,
        'unbilled_revenue' => $unbilled_revenue,
        'pending_drafts' => $pending_drafts,
        'confidence' => $month_count >= 6 ? 'high' : ($month_count >= 3 ? 'medium' : 'low'),
    );
}

/**
 * Berechnet Risiko-Metriken für Cashflow
 * 
 * @param array $forecast Forecast aus wpscrm_forecast_cashflow()
 * 
 * @return array Risiko-Bewertungen
 */
function wpscrm_calculate_cashflow_risks($forecast) {
    $forecast_data = $forecast['forecast'];
    $avg_income = $forecast['avg_monthly_income'];
    $avg_expenses = $forecast['avg_monthly_expenses'];
    
    $risks = array(
        'runway_months' => 0, // Monate bis Geldreserven leer (vereinfacht)
        'critical_months' => array(), // Monate mit negativem Cashflow
        'confidence' => $forecast['confidence'],
    );
    
    foreach ($forecast_data as $month_key => $data) {
        if ($data['total_cash_in'] < $data['expenses']) {
            $risks['critical_months'][] = $month_key;
        }
    }
    
    return $risks;
}

/**
 * Generiert eine Cashflow-Übersicht als Text
 * 
 * @param array $forecast Forecast-Daten
 * 
 * @return string HTML-Zusammenfassung
 */
function wpscrm_format_cashflow_summary($forecast) {
    $summary = '<div style="background: #f5f5f5; padding: 16px; border-radius: 6px; font-size: 13px;">';
    
    $summary .= '<p><strong>' . esc_html__('Cashflow-Prognose Zusammenfassung', 'cpsmartcrm') . ':</strong></p>';
    
    $summary .= '<ul style="margin: 8px 0;">';
    $summary .= '<li>' . sprintf(
        esc_html__('⌀ Monatliche Einnahmen: %s', 'cpsmartcrm'),
        esc_html(WPsCRM_format_currency($forecast['avg_monthly_income']))
    ) . '</li>';
    $summary .= '<li>' . sprintf(
        esc_html__('⌀ Monatliche Ausgaben: %s', 'cpsmartcrm'),
        esc_html(WPsCRM_format_currency($forecast['avg_monthly_expenses']))
    ) . '</li>';
    $summary .= '<li>' . sprintf(
        esc_html__('⌀ Monatlicher Erwerbsüberschuss: %s', 'cpsmartcrm'),
        esc_html(WPsCRM_format_currency($forecast['avg_monthly_income'] - $forecast['avg_monthly_expenses']))
    ) . '</li>';
    
    if ($forecast['unbilled_revenue'] > 0) {
        $summary .= '<li style="color: #0073aa;"><strong>' . sprintf(
            esc_html__('Unbilled Revenue (kommend): %s', 'cpsmartcrm'),
            esc_html(WPsCRM_format_currency($forecast['unbilled_revenue']))
        ) . '</strong></li>';
    }
    
    if ($forecast['pending_drafts'] > 0) {
        $summary .= '<li style="color: #fbc02d;"><strong>' . sprintf(
            esc_html__('Ausstehende Abrechnungen: %s', 'cpsmartcrm'),
            esc_html(WPsCRM_format_currency($forecast['pending_drafts']))
        ) . '</strong></li>';
    }
    
    $confidence_label = array(
        'high' => __('Hoch (6+ Monate Daten)', 'cpsmartcrm'),
        'medium' => __('Mittel (3-6 Monate)', 'cpsmartcrm'),
        'low' => __('Niedrig (&lt;3 Monate)', 'cpsmartcrm'),
    );
    
    $summary .= '<li style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd; color: #666;">' . sprintf(
        esc_html__('Vertrauenslevel: %s', 'cpsmartcrm'),
        $confidence_label[$forecast['confidence']]
    ) . '</li>';
    
    $summary .= '</ul>';
    $summary .= '</div>';
    
    return $summary;
}
?>
