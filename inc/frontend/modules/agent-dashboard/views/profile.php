<?php
/**
 * Agent Dashboard - Profile Section
 * 
 * @var array $user_data User data
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$agents_table = WPsCRM_TABLE . 'agents';
$agent = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$agents_table} WHERE user_id = %d",
    $user_data['ID']
) );
?>

<!-- Profile Section -->
<div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h3 style="margin: 0 0 20px 0; color: #333;">👤 Mein Profil</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Name</label>
                <p style="margin: 0; color: #333; font-weight: 500;">
                    <?php echo esc_html( $user_data['display_name'] ); ?>
                </p>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">E-Mail</label>
                <p style="margin: 0; color: #333;">
                    <?php echo esc_html( $user_data['email'] ); ?>
                </p>
            </div>
        </div>
        
        <div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #666; font-size: 12px; margin-bottom: 4px;">Stundensatz</label>
                <p style="margin: 0; color: #333; font-weight: 500;">
                    €<?php echo esc_html( $agent->hourly_rate ?? '—' ); ?>/h
                </p>
            </div>
            
            <div>
                <a href="<?php echo admin_url( 'user-edit.php?user_id=' . $user_data['ID'] ); ?>" class="button button-small">
                    🔧 Profil bearbeiten
                </a>
            </div>
        </div>
    </div>
</div>
