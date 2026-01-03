<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PS Smart CRM - Vanilla.js Scheduler List
 * 
 * Moderne Planerliste mit Vanilla.js statt Kendo UI
 */

function WPsCRM_display_scheduler_list() {
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

<!-- Tabs for Appointments and TODOs -->
<div class="pscrm-tabs" style="margin: 20px 0;">
    <ul class="nav nav-tabs" role="tablist" style="border-bottom: 2px solid #ddd; list-style: none; padding: 0; margin: 0;">
        <li role="presentation" style="display: inline-block; margin-right: 10px;">
            <a href="#tab-appointments" role="tab" class="nav-link active pscrm-tab-link" data-tab="appointments" style="padding: 10px 15px; text-decoration: none; border: 1px solid #ddd; border-bottom: none; border-radius: 4px 4px 0 0; background: #f5f5f5; cursor: pointer; display: inline-block;">
                <i class="glyphicon glyphicon-pushpin"></i> <?php _e('Appointments','cpsmartcrm') ?>
            </a>
        </li>
        <li role="presentation" style="display: inline-block;">
            <a href="#tab-todos" role="tab" class="nav-link pscrm-tab-link" data-tab="todos" style="padding: 10px 15px; text-decoration: none; border: 1px solid #ddd; border-bottom: none; border-radius: 4px 4px 0 0; background: #f5f5f5; cursor: pointer; display: inline-block;">
                <i class="glyphicon glyphicon-tag"></i> <?php _e('TODOs','cpsmartcrm') ?>
            </a>
        </li>
    </ul>
</div>

<!-- Toolbar Buttons -->
<ul class="select-action">
    <li onClick="location.href='<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=1')?>';return false;" class="btn btn-info btn-sm _flat btn_todo">
        <i class="glyphicon glyphicon-tag"></i> 
        <b><?php _e('NEW TODO','cpsmartcrm')?></b>
    </li>
    <li onClick="location.href='<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=2')?>';return false;" class="btn btn-sm _flat btn_appuntamento">
        <i class="glyphicon glyphicon-pushpin"></i> 
        <b><?php _e('NEW APPOINTMENT','cpsmartcrm')?></b>
    </li>
    
    <span style="float:right;">
        <li class="no-link" style="margin-top:4px">
            <?php _e('Legend','cpsmartcrm') ?>:
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
            <span class="tipped" style="width:13px;height:13px;display:inline-flex" title="<?php _e('Mouse over to display info','cpsmartcrm')?>"></span>
            Info tooltip
        </li>
    </span>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Appointments Tab -->
    <div role="tabpanel" class="tab-pane fade in active" id="tab-appointments">
        <div id="grid-appointments" class="datagrid _scheduler"></div>
    </div>
    
    <!-- TODOs Tab -->
    <div role="tabpanel" class="tab-pane fade" id="tab-todos">
        <div id="grid-todos" class="datagrid _scheduler"></div>
    </div>
</div>

<!-- Modal für Aktivitätsansicht -->
<div id="dialog-view" class="_modal"></div>

<script type="text/javascript">
(function($) {
	'use strict';
	
	$(document).ready(function() {
		
		// Prüfe ob PSCRM vorhanden ist
		if (typeof PSCRM === 'undefined' || typeof PSCRM.createSchedulerGrid !== 'function') {
			console.error('PSCRM oder createSchedulerGrid nicht geladen!');
			return;
		}
		
		try {
			// Grid-Konfiguration
			const gridOptions = {
				currentUser: <?php echo intval($user_id) ?>,
				isAdmin: <?php echo current_user_can('administrator') ? 'true' : 'false' ?>,
				deletionPrivileges: <?php echo (isset($options['deletion_privileges']) && $options['deletion_privileges'] == 1) ? 'true' : 'false' ?>,
				deleteNonce: '<?php echo esc_attr($delete_nonce) ?>',
				baseUrl: 'admin.php?page=smart-crm'
			};
			
			// Scheduler Grid für Appointments erstellen (tipo_agenda = 2)
			const appointmentsGrid = PSCRM.createSchedulerGrid('#grid-appointments', {
				...gridOptions,
				tipo_agenda: 2
			});
			
			// Scheduler Grid für TODOs erstellen (tipo_agenda = 1)
			const todosGrid = PSCRM.createSchedulerGrid('#grid-todos', {
				...gridOptions,
				tipo_agenda: 1
			});
			
			// Tab-Wechsel Handler (ohne Bootstrap)
			$(document).on('click', '.pscrm-tab-link', function(e) {
				e.preventDefault();
				const tabName = $(this).data('tab');
				
				// Alle Tabs als inaktiv markieren
				$('.pscrm-tab-link').removeClass('active');
				$('.tab-pane').removeClass('active');
				
				// Aktuellen Tab als aktiv markieren
				$(this).addClass('active');
				
				// Entsprechenden Tab-Content aktivieren
				if (tabName === 'appointments') {
					$('#tab-appointments').addClass('active');
					if (appointmentsGrid && typeof appointmentsGrid.reload === 'function') {
						appointmentsGrid.reload();
					}
				} else if (tabName === 'todos') {
					$('#tab-todos').addClass('active');
					if (todosGrid && typeof todosGrid.reload === 'function') {
						todosGrid.reload();
					}
				}
			});
			
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
						security: '<?php echo esc_attr($update_nonce) ?>'
					},
					success: function (result) {
						appointmentsGrid.reload();
						todosGrid.reload();
						
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
			
		} catch (error) {
			console.error('Fehler bei der Grid-Initialisierung:', error);
		}
	});
})(jQuery);
</script>
<?php
}
