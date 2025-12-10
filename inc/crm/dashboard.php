<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$view=isset($_GET["view"])? $_GET["view"] : "day";
$update_nonce= wp_create_nonce( "update_scheduler" );
$delete_nonce= wp_create_nonce( "delete_activity" );
$page="dashboard";
?>
<script type="text/javascript">

    jQuery(document).ready(function ($) {

		var $format = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
        //update delle activity da modale
        $(document).on('click', '#save_activity_from_modal', function () {
            var id = $(this).data('id');
            $('.modal_loader').show();
            $.ajax({
                url: ajaxurl,
                method:'POST',
                data: {
                	'action': 'WPsCRM_scheduler_update',
                    'ID': id,
                    'fatto': $('input[type="radio"][name="fatto"]:checked').val(),
                    'esito': $('#esito').val(),
					'security':'<?php echo $update_nonce?>'
                },
            	success: function (result) {
                    setTimeout(function () {
                        $('.modal_loader').fadeOut('fast');
                    }, 300);
                    setTimeout(function () {
                        $('._modal').fadeOut('fast');
                    }, 400);
                    // DataTables-Reload für beide Grids:
                    $('#grid_todo').DataTable().ajax.reload(null, false);
                    $('#grid_appuntamenti').DataTable().ajax.reload(null, false);
                }
            });
        });

        $(document).on('click', '._reset',function () {
            $('._modal').fadeOut('fast');
        });

        $(document).ready(function () {
            $('#grid_todo').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        'action': 'WPsCRM_get_scheduler',
                        'type': 1,
                        'view': '<?php echo $view?>',
                        'self_client': '1'
                    },
                    dataSrc: 'scheduler'
                },
                columns: [
                    { data: 'cliente', title: '<?php _e('Kunde','cpsmartcrm')?>' },
                    { data: 'oggetto', title: '<?php _e('Objekt','cpsmartcrm')?>' },
                    { data: 'annotazioni', title: '<?php _e('Beschreibung','cpsmartcrm')?>' },
                    { data: 'data_scadenza', title: '<?php _e('Ablauf','cpsmartcrm')?>' },
                    { data: 'destinatari', title: '<?php _e('Empfänger','cpsmartcrm')?>' },
                    { data: 'status', title: '<?php _e('Status','cpsmartcrm')?>' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        width: "200px",
                        render: function (data, type, row) {
                            return `
                                <button class="btn _flat open-todo" data-id="${row.id_agenda}"><?php _e('Offen','cpsmartcrm')?></button>
                                <button class="btn btn-danger _flat delete-todo" data-id="${row.id_agenda}"><?php _e('Löschen','cpsmartcrm')?></button>
                            `;
                        }
                    }
                ],
                pageLength: 50,
                lengthMenu: [20, 50, 100],
                order: [[3, 'desc']],
                language: {
                    emptyTable: "<h4 style='text-align:center;padding:5%'><?php _e('Kein TODO zu zeigen','cpsmartcrm')?></h4>",
                    info: "<?php _e('Zeige','cpsmartcrm')?> _START_-_END_ <?php _e('von','cpsmartcrm')?> _TOTAL_ <?php _e('gesammt','cpsmartcrm')?>",
                    infoEmpty: "<?php _e('Keine Einträge vorhanden','cpsmartcrm')?>",
                    infoFiltered: "(<?php _e('gefiltert von','cpsmartcrm')?> _MAX_ <?php _e('gesammt','cpsmartcrm')?>)",
                    lengthMenu: "<?php _e('Beiträge pro Seite','cpsmartcrm')?> _MENU_",
                    loadingRecords: "<?php _e('Lade...','cpsmartcrm')?>",
                    processing: "<?php _e('Verarbeite...','cpsmartcrm')?>",
                    search: "<?php _e('Filtern nach','cpsmartcrm')?>:",
                    zeroRecords: "<?php _e('Keine passenden Einträge gefunden','cpsmartcrm')?>",
                    paginate: {
                        first: "<?php _e('Erste Seite','cpsmartcrm')?>",
                        last: "<?php _e('Letzte Seite','cpsmartcrm')?>",
                        next: "<?php _e('Nächste','cpsmartcrm')?>",
                        previous: "<?php _e('Vorherige','cpsmartcrm')?>"
                    },
                    aria: {
                        sortAscending: ": <?php _e('aktivieren um Spalte aufsteigend zu sortieren','cpsmartcrm')?>",
                        sortDescending: ": <?php _e('aktivieren um Spalte absteigend zu sortieren','cpsmartcrm')?>"
                    }
                }
            });

            // "Offen"-Button
            $(document).on('click', '.open-todo', function () {
                var id = $(this).data('id');
                var position = $(this).offset();
                $.ajax({
                    url: ajaxurl,
                    data: {
                        'action': 'WPsCRM_view_activity_modal',
                        'id': id
                    },
                    success: function (result) {
                        $('#dialog-view').show().html(result);
                        $('.modal_inner').animate({
                            'top': position.top - 320 + 'px',
                        }, 1000);
                    },
                    error: function (errorThrown) {
                        console.log(errorThrown);
                    }
                });
            });

            // "Löschen"-Button
            $(document).on('click', '.delete-todo', function () {
                var id = $(this).data('id');
                if (!confirm("<?php _e('Löschen bestätigen','cpsmartcrm') ?>?")) return false;
                window.location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=scheduler/delete.php&ID=')?>"+id+"&ref=dashboard&security=<?php echo $delete_nonce?>";
            });
        });

        $(document).ready(function () {
            $('#grid_appuntamenti').DataTable({
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        'action': 'WPsCRM_get_scheduler',
                        'type': 2,
                        'view': '<?php echo $view?>'
                    },
                    dataSrc: 'scheduler'
                },
                columns: [
                    { data: 'cliente', title: '<?php _e('Kunde','cpsmartcrm')?>' },
                    { data: 'oggetto', title: '<?php _e('Objekt','cpsmartcrm')?>' },
                    { data: 'annotazioni', title: '<?php _e('Beschreibung','cpsmartcrm')?>' },
                    { data: 'data_scadenza', title: '<?php _e('Ablauf','cpsmartcrm')?>' },
                    { data: 'destinatari', title: '<?php _e('Empfänger','cpsmartcrm')?>' },
                    { data: 'status', title: '<?php _e('Status','cpsmartcrm')?>' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        width: "200px",
                        render: function (data, type, row) {
                            return `
                                <button class="btn _flat open-appointment" data-id="${row.id_agenda}"><?php _e('Offen','cpsmartcrm')?></button>
                                <button class="btn btn-danger _flat delete-appointment" data-id="${row.id_agenda}"><?php _e('Löschen','cpsmartcrm')?></button>
                            `;
                        }
                    }
                ],
                pageLength: 50,
                lengthMenu: [20, 50, 100],
                order: [[3, 'desc']],
                language: {
                    emptyTable: "<h4 style='text-align:center;padding:5%'><?php _e('Keine TERMINE vorhanden','cpsmartcrm')?></h4>",
                    info: "<?php _e('Zeige','cpsmartcrm')?> _START_-_END_ <?php _e('von','cpsmartcrm')?> _TOTAL_ <?php _e('gesammt','cpsmartcrm')?>",
                    infoEmpty: "<?php _e('Keine Einträge vorhanden','cpsmartcrm')?>",
                    infoFiltered: "(<?php _e('gefiltert von','cpsmartcrm')?> _MAX_ <?php _e('gesammt','cpsmartcrm')?>)",
                    lengthMenu: "<?php _e('Beiträge pro Seite','cpsmartcrm')?> _MENU_",
                    loadingRecords: "<?php _e('Lade...','cpsmartcrm')?>",
                    processing: "<?php _e('Verarbeite...','cpsmartcrm')?>",
                    search: "<?php _e('Filtern nach','cpsmartcrm')?>:",
                    zeroRecords: "<?php _e('Keine passenden Einträge gefunden','cpsmartcrm')?>",
                    paginate: {
                        first: "<?php _e('Erste Seite','cpsmartcrm')?>",
                        last: "<?php _e('Letzte Seite','cpsmartcrm')?>",
                        next: "<?php _e('Nächste','cpsmartcrm')?>",
                        previous: "<?php _e('Vorherige','cpsmartcrm')?>"
                    },
                    aria: {
                        sortAscending: ": <?php _e('aktivieren um Spalte aufsteigend zu sortieren','cpsmartcrm')?>",
                        sortDescending: ": <?php _e('aktivieren um Spalte absteigend zu sortieren','cpsmartcrm')?>"
                    }
                }
            });

            // "Offen"-Button
            $(document).on('click', '.open-appointment', function () {
                var id = $(this).data('id');
                var position = $(this).offset();
                $.ajax({
                    url: ajaxurl,
                    data: {
                        'action': 'WPsCRM_view_activity_modal',
                        'id': id
                    },
                    success: function (result) {
                        $('#dialog-view').show().html(result);
                        $('.modal_inner').animate({
                            'top': position.top - 320 + 'px',
                        }, 1000);
                    },
                    error: function (errorThrown) {
                        console.log(errorThrown);
                    }
                });
            });

            // "Löschen"-Button
            $(document).on('click', '.delete-appointment', function () {
                var id = $(this).data('id');
                if (!confirm("<?php _e('Löschen bestätigen','cpsmartcrm') ?>?")) return false;
                window.location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=scheduler/delete.php&ID=')?>"+id+"&ref=dashboard&security=<?php echo $delete_nonce?>";
            });
        });

});
    </script> 
