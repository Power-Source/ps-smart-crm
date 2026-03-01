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

// Steuerlast-Daten laden
$tax_months = wpscrm_get_last_12_months_tax_data($data_user_id);
$business_settings = get_option('CRM_business_settings', array());
$business_type = isset($business_settings['business_type']) ? $business_settings['business_type'] : 'standard';

// Chart-Konfigurationen
$trend_chart_config = wpscrm_generate_trend_chart_config($last_12_months, 'line');
$tax_chart_config = wpscrm_generate_tax_trend_chart_config($tax_months, $business_type);

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

<!-- 🏛️ Steuerlast-Trend Sektion -->
<div style="background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h3 style="margin-top: 0;">🏛️ <?php _e('Steuerlast-Trend (Zahllast)', 'cpsmartcrm'); ?></h3>
    <p style="color: #666; margin-bottom: 16px;">
        <?php _e('USt-Einnahmen minus VSt-Ausgaben der letzten 12 Monate. Positive Werte = Zahllast an Finanzamt.', 'cpsmartcrm'); ?>
    </p>
    
    <div style="position: relative; height: 350px;">
        <?php 
        $tax_config_json = json_encode($tax_chart_config);
        echo '<canvas id="taxChart" style="max-height: 350px;"></canvas>';
        ?>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        try {
            const ctxTax = document.getElementById("taxChart").getContext("2d");
            const configTax = <?php echo $tax_config_json; ?>;
            
            // Formatter für Y-Achse
            configTax.options.scales.y.ticks.callback = function(value) {
                return value.toLocaleString("de-DE", {style: "currency", currency: "EUR", maximumFractionDigits: 0});
            };
            
            new Chart(ctxTax, configTax);
        } catch (e) {
            console.error("Fehler beim Tax Chart-Rendering:", e);
        }
    });
    </script>
    
    <!-- Steuerlast Summary Cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; margin-top: 20px;">
        <?php 
        // Letzte 12 Monate Steuerlast Analyse
        $current_tax_liability = 0;
        $avg_tax_liability = 0;
        $max_tax_liability = 0;
        $tax_months_with_liability = 0;
        
        foreach ($tax_months as $month_data) {
            $tax_liability = $month_data['income_ust'] - $month_data['expense_vst'];
            $current_tax_liability = $tax_liability;
            $avg_tax_liability += abs($tax_liability);
            
            if ($tax_liability > $max_tax_liability) {
                $max_tax_liability = $tax_liability;
            }
            if ($tax_liability > 0) {
                $tax_months_with_liability++;
            }
        }
        $avg_tax_liability = !empty($tax_months) ? $avg_tax_liability / count($tax_months) : 0;
        
        // Prognose für nächste 3 Monate
        $forecast_tax_liability = 0;
        foreach ($forecast['forecast'] as $proj) {
            $forecast_tax_liability += ($proj['income'] * 0.19) - ($proj['expenses'] * 0.19);
        }
        ?>
        
        <div style="background: <?php echo $current_tax_liability > 0 ? '#ffebee' : '#e8f5e9'; ?>; border-left: 4px solid <?php echo $current_tax_liability > 0 ? '#f44336' : '#4caf50'; ?>; padding: 12px; border-radius: 4px;">
            <div style="font-size: 11px; color: #999; margin-bottom: 4px; font-weight: 600;"><?php _e('Aktueller Monat', 'cpsmartcrm'); ?></div>
            <div style="font-size: 16px; font-weight: bold; color: <?php echo $current_tax_liability > 0 ? '#f44336' : '#4caf50'; ?>;">
                <?php echo WPsCRM_format_currency($current_tax_liability); ?>
            </div>
            <div style="font-size: 10px; color: #666; margin-top: 4px;">
                <?php echo $current_tax_liability > 0 ? __('An Finanzamt', 'cpsmartcrm') : __('Von Finanzamt', 'cpsmartcrm'); ?>
            </div>
        </div>
        
        <div style="background: #f0f4f8; border-left: 4px solid #2196F3; padding: 12px; border-radius: 4px;">
            <div style="font-size: 11px; color: #999; margin-bottom: 4px; font-weight: 600;"><?php _e('Durchschnitt (12M)', 'cpsmartcrm'); ?></div>
            <div style="font-size: 16px; font-weight: bold; color: #2196F3;">
                <?php echo WPsCRM_format_currency($avg_tax_liability); ?>
            </div>
            <div style="font-size: 10px; color: #666; margin-top: 4px;"><?php _e('pro Monat', 'cpsmartcrm'); ?></div>
        </div>
        
        <div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 12px; border-radius: 4px;">
            <div style="font-size: 11px; color: #999; margin-bottom: 4px; font-weight: 600;"><?php _e('Maximum', 'cpsmartcrm'); ?></div>
            <div style="font-size: 16px; font-weight: bold; color: #ff9800;">
                <?php echo WPsCRM_format_currency($max_tax_liability); ?>
            </div>
            <div style="font-size: 10px; color: #666; margin-top: 4px;"><?php _e('höchster Monat', 'cpsmartcrm'); ?></div>
        </div>
        
        <div style="background: #f3e5f5; border-left: 4px solid #9c27b0; padding: 12px; border-radius: 4px;">
            <div style="font-size: 11px; color: #999; margin-bottom: 4px; font-weight: 600;"><?php _e('3M Prognose', 'cpsmartcrm'); ?></div>
            <div style="font-size: 16px; font-weight: bold; color: #9c27b0;">
                <?php echo WPsCRM_format_currency($forecast_tax_liability); ?>
            </div>
            <div style="font-size: 10px; color: #666; margin-top: 4px;"><?php _e('kommende Monate', 'cpsmartcrm'); ?></div>
        </div>
    </div>
