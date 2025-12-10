<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function WPsCRM_JS_documents_datasource($type){
	ob_start();
?>
//pluggable/documenti/list.php
var dataSource_<?php echo $type?> = new kendo.data.DataSource(
	{
		transport: {
			read: function (options) {
				$.ajax({
					url: ajaxurl,
					data: {
						'action': 'WPsCRM_get_documents',type:<?php echo $type?>
					},
					success: function (result) {
						console.log(result);
						options.success(result);
					},
					error: function (errorThrown) {
						console.log(errorThrown);
					}
				})
			}

		},
		schema: {
			data: function (response) {
				return response.documents
			},
			total: function (response) {
						return response.documents.length;
			},
			model: {
				ID: "ID",
				fields: {
					ID: { editable: false, type:"number" },
					tipo: { editable: false },
					ID_clienti:{hidden:true},
					progressivo: { editable: false, type: "number" },
					datao: {
						editable: false,
						type: "date",
						filterable: {
							cell: {
								template: '#= kendo.toString(kendo.parseDate(datao, "yyyy-MM-dd"), "' + $format + '") #'
							}
						}
					},
					data_scadenza: { 
                        editable: false, 
                        type: "date",
						filterable: {
							cell: {
								template: '#= kendo.toString(kendo.parseDate(data_scadenza, "yyyy-MM-dd"), "' + $format + '") #'
							}
						} 
                    },
					cliente: { editable: false },
					oggetto: { editable: false },
					importo: { editable: false, type: "number", template: '#= kendo.toString(importo, "n") #'},
					pagato: { editable: false },
					origine_proforma:{hidden:true}
				}
			}
		},
		//group: { field: "tipo", dir: "asc" },
		aggregate: [{ field: "importo", aggregate: "sum"} ],
		pageSize: 50,
	}
);

<?php
	echo ob_get_clean();
}
add_action('WPsCRM_documentsDatasource','WPsCRM_JS_documents_datasource',9,1);

/**
 *display documents grid
 **/
