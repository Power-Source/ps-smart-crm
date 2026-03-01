<?php
/**
 * Agent Dashboard - Inbox Card
 * 
 * Integriert echtes PM System wenn verfügbar
 * Rendert Inbox inline ohne Umleitung
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$pm_integration = null;
if ( class_exists( 'WPsCRM_PM_Integration' ) ) {
    $pm_integration = WPsCRM_PM_Integration::get_instance();
}
?>

<!-- Inbox Card -->
<div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #4caf50;">
    <h3 style="margin: 0 0 15px 0; color: #4caf50; display: flex; align-items: center;">
        <span style="font-size: 24px; margin-right: 10px;">📬</span>
        <span>Mein Postfach</span>
    </h3>
    
    <?php if ( $pm_integration && $pm_integration->is_pm_active() ) : ?>
        <!-- Real PM Inbox - Inline Rendering -->
        <div id="crm-inbox-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; padding: 15px; background: #fafafa;">
            <?php echo do_shortcode( '[message_inbox inline="1"]' ); ?>
        </div>
        
        <?php 
        $pm_inbox_url = $pm_integration->get_pm_inbox_url( 'inbox' );
        if ( $pm_inbox_url ) : 
        ?>
            <a href="<?php echo esc_url( $pm_inbox_url ); ?>" style="display: block; text-align: center; margin-top: 12px; padding: 10px; color: #4caf50; text-decoration: none; border-top: 1px solid #eee; font-weight: 600;">
                Vollständiges Postfach →
            </a>
        <?php endif; ?>
        
    <?php else : ?>
        
        <!-- PM System not available -->
        <div style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 4px;">
            <p style="color: #999; margin: 0;">
                Private Messaging nicht verfügbar
            </p>
        </div>
        
    <?php endif; ?>
</div>
