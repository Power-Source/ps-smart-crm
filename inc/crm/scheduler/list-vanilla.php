<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Vanilla.js Scheduler List
 * 
 * Moderne Planerliste mit Vanilla.js statt Kendo UI
 */

$delete_nonce= wp_create_nonce( "delete_activity" );
$update_nonce= wp_create_nonce( "update_scheduler" );
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$options = get_option('CRM_general_settings');
?>

<!-- Filter Toolbar -->
<div class="pscrm-filter-row">
    <label><?php _e('Filter by date','cpsmartcrm') ?>:</label>
    <label><?php _e('From','cpsmartcrm') ?>:</label>
    <input id="dateFrom" type="text" style="width: 200px" />
    
    <label><?php _e('To','cpsmartcrm') ?>:</label>
    <input id="dateTo" type="text" style="width: 200px" />
    
    <button id="dateRange" class="button button-primary _flat"><?php _e('Filter','cpsmartcrm') ?></button>
    <button id="btn_reset" class="button button-secondary _flat"><?php _e('Reset filters','cpsmartcrm') ?></button>
</div>

<!-- Toolbar Buttons -->
<ul class="select-action">
    <li onClick="location.href='<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=1')?>';return false;" class="btn btn-info btn-sm _flat btn_todo">
        <i class="glyphicon glyphicon-tag"></i> 
        <b><?php _e('NEUES TODO','cpsmartcrm')?></b>
    </li>
    <li onClick="location.href='<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=2')?>';return false;" class="btn btn-sm _flat btn_appuntamento">
        <i class="glyphicon glyphicon-pushpin"></i> 
        <b><?php _e('NEUER TERMIN','cpsmartcrm')?></b>
    </li>
    
    <span style="float:right;">
        <li class="no-link" style="margin-top:4px">
            <?php _e('Legende','cpsmartcrm') ?>:
        </li>
        <li class="no-link">
            <i class="glyphicon glyphicon-ok" style="color:green;font-size:1.3em"></i>
            <?php _e('Erledigt','cpsmartcrm') ?>
        </li>
        <li class="no-link">
            <i class="glyphicon glyphicon-bookmark" style="color:black;font-size:1.3em"></i>
            <?php _e('Noch zu erledigen','cpsmartcrm') ?>
        </li>
        <li class="no-link">
            <i class="glyphicon glyphicon-remove" style="color:red;font-size:1.3em"></i>
            <?php _e('Abgesagt','cpsmartcrm') ?>
        </li>
        <li class="no-link">
            <span class="tipped" style="width:13px;height:13px;display:inline-flex" title="<?php _e('Bewege den Mauszeiger darüber, um weitere Informationen anzuzeigen.
            Info tooltip
        </li>
    </span>
</ul>

<!-- Grid Container -->
<div id="grid" class="datagrid _scheduler"></div> 

<!-- Modal für Aktivitätsansicht -->
<div id="dialog-view" class="_modal"></div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Grid-Konfiguration
    const gridOptions = {
        currentUser: <?php echo $user_id ?>,
        isAdmin: <?php echo current_user_can('administrator') ? 'true' : 'false' ?>,
        deletionPrivileges: <?php echo (isset($options['deletion_privileges']) && $options['deletion_privileges'] == 1) ? 'true' : 'false' ?>,
        deleteNonce: '<?php echo $delete_nonce ?>',
        baseUrl: 'admin.php?page=smart-crm'
    };
    
    // Scheduler Grid erstellen
    const schedulerGrid = PSCRM.createSchedulerGrid('#grid', gridOptions);
    
    // Activity Modal Handler (für bereits implementierte Kendo Fenster)
    $(document).on('click', '#save_activity_from_modal', function () {
        var id = $(this).data('id');
        $('.modal_loader').show();
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'WPsCRM_scheduler_update',
                ID: id,
                fatto: $('input[name="fatto"]:checked').val(),
                esito: $('#esito').val(),
                self_client: '1',
                security: '<?php echo $update_nonce?>'
            },
            success: function (result) {
                schedulerGrid.reload();
                
                setTimeout(function () {
                    $('._modal').fadeOut('fast');
                    $('.modal_loader').fadeOut('fast');
                }, 200);
                
                PSCRM.notify('Aktivität aktualisiert', 'success');
            },
            error: function (errorThrown) {
                console.log(errorThrown);
                PSCRM.notify('Fehler beim Aktualisieren', 'error');
            }
        });
    });
    
    $(document).on('click', '._reset', function () {
        $('._modal').fadeOut('fast');
        $('input[type="reset"]').trigger('click');
    });

});
</script>