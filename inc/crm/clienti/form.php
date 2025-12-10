<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$delete_nonce = wp_create_nonce( "delete_customer" );
$update_nonce= wp_create_nonce( "update_customer" );
$scheduler_nonce = wp_create_nonce( "update_scheduler" );
$ID = isset($_REQUEST["ID"])?$_REQUEST["ID"]:0;
$table = WPsCRM_TABLE."clienti";
$ID_azienda = "1";
$email="";
$where = "FK_aziende=$ID_azienda";
$current_user = wp_get_current_user();
$agent_disabled="";
$style_disabled="";
is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
    $agent_obj=new AGsCRM_agent();
    if ($agent_obj->isAgent){
        $agent_disabled="disabled='disabled'";
        $style_disabled="style='display:none'";        
    }
}
else {
    if ( WPsCRM_is_agent() && ! WPsCRM_agent_can() )
    {
        $agent_disabled="disabled='disabled'";
        $style_disabled="style='display:none'";
    }
}
if ( $ID )
{
	$sql = "select * from $table where ID_clienti=$ID";
	//echo $sql;
    $riga = $wpdb->get_row($sql, ARRAY_A);
    $agente = $riga["agente"];
	$cliente = $riga["ragione_sociale"] ? $riga["ragione_sociale"] : $riga["nome"]." ".$riga["cognome"];
	$cliente = stripslashes( $cliente );
	$email = $riga['email'];
	$custom_tax = maybe_unserialize( $riga['custom_tax'] );
}

if ( ! empty ( $custom_tax ) )
	$_tax=json_encode($custom_tax);
else{
	$_tax=json_encode("");
    $custom_tax="";
    }
?>
<script>
    <?php 
    if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
    $agent_obj=new AGsCRM_agent();
    ?>
    var privileges = <?php echo json_encode($agent_obj->getCustomerPrivileges($ID, "array")) ?>;
    <?php
    } else { ?> 
    var privileges = null;
    <?php } ?>

    var customerTax = JSON.parse('<?php echo $_tax ?>');
    var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
    var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
</script>
<div id="dialog_todo" style="display:none;" data-from="clienti" data-fkcliente="<?php echo $ID?>">
    <?php include ( WPsCRM_DIR."/inc/crm/clienti/form_todo.php" ) ?>
</div>
<?php include ( WPsCRM_DIR."/inc/crm/clienti/script_todo.php" ) ?>

<div id="dialog_appuntamento" style="display:none;" data-from="clienti" data-fkcliente="<?php echo $ID?>">
    <?php include ( WPsCRM_DIR."/inc/crm/clienti/form_appuntamento.php" ) ?>
</div>
<?php include ( WPsCRM_DIR."/inc/crm/clienti/script_appuntamento.php" ) ?>

<div id="dialog_attivita" style="display:none;" data-from="clienti" data-fkcliente="<?php echo $ID?>">
    <?php include ( WPsCRM_DIR."/inc/crm/clienti/form_attivita.php" ) ?>
</div>
<?php include ( WPsCRM_DIR."/inc/crm/clienti/script_attivita.php" ) ?>