function WPsCRM_JS_display_documentsGrid($delete_nonce,$type){
	ob_start();
		?>
		$("#grid-<?php echo $type?>").kendoGrid({
		toolbar: kendo.template($("#gridHeader_<?php echo $type?>").html()),
        dataSource: dataSource_<?php echo $type?>,
		noRecords: {
			template: "<h4 style=\"text-align:center;padding:5%\"><?php _e('Keine Dokumente zum anzeigen. Du kannst über das obige Menü neue Dokumente erstellen. Stelle sicher, dass Du einige Kontakte archiviert hast, bevor Du neue Dokumente erstellst','cpsmartcrm')?></h4>"
    	},
        height: gridheight,
        sortable: true,
        groupable: {
            messages: {
            empty: "<?php _e('Ziehe die Spaltenüberschriften und lege sie hier ab, um sie nach dieser Spalte zu gruppieren','cpsmartcrm') ?>"
            }
        },
        pageable: {
            refresh: true,
            pageSizes: true,
            buttonCount: 5,
            messages:
                {
                    display: "<?php _e('Zeige','cpsmartcrm') ?> {0}-{1}  <?php _e('von','cpsmartcrm') ?> {2} <?php _e('insgesamt','cpsmartcrm') ?>",
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
		filterable:{
            messages:
                {
                info: "<?php _e('Filtern nach','cpsmartcrm') ?> "
                },
        	//filterMenuInit: filterMenu,
            extra: false,
            operators:
                {
                string:
                    {
					contains: "<?php _e('Enthält','cpsmartcrm') ?> ",
					startswith: "<?php _e('Beginnt mit','cpsmartcrm') ?>",
                    eq: "<?php _e('Gleich','cpsmartcrm') ?>",
                    neq: "<?php _e('Nicht gleich','cpsmartcrm') ?>",
                    },
                date: {
                	lte: "<?php _e('Früher als','cpsmartcrm') ?>",
                	gte: "<?php _e('Später als','cpsmartcrm') ?>",
                    eq: "<?php _e('Gleich','cpsmartcrm') ?>"
                }
            }
        },
		selectable:true,
        dataBound:loadCellsAttributesForDocuments,
        columns: [{ field: "ID", title: "ID", hidden: true,width:1 },
				{ 
				field: "tipo", 
				title: "<?php _e('Typ','cpsmartcrm') ?>", 
				width: 60,
				hidden:true
				},
                                { field: "progressivo", title: "#",width:20 },
					{ field: "ID_clienti", title: "Id_cliente ", hidden:true,width:1 },
					{ field: "datao", title: "<?php _e('Datum','cpsmartcrm') ?>", width: 70, template: '#= kendo.toString(kendo.parseDate(datao, "yyyy-MM-dd"), "' + $format + '") #',
						filterable:	{
							ui: function (element) {
								element.kendoDatePicker({
									format: $format
								});
							}
						}
					},
					{ field: "cliente", title: "<?php _e('Kontakt','cpsmartcrm') ?>" , width: 150 },
					{ field: "importo", title: "<?php _e('Summe','cpsmartcrm') ?>", width: 80, template: '#= "<?php echo WPsCRM_get_currency()->symbol ?> " + kendo.toString(importo, "n") #', aggregates: ["sum"], footerTemplate: "Total : #= kendo.toString(parseFloat(sum) , 'n') #"  },
					{ field: "filename", hidden: true },
					{ field: "origine_proforma", title: "proforma", hidden: true},
					{field: "data_scadenza", title: "<?php _e('Verfallsdatum','cpsmartcrm') ?>", width: 80, template: '#= kendo.toString(kendo.parseDate(data_scadenza, "yyyy-MM-dd"), "' + $format + '") #',
						filterable:
							{
        						ui: function (element) {
        							element.kendoDatePicker({
        								format: $format
        							});
        						}
							}
					},
					<?php if( (int)$type==2 || (int)$type==3){ ?>{ field: "pagato", title: "<?php _e('Bezahlt','cpsmartcrm') ?>", width: 80 },<?php } elseif((int)$type==1){
                      ?>
						{ field: "pagato", title: "<?php _e('Akzeptiert','cpsmartcrm') ?>", width: 50 },
					<?php
						  } ?>
					{ field: "registrato", title: "registrato", hidden: true,width:1 },
				    { field: "documentSent", title: "sent", hidden: true,width:1 },
					{ field: "agente", hidden: true,width:1 },
                    {field:"privileges",hidden:true},
					{ width: 240 ,command: [
						{
							name: "<?php _e('Drucken','cpsmartcrm') ?>",
							click: function (e) {
								e.preventDefault();
								var tr = $(e.target).closest("tr"); // get the current table row (tr)
								var data = this.dataItem(tr);
								location.replace("<?php echo admin_url('admin.php?page=smart-crm&p=documenti/document_print.php&id_invoice=')?>"+data.ID )
							},
							className: "btn btn-info _flat"
						},{
							name: "<?php _e('Bearbeiten','cpsmartcrm') ?>",
							click: function (e) {
								e.preventDefault();
								var tr = $(e.target).closest("tr"); // get the current table row (tr)
								var data = this.dataItem(tr);
								if (data.registrato==1)
									{
										alert("<?php _e('Diese Rechnung ist registriert und kann nicht bearbeitet werden','cpsmartcrm') ?>");
										return false;
									}
								if (data.tipo=="P")
									location.href="<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&ID=')?>"+data.ID;
								else
									location.href="<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php&ID=')?>"+data.ID;
							},
							className: "btn _flat"
						},
						{
							name: "<?php _e('Delete','cpsmartcrm') ?>",
							click: function(e) {
								var tr = $(e.target).closest("tr"); // get the current table row (tr)
								var data = this.dataItem(tr);
								if (data.registrato==1)
									{
										alert("<?php _e('Diese Rechnung ist registriert und nicht löschbar','cpsmartcrm') ?>");
										return false;
									}
								if (!confirm("<?php _e('Löschen bestätigen','cpsmartcrm') ?>?"))
									return false;
                  console.log("check");
								location.href="<?php echo admin_url('admin.php?page=smart-crm&p=documenti/delete.php&ID=')?>"+data.ID +"&security=<?php echo $delete_nonce?>&fromGrid=<?php echo $type?>";
							},
							className: "btn btn-danger _flat"
						}
					]
				}
			]
		});
<?php
	echo ob_get_clean();
	return;
}
add_action('WPsCRM_documentsGrid','WPsCRM_JS_display_documentsGrid',9,2);

function WPsCRM_HTML_documentsGridToolbar(){
	ob_start();
?>
<script id="gridHeader_1" type="text/x-kendo-template">
		
	<?php _e('Nach Datum filtern','cpsmartcrm') ?>:
    <label><?php _e('Von','cpsmartcrm') ?>:</label>
	<input id="dateFrom_1" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
	<label><?php _e('Bis','cpsmartcrm') ?>:</label>
    <input id="dateTo_1" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
    <button class="dateRange button-primary _flat" data-grid="1"><?php _e('Filter','cpsmartcrm') ?></button>&nbsp;&nbsp;&nbsp;
	<label><?php _e('Nach Agent filtern','cpsmartcrm') ?>:</label>
	<input id="selectAgent_1" />&nbsp;&nbsp;&nbsp;
	<button  class="btn_reset button-secondary _flat" data-grid="1" style="vertical-align:initial"><?php _e('Filter zurücksetzen','cpsmartcrm') ?></button>

</script>
<script id="gridHeader_2" type="text/x-kendo-template">
	<?php _e('Nach Datum filtern','cpsmartcrm') ?>:
	<label><?php _e('Von','cpsmartcrm') ?>:</label>
	<input id="dateFrom_2" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
	<label><?php _e('Bis','cpsmartcrm') ?>:</label>
    <input id="dateTo_2" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
	<button class="dateRange button-primary _flat" data-grid="2"><?php _e('Filter','cpsmartcrm') ?></button>&nbsp;&nbsp;&nbsp;
		<label><?php _e('Nach Agent filtern','cpsmartcrm') ?>:</label>
	<input id="selectAgent_2" />&nbsp;&nbsp;&nbsp;
	<button  class="btn_reset button-secondary _flat" data-grid="2" style="vertical-align:initial"><?php _e('Filter zurücksetzen','cpsmartcrm') ?></button>
</script>
<script id="gridHeader_3" type="text/x-kendo-template">
    <?php _e('Nach Datum filtern','cpsmartcrm') ?>:
    <label><?php _e('Von','cpsmartcrm') ?>:</label>
	<input id="dateFrom_3" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
	<label><?php _e('Bis','cpsmartcrm') ?>:</label>
    <input id="dateTo_3" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
    <button class="dateRange button-primary _flat" data-grid="3"><?php _e('Filter','cpsmartcrm') ?></button>&nbsp;&nbsp;&nbsp;
		<label><?php _e('Nach Agent filtern','cpsmartcrm') ?>:</label>
	<input id="selectAgent_3" />&nbsp;&nbsp;&nbsp;
	<button  class="btn_reset button-secondary _flat" data-grid="3" style="vertical-align:initial"><?php _e('Filter zurücksetzen','cpsmartcrm') ?></button>

</script>
<?php
	echo ob_get_clean();
	return;
}
add_action('WPsCRM_documents_grid_toolbar','WPsCRM_HTML_documentsGridToolbar',9);