</div>

<!-- Steuerlast Details Tabelle -->
<div style="background: #f5f5f5; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
    <h3 style="margin-top: 0;">📋 <?php _e('Steuerlast nach Monat', 'cpsmartcrm'); ?></h3>
    
    <table style="width: 100%; border-collapse: collapse; background: #fff;">
        <thead>
            <tr style="background: #f0f0f0; border-bottom: 2px solid #ddd;">
                <th style="padding: 12px; text-align: left; font-weight: 600; color: #333;"><?php _e('Monat', 'cpsmartcrm'); ?></th>
                <th style="padding: 12px; text-align: right; font-weight: 600; color: #46b450;"><?php _e('Einnahmen-USt', 'cpsmartcrm'); ?></th>
                <th style="padding: 12px; text-align: right; font-weight: 600; color: #ff6b00;"><?php _e('Ausgaben-VSt', 'cpsmartcrm'); ?></th>
                <th style="padding: 12px; text-align: right; font-weight: 600; color: #f44336;"><?php _e('Zahllast', 'cpsmartcrm'); ?></th>
                <th style="padding: 12px; text-align: center; font-weight: 600; color: #666;"><?php _e('Status', 'cpsmartcrm'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($tax_months) as $idx => $month_data) : 
                $zahllast = $month_data['income_ust'] - $month_data['expense_vst'];
                $status_color = $zahllast > 0 ? '#f44336' : '#4caf50';
                $status_text = $zahllast > 0 ? __('An Finanzamt', 'cpsmartcrm') : __('Rückerstattung', 'cpsmartcrm');
                ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 12px; color: #333;"><?php echo esc_html($month_data['month_label']); ?></td>
                    <td style="padding: 12px; text-align: right; color: #46b450;">
                        <strong><?php echo WPsCRM_format_currency($month_data['income_ust']); ?></strong>
                    </td>
                    <td style="padding: 12px; text-align: right; color: #ff6b00;">
                        <strong><?php echo WPsCRM_format_currency($month_data['expense_vst']); ?></strong>
                    </td>
                    <td style="padding: 12px; text-align: right; font-weight: bold; color: <?php echo $status_color; ?>;">
                        <?php echo WPsCRM_format_currency($zahllast); ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="background: <?php echo substr($status_color, 0, -2) . '20'; ?>; color: <?php echo $status_color; ?>; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Steuerlast Warnung -->
<?php if ((isset(CRM_business_settings['crm_kleinunternehmer']) && CRM_business_settings['crm_kleinunternehmer'] == 'on') || $business_type === 'kleinunternehmer') : ?>
    <div style="background: #fff9c4; border: 1px solid #fbc02d; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: #f57f17;">ℹ️ <?php _e('Kleinunternehmer-Regelung (§19 UStG)', 'cpsmartcrm'); ?></h4>
        <p style="margin: 0; color: #333;">
            <?php _e('Du nutzt die Kleinunternehmer-Regelung. Keine Umsatzsteuern fällig. Die Steuerlast oben dient nur der Information. 👋 Umsatzsteuer wird nicht berechnet.', 'cpsmartcrm'); ?>
        </p>
    </div>
<?php else : ?>
    <div style="background: #e3f2fd; border: 1px solid #2196F3; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <h4 style="margin-top: 0; color: #1565c0;">💡 <?php _e('Standard-Besteuerung', 'cpsmartcrm'); ?></h4>
        <p style="margin: 0; color: #333;">
            <?php printf(
                __('Die Zahllast wird monatlich/quartalsweise an das Finanzamt überwiesen. Nächst wahrscheinlicher Stichtag: %s', 'cpsmartcrm'),
                date('d.m.Y', strtotime('+' . (3 - date('m') % 3) . 'months', strtotime('first day of next month')))
            ); ?>
        </p>
    </div>
<?php endif; ?>


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