<?php if (isset($email) && $email!="") { ?>
    <div id="dialog_mail" style="display:none;" data-from="clienti" data-fkcliente="<?php echo $ID?>">
        <?php include ( WPsCRM_DIR."/inc/crm/clienti/form_mail.php" ) ?>
    </div>
    <?php include ( WPsCRM_DIR."/inc/crm/clienti/script_mail.php" );
} ?>
<script type="text/javascript">
jQuery(document).ready(function ($) {
    $('._showLoader').on('click', function (e) {
        $('#mouse_loader').offset({ left: e.pageX, top: e.pageY });
    });

    // Bootstrap Modals für Rechnung und Angebot öffnen
    $('.btn_invoice').on('click', function () {
        $('#invoiceFrame').attr('src', "<?php echo admin_url('admin.php?page=smart-crm&p=documenti%2Fform_invoice.php&cliente=').$ID?>" + "&layout=iframe");
        $('#invoiceModal').modal('show');
    });
    $('.btn_quote').on('click', function () {
        $('#quoteFrame').attr('src', "<?php echo admin_url('admin.php?page=smart-crm&p=documenti%2Fform_quotation.php&cliente=').$ID?>" + "&layout=iframe");
        $('#quoteModal').modal('show');
    });

    <?php do_action('WPsCRM_menu_tooltip') ?>

    <?php if($ID){ ?>
    $('#cd-timeline').on('click','.glyphicon-remove', function () {
        var complete=false;
        var $this=$(this).closest('.cd-timeline-block');
        var index=$this.data('index');
        $.ajax({
            url: ajaxurl,
            data: {'action': 'WPsCRM_delete_annotation',
                'id_cliente': '<?php echo $ID ?>',
                'index':index,
                'security':'<?php echo $delete_nonce; ?>'},
            type: "POST",
            success: function (response) {
                noty({
                    text: "<?php _e('Anmerkung wurde gelöscht','cpsmartcrm')?>",
                    layout: 'center',
                    type: 'success',
                    template: '<div class="noty_message"><span class="noty_text"></span></div>',
                    timeout: 1000
                });
                complete=true;
                $("*[data-index=" + index + "]").fadeOut(200);
            }
        })
    })
    <?php } ?>

    // Timeline-Animation
    var timelineBlocks = $('.cd-timeline-block'),
        offset = 0.8;
    hideBlocks(timelineBlocks, offset);
    $(window).on('scroll', function () {
        (!window.requestAnimationFrame)
            ? setTimeout(function () { showBlocks(timelineBlocks, offset); }, 100)
            : window.requestAnimationFrame(function () { showBlocks(timelineBlocks, offset); });
    });
    function hideBlocks(blocks, offset) {
        blocks.each(function () {
            ($(this).offset().top > $(window).scrollTop() + $(window).height() * offset) && $(this).find('.cd-timeline-img, .cd-timeline-content').addClass('is-hidden');
        });
    }
    function showBlocks(blocks, offset) {
        blocks.each(function () {
            ($(this).offset().top <= $(window).scrollTop() + $(window).height() * offset && $(this).find('.cd-timeline-img').hasClass('is-hidden')) && $(this).find('.cd-timeline-img, .cd-timeline-content').removeClass('is-hidden').addClass('bounce-in');
        });
    }

    // update activity aus Modal
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
                'security':'<?php echo $scheduler_nonce; ?>'
            },
            success: function (response) {
                $('#grid').DataTable().ajax.reload();
                setTimeout(function () {
                    $('.modal_loader').fadeOut('fast');
                }, 300);
                setTimeout(function () {
                    $('._modal').fadeOut('fast');
                }, 500);
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        })
    });

    // jQuery UI Datepicker
    $("#data_inserimento").datepicker({
        dateFormat: "dd.mm.yy",
        defaultDate: new Date()
    });
    $("#data_nascita").datepicker({
        dateFormat: "dd.mm.yy"
    });

    // AGENT (Select2)
    if ($("#selectAgent").length) {
        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'WPsCRM_get_CRM_users_customer'
            },
            success: function (result) {
                var data = [];
                if (Array.isArray(result)) {
                    data = result.map(function(user) {
                        return { id: user.ID, text: user.display_name };
                    });
                }
                $("#selectAgent").select2({
                    data: data,
                    placeholder: "<?php _e('Select Agent...','cpsmartcrm') ?>",
                    width: '54%'
                });
                var agente = '<?php if(isset($agente)) echo $agente?>';
                if (agente > 0) {
                    $("#selectAgent").val(agente).trigger('change');
                }
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        });
    }

    // LAND (Select2)
    $('#nazione').select2({
        placeholder: "<?php _e('Land auswählen','cpsmartcrm') ?>...",
        width: '100%'
    });
    // Felder aktivieren/deaktivieren je nach Land
    var country = $('#nazione').val();
    if (country != "0") {
        $('._toCheck').attr({ 'readonly': false, 'title': '' });
    } else {
        $('._toCheck').attr({ 'readonly': 'readonly', 'title': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...', 'alt': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...' });
    }
    $('#nazione').on('change', function () {
        if ($(this).val() != "0") {
            $('._toCheck').attr({ 'readonly': false, 'title': '' });
        } else {
            $('._toCheck').attr({ 'readonly': 'readonly', 'title': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...', 'alt': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...' });
        }
    });

    // KATEGORIE (Select2 als Mehrfachauswahl)
    <?php
        echo "var cats = [];";
        if( ! empty($cats) ){
            echo "cats = [";
            foreach($cats as $cat)
                echo '{id:"'.$cat->term_id.'",text:"'.$cat->name.'"},';
            echo "];";
        }
    ?>
    jQuery('#customerCategory').select2({
        data: cats,
        placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
        width: '100%',
        multiple: true
    });
    <?php if(isset($riga) && $riga["categoria"]): ?>
        $('#customerCategory').val([<?php echo $riga["categoria"]?>]).trigger('change');
    <?php endif; ?>

    // PROVENIENZ (Select2 als Mehrfachauswahl)
    <?php
        echo "var provs = [];";
        if( ! empty($provs) ){
            echo "provs = [";
            foreach($provs as $prov)
                echo '{id:"'.$prov->term_id.'",text:"'.$prov->name.'"},';
            echo "];";
        }
    ?>
    jQuery('#customerComesfrom').select2({
        data: provs,
        placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
        width: '100%',
        multiple: true
    });
    <?php if(isset($riga) && $riga["provenienza"]): ?>
        $('#customerComesfrom').val([<?php echo $riga["provenienza"]?>]).trigger('change');
    <?php endif; ?>

    // INTERESSEN (Select2 als Mehrfachauswahl)
    <?php
        echo "var ints = [];";
        if( ! empty($ints) ){
            echo "ints = [";
            foreach($ints as $int)
                echo '{id:"'.$int->term_id.'",text:"'.$int->name.'"},';
            echo "];";
        }
    ?>
    jQuery('#customerInterests').select2({
        data: ints,
        placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
        width: '100%',
        multiple: true
    });
    <?php if(isset($riga) && $riga["interessi"]): ?>
        $('#customerInterests').val([<?php echo $riga["interessi"]?>]).trigger('change');
    <?php endif; ?>

    // Parsley für Validierung
    $('#form_insert').parsley({
        errorsWrapper: '<div class="parsley-errors-list"></div>',
        errorTemplate: '<div></div>',
        trigger: 'change'
    });

    // Eigene Parsley-Regeln
    window.Parsley.addValidator('country', {
        validateString: function(value) {
            return value !== "0" && value !== null && value !== "";
        },
        messages: {
            de: "Du solltest das Kundenland auswählen"
        }
    });
    window.Parsley.addValidator('fiscalcode', {
        validateString: function(value) {
            var country = $('#nazione').val();
            if ($('#fatturabile_1').is(':checked')) {
                if (value === "" && $('#p_iva').val() === "") return false;
                if (country === "DE" && value.length !== 16) return false;
            }
            return true;
        },
        messages: {
            de: "Du solltest die GÜLTIGE Steuernummer oder Umsatzsteuer-Identifikationsnummer des Kunden eingeben"
        }
    });

    // Parsley-Felder zuweisen
    $('#nazione').attr('data-parsley-country', '');
    $('#cod_fis').attr('data-parsley-fiscalcode', '');

    // Speichern-Button
    $('.saveForm').on('click', function(e) {
        e.preventDefault();
        var $form = $('#form_insert');
        if ($form.parsley().validate()) {
            showMouseLoader();
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'WPsCRM_save_client',
                    fields: $form.serialize(),
                    security: '<?php echo $update_nonce; ?>'
                },
                type: "POST",
                success: function (response) {
                    hideMouseLoader();
                    if (response.indexOf('OK') !== -1) {
                        var tmp = response.split("~");
                        var id_cli = tmp[1];
                        noty({
                            text: "<?php _e('Der Kunde wurde gespeichert','cpsmartcrm')?>",
                            layout: 'center',
                            type: 'success',
                            template: '<div class="noty_message"><span class="noty_text"></span></div>',
                            timeout: 1000
                        });
                        $("#ID").val(id_cli);
                        <?php if (! $ID) { ?>
                        setTimeout(function () {
                            location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=clienti/form.php&ID=')?>" + id_cli;
                        }, 1000)
                        <?php } ?>
                    } else {
                        noty({
                            text: "<?php _e('Etwas war falsch','cpsmartcrm')?>" + ": " + response,
                            layout: 'center',
                            type: 'error',
                            template: '<div class="noty_message"><span class="noty_text"></span></div>',
                            closeWith: ['button']
                        });
                    }
                }
            });
        }
    });

    // Reset-Button
    $('.resetForm').on('click', function(e) {
        e.preventDefault();
        location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=clienti/list.php')?>";
    });

    // Löschen-Button
    $('.deleteForm').on('click', function(e) {
        e.preventDefault();
        if (confirm("<?php _e('Löschen bestätigen? Es ist weiterhin möglich, den gelöschten Kunden wiederherzustellen ','cpsmartcrm')?>")) {
            location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=clienti/delete.php&ID='.$ID)?>&security=<?php echo $delete_nonce?>";
        }
    });
});
</script>
<form name="form_insert" method="post" id="form_insert">
<input type="hidden" name="ID" id="ID" value="<?php echo $ID?>">
    
    <h3><?php if ($ID) { ?> <?php _e('Kunde','cpsmartcrm')?>: <?php echo "<span class=\"header_customer\">".stripslashes($cliente)."</span>";
			  } else{
        ?> <?php _e('Neukunde','cpsmartcrm')?> <?php } ?>
    </h3>

    <ul class="select-action">
		<li class="btn btn-sm _flat">
            <span class="crmHelp crmHelp-dark" data-help="customerForm" style="position:relative;top:-3px" data-role="tooltip"></span>
		</li>
        <?php if ($ID){?>
		<li class="btn btn-success btn-sm _flat _showLoader saveForm" onclick="save();return false;">
			<i class="glyphicon glyphicon-floppy-disk"></i>
			<b>
				<?php _e('Speichern','cpsmartcrm')?>
			</b>
		</li>
        <li onClick="annulla();return false;" class="btn btn-warning btn-sm _flat resetForm">
            <i class="glyphicon glyphicon-floppy-remove"></i>
            <b> <?php _e('Zurücksetzen','cpsmartcrm')?></b>
        </li>

        <li onClick="elimina();return false;" class="btn btn-danger btn-sm _flat deleteForm" style="margin-right:10px">
            <i class="glyphicon glyphicon-remove"></i>
            <b> <?php _e('Löschen','cpsmartcrm')?></b>
        </li>
        <li class="_tooltip"><i class="glyphicon glyphicon-menu-right"></i></li>
        <li class="btn btn-info btn-sm _flat btn_todo" style="margin-left:10px" title="<?php _e('NEUE TODO','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-tag"></i>
            <b> </b>
        </li>
        <li class="btn  btn-sm _flat btn_appuntamento" title="<?php _e('NEUER TERMIN','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-pushpin"></i>
            <b> </b>
        </li>
        <li class="btn btn-primary btn-sm _flat btn_activity" title="<?php _e('NEUE ANMERKUNG','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-option-horizontal"></i>
            <b> </b>
        </li>
        <?php do_action('WPsCRM_advanced_buttons',$email);?>
        <?php } ?>
    </ul>
    <div id="tabstrip" style="margin-top:14px">
        <ul>
            <li id="tab1"><?php _e('Stammdaten','cpsmartcrm')?></li>
            <?php
			if ($ID){
            ?>
            <li id="tab2"><?php _e('Kontakte','cpsmartcrm')?></li>
            <li id="tab3"><?php _e('Angeboten','cpsmartcrm')?></li>
            <li id="tab4"><?php _e('Zusammenfassung','cpsmartcrm')?></li>
            <?php 
				do_action('WPsCRM_add_tabs_to_customer_form');
			} ?>
        </ul>
        <!-- TAB 1 -->
        <div>
            <div id="d_anagrafica" style="position:relative">
                <div class="row form-group">
					<label class="col-sm-1 control-label">
						<?php _e('Datum','cpsmartcrm')?>
					</label>
					<div class="col-sm-2">
						<input type="text" id="data_inserimento" name="data_inserimento" />
					</div>
					<?php do_action('WPsCRM_display_anagrafiche_in_form') ?>

                </div>
				<div class="row form-group">
					<label class="col-sm-1 control-label">
						<?php _e('Rechnungspflichtig?','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
                        <span style="margin-right:20px"><input type="radio" name="fatturabile" id="fatturabile_1" value="1" <?php if (isset($riga) && $riga["fatturabile"]==1) echo "checked"?> /><?php _e('Ja','cpsmartcrm')?></span>
                        <span><input type="radio" name="fatturabile" id="fatturabile_2" value="0" <?php if ((isset($riga) && $riga["fatturabile"]==0) || !isset($riga)) echo "checked"?> /><?php _e('Nein','cpsmartcrm')?></span>
					</div>

					<label class="col-sm-1 control-label">
						<?php _e('Typ','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="radio" name="tipo_cliente" value="1" <?php if (isset($riga) && $riga["tipo_cliente"]==1) echo "checked"?> /><?php _e('Privat','cpsmartcrm')?>
						<input type="radio" name="tipo_cliente" value="2" <?php if (isset($riga) && $riga["tipo_cliente"]==2) echo "checked"?> /><?php _e('Business','cpsmartcrm')?>
					</div>
				</div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Land','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <select data-nazione="<?php if(isset($riga)) echo $riga["nazione"]?>" id="nazione" name="nazione" size="20" maxlength='50'><?php if(isset($riga['nazione'])) echo stripslashes( WPsCRM_get_countries($riga["nazione"]) ); else echo stripslashes( WPsCRM_get_countries('DE'))?></select>
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Firmenname','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="ragione_sociale" maxlength='250' value="<?php if(isset($riga)) echo stripslashes($riga["ragione_sociale"])?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Steuer-Code','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="cod_fis" id="cod_fis" value="<?php if(isset($riga)) echo $riga["cod_fis"]?>" class="form-control _toCheck"  readonly title="<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Umsatzsteuer-Identifikationsnummer','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="p_iva" id="p_iva" value="<?php if(isset($riga)) echo $riga["p_iva"]?>" class="form-control _toCheck"  readonly title="<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Vorname','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="nome" value="<?php if(isset($riga)) echo stripslashes($riga["nome"])?>" class="form-control">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Nachname','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="cognome" value="<?php if(isset($riga)) echo stripslashes($riga["cognome"])?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Addresse','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="indirizzo" size="50" maxlength='50' value="<?php if(isset($riga)) echo stripslashes($riga["indirizzo"])?>" class="form-control">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('PLZ','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="cap" size="10" maxlength='10' value="<?php if(isset($riga)) echo $riga["cap"]?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Stadt','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="localita" size="50" maxlength='55' value="<?php if(isset($riga)) echo stripslashes($riga["localita"])?>" class="form-control">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Staat/Prov.','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="provincia" size="5" maxlength='5' value="<?php if(isset($riga)) echo $riga["provincia"]?>" class="form-control">
                    </div>
                </div>
                <div class="row form-group">

                    <label class="col-sm-1 control-label"><?php _e('Telefon','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="telefono1" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["telefono1"]?>" class="form-control">
                    </div>
					<label class="col-sm-1 control-label">
						<?php _e('Fax','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="text" name="fax" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["fax"]?>" class="form-control" />
					</div>
                   
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Mobil','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="telefono2" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["telefono2"]?>" class="form-control">
                    </div>
					<label class="col-sm-1 control-label">
						<?php _e('Email','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="text" name="email" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["email"]?>" class="form-control" />
					</div>
                    
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Geburtsort','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" name="luogo_nascita" size="20" maxlength='50' value="<?php if(isset($riga)) echo stripslashes($riga["luogo_nascita"] )?>" class="form-control">
                    </div>
					<label class="col-sm-1 control-label">
						<?php _e('Skype','cpsmartcrm')?>
					</label>
					<div class="col-sm-4">
						<input type="text" name="skype" size="20" maxlength='100' value="<?php if(isset($riga)) echo $riga["skype"]?>" class="form-control" />
					</div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Geburtsdatum','cpsmartcrm')?></label>
                    <div class="col-sm-4">
                        <input type="text" id="data_nascita" name="data_nascita" value="<?php if(isset($riga)) echo WPsCRM_inverti_data($riga["data_nascita"])?>">
                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Kategorie','cpsmartcrm')?></label>
                    <div class="col-sm-4">
						<input id="customerCategory"  name="customerCategory" value="<?php if(isset($riga)) echo $riga["categoria"]?>" />
						<?php
						$cats = get_terms('WPsCRM_customersCat', array('hide_empty'=>false));
						if (empty($cats)): ?>
							<div class="alert alert-warning">
								<?php _e('Keine Kategorien; Erstelle Kategorien in den CRM-Einstellungen ->Seite „Kundeneinstellungen“.','cpsmartcrm') ?>
							</div>
						<?php endif; ?>
						<script>
							<?php
							echo "var cats = [];";
							if( ! empty($cats) ){
								echo "cats = [";
								foreach($cats as $cat)
									echo '{id:"'.$cat->term_id.'",text:"'.$cat->name.'"},';
								echo "];";
							}
							?>
							jQuery('#customerCategory').select2({
								data: cats,
								placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
								width: '100%',
								multiple: true
							});
							<?php if(isset($riga) && $riga["categoria"]): ?>
								$('#customerCategory').val([<?php echo $riga["categoria"]?>]).trigger('change');
							<?php endif; ?>
						</script>
					</div>
                </div>
                <div class="row form-group">
					<label class="col-sm-1 control-label"><?php _e('Webseite','cpsmartcrm')?></label>
					<div class="col-sm-4">
						<input type="text" name="sitoweb" size="20" maxlength='50' value="<?php if(isset($riga)) echo $riga["sitoweb"]?>" class="form-control">
					</div>
					<label class="col-sm-1 control-label"><?php _e('Interessen','cpsmartcrm')?></label>
					<div class="col-sm-4">
						<input id="customerInterests" name="customerInterests" value="<?php if(isset($riga)) echo $riga["interessi"]?>" />
						<?php
						$ints = get_terms('WPsCRM_customersInt', array('hide_empty'=>false));
						if (empty($ints)): ?>
							<div class="alert alert-warning">
								<?php _e('Keine Interessen; Erstelle Interessen in den CRM-Einstellungen ->Seite „Kundeneinstellungen“.','cpsmartcrm') ?>
							</div>
						<?php endif; ?>
						<script>
							<?php
							echo "var ints = [];";
							if( ! empty($ints) ){
								echo "ints = [";
								foreach($ints as $int)
									echo '{id:"'.$int->term_id.'",text:"'.$int->name.'"},';
								echo "];";
							}
							?>
							jQuery('#customerInterests').select2({
								data: ints,
								placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
								width: '100%',
								multiple: true
							});
							<?php if(isset($riga) && $riga["interessi"]): ?>
								$('#customerInterests').val([<?php echo $riga["interessi"]?>]).trigger('change');
							<?php endif; ?>
						</script>
					</div>
				</div>
				<div class="row form-group">
					<label class="col-sm-1 control-label" <?php echo $style_disabled?>><?php _e('Agent','cpsmartcrm')?>:</label>
					<div class="col-sm-4" <?php echo $style_disabled?>>
						<select id="selectAgent" name="selectAgent" <?php echo $agent_disabled?> style="width:54%" ></select>
					</div>
					<label class="col-sm-1 control-label"><?php _e('Kommt von','cpsmartcrm')?>:</label>
					<div class="col-sm-4">
						<input id="customerComesfrom" name="customerComesfrom" value="<?php if(isset($riga)) echo $riga["provenienza"]?>"  />
						<?php
						$provs = get_terms('WPsCRM_customersProv', array('hide_empty'=>false));
						if (empty($provs)): ?>
							<div class="alert alert-warning">
								<?php _e('Keine Quellen; erstelle Quellen in den CRM-Einstellungen ->Kundeneinstellungen Seite','cpsmartcrm') ?>
							</div>
						<?php endif; ?>
						<script>
							<?php
							echo "var provs = [];";
							if( ! empty($provs) ){
								echo "provs = [";
								foreach($provs as $prov)
									echo '{id:"'.$prov->term_id.'",text:"'.$prov->name.'"},';
								echo "];";
							}
							?>
							jQuery('#customerComesfrom').select2({
								data: provs,
								placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
								width: '100%',
								multiple: true
							});
							<?php if(isset($riga) && $riga["provenienza"]): ?>
								$('#customerComesfrom').val([<?php echo $riga["provenienza"]?>]).trigger('change');
							<?php endif; ?>
						</script>
					</div>
				</div>
				<div class="row form-group">
					<label class="col-sm-1 control-label"><?php _e('Notizen','cpsmartcrm')?></label>
					<div class="col-sm-11">
						<textarea name="note" rows="5" cols="50" class="form-control"><?php if(isset($riga)) echo stripslashes($riga["note"])?></textarea>
					</div>
				</div>
			</div>
        <!--END TAB 1 -->
        <!-- TAB 2 -->
        <?php if ($ID){?>
        <div>

            <div id="grid_contacts"></div>

        </div>
        <!--END TAB 2 -->
        <!--TAB 3 -->

        <div>
            <!--<h2 style="text-align:center"><?php _e('Angeboten','cpsmartcrm')?></h2>-->
            <div style="min-height: 200px">
                <div id="annotation">
                    <h3 style="text-align:center"><?php _e('Notizen-Zeitleiste','cpsmartcrm')?> 
                        <span class="btn btn-primary btn-sm _flat btn_activity" title="<?php _e('NEUE ANMERKUNG','cpsmartcrm')?>">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                        </span>
                    </h3>
                    <div>

                        <section id="cd-timeline" class="cd-container">
                            <?php WPsCRM_timeline_annotation($riga["annotazioni"])?>

                        </section>
                    </div>
                </div>
            </div>
        </div>

        <!-- END TAB 3 -->
        <!-- TAB 4 -->
        <div>
            <div id="grid"></div>
        </div>
        <!-- END TAB 4 -->
        <?php
			do_action('WPsCRM_add_divs_to_customer_form',$email, $ID);
              } ?>
    </div>

    <br>
    <input type="submit" style="display:none" />

    <ul class="select-action">

        <li class="btn btn-success btn-sm _flat _showLoader saveForm" onclick="save()">
            <i class="glyphicon glyphicon-floppy-disk"></i>
            <b>
                <?php _e('Speichern','cpsmartcrm')?>
            </b>
        </li>
        <li onClick="annulla();return false;" class="btn btn-warning btn-sm _flat resetForm">
            <i class="glyphicon glyphicon-floppy-remove"></i>
            <b> <?php _e('Zurücksetzen','cpsmartcrm')?></b>
        </li>
        <?php if ($ID){?>
        <li class="btn btn-danger btn-sm _flat deleteForm" style="margin-right:10px">
            <i class="glyphicon glyphicon-remove"></i>
            <b onClick="elimina();return false;"> <?php _e('Löschen','cpsmartcrm')?></b>
        </li>
        <li class="_tooltip"><i class="glyphicon glyphicon-menu-right"></i></li>
        <li class="btn btn-info btn-sm _flat btn_todo" style="margin-left:10px" title="<?php _e('NEUE TODO','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-tag"></i>
            <b> </b>
        </li>
        <li class="btn  btn-sm _flat btn_appuntamento" title="<?php _e('NEUER TERMIN','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-pushpin"></i>
            <b> </b>
        </li>
        <li class="btn btn-primary btn-sm _flat btn_activity" title="<?php _e('NEUE ANMERKUNG','cpsmartcrm')?>">
            <i class="glyphicon glyphicon-option-horizontal"></i>
            <b> </b>
        </li>
		<?php do_action('WPsCRM_advanced_buttons',$email);?>
		<?php }
		?>
    </ul>
	<!-- Rechnung Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document" style="width:90%">
    <div class="modal-content" style="height:84vh;">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="glyphicon glyphicon-fire"></i> <?php _e('Rechnung erstellen für','cpsmartcrm'); if(isset($cliente)) echo " ". stripslashes($cliente)?>
        </h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" style="height:70vh;">
        <iframe id="invoiceFrame" src="" width="100%" height="100%" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>
<!-- Angebot Modal -->
<div class="modal fade" id="quoteModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document" style="width:90%">
    <div class="modal-content" style="height:84vh;">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="glyphicon glyphicon-send"></i> <?php _e('Angebot erstellen für','cpsmartcrm'); if(isset($cliente)) echo " ". stripslashes($cliente)?>
        </h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" style="height:70vh;">
        <iframe id="quoteFrame" src="" width="100%" height="100%" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>
</form>

<div id="dialog-view" style="display:none;margin: 0 auto; text-align: center; z-index: 1000; width: 100%; height: 100%; background: url('<?php echo str_replace("inc/crm/clienti/","",plugin_dir_url( __FILE__ ))?>css/img/bg_w_tr.png');position: absolute;left: 0;top:0;"  class="_modal" data-from="clienti">

</div>
<div id="createPdf"></div>
<div id="createInvoice"></div>
<div id="createQuote"></div>
<script>

    var media_uploader = null;

    function open_media_uploader_multiple_images() {
        media_uploader = wp.media({
            frame: "post",
            state: "insert",
            multiple: true
        });

        media_uploader.on("insert", function () {

            var length = media_uploader.state().get("selection").length;
            var images = media_uploader.state().get("selection").models
            console.log(images);

            for (var iii = 0; iii < length; iii++) {
                var image_url = images[iii].changed.url;
                console.log(image_url);
                jQuery('.thumbContainer').append('<img src="' + image_url.replace(".jpg", "-150x150.jpg") + '">')
                var image_caption = images[iii].changed.caption;
                var image_title = images[iii].changed.title;
            }
        });

        media_uploader.open();
    }

jQuery(document).ready(function ($) {
    $('._showLoader').on('click', function (e) {
        $('#mouse_loader').offset({ left: e.pageX, top: e.pageY });
    });

    // --- NEU: Bootstrap Modals für Rechnung und Angebot öffnen ---
    $('.btn_invoice').on('click', function () {
        $('#invoiceFrame').attr('src', "<?php echo admin_url('admin.php?page=smart-crm&p=documenti%2Fform_invoice.php&cliente=').$ID?>" + "&layout=iframe");
        $('#invoiceModal').modal('show');
    });
    $('.btn_quote').on('click', function () {
        $('#quoteFrame').attr('src', "<?php echo admin_url('admin.php?page=smart-crm&p=documenti%2Fform_quotation.php&cliente=').$ID?>" + "&layout=iframe");
        $('#quoteModal').modal('show');
    });

    <?php do_action('WPsCRM_menu_tooltip') ?>

    <?php if($ID){ ?>
    $('#cd-timeline').on('click','.glyphicon-remove', function () {
        var complete=false;
        var $this=$(this).closest('.cd-timeline-block');
        var index=$this.data('index');
        $.ajax({
            url: ajaxurl,
            data: {'action': 'WPsCRM_delete_annotation',
                'id_cliente': '<?php echo $ID ?>',
                'index':index,
                'security':'<?php echo $delete_nonce; ?>'},
            type: "POST",
            success: function (response) {
                console.log(response);
                noty({
                    text: "<?php _e('Anmerkung wurde gelöscht','cpsmartcrm')?>",
                    layout: 'center',
                    type: 'success',
                    template: '<div class="noty_message"><span class="noty_text"></span></div>',
                    timeout: 1000
                });
                complete=true;
                $("*[data-index=" + index + "]").fadeOut(200);
            }
        })
    })
    <?php } ?>

    var timelineBlocks = $('.cd-timeline-block'),
    offset = 0.8;

    hideBlocks(timelineBlocks, offset);

    $(window).on('scroll', function () {
        (!window.requestAnimationFrame)
            ? setTimeout(function () { showBlocks(timelineBlocks, offset); }, 100)
            : window.requestAnimationFrame(function () { showBlocks(timelineBlocks, offset); });
    });

    function hideBlocks(blocks, offset) {
        blocks.each(function () {
            ($(this).offset().top > $(window).scrollTop() + $(window).height() * offset) && $(this).find('.cd-timeline-img, .cd-timeline-content').addClass('is-hidden');
        });
    }

    function showBlocks(blocks, offset) {
        blocks.each(function () {
            ($(this).offset().top <= $(window).scrollTop() + $(window).height() * offset && $(this).find('.cd-timeline-img').hasClass('is-hidden')) && $(this).find('.cd-timeline-img, .cd-timeline-content').removeClass('is-hidden').addClass('bounce-in');
        });
    }

    // update activity aus Modal
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
                'security':'<?php echo $scheduler_nonce; ?>'
            },
            success: function (response) {
                // DataTables-Reload statt KendoGrid!
                $('#grid').DataTable().ajax.reload();
                setTimeout(function () {
                    $('.modal_loader').fadeOut('fast');
                }, 300);
                setTimeout(function () {
                    $('._modal').fadeOut('fast');
                }, 500);
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        })
    });

    // --- NEU: jQuery UI Datepicker statt Kendo ---
    $("#data_inserimento").datepicker({
        dateFormat: "dd.mm.yy",
        defaultDate: new Date()
    });
    $("#data_nascita").datepicker({
        dateFormat: "dd.mm.yy"
    });


		jQuery(document).ready(function($){

			// AGENT (Select2)
			if ($("#selectAgent").length) {
				$.ajax({
					url: ajaxurl,
					data: {
						'action': 'WPsCRM_get_CRM_users_customer'
					},
					success: function (result) {
						var data = [];
						if (Array.isArray(result)) {
							data = result.map(function(user) {
								return { id: user.ID, text: user.display_name };
							});
						}
						$("#selectAgent").select2({
							data: data,
							placeholder: "<?php _e('Select Agent...','cpsmartcrm') ?>",
							width: '54%'
						});
						var agente = '<?php if(isset($agente)) echo $agente?>';
						if (agente > 0) {
							$("#selectAgent").val(agente).trigger('change');
						}
					},
					error: function (errorThrown) {
						console.log(errorThrown);
					}
				});
			}

			// LAND (Select2)
			$('#nazione').select2({
				placeholder: "<?php _e('Land auswählen','cpsmartcrm') ?>...",
				width: '100%'
			});
			// Felder aktivieren/deaktivieren je nach Land
			var country = $('#nazione').val();
			if (country != "0") {
				$('._toCheck').attr({ 'readonly': false, 'title': '' });
			} else {
				$('._toCheck').attr({ 'readonly': 'readonly', 'title': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...', 'alt': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...' });
			}
			$('#nazione').on('change', function () {
				if ($(this).val() != "0") {
					$('._toCheck').attr({ 'readonly': false, 'title': '' });
				} else {
					$('._toCheck').attr({ 'readonly': 'readonly', 'title': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...', 'alt': '<?php _e('Wähle zuerst das Land aus','cpsmartcrm') ?>...' });
				}
			});

			// KATEGORIE (Select2 als Mehrfachauswahl)
			<?php
				echo "var cats = [];";
				if( ! empty($cats) ){
					echo "cats = [";
					foreach($cats as $cat)
						echo '{id:"'.$cat->term_id.'",text:"'.$cat->name.'"},';
					echo "];";
				}
			?>
			jQuery('#customerCategory').select2({
				data: cats,
				placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
				width: '100%',
				multiple: true
			});
			// Vorbelegung Kategorie
			<?php if(isset($riga) && $riga["categoria"]): ?>
				$('#customerCategory').val([<?php echo $riga["categoria"]?>]).trigger('change');
			<?php endif; ?>

			// PROVENIENZ (Select2 als Mehrfachauswahl)
			<?php
				echo "var provs = [];";
				if( ! empty($provs) ){
					echo "provs = [";
					foreach($provs as $prov)
						echo '{id:"'.$prov->term_id.'",text:"'.$prov->name.'"},';
					echo "];";
				}
			?>
			jQuery('#customerComesfrom').select2({
				data: provs,
				placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
				width: '100%',
				multiple: true
			});
			// Vorbelegung Provenienz
			<?php if(isset($riga) && $riga["provenienza"]): ?>
				$('#customerComesfrom').val([<?php echo $riga["provenienza"]?>]).trigger('change');
			<?php endif; ?>

			// INTERESSEN (Select2 als Mehrfachauswahl)
			<?php
				echo "var ints = [];";
				if( ! empty($ints) ){
					echo "ints = [";
					foreach($ints as $int)
						echo '{id:"'.$int->term_id.'",text:"'.$int->name.'"},';
					echo "];";
				}
			?>
			jQuery('#customerInterests').select2({
				data: ints,
				placeholder: "<?php _e('Wählen','cpsmartcrm')?>",
				width: '100%',
				multiple: true
			});
			// Vorbelegung Interessen
			<?php if(isset($riga) && $riga["interessi"]): ?>
				$('#customerInterests').val([<?php echo $riga["interessi"]?>]).trigger('change');
			<?php endif; ?>

			// Timeline-Sortierung
			setTimeout(function () {
				var divList = $(".cd-timeline-block");
				divList.sort(function (a, b) {
					var date1 = $(a).data("date");
					date1 = date1.split('-');
					date1 = new Date(date1[0], date1[1] - 1, date1[2]);
					var date2 = $(b).data("date");
					date2 = date2.split('-');
					date2 = new Date(date2[0], date2[1] - 1, date2[2]);
					return date1 < date2;
				}).appendTo('#_timeline');
				$('#_timeline').fadeIn('fast')
			}, 50);

		});
	});

</script>
<style>
    input[type=checkbox] {
        float: initial;
    }
</style>
