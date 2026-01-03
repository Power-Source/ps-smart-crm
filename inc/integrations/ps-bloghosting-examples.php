<?php
/**
 * PS-Bloghosting Zahlungs-Integration - Code Beispiele
 * 
 * Dieses Datei enthält praktische Code-Beispiele für die Integration
 * von PS-Bloghosting Zahlungen in das PS-Smart-CRM Plugin
 */

// ============================================================================
// 1. ZAHLUNGS-HOOK IMPLEMENTIERUNG
// ============================================================================

/**
 * Beispiel 1: Jede neue Zahlung aufzeichnen
 * 
 * Wird ausgelöst, wenn eine Zahlung verarbeitet wird (Stripe, PayPal, etc.)
 */
add_action( 'prosites_transaction_record', function( $transaction ) {
    
    // Sicherstellen, dass wir die notwendigen Daten haben
    if ( empty( $transaction->blog_id ) || empty( $transaction->total ) ) {
        return;
    }
    
    global $wpdb;
    
    // Beispiel: In Smart CRM Tabelle speichern
    // Annahme: Es gibt eine Tabelle "wp_smartcrm_incomes" oder ähnlich
    
    $income_data = array(
        'blog_id'           => $transaction->blog_id,
        'blog_user_id'      => get_user_by( 'login', $transaction->username )->ID ?? null,
        'transaction_id'    => $transaction->invoice_number,
        'transaction_date'  => $transaction->invoice_date,
        'gateway'           => $transaction->gateway,
        'amount'            => $transaction->total,
        'subtotal'          => $transaction->subtotal ?? $transaction->total,
        'tax_rate'          => $transaction->tax_percent ?? 0,
        'tax_amount'        => $transaction->tax ?? 0,
        'currency'          => $transaction->currency_code,
        'country'           => $transaction->billing_country_code ?? '',
        'level'             => $transaction->level ?? null,
        'payment_status'    => 'completed',
        'created_at'        => current_time( 'mysql' ),
    );
    
    // In CRM Datenbank einfügen
    // $wpdb->insert( $wpdb->prefix . 'smartcrm_incomes', $income_data );
    
    error_log( 'Smart CRM: Zahlung registriert - Transaktions-ID: ' . $transaction->invoice_number );
    
} );


// ============================================================================
// 2. ABONNEMENT-VERLÄNGERUNG HOOK
// ============================================================================

/**
 * Beispiel 2: Abonnement-Verlängerung aufzeichnen
 * 
 * Wird ausgelöst, wenn ein Abonnement verlängert wird (monatlich, jährlich, etc.)
 */
add_action( 'psts_extend', function( 
    $blog_id, 
    $new_expire, 
    $level, 
    $manual_notify, 
    $gateway, 
    $last_gateway, 
    $extra 
) {
    
    global $wpdb, $psts;
    
    // Blog-Details abrufen
    $blog_details = get_blog_details( $blog_id );
    $blog_owner_id = get_blog_option( $blog_id, 'admin_user_id' );
    
    // Ablaufdatum formatieren
    $expire_date = date( 'Y-m-d H:i:s', $new_expire );
    $is_permanent = ( $new_expire >= 9999999999 ) ? 1 : 0;
    
    // In CRM als "Subscription Renewal" eintragen
    $renewal_data = array(
        'blog_id'           => $blog_id,
        'blog_user_id'      => $blog_owner_id,
        'level'             => $level,
        'gateway'           => $gateway,
        'previous_gateway'  => $last_gateway,
        'expire_date'       => $expire_date,
        'is_permanent'      => $is_permanent,
        'manual_notify'     => $manual_notify ? 1 : 0,
        'created_at'        => current_time( 'mysql' ),
    );
    
    // Prüfe ob permanenter Status widerrufen wurde
    if ( isset( $extra['permanent_revoked'] ) && $extra['permanent_revoked'] ) {
        $renewal_data['note'] = 'Permanenter Status wurde widerrufen';
    }
    
    // Speichere in CRM
    // $wpdb->insert( $wpdb->prefix . 'smartcrm_subscriptions', $renewal_data );
    
    error_log( sprintf(
        'Smart CRM: Abonnement verlängert - Blog %d, Level %d, Ablaufdatum %s',
        $blog_id,
        $level,
        $expire_date
    ) );
    
}, 10, 7 );


