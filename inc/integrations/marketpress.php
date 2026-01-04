<?php
/**
 * MarketPress accounting integration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ensure incomes table exists (create if missing)
 */
function WPsCRM_mp_ensure_incomes_table() {
    global $wpdb;
    $table = WPsCRM_TABLE . 'incomes';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE `" . $table . "` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `data` date NOT NULL,
            `kategoria` varchar(100) NOT NULL,
            `descrizione` text NOT NULL,
            `imponibile` float(9,2) unsigned NOT NULL,
            `aliquota` float(4,2) unsigned NOT NULL DEFAULT '19',
            `imposta` float(9,2) unsigned NOT NULL,
            `totale` float(9,2) unsigned NOT NULL,
            `created_at` datetime NOT NULL,
            `updated_at` datetime NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `data` (`data`),
            KEY `kategoria` (`kategoria`)
        ) ENGINE=MyISAM " . $charset_collate . " AUTO_INCREMENT=1;";
        
        dbDelta($sql);
        error_log('[WPsCRM MP] Created missing incomes table: ' . $table);
    }
}

add_filter('WPsCRM_accounting_integrations', function ($integrations) {
    // Remove WooCommerce placeholder and register MarketPress
    if (isset($integrations['woocommerce'])) {
        unset($integrations['woocommerce']);
    }

    $integrations['marketpress'] = array(
        'name' => 'MarketPress',
        'description' => __('Automatisierte Erfassung bezahlter Shop-Bestellungen als Einnahmen', 'cpsmartcrm'),
        'plugin' => 'marketpress/marketpress.php',
        'download_url' => 'https://github.com/Power-Source/marketpress/releases/latest/download/marketpress.zip',
        'icon' => 'glyphicon glyphicon-shopping-cart',
        'status' => 'available',
        'fields' => array(),
    );

    return $integrations;
});

/**
 * Resolve product tax rate (fractional, e.g. 0.19) from MarketPress product meta.
 */
function WPsCRM_mp_get_tax_rate_for_product(MP_Product $product)
{
    $charge_tax = $product->get_meta('charge_tax');
    $rate_meta = $product->get_meta('special_tax_rate');

    // No special handling: fall back to global rate
    if (empty($charge_tax)) {
        return (float) mp_tax_rate();
    }

    if (is_string($rate_meta) && strpos($rate_meta, '%') !== false) {
        return (float) preg_replace('/[^0-9.]/', '', $rate_meta) / 100;
    }

    if ($rate_meta === '' || $rate_meta === null) {
        return (float) mp_tax_rate();
    }

    $numeric_rate = (float) $rate_meta;

    if ($numeric_rate > 1) {
        $net_unit = (float) $product->before_tax_price();
        if ($net_unit > 0) {
            return $numeric_rate / $net_unit;
        }
    }

    return $numeric_rate;
}

/**
 * Get order total (gross) from meta; zero if missing.
 */
function WPsCRM_mp_get_order_total($order)
{
    $total = 0;

    if ($order instanceof MP_Order) {
        $total = (float) $order->get_meta('mp_order_total', 0);

        if ($total <= 0 && method_exists($order, 'get_cart')) {
            $cart = $order->get_cart();
            if ($cart instanceof MP_Cart) {
                $total = (float) $cart->total(false);
            }
        }

        if ($total <= 0) {
            $total = (float) $order->get_meta('mp_payment_info->total', 0);
        }
    }

    return $total;
}

function WPsCRM_mp_log($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[WPsCRM MP] ' . $message);
    }
}

/**
 * Insert income rows when a MarketPress order is marked as paid.
 */
