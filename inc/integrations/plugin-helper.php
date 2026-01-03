<?php
/**
 * Integration plugin utilities (install/activate checks)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('WPsCRM_integration_get_plugin_status')) {
    /**
     * Get plugin status: active, inactive, or missing.
     */
    function WPsCRM_integration_get_plugin_status($plugin_file)
    {
        if (empty($plugin_file)) {
            return 'missing';
        }

        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (is_plugin_active($plugin_file) || is_plugin_active_for_network($plugin_file)) {
            return 'active';
        }

        $all_plugins = get_plugins();
        return isset($all_plugins[$plugin_file]) ? 'inactive' : 'missing';
    }
}

if (!function_exists('WPsCRM_integration_install_plugin')) {
    /**
     * Install a plugin from a remote zip.
     */
    function WPsCRM_integration_install_plugin($download_url)
    {
        if (!current_user_can('install_plugins')) {
            return new WP_Error('wpscrm_no_cap', __('Du darfst keine Plugins installieren.', 'cpsmartcrm'));
        }

        if (empty($download_url)) {
            return new WP_Error('wpscrm_no_url', __('Kein Download-Link angegeben.', 'cpsmartcrm'));
        }

        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $skin = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result = $upgrader->install($download_url);

        if (is_wp_error($result)) {
            return $result;
        }

        if (!$result) {
            return new WP_Error('wpscrm_install_failed', __('Die Installation ist fehlgeschlagen.', 'cpsmartcrm'));
        }

        return true;
    }
}