// ============================================================================
// 3. LEVEL-UPGRADE/DOWNGRADE
// ============================================================================

/**
 * Beispiel 3: Level-Upgrade aufzeichnen
 */
add_action( 'psts_upgrade', function( $blog_id, $new_level, $old_level ) {
    
    global $psts;
    
    $new_level_name = $psts->get_level_setting( $new_level, 'name' );
    $old_level_name = $old_level ? $psts->get_level_setting( $old_level, 'name' ) : 'Kostenlos';
    
    error_log( sprintf(
        'Smart CRM: Blog %d aktualisiert von %s auf %s',
        $blog_id,
        $old_level_name,
        $new_level_name
    ) );
    
    // Speichere Upgrade in CRM
    // $wpdb->insert( $wpdb->prefix . 'smartcrm_upgrades', array(
    //     'blog_id'       => $blog_id,
    //     'old_level'     => $old_level,
    //     'new_level'     => $new_level,
    //     'created_at'    => current_time( 'mysql' ),
    // ) );
    
} );

/**
 * Beispiel 4: Level-Downgrade aufzeichnen
 */
add_action( 'psts_downgrade', function( $blog_id, $new_level, $old_level ) {
    
    global $psts;
    
    $new_level_name = $psts->get_level_setting( $new_level, 'name' );
    $old_level_name = $psts->get_level_setting( $old_level, 'name' );
    
    error_log( sprintf(
        'Smart CRM: Blog %d herabgestuft von %s auf %s',
        $blog_id,
        $old_level_name,
        $new_level_name
    ) );
    
} );


// ============================================================================
// 4. ZAHLUNGSDATEN AUSLESEN - FUNKTIONEN
// ============================================================================

/**
 * Beispiel 5: Aktuelle Abonnement-Info abrufen
 */
function pscrm_get_blog_subscription( $blog_id ) {
    global $wpdb;
    
    $subscription = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->base_prefix}pro_sites WHERE blog_ID = %d",
        $blog_id
    ) );
    
    if ( ! $subscription ) {
        return false;
    }
    
    return array(
        'blog_id'           => $subscription->blog_ID,
        'level'             => $subscription->level,
        'amount'            => $subscription->amount,
        'currency'          => get_site_option( 'psts_settings' )['currency'] ?? 'EUR',
        'term'              => $subscription->term, // Monate
        'gateway'           => $subscription->gateway,
        'is_recurring'      => (bool) $subscription->is_recurring,
        'expire_date'       => date( 'Y-m-d H:i:s', $subscription->expire ),
        'is_expired'        => $subscription->expire < time(),
        'is_permanent'      => $subscription->expire >= 9999999999,
    );
}

/**
 * Beispiel 6: Letzte Zahlungen abrufen
 */
function pscrm_get_blog_payments( $blog_id, $limit = 10 ) {
    global $wpdb;
    
    $payments = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->base_prefix}pro_sites_transactions
        WHERE id IN (
            SELECT MAX(id) FROM {$wpdb->base_prefix}pro_sites_transactions
            GROUP BY blog_id HAVING blog_id = %d
        )
        ORDER BY transaction_date DESC
        LIMIT %d",
        $blog_id,
        $limit
    ) );
    
    if ( ! $payments ) {
        // Fallback: Blog-Meta prüfen
        $payment_log = get_blog_option( $blog_id, 'psts_payments_log' );
        if ( ! is_array( $payment_log ) ) {
            return array();
        }
        
        $payments = array();
        foreach ( $payment_log as $txn_id => $payment ) {
            $payments[] = array(
                'transaction_id' => $txn_id,
                'timestamp'      => $payment['timestamp'],
                'amount'         => $payment['amount'],
                'refunded'       => $payment['refunded'] ?? false,
            );
        }
        
        return $payments;
    }
    
    return $payments;
}