function WPsCRM_mp_sync_paid_order($order)
{
    if (!class_exists('MP_Order') || !class_exists('MP_Cart')) {
        return;
    }

    if (!$order instanceof MP_Order) {
        return;
    }

    // Ignore free/zero orders
    $order_total = WPsCRM_mp_get_order_total($order);
    if ($order_total <= 0) {
        return;
    }

    // Guard against duplicates - check if already synced
    $existing = get_post_meta($order->ID, '_wpscrm_income_ids', true);
    if (!empty($existing) && is_array($existing) && count($existing) > 0) {
        WPsCRM_mp_log('order ' . $order->ID . ' already synced');
        return;
    }

    $cart = $order->get_cart();
    if (!$cart instanceof MP_Cart) {
        return;
    }

    WPsCRM_mp_log('sync order ' . $order->ID . ' total ' . $order_total);

    $paid_time = (int) $order->get_meta('mp_paid_time', time());
    $date = date('Y-m-d', $paid_time);
    $created_at = date('Y-m-d H:i:s', $paid_time);

    $invoice_number = $order->get_meta('_mp_invoice_number', '');
    if (empty($invoice_number)) {
        $invoice_number = $order->get_id();
    }

    $admin_link = add_query_arg(
        array(
            'post' => $order->ID,
            'action' => 'edit',
        ),
        admin_url('post.php')
    );

    $description = sprintf(
        __('MarketPress Bestellung %s - %s', 'cpsmartcrm'),
        $order->get_id(),
        sprintf('<a href="%s" target="_blank">%s</a>', esc_url($admin_link), esc_html($invoice_number))
    );

    $totals_by_rate = array();

    foreach ($cart->get_items_as_objects() as $product) {
        $qty = isset($product->qty) ? (float) $product->qty : 1;
        $net_unit = (float) $product->before_tax_price();
        $gross_unit = (float) $product->get_price('lowest');
        $rate = WPsCRM_mp_get_tax_rate_for_product($product);

        $net_total = $net_unit * $qty;
        $tax_total = max(($gross_unit - $net_unit) * $qty, 0);

        if ($tax_total <= 0 && $rate > 0) {
            $tax_total = $net_total * $rate;
        }

        $gross_total = $net_total + $tax_total;
        $rate_key = number_format($rate * 100, 2, '.', '');

        if (!isset($totals_by_rate[$rate_key])) {
            $totals_by_rate[$rate_key] = array('net' => 0, 'tax' => 0, 'gross' => 0);
        }

        $totals_by_rate[$rate_key]['net'] += $net_total;
        $totals_by_rate[$rate_key]['tax'] += $tax_total;
        $totals_by_rate[$rate_key]['gross'] += $gross_total;
    }

    // Include shipping totals using recorded tax split
    $shipping_total = (float) $order->get_meta('mp_shipping_total', 0);
    $shipping_tax = (float) $order->get_meta('mp_shipping_tax', 0);
    if ($shipping_total > 0) {
        $net_shipping = max($shipping_total - $shipping_tax, 0);
        $rate_ship = ($net_shipping > 0) ? ($shipping_tax / $net_shipping) : (float) mp_tax_rate();
        $rate_key = number_format($rate_ship * 100, 2, '.', '');

        if (!isset($totals_by_rate[$rate_key])) {
            $totals_by_rate[$rate_key] = array('net' => 0, 'tax' => 0, 'gross' => 0);
        }

        $totals_by_rate[$rate_key]['net'] += $net_shipping;
        $totals_by_rate[$rate_key]['tax'] += $shipping_tax;
        $totals_by_rate[$rate_key]['gross'] += $shipping_total;
    }

    if (empty($totals_by_rate) && $order_total > 0) {
        $totals_by_rate['0.00'] = array(
            'net' => (float) $order_total,
            'tax' => 0,
            'gross' => (float) $order_total,
        );
    }

    if (empty($totals_by_rate)) {
        return;
    }

    global $wpdb;
    $table = WPsCRM_TABLE . 'incomes';
    
    // Ensure incomes table exists
    WPsCRM_mp_ensure_incomes_table();
    
    // Double-check table still exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        error_log('[WPsCRM MP] incomes table could not be created: ' . $table);
        return;
    }
    
    $inserted_ids = array();
    $insert_count = 0;

    foreach ($totals_by_rate as $rate_key => $totals) {
        $net = round($totals['net'], 2);
        $tax = round($totals['tax'], 2);
        $gross = round($totals['gross'], 2);
        $percentage = (float) $rate_key;

        $inserted = $wpdb->insert(
            $table,
            array(
                'data' => $date,
                'kategoria' => 'eCommerce',
                'descrizione' => $description,
                'imponibile' => $net,
                'aliquota' => $percentage,
                'imposta' => $tax,
                'totale' => $gross,
                'created_at' => $created_at,
            ),
            array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s')
        );

        if ($inserted) {
            $inserted_ids[] = $wpdb->insert_id;
            $insert_count++;
            
            // Save meta immediately after first insert to prevent race conditions
            if ($insert_count === 1) {
                update_post_meta($order->ID, '_wpscrm_income_ids', array('processing'));
                WPsCRM_mp_log('locked order ' . $order->ID . ' to prevent duplicate processing');
            }
        } else {
            WPsCRM_mp_log('insert failed for order ' . $order->ID . ' err: ' . $wpdb->last_error);
        }
    }

    // Update meta with final list of inserted IDs
    if (!empty($inserted_ids)) {
        update_post_meta($order->ID, '_wpscrm_income_ids', $inserted_ids);
        WPsCRM_mp_log('synced order ' . $order->ID . ' with ' . count($inserted_ids) . ' income entries');
    }
}