<h4 class="page-header"><?php _e('Schnellmenü','cpsmartcrm')?><!--<span class="crmHelp" data-help="quick-menu"></span>--></h4>
<div class="col-md-12" style="border-bottom:8px solid #337ab7;margin-bottom:30px">
<ul class="quick_menu" style="padding-bottom:10px;float: left;width: 100%;">
    <?php if($privileges ==null || $privileges['agenda'] ==2){?>
    <li onClick="location.href='<?php echo admin_url()?>?page=smart-crm&p=scheduler/form.php&tipo_agenda=1';return false;">
        <i class="glyphicon glyphicon-tag"></i><br /><b ><?php _e('Neues Todo','cpsmartcrm')?><small></small></b>
    </li>
    <li onClick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=scheduler/form.php&tipo_agenda=2')?>';return false;">
        <i class="glyphicon glyphicon-pushpin"></i><br /><b ><?php _e('Neuer Termin','cpsmartcrm')?><small></small></b>
    </li>
    <?php } ?>
    <?php if($privileges ==null || $privileges['customer'] ==2){?>
	<li onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=clienti/form.php')?>';return false;">
        <i class="glyphicon glyphicon-user"></i><br /><b ><?php _e('Neukunde','cpsmartcrm')?><small></small></b>
    </li>
    <?php } ?>
    <?php if($privileges ==null || $privileges['quote'] ==2){?>
    <li onClick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&type=1')?>';return false;">
		<i class="glyphicon glyphicon-circle-arrow-right"></i>
		<br />
		<b>
			<?php _e('Neues Angebot','cpsmartcrm')?>
			<small></small>
		</b>
	</li>
    <?php } ?>
    <?php if($privileges ==null || $privileges['invoice'] ==2){?>
	<li onclick="location.href='<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php&type=2')?>';return false;">
        <i class="glyphicon glyphicon-open-file"></i><br /><b ><?php _e('Neue Rechnung','cpsmartcrm')?><small></small></b>
    </li>
    <?php } ?>
    <?php
	if(current_user_can('manage_options') ){
?>

	<li onclick="location.href='<?php echo admin_url('admin.php?page=smartcrm_settings&tab=CRM_general_settings')?>';return false;">
		<i class="glyphicon glyphicon-cog"></i>
		<br />
		<b>
			<?php _e('Einstellungen','cpsmartcrm')?>
			<small></small>
		</b>
	</li>
	<?php }?>
