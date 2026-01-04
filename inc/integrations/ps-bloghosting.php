<?php
/**
 * PS-Bloghosting (ProSites) accounting integration
 * Auto-books paid blog hosting subscriptions as incomes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ensure incomes table exists (create if missing)
 */
function WPsCRM_bh_ensure_incomes_table() {
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
        error_log('[WPsCRM BH] Created missing incomes table: ' . $table);
    }
}

add_filter('WPsCRM_accounting_integrations', function ($integrations) {
    // Remove placeholder and register PS-Bloghosting
    if (isset($integrations['woocommerce'])) {
        unset($integrations['woocommerce']);
    }

    $integrations['ps-bloghosting'] = array(
        'name' => 'PS-Bloghosting',
        'description' => __('Automatische Erfassung bezahlter Blog-Hosting Abonnements als Einnahmen', 'cpsmartcrm'),
        'plugin' => 'ps-bloghosting/pro-sites.php',
        'download_url' => 'https://github.com/Power-Source/ps-bloghosting/releases/latest/download/ps-bloghosting.zip',
        'icon' => 'glyphicon glyphicon-globe',
        'status' => 'available',
        'fields' => array(
            'sync_enabled' => array(
                'type' => 'checkbox',
                'label' => __('Automatische Synchronisation aktivieren', 'cpsmartcrm'),
                'description' => __('Bezahlte Blog-Hosting Abonnements werden automatisch in Echtzeit als Einnahmen erfasst', 'cpsmartcrm'),
            ),
        ),
    );

    return $integrations;
});

/**
 * Get blog/site name for transaction
 */
function WPsCRM_bh_get_blog_name($blog_id)
{
    $blog_name = get_blog_option($blog_id, 'blogname');
    if (!empty($blog_name)) {
        return sanitize_text_field($blog_name);
    }
    return 'Blog #' . $blog_id;
}

/**
 * Log helper
 */
function WPsCRM_bh_log($message)
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[WPsCRM BH] ' . $message);
    }
}

/**
 * Main hook: Record all ProSites transactions
 */
function WPsCRM_bh_sync_transaction($transaction)
{
    // Check if integration is enabled
    $integration_options = get_option('CRM_accounting_integrations', array());
    if (empty($integration_options['ps-bloghosting']['sync_enabled'])) {
        return; // Integration disabled
    }

    if (!class_exists('ProSites')) {
        return;
    }

    // Sanity checks
    if (empty($transaction) || !is_object($transaction)) {
        return;
    }

    if (empty($transaction->blog_id) || empty($transaction->total)) {
        return;
    }

    $blog_id = (int) $transaction->blog_id;
    $total = (float) $transaction->total;

    // Skip free/zero transactions
    if ($total <= 0) {
        WPsCRM_bh_log('skip transaction for blog ' . $blog_id . ' (zero total)');
        return;
    }

    // Guard against duplicates - check if already synced
    $existing = get_blog_option($blog_id, '_wpscrm_bh_income_ids', false);
    if (!empty($existing) && is_array($existing)) {
        WPsCRM_bh_log('blog ' . $blog_id . ' already synced');
        return;
    }

    // Ensure table exists
    WPsCRM_bh_ensure_incomes_table();

    global $wpdb;
    $table = WPsCRM_TABLE . 'incomes';
    
    // Double-check table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        error_log('[WPsCRM BH] incomes table could not be created: ' . $table);
        return;
    }

    WPsCRM_bh_log('sync transaction blog ' . $blog_id . ' total ' . $total);

    // Get transaction details
    $invoice_id = !empty($transaction->invoice_number) ? $transaction->invoice_number : $transaction->blog_id;
    $transaction_date = !empty($transaction->transaction_date) ? $transaction->transaction_date : current_time('mysql');
    $date = date('Y-m-d', strtotime($transaction_date));
    $created_at = date('Y-m-d H:i:s', strtotime($transaction_date));

    // Build admin link to blog
    $blog_name = WPsCRM_bh_get_blog_name($blog_id);
    $admin_link = get_admin_url($blog_id, 'admin.php?page=pro_sites&action=manage&blog_id=' . $blog_id);

    $description = sprintf(
        __('Blog-Hosting %s - %s', 'cpsmartcrm'),
        $blog_name,
        sprintf('<a href="%s" target="_blank">%s</a>', esc_url($admin_link), esc_html('Abo anzeigen'))
    );

    // Calculate tax
    $tax_rate = !empty($transaction->tax_rate) ? (float) $transaction->tax_rate : 19;
    $tax_percent = $tax_rate / 100;

    // Calculate net amount
    $net_amount = $total / (1 + $tax_percent);
    $tax_amount = $total - $net_amount;

    // Insert income entry
    $result = $wpdb->insert(
        $table,
        array(
            'data' => $date,
            'kategoria' => 'eCommerce',
            'descrizione' => $description,
            'imponibile' => round($net_amount, 2),
            'aliquota' => $tax_rate,
            'imposta' => round($tax_amount, 2),
            'totale' => round($total, 2),
            'created_at' => $created_at,
        ),
        array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s')
    );

    if ($result) {
        $inserted_id = $wpdb->insert_id;
        
        // Save meta to prevent duplicate processing
        update_blog_option($blog_id, '_wpscrm_bh_income_ids', array($inserted_id));
        WPsCRM_bh_log('booked transaction blog ' . $blog_id . ' income id ' . $inserted_id);
    } else {
        WPsCRM_bh_log('insert failed for blog ' . $blog_id . ' err: ' . $wpdb->last_error);
    }
}

/**
 * Hook into ProSites transaction recording
 */
add_action('prosites_transaction_record', 'WPsCRM_bh_sync_transaction');

/**
 * Cleanup duplicate income entries from the same blog
 */
function WPsCRM_bh_cleanup_duplicates() {
    global $wpdb;
    $table = WPsCRM_TABLE . 'incomes';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        return;
    }
    
    // Find and remove duplicate Blog-Hosting entries (same date, category, and amount)
    $duplicates = $wpdb->get_results(
        "SELECT descrizione, data, kategoria, imponibile, aliquota, COUNT(*) as cnt 
         FROM {$table}
         WHERE kategoria = 'eCommerce' AND descrizione LIKE '%Blog-Hosting%'
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
                     AND descrizione LIKE '%Blog-Hosting%'
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
                    error_log('[WPsCRM BH] Removed duplicate income entry ID ' . $id);
                }
            }
        }
    }
}

add_action('admin_init', 'WPsCRM_bh_cleanup_duplicates', 9); // Run before backfill
