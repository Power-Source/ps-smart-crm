<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$delete_nonce= wp_create_nonce( "delete_activity" );
$update_nonce= wp_create_nonce( "update_scheduler" );
?>
<script id="gridHeader" type="text/x-kendo-template">
	<?php _e('Filter by date','cpsmartcrm') ?>:
    <label><?php _e('From','cpsmartcrm') ?>:</label>
    <input id="dateFrom" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
	<label><?php _e('To','cpsmartcrm') ?>:</label>
    <input id="dateTo" style="width: 200px" data-role="datepicker" />&nbsp;&nbsp;
    <button id="dateRange" class="button-primary _flat"><?php _e('Filter','cpsmartcrm') ?></button>&nbsp;&nbsp;&nbsp;
	<button  id="btn_reset"  class="button-secondary _flat" style="vertical-align:initial"><?php _e('Reset filters','cpsmartcrm') ?></button>

</script>
<script type="text/javascript">

jQuery(document).ready(function ($) {
	var $format = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
    var ragione_sociale=$("#ragione_sociale").val();
    var param="?";
    if (ragione_sociale)
      param+="ragione_sociale="+ragione_sociale+"&";
    param = param.substr(0, param.length - 1);
	<?php do_action('WPsCRM_scheduler_datasource') ?>

	<?php do_action('WPsCRM_schedulerGrid',$delete_nonce) ?>

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
				security:'<?php echo $update_nonce?>'
            },
            success: function (result) {
            	var grid = $("#grid").data("kendoGrid"), _group=[];
            console.log( grid.dataSource.group());
                if(grid.dataSource.group().length){
                     _group= { field: "tipo_agenda", dir: "asc" }
                }
            	var newDatasource = new kendo.data.DataSource({
                    transport: {
                        read: function (options) {
                            jQuery.ajax({
                                url: ajaxurl,
                                data: {
                                    'action': 'WPsCRM_get_scheduler',
                                    //'id_cliente': id
                                     'self_client':1
                                },
                                success: function (result) {
                                    console.log(result);
                                    jQuery("#grid").data("kendoGrid").dataSource.data(result.scheduler);

                                },
                                error: function (errorThrown) {
                                    console.log(errorThrown);
                                }
                            })
                        }
                    },
                    group: _group,
                    schema: {
                        model: {
                            id: "id_agenda",
                            fields: {
                                tipo: { editable: false },
                                oggetto: { editable: false },
                                annotazioni: { editable: false },
                                data_scadenza: { type: "date", editable: false },
                            }
                        }
                    },
                    pageSize: 50,
                });
            	setTimeout(function () {
            		grid.setDataSource(newDatasource);
            		grid.dataSource.read();

                }, 20);
                setTimeout(function () {
                	grid.refresh()
                }, 10);
                setTimeout(function () {
                	$('._modal').fadeOut('fast');
                	$('.modal_loader').fadeOut('fast');
                }, 200);

            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        })

    })
	$(document).on('click', '._reset', function () {

        $('._modal').fadeOut('fast');
        $('input[type="reset"]').trigger('click');
    })
    var dateFrom = $("#dateFrom").kendoDateTimePicker({
    	format: $format
    }).data('kendoDateTimePicker');
    console.log(dateFrom.value());
    var dateTo = $("#dateTo").kendoDateTimePicker({
    	format: $format
    	//value: new Date(), format: $format
    }).data('kendoDateTimePicker');
    $("#dateRange").on("click", function (e) {
    	var grid = $('#grid').data('kendoGrid');
    	var ds = grid.dataSource;

    	var filter = [
			{ field: "data_scadenza", operator: "gte", value: dateFrom.value() },
			{ field: "data_scadenza", operator: "lte", value: dateTo.value() }
    	];
    	ds.filter(filter);
    })
    $("#btn_reset").on("click", function (e) {
    	var ds = $("#grid").data("kendoGrid").dataSource;
    	ds.filter([]);
    	dateFrom.value('');
    	dateTo.value('');

    })
});
    </script>
<ul class="select-action">

        <li onClick="location.href='<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=1')?>';return false;" class="btn btn-info btn-sm _flat btn_todo"><i class="glyphicon glyphicon-tag"></i> 
            <b> <?php _e('NEW TODO','cpsmartcrm')?></b>
        </li>
        <li  onClick="location.href='<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=2')?>';return false;" class="btn  btn-sm _flat btn_appuntamento"><i class="glyphicon glyphicon-pushpin"></i> 
            <b> <?php _e('NEW APPOINTMENT','cpsmartcrm')?></b>
        </li>
    <li class="btn  btn-sm _flat" style="background:#ccc;"><span class="crmHelp" data-help="section-scheduler" style="position:relative;top:-3px"></span></li>
        <!--<li  onClick="location.href='?page=smart-crm&p=scheduler/form.php&tipo_agenda=3';return false;" class="btn btn-primary btn-sm _flat btn_activity"><i class="glyphicon glyphicon-option-horizontal"></i> 
            <b> <?php _e('NEW ACTIVITY','cpsmartcrm')?></b>
        </li>-->
    <span style="float:right;">
        <li class="no-link" style="margin-top:4px">
            <?php _e('Legend','cpsmartcrm') ?>:
        </li>
        <li class="no-link">
            <i class="glyphicon glyphicon-ok" style="color:green;font-size:1.3em"></i>
            <?php _e('Done','cpsmartcrm') ?>
        </li>
        <li class="no-link">
            <i class="glyphicon glyphicon-bookmark  " style="color:black;font-size:1.3em"></i>
            <?php _e('Noch zu erledigen','cpsmartcrm') ?>
        </li>
        <li class="no-link">
            <i class="glyphicon glyphicon-remove" style="color:red;font-size:1.3em"></i>
            <?php _e('Canceled','cpsmartcrm') ?>
        </li>
        <li class="no-link">
            <span class="tipped" style="width:13px;height:13px;display:inline-flex" title="<?php _e('Mouse over to display info','cpsmartcrm')?>"></span>
            Info tooltip
        </li>
    </span>
</ul>
<!--<input type="button" id="btn_cerca" value="CERCA" style="display:none">-->
<div id="grid" class="datagrid _scheduler"></div> 
<div id="dialog-view"  class="_modal">
</div>    
