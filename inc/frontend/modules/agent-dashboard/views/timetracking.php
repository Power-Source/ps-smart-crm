<?php
/**
 * Agent Dashboard - Zeiterfassung Card
 * 
 * Features:
 * - Manuelle Zeiteinträge
 * - Monatliche Statistik (Soll/Ist, Überstunden, Fehlstunden)
 * - Letzte 5 Einträge
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Hole Zeiterfassungs-Daten
$user_id = get_current_user_id();
$timetracking = WPsCRM_Timetracking::get_instance();

// Monatliche Statistik berechnen
$current_month = date( 'Y-m' );
$stats = $timetracking->get_monthly_stats( $user_id, $current_month );

// Sollstunden (Standard: 160h = 4 Wochen x 40h)
$target_hours = 160;
$actual_hours = $stats['total_hours'] ?? 0;
$overtime = max( 0, $actual_hours - $target_hours );
$missing_hours = max( 0, $target_hours - $actual_hours );

// Hole alle Kunden für Dropdown (nur die dem Agent zugeordnet sind)
global $wpdb;
$customers_table = WPsCRM_TABLE . 'kunde';
$customers = $wpdb->get_results( $wpdb->prepare(
    "SELECT ID_kunde, name, nachname, firmenname 
    FROM {$customers_table} 
    WHERE agente = %d
    AND eliminato = 0
    ORDER BY firmenname, name 
    LIMIT 500",
    $user_id
) );
?>

<!-- Time Tracking Card -->
<div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #2196F3;">
    
    <!-- Header mit Tabs -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #2196F3; display: flex; align-items: center;">
            <span style="font-size: 24px; margin-right: 10px;">⏱️</span>
            <span>Zeiterfassung</span>
        </h3>
        <div style="display: flex; gap: 8px;">
            <button class="crm-time-tab active" data-tab="manual" style="padding: 6px 12px; border: none; background: #2196F3; color: white; border-radius: 4px; cursor: pointer; font-size: 12px;">✏️ Eintragen</button>
            <button class="crm-time-tab" data-tab="stats" style="padding: 6px 12px; border: none; background: #e0e0e0; color: #666; border-radius: 4px; cursor: pointer; font-size: 12px;">📊 Statistik</button>
        </div>
    </div>

    <!-- Tab: Manueller Eintrag -->
    <div class="crm-time-content active" data-content="manual">
        <form id="crm-manual-time-form" style="display: grid; gap: 12px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Datum</label>
                    <input type="date" name="work_date" value="<?php echo date( 'Y-m-d' ); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Dauer (Stunden)</label>
                    <input type="number" name="duration_hours" step="0.25" min="0.25" max="24" placeholder="8.5" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div>
                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">
                    Kunde <span style="color: #999; font-weight: normal;">(optional)</span>
                </label>
                <select name="customer_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: white;">
                    <option value="">-- Kein Kunde zugeordnet --</option>
                    <?php if ( ! empty( $customers ) ) : ?>
                        <?php foreach ( $customers as $customer ) : 
                            $display_name = ! empty( $customer->firmenname ) 
                                ? $customer->firmenname 
                                : trim( $customer->name . ' ' . $customer->nachname );
                        ?>
                        <option value="<?php echo esc_attr( $customer->ID_kunde ); ?>">
                            <?php echo esc_html( $display_name ); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Beschreibung</label>
                <textarea name="task_description" rows="2" placeholder="Was hast du gemacht?" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
            </div>
            
            <button type="submit" class="button button-primary" style="width: 100%;">
                ✅ Zeiteintrag hinzufügen
            </button>
        </form>
        
        <div id="crm-manual-time-message" style="margin-top: 12px; padding: 10px; border-radius: 4px; display: none;"></div>
    </div>

    <!-- Tab: Statistik -->
    <div class="crm-time-content" data-content="stats" style="display: none;">
        <div style="background: #f5f5f5; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 8px;">
                Monat: <strong><?php echo date_i18n( 'F Y' ); ?></strong>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 12px;">
                <!-- Sollstunden -->
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #666;"><?php echo $target_hours; ?>h</div>
                    <div style="font-size: 11px; color: #999;">Soll</div>
                </div>
                
                <!-- Ist-Stunden -->
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #2196F3;"><?php echo number_format( $actual_hours, 1 ); ?>h</div>
                    <div style="font-size: 11px; color: #999;">Ist</div>
                </div>
                
                <!-- Differenz -->
                <div style="text-align: center;">
                    <?php if ( $overtime > 0 ) : ?>
                        <div style="font-size: 24px; font-weight: bold; color: #4caf50;">+<?php echo number_format( $overtime, 1 ); ?>h</div>
                        <div style="font-size: 11px; color: #4caf50;">Überstunden</div>
                    <?php else : ?>
                        <div style="font-size: 24px; font-weight: bold; color: #f44336;">-<?php echo number_format( $missing_hours, 1 ); ?>h</div>
                        <div style="font-size: 11px; color: #f44336;">Fehlstunden</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <?php 
            $progress_percent = min( 100, ( $actual_hours / $target_hours ) * 100 );
            $bar_color = $progress_percent >= 100 ? '#4caf50' : ( $progress_percent >= 80 ? '#ff9800' : '#2196F3' );
            ?>
            <div style="margin-top: 15px;">
                <div style="background: #e0e0e0; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="background: <?php echo $bar_color; ?>; height: 100%; width: <?php echo $progress_percent; ?>%; transition: width 0.3s;"></div>
                </div>
                <div style="font-size: 11px; color: #999; margin-top: 4px; text-align: right;">
                    <?php echo number_format( $progress_percent, 1 ); ?>% erreicht
                </div>
            </div>
        </div>
        
        <!-- Letzte Einträge -->
        <div style="font-size: 12px; color: #666; margin-bottom: 8px; font-weight: 600;">Letzte Einträge</div>
        <div id="crm-recent-entries" style="max-height: 200px; overflow-y: auto;">
            <?php
            $recent = $timetracking->get_recent_entries( $user_id, 5 );
            if ( $recent ) :
                foreach ( $recent as $entry ) :
                    $duration_hours = $entry->duration_minutes / 60;
                    $date_display = date_i18n( 'd.m.Y', strtotime( $entry->start_time ) );
            ?>
            <div style="padding: 8px; border-bottom: 1px solid #eee; font-size: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #333;"><?php echo esc_html( $entry->task_description ?: 'Arbeitszeit' ); ?></div>
                        <div style="color: #999; font-size: 11px;"><?php echo $date_display; ?></div>
                    </div>
                    <div style="font-weight: bold; color: #2196F3;">
                        <?php echo number_format( $duration_hours, 1 ); ?>h
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            else : 
            ?>
            <div style="padding: 20px; text-align: center; color: #999; font-size: 12px;">
                Noch keine Einträge vorhanden
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.crm-time-tab.active {
    background: #2196F3 !important;
    color: white !important;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab Switching
    $('.crm-time-tab').on('click', function() {
        const tab = $(this).data('tab');
        
        // Update Tabs
        $('.crm-time-tab').removeClass('active').css({'background': '#e0e0e0', 'color': '#666'});
        $(this).addClass('active');
        
        // Update Content
        $('.crm-time-content').hide();
        $('.crm-time-content[data-content="' + tab + '"]').show();
    });
    
    // Manual Time Entry Form
    $('#crm-manual-time-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'crm_add_manual_time',
            nonce: '<?php echo wp_create_nonce( 'crm_manual_time' ); ?>',
            work_date: $('[name="work_date"]').val(),
            duration_hours: $('[name="duration_hours"]').val(),
            customer_id: $('[name="customer_id"]').val(),
            task_description: $('[name="task_description"]').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            const $msg = $('#crm-manual-time-message');
            if (response.success) {
                $msg.css({'background': '#d4edda', 'color': '#155724', 'border': '1px solid #c3e6cb'})
                    .text('✅ ' + response.data.message)
                    .show();
                
                // Reset Form
                $('#crm-manual-time-form')[0].reset();
                $('[name="work_date"]').val('<?php echo date( 'Y-m-d' ); ?>');
                
                // Reload nach 2 Sekunden
                setTimeout(() => location.reload(), 2000);
            } else {
                $msg.css({'background': '#f8d7da', 'color': '#721c24', 'border': '1px solid #f5c6cb'})
                    .text('❌ ' + response.data.message)
                    .show();
            }
        });
    });
});
</script>
