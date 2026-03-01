<?php
/**
 * Buchhaltungs-Analytics Tab
 * 
 * Zeigt Trends, Grafen, Cashflow-Prognose und Abweichungsanalyse
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

$wpscrm_user_id = get_current_user_id();
$wpscrm_is_admin = current_user_can('manage_options');
$can_view_accounting = $wpscrm_is_admin || !function_exists('wpscrm_user_can') || wpscrm_user_can($wpscrm_user_id, 'can_view_accounting');

if (!$can_view_accounting) {
    wp_die(__('Du hast keine Berechtigung für diesen Bereich.', 'cpsmartcrm'));
}

// Enqueue Chart.js
wpscrm_enqueue_chart_js();

// Daten laden
$is_own_scope = !current_user_can('manage_options');
$data_user_id = $is_own_scope ? $wpscrm_user_id : null;

$last_12_months = wpscrm_get_last_12_months_data($data_user_id);
$forecast = wpscrm_forecast_cashflow(3, $data_user_id);
$risks = wpscrm_calculate_cashflow_risks($forecast);

// Chart-Konfigurationen
$trend_chart_config = wpscrm_generate_trend_chart_config($last_12_months, 'line');

// Letzte 3 Monate für Vergleich
$last_3_months = array_slice($last_12_months, -3, 3);
?>

<h2><?php _e('📊 Finanz-Analytics', 'cpsmartcrm'); ?></h2>

<p style="color: #666; margin-bottom: 20px;">
    <?php _e('Trends, Cashflow-Prognose und detaillierte Analysen für bessere finanzielle Entscheidungen.', 'cpsmartcrm'); ?>
</p>

<!-- PDF Export -->
<div style="text-align: right; margin-bottom: 16px;">
    <?php echo wpscrm_get_pdf_export_button(date('Y-01-01'), date('Y-m-d'), __('Analytics Report', 'cpsmartcrm')); ?>
</div>

<!-- Trends Sektion -->
<div style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h3 style="margin-top: 0;">📈 <?php _e('12-Monats Trend', 'cpsmartcrm'); ?></h3>
    
    <div style="position: relative; height: 400px;">
        <?php 
        $config_json = json_encode($trend_chart_config);
        echo '<canvas id="trendChart" style="max-height: 400px;"></canvas>';
        ?>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        try {
            const ctxTrend = document.getElementById("trendChart").getContext("2d");
            const configTrend = <?php echo $config_json; ?>;
            
            // Formatter für Y-Achse
            configTrend.options.scales.y.ticks.callback = function(value) {
                return value.toLocaleString("de-DE", {style: "currency", currency: "EUR", maximumFractionDigits: 0});
            };
            
            new Chart(ctxTrend, configTrend);
        } catch (e) {
            console.error("Fehler beim Chart-Rendering:", e);
        }
    });
    </script>
</div>

<!-- Cashflow-Prognose Sektion -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 20px; margin-bottom: 20px; color: #fff;">
    <h3 style="margin-top: 0; margin-bottom: 16px;">💰 <?php _e('3-Monats Cashflow-Prognose', 'cpsmartcrm'); ?></h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
        <?php foreach ($forecast['forecast'] as $month_key => $proj) : ?>
            <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px; backdrop-filter: blur(10px);">
                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 8px; font-weight: 600;">
                    <?php echo esc_html($proj['label']); ?>
                </div>
                
                <div style="font-size: 14px; margin-bottom: 4px;">
                    💵 <?php echo esc_html(WPsCRM_format_currency($proj['income'])); ?>
                </div>
                
                <?php if ($proj['special_income'] > 0) : ?>
                    <div style="font-size: 12px; background: rgba(255,255,255,0.15); padding: 4px 6px; border-radius: 3px; margin-bottom: 4px;">
                        ⚡ +<?php echo esc_html(WPsCRM_format_currency($proj['special_income'])); ?> (TT)
                    </div>
                <?php endif; ?>
                
                <?php if ($proj['special_events'] > 0) : ?>
                    <div style="font-size: 12px; background: rgba(255,255,255,0.15); padding: 4px 6px; border-radius: 3px; margin-bottom: 4px;">
                        📋 +<?php echo esc_html(WPsCRM_format_currency($proj['special_events'])); ?> (Draft)
                    </div>
                <?php endif; ?>
                
                <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 8px; margin-top: 8px;">
                    <div style="font-size: 13px; margin-bottom: 2px;">
                        💸 <?php echo esc_html(WPsCRM_format_currency($proj['expenses'])); ?>
                    </div>
                    <div style="font-size: 14px; font-weight: bold; color: #fff;">
                        = <?php echo esc_html(WPsCRM_format_currency($proj['total_cash_in'] - $proj['expenses'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div style="background: rgba(0,0,0,0.1); padding: 12px; border-radius: 6px;">
        <?php echo wpscrm_format_cashflow_summary($forecast); ?>
    </div>
</div>

<!-- Monatliches Vergleichspanel -->
<div style="background: #f5f5f5; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
    <h3 style="margin-top: 0;">📊 <?php _e('Letzte 3 Monate im Überblick', 'cpsmartcrm'); ?></h3>
    
    <table style="width: 100%; border-collapse: collapse; background: #fff;">
        <thead>
            <tr style="background: #f0f0f0; border-bottom: 2px solid #ddd;">
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;">Monat</th>
                <th style="padding: 12px; text-align: right; font-weight: 600; color: #46b450;">Einnahmen</th>
                <th style="padding: 12px; text-align: right; font-weight: 600; color: #ff6b00;">Ausgaben</th>
                <th style="padding: 12px; text-align: right; font-weight: 600; color: #2196F3;">Gewinn</th>
                <th style="padding: 12px; text-align: center; font-weight: 600; color: #666;">Trend</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $prev_profit = null;
            foreach ($last_3_months as $month_key => $month_data) : 
                $trend_icon = '—';
                if ($prev_profit !== null) {
                    if ($month_data['profit'] > $prev_profit) {
                        $trend_icon = '📈 +' . number_format(abs($month_data['profit'] - $prev_profit), 0, ',', '.');
                    } elseif ($month_data['profit'] < $prev_profit) {
                        $trend_icon = '📉 ' . number_format(abs($month_data['profit'] - $prev_profit), 0, ',', '.');
                    } else {
                        $trend_icon = '→ =' ;
                    }
                }
                $prev_profit = $month_data['profit'];
            ?>
                <tr style="border-bottom: 1px solid #e8e8e8;">
                    <td style="padding: 12px; font-weight: 600; color: #333;">
                        <?php echo esc_html($month_data['label']); ?>
                    </td>
                    <td style="padding: 12px; text-align: right; color: #46b450;">
                        <?php echo esc_html(WPsCRM_format_currency($month_data['income'])); ?>
                    </td>
                    <td style="padding: 12px; text-align: right; color: #ff6b00;">
                        <?php echo esc_html(WPsCRM_format_currency($month_data['expenses'])); ?>
                    </td>
                    <td style="padding: 12px; text-align: right; color: <?php echo $month_data['profit'] >= 0 ? '#2196F3' : '#c62828'; ?>; font-weight: 600;">
                        <?php echo esc_html(WPsCRM_format_currency($month_data['profit'])); ?>
                    </td>
                    <td style="padding: 12px; text-align: center; color: #666; font-size: 12px;">
                        <?php echo esc_html($trend_icon); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Abweichungsanalyse -->
<?php if (count($last_3_months) >= 2) : ?>
    <?php 
    $months_list = array_values($last_3_months);
    $current_month = end($months_list);
    $previous_month = prev($months_list);
    
    $income_change = (($current_month['income'] - $previous_month['income']) / ($previous_month['income'] ?: 1)) * 100;
    $expense_change = (($current_month['expenses'] - $previous_month['expenses']) / ($previous_month['expenses'] ?: 1)) * 100;
    $profit_change = (($current_month['profit'] - $previous_month['profit']) / abs($previous_month['profit'] ?: 1)) * 100;
    ?>
    
    <div style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; border: 1px solid #ddd;">
        <h3 style="margin-top: 0;">📍 <?php _e('Abweichungsanalyse: Aktueller vs. Vormonat', 'cpsmartcrm'); ?></h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <!-- Einnahmen Abweichung -->
            <div style="padding: 16px; background: #f5f5f5; border-radius: 6px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; font-weight: 600;">
                    <?php _e('Einnahmen-Abweichung', 'cpsmartcrm'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: <?php echo $income_change >= 0 ? '#46b450' : '#ff6b00'; ?>;">
                    <?php echo esc_html(number_format($income_change, 1, ',', '.')); ?>%
                </div>
                <div style="font-size: 12px; color: #999; margin-top: 4px;">
                    <?php echo esc_html(WPsCRM_format_currency(abs($current_month['income'] - $previous_month['income']))); ?> 
                    <?php echo $income_change >= 0 ? __('mehr', 'cpsmartcrm') : __('weniger', 'cpsmartcrm'); ?>
                </div>
                <div style="font-size: 11px; color: #666; margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
                    <strong><?php _e('Grund analysieren:', 'cpsmartcrm'); ?></strong><br/>
                    <?php if ($income_change > 10) : ?>
                        ✓ Positiver Trend - neue Kunden?
                    <?php elseif ($income_change < -10) : ?>
                        ⚠️ Rückgang - Saisonalität? Verluste?
                    <?php else : ?>
                        → Stabil
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ausgaben Abweichung -->
            <div style="padding: 16px; background: #f5f5f5; border-radius: 6px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; font-weight: 600;">
                    <?php _e('Ausgaben-Abweichung', 'cpsmartcrm'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: <?php echo $expense_change <= 0 ? '#46b450' : '#ff6b00'; ?>;">
                    <?php echo esc_html(number_format($expense_change, 1, ',', '.')); ?>%
                </div>
                <div style="font-size: 12px; color: #999; margin-top: 4px;">
                    <?php echo esc_html(WPsCRM_format_currency(abs($current_month['expenses'] - $previous_month['expenses']))); ?> 
                    <?php echo $expense_change >= 0 ? __('mehr', 'cpsmartcrm') : __('weniger', 'cpsmartcrm'); ?>
                </div>
                <div style="font-size: 11px; color: #666; margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
                    <strong><?php _e('Grund analysieren:', 'cpsmartcrm'); ?></strong><br/>
                    <?php if ($expense_change > 10) : ?>
                        ⚠️ Kosten steigen - einmalig?
                    <?php elseif ($expense_change < -10) : ?>
                        ✓ Kostenersparnis - gut!
                    <?php else : ?>
                        → Stabil
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Gewinn Abweichung -->
            <div style="padding: 16px; background: #f5f5f5; border-radius: 6px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; font-weight: 600;">
                    <?php _e('Gewinn-Abweichung', 'cpsmartcrm'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: <?php echo $profit_change >= 0 ? '#2196F3' : '#c62828'; ?>;">
                    <?php echo esc_html(number_format($profit_change, 1, ',', '.')); ?>%
                </div>
                <div style="font-size: 12px; color: #999; margin-top: 4px;">
                    <?php echo esc_html(WPsCRM_format_currency(abs($current_month['profit'] - $previous_month['profit']))); ?> 
                    <?php echo $profit_change >= 0 ? __('höher', 'cpsmartcrm') : __('niedriger', 'cpsmartcrm'); ?>
                </div>
                <div style="font-size: 11px; color: #666; margin-top: 8px; padding-top: 8px; border-top: 1px solid #ddd;">
                    <strong><?php _e('Status:', 'cpsmartcrm'); ?></strong><br/>
                    <?php if (abs($profit_change) <= 5) : ?>
                        ✓ Sehr stabil
                    <?php elseif ($profit_change >= 0) : ?>
                        📈 Verbesserung
                    <?php else : ?>
                        📉 Rückgang
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Risikowarnung -->
<?php if (!empty($risks['critical_months'])) : ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: #ff6b00;">⚠️ <?php _e('Kashflow-Warnung', 'cpsmartcrm'); ?></h4>
        <p style="margin: 0; color: #333;">
            <?php printf(
                esc_html__('Basierend auf der Prognose werden die folgenden Monate kritisch: %s. Stelle sicher, dass genügend Reserven vorhanden sind oder erhöhe die Einnahmen.', 'cpsmartcrm'),
                esc_html(implode(', ', array_map(function($m) { 
                    $date = DateTime::createFromFormat('Y-m', $m);
                    return $date->format('M.Y');
                }, $risks['critical_months'])))
            ); ?>
        </p>
    </div>
<?php endif; ?>

<!-- Footer Info -->
<div style="background: #e7f3ff; border: 1px solid #0073aa; border-radius: 8px; padding: 12px; margin-top: 20px;">
    <strong><?php _e('ℹ️ Hinweis zu Prognosen:', 'cpsmartcrm'); ?></strong><br/>
    <small style="color: #333;">
        <?php _e('Diese Prognosen basieren auf durchschnittlichen historischen Daten der letzten 6 Monate. Die Genauigkeit steigt mit mehr verfügbaren Daten. Für langfristige Planung sollten auch saisonale Schwankungen und spezielle Ereignisse berücksichtigt werden.', 'cpsmartcrm'); ?>
    </small>
</div>
