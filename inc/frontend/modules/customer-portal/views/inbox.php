<?php
/**
 * Customer Portal - Inbox Tab
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$pm_integration = null;
if ( class_exists( 'WPsCRM_PM_Integration' ) ) {
    $pm_integration = WPsCRM_PM_Integration::get_instance();
}
?>

<?php if ( $pm_integration && $pm_integration->is_pm_active() ) : ?>
    
    <!-- Real PM Inbox -->
    <div id="crm-inbox-container" style="border: 1px solid #eee; border-radius: 4px; padding: 15px;">
        <?php echo do_shortcode( '[message_inbox]' ); ?>
    </div>
    
    <?php 
    $pm_inbox_url = $pm_integration->get_pm_inbox_url( 'inbox' );
    if ( $pm_inbox_url ) : 
    ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="<?php echo esc_url( $pm_inbox_url ); ?>" class="button button-primary">
                Zum vollständigen Postfach →
            </a>
        </div>
    <?php endif; ?>
    
<?php else : ?>
    
    <!-- PM Not Available -->
    <div style="text-align: center; padding: 40px; color: #999;">
        <span style="font-size: 48px; display: block; margin-bottom: 10px;">📬</span>
        <p>Nachrichtensystem nicht verfügbar.</p>
    </div>
    
<?php endif; ?>
