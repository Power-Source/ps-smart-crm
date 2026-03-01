<?php
/**
 * Agent Dashboard - Main Layout
 * 
 * @var string $user_type User type (agent|customer|guest)
 * @var array $user_data User data array
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="crm-agent-dashboard" style="padding: 20px; background: #f5f5f5; min-height: 100vh;">
    
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <?php if ( 'agent' === $user_type ) : ?>
                <h1 style="margin: 0; color: #333;">👋 Willkommen, <?php echo esc_html( $user_data['display_name'] ); ?></h1>
            <?php elseif ( 'customer' === $user_type ) : ?>
                <h1 style="margin: 0; color: #333;">🏢 Kundenzone</h1>
            <?php else : ?>
                <h1 style="margin: 0; color: #333;">💼 Agent Dashboard</h1>
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

    <?php if ( 'agent' === $user_type ) : ?>
        
        <!-- Agent Dashboard Content -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <!-- Load Agent Cards -->
            <?php
            // Zeiterfassung
            include __DIR__ . '/timetracking.php';
            
            // Aufgaben
            include __DIR__ . '/tasks.php';
            ?>
            
        </div>

        <!-- Kundenverwaltung (Full Width) -->
        <?php include __DIR__ . '/customers.php'; ?>

        <?php 
        // PM Inbox Container - wenn PM aktiv ist
        $pm_integration = null;
        if ( class_exists( 'WPsCRM_PM_Integration' ) ) {
            $pm_integration = WPsCRM_PM_Integration::get_instance();
        }
        
        if ( $pm_integration && $pm_integration->is_pm_active() ) : 
        ?>
        <!-- PM Inbox Container -->
        <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px;">
            <?php echo do_shortcode( '[message_inbox]' ); ?>
        </div>
        <?php endif; ?>

        <!-- Profile Section -->
        <?php include __DIR__ . '/profile.php'; ?>

    <?php elseif ( 'customer' === $user_type ) : ?>
        
        <!-- Customer notice -->
        <div class="crm-card" style="background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 8px; padding: 30px; text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #1565c0;">🏢 Kundenbereich</h2>
            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                Du bist angemeldet als <strong>Kunde</strong>. Bitte nutze das Kundenportal für deine Rechnungen und Angebote.
            </p>
        </div>

    <?php else : ?>
        
        <!-- Guest/Non-logged-in -->
        <div class="crm-card" style="background: #fff9c4; border-left: 4px solid #fbc02d; border-radius: 8px; padding: 30px; text-align: center;">
            <h2 style="margin: 0 0 15px 0; color: #f57f17;">🔐 Mitarbeiter-Bereich</h2>
            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                Dieser Bereich ist nur für angemeldete Mitarbeiter sichtbar.
            </p>
            <a href="<?php echo wp_login_url(); ?>" class="button button-primary">
                🔐 Jetzt anmelden
            </a>
        </div>

    <?php endif; ?>
</div>