/**
 * Beispiel 7: Zahlungs-Statistiken abrufen
 */
function pscrm_get_payment_stats( $start_date = null, $end_date = null ) {
    global $wpdb;
    
    if ( ! $start_date ) {
        $start_date = date( 'Y-01-01' );
    }
    if ( ! $end_date ) {
        $end_date = date( 'Y-m-d' );
    }
    
    $stats = $wpdb->get_row( $wpdb->prepare(
        "SELECT 
            COUNT(*) as total_transactions,
            SUM(total) as total_amount,
            AVG(total) as average_amount,
            SUM(tax_amount) as total_tax,
            COUNT(DISTINCT currency) as currency_count,
            COUNT(DISTINCT country) as country_count
        FROM {$wpdb->base_prefix}pro_sites_transactions
        WHERE transaction_date BETWEEN %s AND %s",
        $start_date,
        $end_date
    ) );
    
    return $stats;
}

/**
 * Beispiel 8: Zahlungen pro Gateway
 */
function pscrm_get_payments_by_gateway( $start_date = null, $end_date = null ) {
    global $wpdb, $psts;
    
    if ( ! $start_date ) {
        $start_date = date( 'Y-01-01' );
    }
    if ( ! $end_date ) {
        $end_date = date( 'Y-m-d' );
    }
    
    // Hinweis: Gateway-Info muss aus wp_pro_sites ermittelt werden
    // da pro_sites_transactions keine gateway-spalte hat
    
    $result = $wpdb->get_results( $wpdb->prepare(
        "SELECT 
            ps.gateway,
            COUNT(pt.id) as count,
            SUM(pt.total) as total
        FROM {$wpdb->base_prefix}pro_sites_transactions pt
        RIGHT JOIN {$wpdb->base_prefix}pro_sites ps ON ps.blog_ID = pt.blog_id
        WHERE pt.transaction_date BETWEEN %s AND %s
        GROUP BY ps.gateway",
        $start_date,
        $end_date
    ) );
    
    return $result;
}

/**
 * Beispiel 9: Abonnement-Status pro Level
 */
function pscrm_get_active_subscriptions_by_level() {
    global $wpdb;
    
    $now = time();
    
    $result = $wpdb->get_results(
        "SELECT 
            level,
            COUNT(*) as count,
            SUM(CAST(amount AS DECIMAL(10,2))) as total_revenue,
            AVG(CAST(amount AS DECIMAL(10,2))) as avg_amount
        FROM {$wpdb->base_prefix}pro_sites
        WHERE expire > $now
        GROUP BY level
        ORDER BY level ASC"
    );
    
    return $result;
}

/**
 * Beispiel 10: Blog-Zahlungshistorie als Array
 */
function pscrm_get_complete_payment_history( $blog_id ) {
    global $wpdb, $psts;
    
    // Hauptdaten aus wp_pro_sites
    $subscription = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->base_prefix}pro_sites WHERE blog_ID = %d",
        $blog_id
    ) );
    
    if ( ! $subscription ) {
        return false;
    }
    
    // Zahlungshistorie aus Blog-Meta
    $payment_log = get_blog_option( $blog_id, 'psts_payments_log' );
    $action_log = get_blog_option( $blog_id, 'psts_action_log' );
    
    // Zusammenstellen
    $history = array(
        'blog_id'           => $blog_id,
        'current_level'     => $subscription->level,
        'current_gateway'   => $subscription->gateway,
        'expire_date'       => date( 'Y-m-d H:i:s', $subscription->expire ),
        'is_active'         => $subscription->expire > time(),
        'is_recurring'      => (bool) $subscription->is_recurring,
        'payments'          => $payment_log ?? array(),
        'actions'           => $action_log ?? array(),
    );
    
    return $history;
}


