<?php
/**
 * Guest View - Main Layout
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="crm-guest-view" style="padding: 20px; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    
    <div style="background: white; border-radius: 8px; padding: 40px; max-width: 500px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center;">
        
        <div style="font-size: 64px; margin-bottom: 20px;">🔐</div>
        
        <h1 style="margin: 0 0 10px 0; color: #333; font-size: 28px;">Willkommen!</h1>
        
        <p style="margin: 0 0 30px 0; color: #666; font-size: 16px; line-height: 1.6;">
            Dieser Bereich ist nur für angemeldete Benutzer zugänglich.
        </p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <a href="<?php echo wp_login_url(); ?>" class="button button-primary" style="padding: 10px 20px; text-decoration: none; display: block;">
                🔐 Anmelden
            </a>
            <a href="<?php echo wp_registration_url(); ?>" class="button button-secondary" style="padding: 10px 20px; text-decoration: none; display: block;">
                📝 Registrieren
            </a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #eee;">
            <p style="margin: 0; color: #999; font-size: 14px;">
                © <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>
            </p>
        </div>
    </div>
</div>

<style>
.crm-guest-view {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.crm-guest-view .button {
    transition: all 0.3s ease;
}

.crm-guest-view .button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
