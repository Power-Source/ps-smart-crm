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

// Load helpers
require_once dirname(__FILE__) . '/helpers.php';

// Available tabs
$available_tabs = array(
    'dashboard' => array(
        'label' => __('Dashboard', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-dashboard',
        'file' => 'tabs/dashboard.php',
        'capability' => 'manage_crm',
    ),
    'bookings' => array(
        'label' => __('Buchungen', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-list',
        'file' => 'tabs/bookings.php',
        'capability' => 'manage_crm',
    ),
    'settings' => array(
        'label' => __('Einstellungen', 'cpsmartcrm'),
        'icon' => 'glyphicon glyphicon-cog',
        'file' => 'tabs/settings.php',
        'capability' => 'manage_crm',
    ),
);

// Allow third-party plugins to add tabs
$available_tabs = apply_filters('WPsCRM_accounting_tabs', $available_tabs);

// Determine active tab
$active_tab = sanitize_key($_GET['tab'] ?? 'dashboard');

// Validate tab exists and user has capability
if (!isset($available_tabs[$active_tab]) || !current_user_can($available_tabs[$active_tab]['capability'])) {
    $active_tab = 'dashboard';
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
            <?php if (current_user_can($tab_info['capability'])) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=smart-crm&accounting_tab=' . $tab_key)); ?>" 
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
