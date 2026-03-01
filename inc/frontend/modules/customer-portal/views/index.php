<?php
/**
 * Customer Portal - Main Layout
 * 
 * @var string $user_type User type (agent|customer|guest)
 * @var array $user_data User data array
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="crm-customer-portal" style="padding: 20px; background: #f5f5f5; min-height: 100vh;">
    
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <?php if ( 'customer' === $user_type ) : ?>
                <h1 style="margin: 0; color: #333;">👋 Herzlich Willkommen, <?php echo esc_html( $user_data['display_name'] ); ?></h1>
            <?php elseif ( 'agent' === $user_type ) : ?>
                <h1 style="margin: 0; color: #333;">🏢 Kundenportal</h1>
            <?php else : ?>
                <h1 style="margin: 0; color: #333;">💼 Kundenportal</h1>
            <?php endif; ?>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                <?php echo date_i18n( 'l, d. F Y' ); ?>
            </p>
        </div>
        <div>
            <?php if ( $user_data['ID'] ) : ?>
                <a href="<?php echo wp_logout_url(); ?>" class="button button-secondary">🚪 Logout</a>
            <?php else : ?>
                <a href="<?php echo wp_login_url(); ?>" class="button button-primary">🔐 Login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( 'customer' === $user_type ) : ?>
        
        <!-- Customer Portal Content -->
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            
            <!-- Tab Navigation -->
            <div style="display: flex; border-bottom: 2px solid #eee; margin-bottom: 30px;">
                <button class="crm-tab-btn active" data-tab="invoices" 
                    style="padding: 12px 20px; border: none; background: none; cursor: pointer; border-bottom: 3px solid #2196F3; color: #2196F3; font-weight: 500; font-size: 16px;">
                    📄 Rechnungen
                </button>
                <button class="crm-tab-btn" data-tab="quotations"
                    style="padding: 12px 20px; border: none; background: none; cursor: pointer; color: #666; font-weight: 500; font-size: 16px;">
                    📋 Angebote
                </button>
                <button class="crm-tab-btn" data-tab="inbox"
                    style="padding: 12px 20px; border: none; background: none; cursor: pointer; color: #666; font-weight: 500; font-size: 16px;">
                    📬 Postfach
                </button>
            </div>
            
            <!-- Tab Content -->
            <div id="crm-tab-content">
                <!-- Invoices Tab -->
                <div class="crm-tab-pane active" id="tab-invoices">
                    <?php include __DIR__ . '/views/invoices.php'; ?>
                </div>
                
                <!-- Quotations Tab -->
                <div class="crm-tab-pane" id="tab-quotations" style="display: none;">
                    <?php include __DIR__ . '/views/quotations.php'; ?>
                </div>
                
                <!-- Inbox Tab -->
                <div class="crm-tab-pane" id="tab-inbox" style="display: none;">
                    <?php include __DIR__ . '/views/inbox.php'; ?>
                </div>
            </div>
        </div>

    <?php elseif ( 'agent' === $user_type ) : ?>
        
        <!-- Agent notice -->
        <div class="crm-card" style="background: #c8e6c9; border-left: 4px solid #4caf50; border-radius: 8px; padding: 30px; text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #1b5e20;">🎯 Agent-Ansicht</h2>
            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                Du bist angemeldet als <strong>Agent</strong>. Bitte nutze dein Agent Dashboard.
            </p>
            <a href="<?php echo add_query_arg( 'crm-view', 'agent-dashboard' ); ?>" class="button button-primary">
                ← Zum Agent Dashboard
            </a>
        </div>

    <?php else : ?>
        
        <!-- Guest/Non-logged-in -->
        <div class="crm-card" style="background: #fff9c4; border-left: 4px solid #fbc02d; border-radius: 8px; padding: 30px; text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #f57f17;">🔐 Kundenbereich</h2>
            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                Bitte melden Sie sich an um auf Ihre Daten zuzugreifen.
            </p>
            <a href="<?php echo wp_login_url(); ?>" class="button button-primary">
                🔐 Jetzt anmelden
            </a>
        </div>

    <?php endif; ?>
</div>

<style>
.crm-customer-portal {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.crm-tab-btn {
    transition: all 0.3s ease;
}

.crm-tab-btn:hover {
    color: #2196F3 !important;
}

.crm-tab-btn.active {
    color: #2196F3 !important;
    border-bottom: 3px solid #2196F3 !important;
}

.crm-tab-pane {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.crm-tab-btn').on('click', function(e) {
        e.preventDefault();
        
        const tab = $(this).data('tab');
        
        // Hide all tabs
        $('.crm-tab-pane').hide();
        
        // Remove active class from buttons
        $('.crm-tab-btn').removeClass('active');
        
        // Show selected tab
        $('#tab-' + tab).show();
        
        // Add active class
        $(this).addClass('active');
    });
});
</script>