</ul>
</div>
<div class="col-md-12" style="background:#fafafa;padding:15px">
    <h4 class="page-header"><?php _e('Ihre aktuellen Benachrichtigungen','cpsmartcrm')?><span class="crmHelp" data-help="home-notifications"></span> 
		<div id="week_menu" style="float: right;margin-right: 50px;margin-top: -6px;">
			<ul class="nav nav-pills">
				<li role="presentation" <?php echo  (strstr($menu,"day") || !isset($_GET['view']) ) ? "class=\"active\"" :null  ?>><a href="<?php echo admin_url()?>?page=smart-crm&view=day"><?php _e('Tagesansicht','cpsmartcrm')?></a></li>
				<li role="presentation" <?php echo  strstr($menu,"week") ? "class=\"active\"" :null  ?>><a href="<?php echo admin_url()?>?page=smart-crm&view=week"><?php _e('Wochenansicht','cpsmartcrm')?></a></li>

			</ul>
		</div>
	</h4>
    <?php if($privileges==null || $privileges['agenda'] >0){ ?>
<h3 style="margin:0 20px"><?php _e('ToDo','cpsmartcrm')?>
	<ul class="select-action _llegend pull-right" style="width:initial">
		<span style="float:right;font-size:.6em;background: none!important;">
			<li class="no-link" style="margin-top:4px">
				<?php _e('Legende','cpsmartcrm') ?>:
			</li>
			<li class="no-link">
				<i class="glyphicon glyphicon-ok" style="color:green;font-size:1.3em"></i><?php _e('Erledigt','cpsmartcrm') ?>
			</li>
			<li class="no-link">
				<i class="glyphicon glyphicon-bookmark  " style="color:black;font-size:1.3em"></i><?php _e('Noch zu erledigen','cpsmartcrm') ?>
			</li>
			<li class="no-link">
				<i class="glyphicon glyphicon-remove" style="color:red;font-size:1.3em"></i><?php _e('Abgesagt','cpsmartcrm') ?>
			</li>
			<li class="no-link">
				<span class="tipped" style="width:13px;height:13px;display:inline-flex" title="<?php _e('Maus darüber fahren, um Informationen anzuzeigen','cpsmartcrm')?>"></span><?php _e('Info Tooltip','cpsmartcrm')?>
			</li>
		</span>
	</ul>



</h3>
<table id="grid_todo" class="datagrid table table-striped table-bordered" style="margin-bottom:24px"></table>
	
<h3><?php _e('Termine','cpsmartcrm')?>
	<ul class="select-action _llegend pull-right" style="width:initial">
		<span style="float:right;font-size:.6em;background: none!important;">
			<li class="no-link" style="margin-top:4px">
				<?php _e('Legende','cpsmartcrm') ?>:
			</li>
			<li class="no-link">
				<i class="glyphicon glyphicon-ok" style="color:green;font-size:1.3em"></i><?php _e('Erledigt','cpsmartcrm') ?>
			</li>
			<li class="no-link">
				<i class="glyphicon glyphicon-bookmark  " style="color:black;font-size:1.3em"></i><?php _e('Noch zu erledigen','cpsmartcrm') ?>
			</li>
			<li class="no-link">
				<i class="glyphicon glyphicon-remove" style="color:red;font-size:1.3em"></i><?php _e('Abgesagt','cpsmartcrm') ?>
			</li>
			<li class="no-link">
				<span class="tipped" style="width:13px;height:13px;display:inline-flex" title="<?php _e('Bewege die Maus darüber, um Informationen anzuzeigen','cpsmartcrm')?>"></span><?php _e('Info Tooltip','cpsmartcrm')?>
			</li>
		</span>
	</ul>
	</h3>
<table id="grid_appuntamenti" class="datagrid table table-striped table-bordered" style="margin-bottom:24px"></table> 
</div>
<?php } else{?> 
<h3 style="color:crimson"><?php _e("Du hast keine Berechtigung, auf den Benachrichtigungsbereich zuzugreifen","cpsmartcrm") ?></h3>
<?php } ?>
<div id="dialog-view" style="display:none;margin: 0 auto; text-align: center; z-index: 1000; width: 100%; height: 100%; position: absolute;left: 0;top:0;"  class="_modal">
</div>