// ============================================================================
// 5. ZAHLUNGS-DATEN TRANSFORMIEREN
// ============================================================================

/**
 * Beispiel 11: Transaction-Objekt in CRM-Format konvertieren
 */
function pscrm_transaction_to_crm_format( $transaction ) {
    
    // Transaktionszeilen zusammenfassen
    $items_summary = '';
    if ( isset( $transaction->transaction_lines ) && is_array( $transaction->transaction_lines ) ) {
        $items = maybe_unserialize( $transaction->transaction_lines );
        if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
                $items_summary .= $item->description . ' (' . $item->amount . '), ';
            }
            $items_summary = rtrim( $items_summary, ', ' );
        }
    }
    
    return array(
        'crm_transaction_id' => 'TXN-' . $transaction->transaction_id,
        'blog_id'            => '', // Muss von außen hinzugefügt werden
        'amount'             => (float) $transaction->total,
        'tax_rate'           => (float) $transaction->tax_percentage,
        'tax_amount'         => (float) $transaction->tax_amount,
        'currency'           => $transaction->currency,
        'country'            => $transaction->country,
        'items'              => $items_summary,
        'date'               => $transaction->transaction_date,
        'status'             => 'completed',
    );
}


// ============================================================================
// 6. HILFSFUNKTIONEN FÜR CRM-VERARBEITUNG
// ============================================================================

/**
 * Beispiel 12: Email-Adressen aus Zahlungen abrufen
 */
function pscrm_get_payment_contacts( $start_date = null, $end_date = null ) {
    global $wpdb;
    
    if ( ! $start_date ) {
        $start_date = date( 'Y-01-01' );
    }
    if ( ! $end_date ) {
        $end_date = date( 'Y-m-d' );
    }
    
    // Zahlungs-Transaktionen mit Kontakt-Info zusammenbringen
    $result = $wpdb->get_results( $wpdb->prepare(
        "SELECT DISTINCT
            ps.blog_ID,
            u.ID as user_id,
            u.user_login,
            u.user_email,
            ps.level,
            ps.expire,
            pt.total as last_payment
        FROM {$wpdb->base_prefix}pro_sites ps
        LEFT JOIN {$wpdb->base_prefix}pro_sites_transactions pt ON ps.blog_ID = pt.blog_id
        LEFT JOIN {$wpdb->base_prefix}users u ON u.user_login = ps.identifier
        WHERE pt.transaction_date BETWEEN %s AND %s
        ORDER BY pt.transaction_date DESC",
        $start_date,
        $end_date
    ) );
    
    return $result;
}

/**
 * Beispiel 13: Ablauf-Benachrichtigungen vorbereiten
 */
function pscrm_get_expiring_soon_subscriptions( $days = 7 ) {
    global $wpdb;
    
    $now = time();
    $future = $now + ( $days * 86400 );
    
    $subscriptions = $wpdb->get_results( $wpdb->prepare(
        "SELECT 
            ps.blog_ID,
            ps.level,
            ps.gateway,
            ps.amount,
            ps.expire,
            ps.term,
            b.blogname,
            b.siteurl
        FROM {$wpdb->base_prefix}pro_sites ps
        LEFT JOIN {$wpdb->base_prefix}blogs b ON ps.blog_ID = b.blog_id
        WHERE ps.expire BETWEEN %d AND %d
        AND ps.expire < 9999999999
        ORDER BY ps.expire ASC",
        $now,
        $future
    ) );
    
    return $subscriptions;
}


// ============================================================================
// 7. TESTING & DEBUGGING
// ============================================================================

/**
 * Beispiel 14: Test-Zahlungs-Hook
 */
