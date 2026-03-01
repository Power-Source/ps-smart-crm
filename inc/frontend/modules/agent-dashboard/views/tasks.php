<?php
/**
 * Agent Dashboard - Tasks/Aufgaben Card
 * 
 * Zeigt offene Todos aus der Agenda-Tabelle
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Hole offene Aufgaben des Agenten
global $wpdb;
$user_id = get_current_user_id();
$agenda_table = WPsCRM_TABLE . 'agenda';
$kunde_table = WPsCRM_TABLE . 'kunde';

$tasks = $wpdb->get_results( $wpdb->prepare(
    "SELECT 
        a.id_agenda,
        a.oggetto,
        a.data_agenda,
        a.ora_agenda,
        a.priorita,
        a.importante,
        a.urgente,
        a.fatto,
        a.fk_kunde,
        k.firmenname,
        k.name as kunde_name,
        k.nachname as kunde_nachname
    FROM {$agenda_table} a
    LEFT JOIN {$kunde_table} k ON a.fk_kunde = k.ID_kunde
    WHERE a.fk_utenti_des = %d
    AND a.tipo_agenda = 1
    AND a.fatto IN (0, 1)
    AND a.eliminato = 0
    ORDER BY 
        a.importante DESC,
        a.urgente DESC,
        a.priorita DESC,
        a.data_agenda ASC
    LIMIT 5",
    $user_id
) );
?>

<!-- Tasks Card -->
<div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #ff9800;">
    <h3 style="margin: 0 0 15px 0; color: #ff9800; display: flex; align-items: center; justify-content: space-between;">
        <span style="display: flex; align-items: center;">
            <span style="font-size: 24px; margin-right: 10px;">✅</span>
            <span>Meine Aufgaben</span>
        </span>
        <span style="background: #ff9800; color: white; font-size: 12px; padding: 4px 8px; border-radius: 12px; font-weight: bold;">
            <?php echo count( $tasks ); ?>
        </span>
    </h3>
    
    <div id="crm-tasks-list" style="max-height: 300px; overflow-y: auto;">
        <?php if ( ! empty( $tasks ) ) : ?>
            <?php foreach ( $tasks as $task ) : 
                // Priorität Badge
                $priority_color = '#999';
                $priority_label = '';
                if ( $task->wichtig === 'Si' || $task->importante === 'Si' ) {
                    $priority_color = '#f44336';
                    $priority_label = '🔴';
                } elseif ( $task->urgente === 'Si' ) {
                    $priority_color = '#ff9800';
                    $priority_label = '🟠';
                }
                
                // Datum formatieren
                $date_display = $task->data_agenda ? date_i18n( 'd.m.Y', strtotime( $task->data_agenda ) ) : '—';
                
                // Kunde
                $customer_name = '';
                if ( $task->fk_kunde ) {
                    $customer_name = ! empty( $task->firmenname ) 
                        ? $task->firmenname 
                        : trim( $task->kunde_name . ' ' . $task->kunde_nachname );
                }
            ?>
            <div class="crm-task-item" data-task-id="<?php echo $task->id_agenda; ?>" style="padding: 12px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
                <div style="display: flex; align-items: start; gap: 10px;">
                    <input type="checkbox" class="crm-task-checkbox" data-task-id="<?php echo $task->id_agenda; ?>" style="margin-top: 4px; cursor: pointer;" <?php checked( $task->fatto, 2 ); ?>>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; color: #333; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                            <?php if ( $priority_label ) : ?>
                                <span style="font-size: 16px;"><?php echo $priority_label; ?></span>
                            <?php endif; ?>
                            <span style="flex: 1;"><?php echo esc_html( $task->oggetto ?: 'Ohne Betreff' ); ?></span>
                        </div>
                        <div style="font-size: 11px; color: #666; display: flex; gap: 12px;">
                            <?php if ( $customer_name ) : ?>
                                <span>👤 <?php echo esc_html( $customer_name ); ?></span>
                            <?php endif; ?>
                            <span>📅 <?php echo $date_display; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div style="padding: 40px 20px; text-align: center; color: #999;">
                <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
                <p style="margin: 0; font-size: 14px;">Keine offenen Aufgaben!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ( ! empty( $tasks ) ) : ?>
    <a href="<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/list.php' ); ?>" style="display: block; text-align: center; margin-top: 12px; padding: 10px; color: #ff9800; text-decoration: none; border-top: 1px solid #eee; font-weight: 600;">
        Alle Aufgaben →
    </a>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Task als erledigt markieren
    $('.crm-task-checkbox').on('change', function() {
        const taskId = $(this).data('task-id');
        const isChecked = $(this).is(':checked');
        const $item = $(this).closest('.crm-task-item');
        
        $.post(ajaxurl, {
            action: 'crm_toggle_task',
            task_id: taskId,
            fatto: isChecked ? 2 : 1,
            nonce: '<?php echo wp_create_nonce( 'crm_task_toggle' ); ?>'
        }, function(response) {
            if (response.success) {
                // Fade out und entfernen
                $item.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Counter aktualisieren
                    const $counter = $('.crm-card h3 span[style*="background"]');
                    const count = parseInt($counter.text()) - 1;
                    $counter.text(count);
                    
                    // Wenn keine Tasks mehr, Erfolgsmeldung zeigen
                    if ($('.crm-task-item').length === 0) {
                        $('#crm-tasks-list').html('<div style="padding: 40px 20px; text-align: center; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">🎉</div><p style="margin: 0; font-size: 14px;">Keine offenen Aufgaben!</p></div>');
                    }
                });
            }
        });
    });
});
</script>