/**
 * Clean up duplicate income entries from the same order
 */
function WPsCRM_mp_cleanup_duplicates() {
    global $wpdb;
    $table = WPsCRM_TABLE . 'incomes';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return;
    }
    
    // Find and remove duplicate MarketPress entries (same date, category, and amount)
    $duplicates = $wpdb->get_results(
        "SELECT descrizione, data, kategoria, imponibile, aliquota, COUNT(*) as cnt 
         FROM {$table}
         WHERE kategoria = 'eCommerce' 
         GROUP BY data, kategoria, imponibile, aliquota
         HAVING cnt > 1"
    );
    
    if (!empty($duplicates)) {
        foreach ($duplicates as $dup) {
            // Get IDs of duplicates for this entry
            $dup_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM {$table}
                     WHERE data = %s AND kategoria = %s AND imponibile = %f AND aliquota = %f
                     ORDER BY id DESC
                     LIMIT 999 OFFSET 1",
                    $dup->data,
                    $dup->kategoria,
                    $dup->imponibile,
                    $dup->aliquota
                )
            );
            
            // Delete duplicates, keeping the first one
            if (!empty($dup_ids)) {
                foreach ($dup_ids as $id) {
                    $wpdb->delete($table, array('id' => $id));
                    error_log('[WPsCRM MP] Removed duplicate income entry ID ' . $id);
                }
            }
        }
    }
}

add_action('admin_init', 'WPsCRM_mp_cleanup_duplicates', 9); // Run before backfill

/**
 * Backfill existing paid orders without income entries (admin side).
 */
function WPsCRM_mp_backfill_paid_orders()
{
    if (!class_exists('MP_Order')) {
        return;
    }

    if (!current_user_can('manage_crm')) {
        return;
    }

    if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
        return; // Avoid running during admin-ajax to prevent MP order admin hooks from firing without context
    }

    $args = array(
        'post_type' => 'mp_order',
        'post_status' => 'order_paid',
        'posts_per_page' => 25,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_wpscrm_income_ids',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => '_wpscrm_income_ids',
                'value' => '',
                'compare' => '=',
            ),
        ),
        'orderby' => 'date',
        'order' => 'DESC',
        'suppress_filters' => true, // Skip other plugins' pre_get_posts filters that expect admin screen context
    );

    $query = new WP_Query($args);
    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $order = new MP_Order($post->ID);
            if (WPsCRM_mp_get_order_total($order) <= 0) {
                continue;
            }
            WPsCRM_mp_sync_paid_order($order);
        }
    }
}

add_action('admin_init', 'WPsCRM_mp_backfill_paid_orders');

/**
 * Catch payment completion to sync immediately.
 * Use only the main MarketPress hook to avoid duplicates.
 */
add_action('mp_order_paid', 'WPsCRM_mp_sync_paid_order');
