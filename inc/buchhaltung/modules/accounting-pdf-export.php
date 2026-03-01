<?php
/**
 * Modul: PDF-Export für Buchhaltungsberichte
 * 
 * Erzeugt professionelle PDF-Reports mit Grafiken,
 * Tabellen und Analysen
 */

if (!defined('ABSPATH')) exit;

/**
 * Generiert einen Buchhaltungs-PDF-Report
 * 
 * @param string $from Startdatum (Y-m-d)
 * @param string $to Enddatum (Y-m-d)
 * @param string $title Berichtstitel
 * @param array $sections Zu include Sektionen: 'summary', 'transactions', 'charts', 'cashflow'
 * @param int|null $user_id Optional: Nur Daten des Benutzers
 * 
 * @return string HTML-Content für PDF (kann mit mPDF oder ähnlich konvertiert werden)
 */
function wpscrm_generate_accounting_report_html($from, $to, $title = '', $sections = array(), $user_id = null) {
    if (empty($sections)) {
        $sections = array('summary', 'transactions', 'charts');
    }
    
    global $wpdb;
    
    $data = wpscrm_get_accounting_period_data($from, $to, $user_id);
    $last_12_months = wpscrm_get_last_12_months_data($user_id);
    
    $title = $title ?: sprintf(__('Buchhaltungsbericht %s bis %s', 'cpsmartcrm'), $from, $to);
    
    // HTML-Vorlage starten
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . esc_html($title) . '</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
                line-height: 1.6;
                page-break-after: always;
            }
            
            .page {
                page-break-after: always;
            }
            
            .header {
                text-align: center;
                padding: 40px 20px;
                border-bottom: 3px solid #0073aa;
                margin-bottom: 30px;
            }
            
            .header h1 {
                color: #0073aa;
                font-size: 28px;
                margin-bottom: 8px;
            }
            
            .header p {
                color: #666;
                font-size: 13px;
            }
            
            .info-box {
                background: #f5f5f5;
                padding: 16px;
                border-radius: 6px;
                margin: 16px 0;
                border-left: 4px solid #0073aa;
            }
            
            .metrics {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr 1fr;
                gap: 16px;
                margin: 20px 0;
            }
            
            .metric-card {
                padding: 20px;
                background: #f8f8f8;
                border-radius: 6px;
                text-align: center;
                border-top: 4px solid #999;
            }
            
            .metric-card.income { border-top-color: #46b450; }
            .metric-card.expense { border-top-color: #ff6b00; }
            .metric-card.profit { border-top-color: #2196F3; }
            .metric-card.tax { border-top-color: #dd3333; }
            
            .metric-card h3 {
                font-size: 12px;
                color: #666;
                margin-bottom: 8px;
                text-transform: uppercase;
                font-weight: 600;
            }
            
            .metric-card .value {
                font-size: 22px;
                font-weight: bold;
                color: #333;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 16px 0;
            }
            
            table th {
                background: #f5f5f5;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #ddd;
                font-size: 12px;
            }
            
            table td {
                padding: 10px 12px;
                border-bottom: 1px solid #e8e8e8;
                font-size: 13px;
            }
            
            table tr:last-child td {
                border-bottom: 2px solid #ddd;
            }
            
            .section-title {
                font-size: 18px;
                font-weight: 600;
                color: #0073aa;
                margin: 30px 0 16px 0;
                padding-bottom: 8px;
                border-bottom: 2px solid #0073aa;
            }
            
            .amount {
                font-family: "Courier New", monospace;
            }
            
            .positive {
                color: #46b450;
            }
            
            .negative {
                color: #c62828;
            }
            
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                color: #999;
                font-size: 11px;
            }
            
            h2 {
                font-size: 16px;
                color: #333;
                margin: 20px 0 12px 0;
                border-left: 4px solid #0073aa;
                padding-left: 12px;
            }
        </style>
    </head>
    <body>';
    
    // Header
    $html .= '<div class="header">
        <h1>' . esc_html($title) . '</h1>
        <p>' . sprintf(
            __('Zeitraum: %s bis %s | Generiert am %s', 'cpsmartcrm'),
            date_i18n('d.m.Y', strtotime($from)),
            date_i18n('d.m.Y', strtotime($to)),
            date_i18n('d.m.Y H:i')
        ) . '</p>
    </div>';
    
    // Zusammenfassung (Summary)
    if (in_array('summary', $sections)) {
        $html .= '<div class="page">
            <h2 class="section-title">' . esc_html__('Zusammenfassung', 'cpsmartcrm') . '</h2>
            
            <div class="metrics">
                <div class="metric-card income">
                    <h3>' . esc_html__('Einnahmen (Brutto)', 'cpsmartcrm') . '</h3>
                    <div class="value amount">' . esc_html(WPsCRM_format_currency($data['income']['total_gross'])) . '</div>
                </div>
                
                <div class="metric-card expense">
                    <h3>' . esc_html__('Ausgaben (Brutto)', 'cpsmartcrm') . '</h3>
                    <div class="value amount">' . esc_html(WPsCRM_format_currency($data['expenses']['gross'])) . '</div>
                </div>
                
                <div class="metric-card profit" style="color: ' . ($data['summary']['profit_gross'] >= 0 ? '#2196F3' : '#c62828') . '">
                    <h3>' . esc_html__('Gewinn (Brutto)', 'cpsmartcrm') . '</h3>
                    <div class="value amount ' . ($data['summary']['profit_gross'] >= 0 ? 'positive' : 'negative') . '">' . 
                        esc_html(WPsCRM_format_currency($data['summary']['profit_gross'])) . '
                    </div>
                </div>
                
                <div class="metric-card tax">
                    <h3>' . esc_html__('Steuerlast', 'cpsmartcrm') . '</h3>
                    <div class="value amount">' . esc_html(WPsCRM_format_currency($data['income']['total_tax'] - $data['expenses']['tax'])) . '</div>
                </div>
            </div>';
        
        // Detail-Tabelle
        $html .= '<table>
            <thead>
                <tr>
                    <th>' . esc_html__('Kategorie', 'cpsmartcrm') . '</th>
                    <th style="text-align: right;">' . esc_html__('Netto', 'cpsmartcrm') . '</th>
                    <th style="text-align: right;">' . esc_html__('Steuer', 'cpsmartcrm') . '</th>
                    <th style="text-align: right;">' . esc_html__('Brutto', 'cpsmartcrm') . '</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>' . esc_html__('Rechnungen', 'cpsmartcrm') . '</strong></td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['income']['invoices_net'])) . '</td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['income']['invoices_tax'])) . '</td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['income']['invoices_gross'])) . '</td>
                </tr>
                <tr>
                    <td><strong>' . esc_html__('Manuelle Einnahmen', 'cpsmartcrm') . '</strong></td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['income']['manual_net'])) . '</td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['income']['manual_tax'])) . '</td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['income']['manual_gross'])) . '</td>
                </tr>
                <tr style="background: #f5f5f5;">
                    <td><strong>' . esc_html__('GESAMTEINNAHMEN', 'cpsmartcrm') . '</strong></td>
                    <td class="amount" style="text-align: right;"><strong>' . esc_html(WPsCRM_format_currency($data['income']['total_net'])) . '</strong></td>
                    <td class="amount" style="text-align: right;"><strong>' . esc_html(WPsCRM_format_currency($data['income']['total_tax'])) . '</strong></td>
                    <td class="amount" style="text-align: right;"><strong>' . esc_html(WPsCRM_format_currency($data['income']['total_gross'])) . '</strong></td>
                </tr>
                <tr>
                    <td><strong>' . esc_html__('Ausgaben', 'cpsmartcrm') . '</strong></td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['expenses']['net'])) . '</td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['expenses']['tax'])) . '</td>
                    <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($data['expenses']['gross'])) . '</td>
                </tr>
                <tr style="background: #f0f0f0;">
                    <td><strong>' . esc_html__('GEWINN/VERLUST', 'cpsmartcrm') . '</strong></td>
                    <td class="amount" style="text-align: right;"><strong>' . esc_html(WPsCRM_format_currency($data['summary']['profit_net'])) . '</strong></td>
                    <td class="amount" style="text-align: right;"><strong>' . esc_html(WPsCRM_format_currency($data['income']['total_tax'] - $data['expenses']['tax'])) . '</strong></td>
                    <td class="amount" style="text-align: right;"><strong class="' . ($data['summary']['profit_gross'] >= 0 ? 'positive' : 'negative') . '">' . 
                        esc_html(WPsCRM_format_currency($data['summary']['profit_gross'])) . '</strong>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>';
    }
    
    // 12-Monats Übersicht (Cashflow für Charts)
    if (in_array('charts', $sections)) {
        $html .= '<div class="page">
            <h2 class="section-title">' . esc_html__('12-Monats Trend', 'cpsmartcrm') . '</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>' . esc_html__('Monat', 'cpsmartcrm') . '</th>
                        <th style="text-align: right;">' . esc_html__('Einnahmen', 'cpsmartcrm') . '</th>
                        <th style="text-align: right;">' . esc_html__('Ausgaben', 'cpsmartcrm') . '</th>
                        <th style="text-align: right;">' . esc_html__('Gewinn', 'cpsmartcrm') . '</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($last_12_months as $month_key => $month_data) {
            $html .= '<tr>
                <td>' . esc_html($month_data['label']) . '</td>
                <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($month_data['income'])) . '</td>
                <td class="amount" style="text-align: right;">' . esc_html(WPsCRM_format_currency($month_data['expenses'])) . '</td>
                <td class="amount" style="text-align: right; ' . ($month_data['profit'] >= 0 ? 'color: #46b450' : 'color: #c62828') . '"><strong>' . 
                    esc_html(WPsCRM_format_currency($month_data['profit'])) . '</strong></td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
        </div>';
    }
    
    // Footer
    $html .= '<div class="footer">
        <p>' . sprintf(
            esc_html__('Bericht generiert von %s am %s', 'cpsmartcrm'),
            bloginfo('name'),
            date_i18n('d.m.Y H:i:s')
        ) . '</p>
    </div>';
    
    $html .= '</body>
    </html>';
    
    return $html;
}

