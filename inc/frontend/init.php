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
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div style="background: #fff3cd; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>⛔ Zugriff verweigert</h3>
                    <p>Diese Seite ist nur für angemeldete Mitarbeiter verfügbar.</p>
                </div>';
    }
    
    $current_user = wp_get_current_user();
    
    // Check if user is agent (Agent-Rolle prüfen)
    $is_agent = function_exists('wpscrm_is_user_agent') 
        ? wpscrm_is_user_agent($current_user->ID) 
        : current_user_can('manage_crm');
    
    if (!$is_agent && !current_user_can('manage_options')) {
        return '<div style="background: #ffcdd2; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>❌ Keine Berechtigung</h3>
                    <p>Du hast keine Berechtigung, das Agent-Dashboard zu sehen.</p>
                </div>';
    }
    
    // Load Agent Dashboard
    ob_start();
    include dirname(__FILE__) . '/modules/agent-dashboard.php';
    return ob_get_clean();
}

/**
 * Customer Portal Shortcode Handler
 */
function wpscrm_render_customer_portal($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div style="background: #fff3cd; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>🔐 Anmeldung erforderlich</h3>
                    <p>Bitte melden Sie sich an, um Ihre Rechnungen und Angebote zu sehen.</p>
                    ' . wp_login_form(array('echo' => false)) . '
                </div>';
    }
    
    $current_user = wp_get_current_user();
    
    // Check if user is customer
    $customer_id = wpscrm_get_customer_by_email($current_user->user_email);
    
    if (!$customer_id) {
        return '<div style="background: #ffcdd2; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>⚠️ Kein Kundenkonto</h3>
                    <p>Es konnte kein Kundenkonto für Ihre E-Mail-Adresse gefunden werden.</p>
                    <p><a href="' . wp_logout_url() . '">Logout</a></p>
                </div>';
    }
    
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
 * Helper: Check if user is agent
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
 * Load Frontend API Handlers
 */
require_once dirname(__FILE__) . '/api/agent-api.php';
require_once dirname(__FILE__) . '/api/customer-api.php';