function pscrm_test_payment_hook() {
    
    // Erstelle ein Test-Transaction-Objekt
    $test_transaction = (object) array(
        'invoice_number'        => 'TEST-' . time(),
        'invoice_date'          => date( 'Y-m-d' ),
        'blog_id'               => 2,
        'level'                 => 1,
        'gateway'               => 'test',
        'username'              => 'admin',
        'email'                 => 'admin@example.com',
        'currency_code'         => 'EUR',
        'subtotal'              => 99.99,
        'tax_percent'           => 20,
        'tax'                   => 20.00,
        'total'                 => 119.99,
        'billing_country_code'  => 'AT',
        'transaction_lines'     => array(
            (object) array(
                'description' => 'Test Hostingpaket',
                'amount'      => 99.99,
                'quantity'    => 1,
            ),
        ),
    );
    
    // Triggere den Hook
    do_action( 'prosites_transaction_record', $test_transaction );
    
    error_log( 'Smart CRM: Test-Hook ausgelöst mit Transaktions-ID: ' . $test_transaction->invoice_number );
}

/**
 * Beispiel 15: Alle Zahlungs-Logs anzeigen (für Admin)
 */
function pscrm_debug_payment_logs( $blog_id ) {
    
    $subscription = pscrm_get_blog_subscription( $blog_id );
    $payments = pscrm_get_blog_payments( $blog_id );
    $history = pscrm_get_complete_payment_history( $blog_id );
    
    error_log( '=== Smart CRM Zahlungs-Debug für Blog ' . $blog_id . ' ===' );
    error_log( 'Aktuelles Abonnement: ' . print_r( $subscription, true ) );
    error_log( 'Letzte Zahlungen: ' . print_r( $payments, true ) );
    error_log( 'Vollständige Historie: ' . print_r( $history, true ) );
}


// ============================================================================
// 8. CRM-TABELLEN ERSTELLEN (Setup-Funktion)
// ============================================================================

/**
 * Beispiel 16: Setup-Funktion für CRM-Integration
 * 
 * Wird ausgelöst beim Plugin-Aktivieren
 */
function pscrm_setup_payment_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabelle für Zahlungseinträge
    $sql_incomes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smartcrm_incomes (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        blog_id BIGINT(20) NOT NULL,
        blog_user_id BIGINT(20) NOT NULL,
        transaction_id VARCHAR(255) NOT NULL UNIQUE,
        transaction_date DATE NOT NULL,
        gateway VARCHAR(50) NOT NULL,
        amount DECIMAL(13,4) NOT NULL,
        subtotal DECIMAL(13,4) NOT NULL,
        tax_rate DECIMAL(4,2) DEFAULT 0,
        tax_amount DECIMAL(13,4) DEFAULT 0,
        currency VARCHAR(3) NOT NULL DEFAULT 'EUR',
        country VARCHAR(3),
        level INT(3),
        payment_status VARCHAR(50) DEFAULT 'completed',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        INDEX idx_blog_id (blog_id),
        INDEX idx_user_id (blog_user_id),
        INDEX idx_date (transaction_date),
        INDEX idx_gateway (gateway)
    ) $charset_collate;";
    
    // Tabelle für Abonnements
    $sql_subscriptions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smartcrm_subscriptions (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        blog_id BIGINT(20) NOT NULL,
        blog_user_id BIGINT(20),
        level INT(3) NOT NULL,
        gateway VARCHAR(50) NOT NULL,
        previous_gateway VARCHAR(50),
        expire_date DATETIME NOT NULL,
        is_permanent TINYINT(1) DEFAULT 0,
        manual_notify TINYINT(1) DEFAULT 0,
        note TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        INDEX idx_blog_id (blog_id),
        INDEX idx_expire_date (expire_date)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_incomes );
    dbDelta( $sql_subscriptions );
    
    error_log( 'Smart CRM: Zahlungs-Tabellen erstellt/aktualisiert' );
}

// Registriere Setup-Funktion
add_action( 'psts_activated', 'pscrm_setup_payment_tables' );


// ============================================================================
// ENDE - Weitere Beispiele nach Bedarf hinzufügen
// ============================================================================
?>
