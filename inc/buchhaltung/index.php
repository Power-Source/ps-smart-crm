<?php
/**
 * Buchhaltungs-System - Hauptverwaltung
 * 
 * Verwaltet alle Buchhaltungs-Tabs und deren Inhalte
 * Modulares System für flexible Erweiterung
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_options');

if (!$is_admin && function_exists('wpscrm_user_can') && !wpscrm_user_can($current_user_id, 'can_view_accounting')) {
	wp_die(__('Du hast keine Berechtigung für die Buchhaltung.', 'cpsmartcrm'));
}

// Load helpers
require_once dirname(__FILE__) . '/helpers.php';

// Load timetracking integration module
require_once dirname(__FILE__) . '/modules/timetracking-integration.php';

// Load accounting period filters module
require_once dirname(__FILE__) . '/modules/accounting-period-filters.php';

// Load chart and analytics modules
require_once dirname(__FILE__) . '/modules/accounting-charts.php';
require_once dirname(__FILE__) . '/modules/cashflow-analytics.php';

// Load PDF export module
require_once dirname(__FILE__) . '/modules/accounting-pdf-export.php';

// Load smart financial alerts module
require_once dirname(__FILE__) . '/modules/smart-financial-alerts.php';

// Load AJAX handlers for accounting
require_once dirname(__FILE__) . '/ajax-handlers.php';

// Available tabs
$available_tabs = array(
    'dashboard' => array(
        'label' => __('Dashboard', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-dashboard',
        'file' => 'tabs/dashboard.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_view_all_accounting',
    ),
    'accounting' => array(
        'label' => __('Buchhaltung', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-book',
        'file' => 'tabs/accounting.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_view_accounting',
    ),
    'abrechnung' => array(
        'label' => __('Abrechnung (Zeiterfassung)', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-time',
        'file' => 'tabs/abrechnung.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_view_accounting',
    ),
    'belege' => array(
        'label' => __('Belege', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-folder-open',
        'file' => 'tabs/belege.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_view_documents',
    ),
    'settings' => array(
        'label' => __('Einstellungen', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-cog',
        'file' => 'tabs/settings.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_edit_accounting',
    ),
    'integrations' => array(
        'label' => __('Integrationen', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-plug',
        'file' => 'tabs/integrations.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_edit_accounting',
    ),
    'analytics' => array(
        'label' => __('Analytics', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-stats',
        'file' => 'tabs/analytics.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_view_accounting',
    ),
    'statistics' => array(
        'label' => __('Statistik', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-stats',
        'file' => 'tabs/statistics.php',
        'capability' => 'manage_crm',
        'permission_key' => 'can_view_all_accounting',
    ),
    'audit_log' => array(
        'label' => __('Audit-Log', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-list-alt',
        'file' => 'tabs/audit-log.php',
        'capability' => 'manage_options', // Nur Admin + Chef
        'permission_key' => 'chef_role', // Chef-Rolle Zugriff
    ),
);

// Allow third-party plugins to add tabs
$available_tabs = apply_filters('WPsCRM_accounting_tabs', $available_tabs);

// Determine active tab
$active_tab = sanitize_key($_GET['accounting_tab'] ?? 'dashboard');

$wpscrm_tab_allowed = function($tab_info) use ($is_admin, $current_user_id) {
    if (!current_user_can($tab_info['capability'])) {
        return false;
    }
    if ($is_admin) {
        return true;
    }
    if (!empty($tab_info['permission_key']) && function_exists('wpscrm_user_can')) {
        return wpscrm_user_can($current_user_id, $tab_info['permission_key']);
    }
    return true;
};

// Validate tab exists and user has capability
if (!isset($available_tabs[$active_tab]) || !$wpscrm_tab_allowed($available_tabs[$active_tab])) {
    $active_tab = 'dashboard';
}

if (!isset($available_tabs[$active_tab]) || !$wpscrm_tab_allowed($available_tabs[$active_tab])) {
    wp_die(__('Kein erlaubter Buchhaltungs-Tab verfügbar.', 'cpsmartcrm'));
}

// Get tab file path
$tab_file = dirname(__FILE__) . '/' . $available_tabs[$active_tab]['file'];

if (!file_exists($tab_file)) {
    wp_die(__('Tab-Datei nicht gefunden.', 'cpsmartcrm'));
}
?>
<div class="wrap">
    <h1>
        <i class="glyphicon glyphicon-book" style="margin-right: 8px;"></i>
        <?php _e('Buchhaltung', 'cpsmartcrm'); ?>
    </h1>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper" style="background: #fff; border-bottom: 1px solid #ccc; margin-bottom: 20px;">
        <?php foreach ($available_tabs as $tab_key => $tab_info) : ?>
                        <?php if ($wpscrm_tab_allowed($tab_info)) : ?>
                     <a href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&p=buchhaltung/index.php&accounting_tab=' . $tab_key)); ?>" 
                   class="nav-tab <?php echo ($active_tab === $tab_key) ? 'nav-tab-active' : ''; ?>" 
                   style="padding: 12px 16px; display: inline-flex; align-items: center; gap: 8px;">
                    <?php if (!empty($tab_info['icon'])) : ?>
                        <i class="<?php echo esc_attr($tab_info['icon']); ?>"></i>
                    <?php endif; ?>
                    <?php echo esc_html($tab_info['label']); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Tab Content -->
    <div class="tab-content">
        <?php
        // Load and display the active tab content
        include $tab_file;
        ?>
    </div>
</div>

<?php
/**
 * Hook: WPsCRM_accounting_after
 * Allows plugins to add content after accounting system
 */
do_action('WPsCRM_accounting_after');
?>