/**
 * Exportiert HTML zu PDF-Download (client-side mit print)
 * 
 * @param string $html HTML-Content
 * @param string $filename Dateiname ohne .pdf
 * 
 * @return void Sendet Header + Output
 */
function wpscrm_export_html_to_pdf($html, $filename = 'report') {
    // Diese Funktion triggert print-Dialog im Browser
    // Der Nutzer speichert manuell als PDF
    
    // Alternative: Nutzt JavaScript zum automatischen Druck
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo esc_html($filename); ?></title>
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </head>
    <body>
        <?php echo $html; ?>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Erstellt einen Download-Link für PDF-Export
 * 
 * @param string $from Startdatum
 * @param string $to Enddatum
 * @param string $title Report-Titel
 * 
 * @return string HTML für Download-Link
 */
function wpscrm_get_pdf_export_button($from, $to, $title = '') {
    $nonce = wp_create_nonce('wpscrm_export_pdf');
    
    ob_start();
    ?>
    <form method="POST" action="" style="display: inline;">
        <input type="hidden" name="action" value="export_pdf" />
        <input type="hidden" name="from" value="<?php echo esc_attr($from); ?>" />
        <input type="hidden" name="to" value="<?php echo esc_attr($to); ?>" />
        <input type="hidden" name="title" value="<?php echo esc_attr($title); ?>" />
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
        <button type="submit" class="button button-primary">
            <?php esc_html_e('📄 Als PDF exportieren', 'cpsmartcrm'); ?>
        </button>
    </form>
    <?php
    return ob_get_clean();
}
?>
