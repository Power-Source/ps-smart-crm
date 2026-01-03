<?php
/**
 * Buchhaltungs-Integrationen Tab
 * 
 * Modulares System für Plugin-Integrationen
 * Andere PSOURCE Plugins können ihre Integrationen registrieren
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

/**
 * Get registered accounting integrations
 * Plugins can add integrations via do_action('WPsCRM_register_integrations')
 */
$integrations = array(
    'woocommerce' => array(
        'name' => 'WooCommerce Shop',
        'description' => __('Synchronisiere Bestellungen und Umsätze direkt aus deinem Online-Shop', 'cpsmartcrm'),
        'plugin' => 'woocommerce/woocommerce.php',
        'icon' => 'glyphicon glyphicon-shopping-cart',
        'status' => 'available',
        'fields' => array(
            'sync_orders' => array(
                'type' => 'checkbox',
                'label' => __('Bestellungen synchronisieren', 'cpsmartcrm'),
                'description' => __('Neue Bestellungen werden automatisch als Rechnungen angelegt', 'cpsmartcrm'),
            ),
            'sync_interval' => array(
                'type' => 'select',
                'label' => __('Sync-Intervall', 'cpsmartcrm'),
                'options' => array(
                    'realtime' => __('Echtzeit', 'cpsmartcrm'),
                    'hourly' => __('Stündlich', 'cpsmartcrm'),
                    'daily' => __('Täglich', 'cpsmartcrm'),
                ),
            ),
        ),
    ),
    'events' => array(
        'name' => 'Event Booking',
        'description' => __('Automatisiere Ticketverkäufe und Einnahmen aus Eventbuchungen', 'cpsmartcrm'),
        'plugin' => 'wp-smart-crm-events/wp-smart-crm-events.php',
        'icon' => 'glyphicon glyphicon-calendar',
        'status' => 'available',
        'fields' => array(
            'sync_bookings' => array(
                'type' => 'checkbox',
                'label' => __('Buchungen als Einnahmen erfassen', 'cpsmartcrm'),
            ),
            'tax_rate' => array(
                'type' => 'select',
                'label' => __('Steuersatz für Events', 'cpsmartcrm'),
                'options' => 'tax_rates', // Special key to load from settings
            ),
        ),
    ),
    'blog' => array(
        'name' => 'Blog Monetization',
        'description' => __('Verfolge Bloghosting-Gebühren und Sponsorship-Einnahmen', 'cpsmartcrm'),
        'plugin' => 'wp-smart-crm-blog/wp-smart-crm-blog.php',
        'icon' => 'glyphicon glyphicon-pencil',
        'status' => 'planned',
        'fields' => array(),
    ),
);

// Allow plugins to register integrations
$integrations = apply_filters('WPsCRM_accounting_integrations', $integrations);

// Load active integration settings
$integration_options = get_option('CRM_accounting_integrations', array());

// Handle integration settings save
if (isset($_POST['save_integration']) && check_admin_referer('integration_settings_nonce')) {
    $integration_id = sanitize_key($_POST['integration_id']);
    
    if (isset($integrations[$integration_id])) {
        $settings = array();
        
        // Save fields
        if (!empty($integrations[$integration_id]['fields'])) {
            foreach ($integrations[$integration_id]['fields'] as $field_key => $field_config) {
                if ($field_config['type'] === 'checkbox') {
                    $settings[$field_key] = isset($_POST[$field_key]) ? 1 : 0;
                } elseif ($field_config['type'] === 'select' || $field_config['type'] === 'text') {
                    $settings[$field_key] = sanitize_text_field($_POST[$field_key] ?? '');
                }
            }
        }
        
        $integration_options[$integration_id] = $settings;
        update_option('CRM_accounting_integrations', $integration_options);
        
        echo '<div class="notice notice-success"><p>' . sprintf(__('%s-Integration gespeichert.', 'cpsmartcrm'), esc_html($integrations[$integration_id]['name'])) . '</p></div>';
    }
}

?>

<h2><?php _e('Integrationen', 'cpsmartcrm'); ?></h2>

<p style="color: #666; margin-bottom: 20px;">
    <?php _e('Verbinde deine Buchhaltung mit anderen PSOURCE Plugins. Automatische Synchronisation von Umsätzen und Transaktionen.', 'cpsmartcrm'); ?>
