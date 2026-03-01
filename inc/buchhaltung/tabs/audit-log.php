<?php
/**
 * Audit-Log Dashboard
 * 
 * Zeigt alle Buchhaltungs-Aktivitäten an (nur für Admin und Chef-Rolle)
 * Protokolliert: Einnahmen/Ausgaben erstellen, Transaktionen löschen
 */

if (!defined('ABSPATH')) exit;

// Permissions check: Nur Admin oder Chef-Rolle
if (!current_user_can('manage_options')) {
    // Prüfe ob Benutzer Chef-Rolle hat
    $current_user_id = get_current_user_id();
    $user_crm_role = get_user_meta($current_user_id, 'WPsCRM_assigned_role', true);
    
    if ($user_crm_role !== 'chef') {
        wp_die(__('Sie haben keine Berechtigung, diese Seite anzuzeigen.', 'cpsmartcrm'));
    }
}

// Get filter parameters
$filter_action = isset($_GET['filter_action']) ? sanitize_text_field($_GET['filter_action']) : '';
$filter_user = isset($_GET['filter_user']) ? (int)$_GET['filter_user'] : 0;
$filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
$filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';

// Build query args
$query_args = array(
    'limit' => 100,
    'offset' => 0,
);

if ($filter_action) {
    $query_args['action'] = $filter_action;
}
if ($filter_user) {
    $query_args['user_id'] = $filter_user;
}
if ($filter_date_from) {
    $query_args['date_from'] = $filter_date_from . ' 00:00:00';
}
if ($filter_date_to) {
    $query_args['date_to'] = $filter_date_to . ' 23:59:59';
}

// Get logs
$logs = wpscrm_get_accounting_logs($query_args);
$total_count = wpscrm_get_accounting_logs_count($query_args);

// Get all users who have logged activities
global $wpdb;
$log_table = WPsCRM_TABLE . 'accounting_log';
$users_with_logs = $wpdb->get_results("
    SELECT DISTINCT user_id, username 
    FROM {$log_table} 
    WHERE user_id IS NOT NULL 
    ORDER BY username ASC
");

// Action type labels
$action_labels = array(
    'create_income' => '➕ Einnahme erstellt',
    'create_expense' => '➖ Ausgabe erstellt',
    'delete_transaction' => '🗑️ Transaktion storniert',
);

// Action type colors
$action_colors = array(
    'create_income' => '#4caf50',
    'create_expense' => '#f44336',
    'delete_transaction' => '#ff9800',
);
?>

<style>
.crm-audit-log-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.crm-audit-log-header h2 {
    margin: 0 0 10px 0;
    color: white;
    font-size: 28px;
    font-weight: 600;
}

.crm-audit-log-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.crm-audit-filters {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.crm-audit-filters h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
}

.crm-audit-filter-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.crm-audit-filter-group {
    flex: 1;
    min-width: 200px;
}

.crm-audit-filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.crm-audit-filter-group select,
.crm-audit-filter-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.crm-audit-filter-actions {
    display: flex;
    gap: 10px;
}

.crm-audit-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.crm-audit-stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
}

.crm-audit-stat-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #777;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.crm-audit-stat-card .value {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.crm-audit-log-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.crm-audit-log-table table {
    width: 100%;
    border-collapse: collapse;
}

.crm-audit-log-table thead {
    background: #f8f9fa;
    border-bottom: 2px solid #e0e0e0;
}

.crm-audit-log-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.crm-audit-log-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
}

.crm-audit-log-table tbody tr:hover {
    background: #f8f9fa;
}

.crm-audit-action-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    color: white;
}

.crm-audit-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.crm-audit-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e0e0e0;
}

.crm-audit-data {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
    font-family: monospace;
    max-width: 400px;
    word-wrap: break-word;
}

.crm-audit-data-item {
    margin: 3px 0;
    color: #555;
}

.crm-audit-data-item strong {
    color: #333;
}

.crm-audit-timestamp {
    color: #777;
    font-size: 13px;
}

