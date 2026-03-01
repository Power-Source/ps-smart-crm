<?php
/**
 * Frontend System Initialization
 * 
 * Registriert Shortcodes für Agent Dashboard & Customer Portal
 * Aktiviert Frontend-Komponenten basierend auf Settings
 */

if (!defined('ABSPATH')) exit;

/**
 * Register Frontend Shortcodes
 */
function wpscrm_register_frontend_shortcodes() {
    add_shortcode('crm_agent_dashboard', 'wpscrm_render_agent_dashboard');
    add_shortcode('crm_customer_portal', 'wpscrm_render_customer_portal');
}
add_action('init', 'wpscrm_register_frontend_shortcodes');

/**
 * Agent Dashboard Shortcode Handler
 */
function wpscrm_render_agent_dashboard($atts) {
    // Dashboard ist OFFEN, aber Inhalte unterschiedlich basierend auf User
    $current_user = wp_get_current_user();
    
    // Check if user is agent (Agent-Rolle prüfen)
    $is_agent = is_user_logged_in() && (
        (function_exists('wpscrm_is_user_agent') && wpscrm_is_user_agent($current_user->ID)) ||
        current_user_can('manage_options')
    );
    
    // Load Agent Dashboard
    ob_start();
    include dirname(__FILE__) . '/modules/agent-dashboard.php';
    return ob_get_clean();
}

/**
 * Customer Portal Shortcode Handler
 */
function wpscrm_render_customer_portal($atts) {
    // Portal ist OFFEN, aber Inhalte unterschiedlich basierend auf User
    $current_user = wp_get_current_user();
    
    // Load Customer Portal
    ob_start();
    include dirname(__FILE__) . '/modules/customer-portal.php';
    return ob_get_clean();
}

/**
 * Enqueue Frontend Assets
 */
function wpscrm_enqueue_frontend_assets() {
    wp_enqueue_style(
        'crm-frontend-style',
        plugins_url('assets/css/frontend-theme.css', dirname(__FILE__)),
        array(),
        WPSCRM_VERSION
    );
    
    wp_enqueue_script(
        'crm-frontend-script',
        plugins_url('assets/js/frontend-common.js', dirname(__FILE__)),
        array('jquery'),
        WPSCRM_VERSION,
        true
    );
    
    // Localize script für AJAX
    wp_localize_script('crm-frontend-script', 'crmFrontend', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('crm_frontend_nonce'),
        'current_user_id' => get_current_user_id(),
    ));
}
add_action('wp_enqueue_scripts', 'wpscrm_enqueue_frontend_assets');

/**
 * Helper: Get customer by email
 */
function wpscrm_get_customer_by_email($email) {
    global $wpdb;
    $customer = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM " . WPsCRM_TABLE . "contacts WHERE email = %s AND deleted = 0 LIMIT 1",
        $email
    ));
    return $customer ?: null;
}

/**
 * Check if user is agent
 */
if (!function_exists('wpscrm_is_user_agent')) {
    function wpscrm_is_user_agent($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        $agent = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . WPsCRM_TABLE . "agents WHERE user_id = %d",
            $user_id
        ));
        
        return !empty($agent);
    }
}

/**
 * AJAX: Create Frontend Page
 */
function wpscrm_create_frontend_page_callback() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crm_create_frontend_page')) {
        wp_send_json_error(array('message' => 'Sicherheitsüberprüfung fehlgeschlagen.'));
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Sie haben keine Berechtigung, diese Aktion durchzuführen.'));
    }

    $page_type = sanitize_text_field($_POST['page_type']);
    
    // Determine page title & content based on type
    if ($page_type === 'intranet') {
        $page_title = 'Intranet - Agenten-Dashboard';
        $page_content = '[crm_agent_dashboard]';
    } elseif ($page_type === 'customer') {
        $page_title = 'Kundenzone - Kundenkonto';
        $page_content = '[crm_customer_portal]';
    } else {
        wp_send_json_error(array('message' => 'Ungültiger Seitentyp.'));
    }

    // Create WordPress page
    $page_id = wp_insert_post(array(
        'post_type'    => 'page',
        'post_title'   => $page_title,
        'post_content' => $page_content,
        'post_status'  => 'publish',
    ));

    if (is_wp_error($page_id)) {
        wp_send_json_error(array('message' => 'Seite konnte nicht erstellt werden.'));
    }

    // Save to settings
    $options = get_option('CRM_frontend_settings', array());
    if ($page_type === 'intranet') {
        $options['frontend_intranet_page'] = $page_id;
    } else {
        $options['frontend_customer_page'] = $page_id;
    }
    update_option('CRM_frontend_settings', $options);

    wp_send_json_success(array(
        'page_id' => $page_id,
        'page_title' => $page_title,
        'message' => sprintf(__('%s wurde erfolgreich erstellt!', 'cpsmartcrm'), $page_title)
    ));
}
add_action('wp_ajax_crm_create_frontend_page', 'wpscrm_create_frontend_page_callback');

/**
 * Load Frontend API Handlers
 */
require_once dirname(__FILE__) . '/api/agent-api.php';
require_once dirname(__FILE__) . '/api/customer-api.php';