</p>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
    <?php foreach ($integrations as $integration_id => $integration) : ?>
        <div class="card" style="padding: 20px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <?php if (!empty($integration['icon'])) : ?>
                    <i class="<?php echo esc_attr($integration['icon']); ?>" style="font-size: 32px; color: #0073aa;"></i>
                <?php endif; ?>
                <div>
                    <h3 style="margin: 0;"><?php echo esc_html($integration['name']); ?></h3>
                    <?php if ($integration['status'] !== 'available') : ?>
                        <small style="color: #999;">
                            <?php 
                            if ($integration['status'] === 'planned') {
                                _e('Geplant für später', 'cpsmartcrm');
                            } elseif ($integration['status'] === 'beta') {
                                _e('Beta-Version', 'cpsmartcrm');
                            }
                            ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <p style="color: #666; font-size: 13px; margin-bottom: 16px;">
                <?php echo esc_html($integration['description']); ?>
            </p>

            <?php if ($integration['status'] === 'available') : ?>
                <form method="post" action="">
                    <?php wp_nonce_field('integration_settings_nonce'); ?>
                    <input type="hidden" name="integration_id" value="<?php echo esc_attr($integration_id); ?>" />
                    <input type="hidden" name="save_integration" value="1" />

                    <?php if (!empty($integration['fields'])) : ?>
                        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #eee;">
                            <?php foreach ($integration['fields'] as $field_key => $field_config) : ?>
                                <?php 
                                $current_value = $integration_options[$integration_id][$field_key] ?? '';
                                
                                // Load tax rates for special field type
                                if ($field_config['options'] === 'tax_rates') {
                                    $field_config['options'] = array();
                                    $tax_rates = WPsCRM_get_tax_rates();
                                    foreach ($tax_rates as $rate) {
                                        $field_config['options'][$rate['percentage']] = $rate['label'];
                                    }
                                }
                                ?>
                                
                                <?php if ($field_config['type'] === 'checkbox') : ?>
                                    <div style="margin-bottom: 12px;">
                                        <label>
                                            <input type="checkbox" name="<?php echo esc_attr($field_key); ?>" value="1" <?php checked($current_value, 1); ?> />
                                            <strong><?php echo esc_html($field_config['label']); ?></strong>
                                        </label>
                                        <?php if (!empty($field_config['description'])) : ?>
                                            <p style="color: #666; font-size: 12px; margin: 4px 0 0 24px;">
                                                <?php echo esc_html($field_config['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($field_config['type'] === 'select') : ?>
                                    <div style="margin-bottom: 12px;">
                                        <label for="<?php echo esc_attr($field_key); ?>">
                                            <strong><?php echo esc_html($field_config['label']); ?></strong>
                                        </label>
                                        <select id="<?php echo esc_attr($field_key); ?>" name="<?php echo esc_attr($field_key); ?>" class="widefat" style="margin-top: 4px;">
                                            <option value=""><?php _e('Wählen', 'cpsmartcrm'); ?></option>
                                            <?php foreach ($field_config['options'] as $opt_value => $opt_label) : ?>
                                                <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($current_value, $opt_value); ?>>
                                                    <?php echo esc_html($opt_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="button button-primary button-block" style="width: 100%;">
                            <?php _e('Speichern', 'cpsmartcrm'); ?>
                        </button>
                    <?php else : ?>
                        <button type="button" class="button button-primary button-block" style="width: 100%;" disabled>
                            <?php _e('✓ Aktiviert', 'cpsmartcrm'); ?>
                        </button>
                    <?php endif; ?>
                </form>
            <?php else : ?>
                <button type="button" class="button button-block" style="width: 100%;" disabled>
                    <?php 
                    if ($integration['status'] === 'planned') {
                        _e('Kommt bald', 'cpsmartcrm');
                    }
                    ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="card" style="max-width: 960px; padding: 20px; margin-top: 20px;">
    <h3><?php _e('Custom Integration?', 'cpsmartcrm'); ?></h3>
    <p style="color: #666;">
        <?php _e('Du möchtest eine eigene Integration entwickeln? Nutze den Hook:', 'cpsmartcrm'); ?><br/>
        <code style="background: #f0f0f0; padding: 4px 8px; border-radius: 3px;">do_action('WPsCRM_register_integrations')</code>
    </p>
    <p style="color: #666; font-size: 12px;">
        <?php _e('Weitere Infos in der Dokumentation oder kontaktiere uns unter support@psource.de', 'cpsmartcrm'); ?>
    </p>
</div>