.crm-audit-ip {
    color: #999;
    font-size: 12px;
}

.crm-no-logs {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.crm-no-logs-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}
</style>

<div class="crm-audit-log-container">
    <!-- Header -->
    <div class="crm-audit-log-header">
        <h2>🔒 Buchhaltungs Audit-Log</h2>
        <p>Alle Buchhaltungsaktivitäten werden automatisch protokolliert. Nur für Administratoren und Chef-Rolle sichtbar.</p>
    </div>

    <!-- Statistics -->
    <?php
    $stats_today = wpscrm_get_accounting_logs_count(array(
        'date_from' => date('Y-m-d') . ' 00:00:00',
        'date_to' => date('Y-m-d') . ' 23:59:59',
    ));
    
    $stats_week = wpscrm_get_accounting_logs_count(array(
        'date_from' => date('Y-m-d', strtotime('-7 days')) . ' 00:00:00',
    ));
    
    $stats_incomes = wpscrm_get_accounting_logs_count(array('action' => 'create_income'));
    $stats_expenses = wpscrm_get_accounting_logs_count(array('action' => 'create_expense'));
    $stats_deletes = wpscrm_get_accounting_logs_count(array('action' => 'delete_transaction'));
    ?>
    
    <div class="crm-audit-stats">
        <div class="crm-audit-stat-card">
            <h4><?php _e('Gesamt Einträge', 'cpsmartcrm'); ?></h4>
            <div class="value"><?php echo number_format($total_count, 0, ',', '.'); ?></div>
        </div>
        <div class="crm-audit-stat-card">
            <h4><?php _e('Heute', 'cpsmartcrm'); ?></h4>
            <div class="value"><?php echo number_format($stats_today, 0, ',', '.'); ?></div>
        </div>
        <div class="crm-audit-stat-card">
            <h4><?php _e('Letzte 7 Tage', 'cpsmartcrm'); ?></h4>
            <div class="value"><?php echo number_format($stats_week, 0, ',', '.'); ?></div>
        </div>
        <div class="crm-audit-stat-card">
            <h4><?php _e('Einnahmen', 'cpsmartcrm'); ?></h4>
            <div class="value"><?php echo number_format($stats_incomes, 0, ',', '.'); ?></div>
        </div>
        <div class="crm-audit-stat-card">
            <h4><?php _e('Ausgaben', 'cpsmartcrm'); ?></h4>
            <div class="value"><?php echo number_format($stats_expenses, 0, ',', '.'); ?></div>
        </div>
        <div class="crm-audit-stat-card">
            <h4><?php _e('Storniert', 'cpsmartcrm'); ?></h4>
            <div class="value"><?php echo number_format($stats_deletes, 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="crm-audit-filters">
        <h3>📊 Filter</h3>
        <form method="get" action="">
            <input type="hidden" name="page" value="smart-crm">
            <input type="hidden" name="p" value="buchhaltung/index.php">
            <input type="hidden" name="accounting_tab" value="audit_log">
            
            <div class="crm-audit-filter-row">
                <div class="crm-audit-filter-group">
                    <label><?php _e('Aktion', 'cpsmartcrm'); ?></label>
                    <select name="filter_action">
                        <option value=""><?php _e('Alle Aktionen', 'cpsmartcrm'); ?></option>
                        <?php foreach ($action_labels as $action_key => $action_label) : ?>
                            <option value="<?php echo esc_attr($action_key); ?>" <?php selected($filter_action, $action_key); ?>>
                                <?php echo esc_html($action_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="crm-audit-filter-group">
                    <label><?php _e('Benutzer', 'cpsmartcrm'); ?></label>
                    <select name="filter_user">
                        <option value=""><?php _e('Alle Benutzer', 'cpsmartcrm'); ?></option>
                        <?php foreach ($users_with_logs as $user) : ?>
                            <option value="<?php echo esc_attr($user->user_id); ?>" <?php selected($filter_user, $user->user_id); ?>>
                                <?php echo esc_html($user->username); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="crm-audit-filter-group">
                    <label><?php _e('Von Datum', 'cpsmartcrm'); ?></label>
                    <input type="date" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>">
                </div>
                
                <div class="crm-audit-filter-group">
                    <label><?php _e('Bis Datum', 'cpsmartcrm'); ?></label>
                    <input type="date" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>">
                </div>
                
                <div class="crm-audit-filter-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Filtern', 'cpsmartcrm'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=audit_log')); ?>" 
                       class="button">
                        <?php _e('Zurücksetzen', 'cpsmartcrm'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Log Table -->
    <div class="crm-audit-log-table">
        <?php if (empty($logs)) : ?>
            <div class="crm-no-logs">
                <div class="crm-no-logs-icon">📋</div>
                <h3><?php _e('Keine Einträge gefunden', 'cpsmartcrm'); ?></h3>
                <p><?php _e('Es wurden noch keine Buchhaltungsaktivitäten protokolliert.', 'cpsmartcrm'); ?></p>
            </div>
        <?php else : ?>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Zeitstempel', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Aktion', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Benutzer', 'cpsmartcrm'); ?></th>
                        <th><?php _e('Details', 'cpsmartcrm'); ?></th>
                        <th><?php _e('IP / User Agent', 'cpsmartcrm'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <?php
                        $log_data = json_decode($log->data, true);
                        $action_label = isset($action_labels[$log->action]) ? $action_labels[$log->action] : $log->action;
                        $action_color = isset($action_colors[$log->action]) ? $action_colors[$log->action] : '#999';
                        ?>
                        <tr>
                            <td>
                                <div class="crm-audit-timestamp">
                                    <?php echo date_i18n('d.m.Y H:i:s', strtotime($log->created_at)); ?>
                                </div>
                            </td>
                            <td>
                                <span class="crm-audit-action-badge" style="background: <?php echo esc_attr($action_color); ?>;">
                                    <?php echo esc_html($action_label); ?>
                                </span>
                            </td>
                            <td>
                                <div class="crm-audit-user">
                                    <?php echo get_avatar($log->user_id, 32, '', '', array('class' => 'crm-audit-user-avatar')); ?>
                                    <div>
                                        <strong><?php echo esc_html($log->username); ?></strong><br>
                                        <small style="color: #999;">ID: <?php echo esc_html($log->user_id); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($log_data) : ?>
                                    <div class="crm-audit-data">
                                        <?php if (isset($log_data['kategorie'])) : ?>
                                            <div class="crm-audit-data-item">
                                                <strong>Kategorie:</strong> <?php echo esc_html($log_data['kategorie']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($log_data['betrag'])) : ?>
                                            <div class="crm-audit-data-item">
                                                <strong>Betrag:</strong> <?php echo number_format($log_data['betrag'], 2, ',', '.'); ?> €
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($log_data['beschreibung']) && !empty($log_data['beschreibung'])) : ?>
                                            <div class="crm-audit-data-item">
                                                <strong>Beschreibung:</strong> <?php echo esc_html($log_data['beschreibung']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($log_data['datum'])) : ?>
                                            <div class="crm-audit-data-item">
                                                <strong>Datum:</strong> <?php echo date_i18n('d.m.Y', strtotime($log_data['datum'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($log_data['typ'])) : ?>
                                            <div class="crm-audit-data-item">
                                                <strong>Typ:</strong> <?php echo esc_html($log_data['typ']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($log->transaction_id) : ?>
                                            <div class="crm-audit-data-item">
                                                <strong>Transaktions-ID:</strong> <?php echo esc_html($log->transaction_id); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log->ip_address) : ?>
                                    <div class="crm-audit-ip">
                                        <strong>IP:</strong> <?php echo esc_html($log->ip_address); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($log->user_agent) : ?>
                                    <div class="crm-audit-ip" style="margin-top: 5px;">
                                        <strong>UA:</strong> <?php echo esc_html(substr($log->user_agent, 0, 50)); ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
