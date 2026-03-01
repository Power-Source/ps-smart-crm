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
    
    <!-- PM Inbox - einfacher Container mit Shortcode -->
    <?php echo do_shortcode( '[message_inbox]' ); ?>
    
<?php else : ?>
    
    <!-- PM Not Available -->
    <div style="text-align: center; padding: 40px; color: #999;">
        <span style="font-size: 48px; display: block; margin-bottom: 10px;">📬</span>
        <p>Nachrichtensystem nicht verfügbar.</p>
    </div>
    
<?php endif; ?>
