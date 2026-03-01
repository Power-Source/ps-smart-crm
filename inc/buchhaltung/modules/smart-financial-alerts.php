<?php
/**
 * Modul: Smart Financial Alerts
 * 
 * Automatische Benachrichtigungen für kritische finanzielle Ereignisse
 */

if (!defined('ABSPATH')) exit;

/**
 * Prüft alle Budgets und gibt Alerts zurück
 * 
 * @param int|null $user_id Optional: Nur Daten des Benutzers
 * 
 * @return array Array mit 'errors', 'warnings', 'info'
 */
function wpscrm_check_financial_alerts($user_id = null) {
    global $wpdb;
    
    $alerts = array(
        'critical' => array(),
        'warning' => array(),
        'info' => array(),
    );
    
    // 1. Cashflow-Risiko prüfen
    $forecast = wpscrm_forecast_cashflow(3, $user_id);
    
    if (!empty($forecast['forecast'])) {
        $critical_count = 0;
        foreach ($forecast['forecast'] as $month_data) {
            if ($month_data['total_cash_in'] < $month_data['expenses']) {
                $critical_count++;
            }
        }
        
        if ($critical_count > 0) {
            $alerts['critical'][] = array(
                'icon' => '🚨',
                'title' => __('Cashflow-Risiko erkannt', 'cpsmartcrm'),
                'message' => sprintf(
                    esc_html__('%d der nächsten 3 Monate zeigen negatives Cashflow. Prüfe deine Einnahmen oder Ausgaben!', 'cpsmartcrm'),
                    $critical_count
                ),
            );
        }
    }
    
    // 2. Überfällige Abrechnungsentwürfe prüfen
    $billing_table = WPsCRM_TABLE . 'billing_drafts';
    $old_drafts = 0;
    
    // Only check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$billing_table'")) {
        $old_drafts = $wpdb->get_var("SELECT COUNT(*) FROM $billing_table WHERE status = 'draft' AND created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)");
    }
    
    if ((int)$old_drafts > 0) {
        $alerts['warning'][] = array(
            'icon' => '📋',
            'title' => __('Alte Abrechnungsentwürfe', 'cpsmartcrm'),
            'message' => sprintf(
                esc_html__('%d Abrechnungsentwürfe sind älter als 14 Tage und warten auf Buchung.', 'cpsmartcrm'),
                (int)$old_drafts
            ),
        );
    }
    
    // 3. Unbilled Timetracking warning
    if (function_exists('wpscrm_get_timetracking_summary')) {
        $today = new DateTime();
        $tt_summary = wpscrm_get_timetracking_summary(date('Y-m-01'), $today->format('Y-m-d'));
        
        $total_hours = 0;
        foreach ($tt_summary as $agent) {
            $total_hours += $agent->total_minutes / 60;
        }
        
        if ($total_hours > 40) {  // Mehr als 40 Stunden
            $alerts['info'][] = array(
                'icon' => '⏱️',
                'title' => __('Zeiterfassung: 40+ Stunden ausstehend', 'cpsmartcrm'),
                'message' => sprintf(
                    esc_html__('%d Stunden wurden erfasst, aber noch nicht abgerechnet. Erstelle einen Abrechnungsentwurf!', 'cpsmartcrm'),
                    round($total_hours)
                ),
            );
        }
    }
    
    // 4. Monatliches Budget-Tracking
    $current_data = wpscrm_get_accounting_period_data(date('Y-m-01'), date('Y-m-d'), $user_id);
    
    // Wenn Ausgaben mehr als 70% der Einnahmen sind
    if ($current_data['income']['total_gross'] > 0) {
        $expense_ratio = ($current_data['expenses']['gross'] / $current_data['income']['total_gross']) * 100;
        
        if ($expense_ratio > 70) {
            $alerts['warning'][] = array(
                'icon' => '💰',
                'title' => __('Hohe Kostenquote', 'cpsmartcrm'),
                'message' => sprintf(
                    esc_html__('Deine Ausgaben betragen %d%% deiner Einnahmen diesen Monat. Zielwert: unter 60%%.', 'cpsmartcrm'),
                    round($expense_ratio)
                ),
            );
        }
    }
    
    // 5. Profit-Rückgang
    $recent_6_months = array_slice(wpscrm_get_last_12_months_data($user_id), -6, 6);
    if (count($recent_6_months) >= 2) {
        $months_list = array_values($recent_6_months);
        $current_profit = end($months_list)['profit'];
        $previous_profit = prev($months_list)['profit'];
        
        $profit_change = (($current_profit - $previous_profit) / abs($previous_profit ?: 1)) * 100;
        
        if ($profit_change < -20) {
            $alerts['warning'][] = array(
                'icon' => '📉',
                'title' => __('Gewinn stark gesunken', 'cpsmartcrm'),
                'message' => sprintf(
                    esc_html__('Dein Gewinn ist im Vergleich zum Vormonat um %d%% gesunken.', 'cpsmartcrm'),
                    round(abs($profit_change))
                ),
            );
        }
    }
    
    return $alerts;
}

/**
 * Rendet Alerts als HTML
 * 
 * @param array $alerts Alert-Array aus wpscrm_check_financial_alerts()
 * 
 * @return string HTML
 */
function wpscrm_render_financial_alerts($alerts) {
    if (empty($alerts['critical']) && empty($alerts['warning']) && empty($alerts['info'])) {
        return '';
    }
    
    $html = '<div style="margin-bottom: 20px;">';
    
    // Critical Alerts
    foreach ($alerts['critical'] as $alert) {
        $html .= '<div style="background: #fff3cd; border: 1px solid #ff6b00; border-radius: 6px; padding: 16px; margin-bottom: 12px;">
            <div style="font-weight: 600; color: #ff6b00; margin-bottom: 4px;">
                ' . esc_html($alert['icon']) . ' ' . esc_html($alert['title']) . '
            </div>
            <div style="color: #333; font-size: 14px;">
                ' . esc_html($alert['message']) . '
            </div>
        </div>';
    }
    
    // Warning Alerts
    foreach ($alerts['warning'] as $alert) {
        $html .= '<div style="background: #e7f3ff; border: 1px solid #0073aa; border-radius: 6px; padding: 16px; margin-bottom: 12px;">
            <div style="font-weight: 600; color: #0073aa; margin-bottom: 4px;">
                ' . esc_html($alert['icon']) . ' ' . esc_html($alert['title']) . '
            </div>
            <div style="color: #333; font-size: 14px;">
                ' . esc_html($alert['message']) . '
            </div>
        </div>';
    }
    
    // Info Alerts
    foreach ($alerts['info'] as $alert) {
        $html .= '<div style="background: #f0f8ff; border: 1px solid #2196F3; border-radius: 6px; padding: 16px; margin-bottom: 12px;">
            <div style="font-weight: 600; color: #2196F3; margin-bottom: 4px;">
                ' . esc_html($alert['icon']) . ' ' . esc_html($alert['title']) . '
            </div>
            <div style="color: #333; font-size: 14px;">
                ' . esc_html($alert['message']) . '
            </div>
        </div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>
