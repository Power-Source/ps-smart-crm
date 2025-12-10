<?php

/**
 * Sets the datasource used in scheduler grid
 */
function WPsCRM_JS_schedulerList_datasource(){
	ob_start();
?>
    var dataSource = new kendo.data.DataSource(
			{
				transport: {
					read: function (options) {
						$.ajax({
							url: ajaxurl,
							data: {
								action: 'WPsCRM_get_scheduler',
								self_client:1
							},
							success: function (result) {
								options.success(result);
								//$("#grid").data("kendoGrid").dataSource.data(result.scheduler);

							},
							error: function (errorThrown) {
								console.log(errorThrown);
							}
						})
					}
				},
				group: { field: "tipo_agenda", dir: "asc" },
				schema: {
					data: function (response) {
						return response.scheduler
					},total: function (response) {
						return response.scheduler.length;
					},
					model: {
						id: "id_agenda",
						fields: {
							tipo_agenda: { editable: false },
							cliente: { editable: false },
							oggetto: { editable: false },
							annotazioni: { editable: false },
							esito: { editable: false },
							data_scadenza: {
								type: "date", editable: false,
								filterable: {
									cell: {
										template: '#= kendo.toString(kendo.parseDate(datao, "yyyy-MM-dd"), "' + $format + '") #'
									}
								}
							},
							destinatari: { editable: false },
						}
					}
				},
				pageSize: 50,
			}
		)

<?php
	echo ob_get_clean();
	return;
}
add_action('WPsCRM_scheduler_datasource','WPsCRM_JS_schedulerList_datasource',9);

/**
 *display scheduler list  grid
 **/
function WPsCRM_JS_display_schedulerGrid($delete_nonce){
	ob_start();
        $current_user = wp_get_current_user();
        $user_id=$current_user->ID;
        $options=get_option('CRM_general_settings');
?>
    var c_user=parseInt(<?php echo $user_id?>);
    var del_priv="<?php echo isset($options['deletion_privileges'])&& $options['deletion_privileges']=="1" ?>";
    var is_admin="<?php echo current_user_can('administrator')?>";
    var grid = $("#grid").kendoGrid({
    	toolbar: kendo.template($("#gridHeader").html()),
    	dataSource: dataSource,
		noRecords: {
			template: "<h4 style=\"text-align:center;padding:5%\"><?php _e('Keine AKTIVITÄTEN zum Anzeigen; Du kannst neue Aufgaben oder neue Termine hinzufügen. Stelle sicher, dass einige Kunden verfügbar sind, oder erstelle im Hauptmenü einen neuen Kunden','cpsmartcrm')?></h4>"
		},
        groupable: {
            messages: {
                empty: "<?php _e('Ziehe die Spaltenüberschriften und lege sie hier ab, um sie nach dieser Spalte zu gruppieren','cpsmartcrm') ?>"
            }
        },
		pageable:
        {
            pageSizes: [20, 50, 100],
            messages:
                {
                    display: "<?php _e('Zeige','cpsmartcrm') ?> {0}-{1}  <?php _e('von','cpsmartcrm') ?> {2} <?php _e('gesamt','cpsmartcrm') ?>",
                    of: "<?php _e('von','cpsmartcrm') ?> {0}",
                    itemsPerPage: "<?php _e('Beiträge pro Seite','cpsmartcrm') ?>",
                    first: "<?php _e('Erste Seite','cpsmartcrm') ?>",
                    last: "<?php _e('Letzte Seite','cpsmartcrm') ?>",
                    next: "<?php _e('Nächste','cpsmartcrm') ?>",
                    previous: "<?php _e('Vorherige','cpsmartcrm') ?>",
                    refresh: "<?php _e('Neu laden','cpsmartcrm') ?>",
                    morePages: "<?php _e('Mehr','cpsmartcrm') ?>"
                },
        },
    	sortable: true,
		filterable:true,
        serverPaging: true,
        dataBound: loadCellsAttributesScheduler,
        columns: [{ field: "id_agenda", title: "ID", hidden: true },
				  { field: "fk_utenti_ins", title: "Ins", hidden: true },
				  { field: "tipo_agenda", title: "<?php _e('Typ','cpsmartcrm') ?>", width: 150 },
				  { field: "cliente", title: "<?php _e('Kunde','cpsmartcrm') ?>" },
				  { field: "oggetto", title: "<?php _e('Objekt','cpsmartcrm') ?>" },
				  { field: "data_scadenza", title: "<?php _e('Ablauf','cpsmartcrm') ?>", template: '#= kendo.toString(kendo.parseDate(data_scadenza, "yyyy-MM-dd HH:mm:ss"), "' + $format + '") #' ,
				  	filterable: {
				  		ui: function (element) {
				  			element.kendoDateTimePicker({
				  				format: $format
				  			});
				  		}
				  	}
				  },
				{ field: "destinatari", title: "<?php _e('Empfänger','cpsmartcrm')?>" },
                {field:"privileges",hidden:true},
				{ command: [

				{
                name: "<?php _e('Offen','cpsmartcrm') ?>",
                click: function (e) {
                    e.preventDefault();
                    var tr = $(e.target).closest("tr"); // get the current table row (tr)
                    var _row = this.dataItem(tr);
                    $.ajax({
                        url: ajaxurl,
                        data: {
                        	'action': 'WPsCRM_view_activity_modal',
                            'id': _row.id,
                            'report':$(e.currentTarget).data('report')
                        },
                        success: function (result) {
                            //console.log(result);
                            $('#dialog-view').show().html(result)
                            //$("#grid").data("kendoGrid").dataSource.data(result.scheduler);

                        },
                        error: function (errorThrown) {
                            console.log(errorThrown);
                        }
                    })

                },
                className: "btn btn-inverse _flat"
            },
            {
            name: "<?php _e('Löschen','cpsmartcrm') ?>",
            click: function (e) {
                var tr = $(e.target).closest("tr");
                var data = this.dataItem(tr);
                if ((is_admin=="" && del_priv==1) || (is_admin=="" && del_priv==""  && c_user!=parseInt(data.fk_utenti_ins))) {
                    alert("<?php _e('Du hast nicht die Berechtigung, dieses Ereignis zu löschen.','cpsmartcrm') ?>")
                    return false;
                }
                if (!confirm("<?php _e('Löschen bestätigen','cpsmartcrm') ?>?"))
                    return false;
                e.preventDefault();
                location.href="<?php echo admin_url('admin.php?page=smart-crm&p=scheduler/delete.php&ID=')?>"+data.id +"ref=scheduler&security=<?php echo $delete_nonce?>";
            },
           className: "btn btn-danger _flat"
           }
        ], width: 200
        }, { field: "esito", hidden: true }
		, { field: "status", title: "<?php _e('Status','cpsmartcrm')?>", width: 100, "filterable":false }
        , { field: "class", hidden: true }

        ],
        height: 500,
        editable:"popup"
      });


<?php
	echo ob_get_clean();
	return;
}
add_action('WPsCRM_schedulerGrid','WPsCRM_JS_display_schedulerGrid',9,1);