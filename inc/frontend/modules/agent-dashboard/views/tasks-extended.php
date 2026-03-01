<?php
/**
 * Agent Dashboard - Tasks/Aufgaben Card (Extended)
 * 
 * Zeigt offene Todos/Termine + Inline Formulare für neue Einträge
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$user_id = get_current_user_id();
$agenda_table = WPsCRM_TABLE . 'agenda';
$kunde_table = WPsCRM_TABLE . 'kunde';

// Hole offene Aufgaben des Agenten
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

$nonce = wp_create_nonce( 'crm_task_management' );
?>

<!-- Tasks Card -->
<div class="crm-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-left: 4px solid #ff9800;">
    <h3 style="margin: 0 0 15px 0; color: #ff9800; display: flex; align-items: center; justify-content: space-between;">
        <span style="display: flex; align-items: center;">
            <span>Meine Aufgaben</span>
        </span>
        <span style="background: #ff9800; color: white; font-size: 12px; padding: 4px 8px; border-radius: 12px; font-weight: bold;">
            <?php echo count( $tasks ); ?>
        </span>
    </h3>
    
    <!-- Action Buttons -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
        <button class="crm-btn crm-btn-sm crm-btn-primary" id="btn-add-todo" style="cursor: pointer;">
            Todo
        </button>
        <button class="crm-btn crm-btn-sm crm-btn-info" id="btn-add-appointment" style="cursor: pointer;">
            Termin
        </button>
    </div>

    <div id="crm-tasks-feedback" style="display:none;margin-bottom:12px;padding:10px;border-radius:4px;background:#f5f5f5;color:#333;border:1px solid #ddd;"></div>
    
    <!-- Todo Form (Hidden) -->
    <form id="form-add-todo" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Betreff</label>
            <input type="text" name="todo-subject" placeholder="z.B. Angebot vorbereiten..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
            <div>
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Startdatum</label>
                <input type="date" name="todo-start" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 13px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Enddatum</label>
                <input type="date" name="todo-end" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 13px;" required>
            </div>
        </div>
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Priorität</label>
            <select name="todo-priority" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <option value="1">🟢 Niedrig</option>
                <option value="2" selected>🟡 Normal</option>
                <option value="3">🔴 Hoch</option>
            </select>
        </div>
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Notizen</label>
            <textarea name="todo-notes" placeholder="Zusätzliche Informationen..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 13px; min-height: 60px; resize: vertical;"></textarea>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="crm-btn crm-btn-sm crm-btn-success" style="cursor: pointer;">✅ Speichern</button>
            <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary btn-cancel-form" style="cursor: pointer;">Abbrechen</button>
        </div>
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    </form>
    
    <!-- Appointment Form (Hidden) -->
    <form id="form-add-appointment" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Betreff</label>
            <input type="text" name="appt-subject" placeholder="z.B. Kundengespräch..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
            <div>
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Datum</label>
                <input type="date" name="appt-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 13px;" required>
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Uhrzeit</label>
                <input type="time" name="appt-time" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 13px;" required>
            </div>
        </div>
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Priorität</label>
            <select name="appt-priority" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                <option value="1">🟢 Niedrig</option>
                <option value="2" selected>🟡 Normal</option>
                <option value="3">🔴 Hoch</option>
            </select>
        </div>
        <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Notizen</label>
            <textarea name="appt-notes" placeholder="Agendapunkte, Vorbereitung..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 13px; min-height: 60px; resize: vertical;"></textarea>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="crm-btn crm-btn-sm crm-btn-success" style="cursor: pointer;">✅ Speichern</button>
            <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary btn-cancel-form" style="cursor: pointer;">Abbrechen</button>
        </div>
        <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    </form>
    
    <!-- Tasks List -->
    <div id="crm-tasks-list" style="max-height: 300px; overflow-y: auto;">
        <?php if ( ! empty( $tasks ) ) : ?>
            <?php foreach ( $tasks as $task ) : 
                $priority_color = '#999';
                $priority_label = '';
                if ( $task->importante === 'Si' ) {
                    $priority_color = '#f44336';
                    $priority_label = '🔴';
                } elseif ( $task->urgente === 'Si' ) {
                    $priority_color = '#ff9800';
                    $priority_label = '🟠';
                }
                
                $date_display = $task->data_agenda ? date_i18n( 'd.m.Y', strtotime( $task->data_agenda ) ) : '—';
                
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

<style>
    #form-add-todo,
    #form-add-appointment {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
jQuery(document).ready(function($) {
    const ajaxurl = crmAjax?.ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
    const nonce = '<?php echo $nonce; ?>';

    function showTasksFeedback(message) {
        const $feedback = $('#crm-tasks-feedback');
        $feedback.stop(true, true).text(message).slideDown(150);
    }
    
    // Toggle Todo Form
    $('#btn-add-todo').on('click', function() {
        const $form = $('#form-add-todo');
        if ($form.is(':visible')) {
            $form.slideUp(200);
        } else {
            $('#form-add-appointment').slideUp(200);
            $form.slideDown(200);
            $form.find('input[name="todo-start"]').focus();
        }
    });
    
    // Toggle Appointment Form
    $('#btn-add-appointment').on('click', function() {
        const $form = $('#form-add-appointment');
        if ($form.is(':visible')) {
            $form.slideUp(200);
        } else {
            $('#form-add-todo').slideUp(200);
            $form.slideDown(200);
            $form.find('input[name="appt-date"]').focus();
        }
    });
    
    // Cancel Form Buttons
    $('.btn-cancel-form').on('click', function() {
        $(this).closest('form').slideUp(200);
    });
    
    // Submit Todo Form
    $('#form-add-todo').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_create_todo',
                nonce: nonce,
                subject: $('input[name="todo-subject"]').val(),
                start_date: $('input[name="todo-start"]').val(),
                end_date: $('input[name="todo-end"]').val(),
                priority: $('select[name="todo-priority"]').val(),
                notes: $('textarea[name="todo-notes"]').val()
            },
            success: function(response) {
                if (response.success) {
                    showTasksFeedback('Todo erstellt.');
                    $('#form-add-todo')[0].reset();
                    $('#form-add-todo').slideUp(200);
                    setTimeout(function() { location.reload(); }, 400);
                } else {
                    showTasksFeedback('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            }
        });
    });
    
    // Submit Appointment Form
    $('#form-add-appointment').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_create_appointment',
                nonce: nonce,
                subject: $('input[name="appt-subject"]').val(),
                date: $('input[name="appt-date"]').val(),
                time: $('input[name="appt-time"]').val(),
                priority: $('select[name="appt-priority"]').val(),
                notes: $('textarea[name="appt-notes"]').val()
            },
            success: function(response) {
                if (response.success) {
                    showTasksFeedback('Termin erstellt.');
                    $('#form-add-appointment')[0].reset();
                    $('#form-add-appointment').slideUp(200);
                    setTimeout(function() { location.reload(); }, 400);
                } else {
                    showTasksFeedback('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            }
        });
    });
    
    // Task Toggle
    $('.crm-task-checkbox').on('change', function() {
        const taskId = $(this).data('task-id');
        const isChecked = $(this).is(':checked');
        const $item = $(this).closest('.crm-task-item');
        
        $.post(ajaxurl, {
            action: 'crm_toggle_task',
            task_id: taskId,
            fatto: isChecked ? 2 : 1,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                $item.fadeOut(300, function() {
                    $(this).remove();
                    
                    const $counter = $('.crm-card h3 span[style*="background"]');
                    const count = parseInt($counter.text()) - 1;
                    $counter.text(count);
                    
                    if ($('.crm-task-item').length === 0) {
                        $('#crm-tasks-list').html('<div style="padding: 40px 20px; text-align: center; color: #999;"><div style="font-size: 48px; margin-bottom: 10px;">🎉</div><p style="margin: 0; font-size: 14px;">Keine offenen Aufgaben!</p></div>');
                    }
                });
            }
        });
    });
});
</script>
