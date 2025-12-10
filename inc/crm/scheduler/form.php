<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$update_nonce= wp_create_nonce( "update_scheduler" );

$tipo_agenda=$_GET["tipo_agenda"];
$a_table=WPsCRM_TABLE."agenda";
$c_table=WPsCRM_TABLE."clienti";
$s_table=WPsCRM_TABLE."subscriptionrules";
$data_scadenza=date("d-m-Y");

$where="1";
switch ($tipo_agenda)
{
    case 1:
        
        $icon='<i class="glyphicon glyphicon-tag"></i> '.__('NEUES TODO','cpsmartcrm');
        break;
    case 2:
        $icon='<i class="glyphicon glyphicon-pushpin"></i> '.__('NEUER TERMIN','cpsmartcrm');
        break;
    case 3:
        $icon='<i class="glyphicon glyphicon-option-horizontal"></i> '.__('NEUE AKTIVITÄT','cpsmartcrm');
        break;
    default:
        $tipo="";
        break;
}
?>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		var $format = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
		var _clients = new kendo.data.DataSource({
			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_clients2'
						<?php if($tipo_agenda ==1) {?>, 'self_client': 1 <?php } ?>
						},
						success: function (result) {
							console.log(result);
							$("#fk_clienti").data("kendoDropDownList").dataSource.data(result.clients);

						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		jQuery('#fk_clienti').select2({
			optionLabel : "<?php _e('Wähle Kunde aus','cpsmartcrm') ?>...",
			dataSource: _clients,
			dataTextField: "ragione_sociale",
			dataValueField: "ID_clienti",
			//filter: "contains",
			autoBind: true,
			minLength: 3,

	}).data('kendoDropDownList');

		var _users = new kendo.data.DataSource({
			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_CRM_users'
						},
						success: function (result) {
							console.log(result);
							$("#remindToUser").data("kendoMultiSelect").dataSource.data(result);

						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		var users=$('#remindToUser').kendoMultiSelect({
			placeholder: "<?php _e('Benutzer wählen','cpsmartcrm') ?>...",
			dataTextField: "display_name",
			dataValueField: "ID",
			autoBind: true,
			dataSource: _users,
			change: function (e) {
				var selectedUsers = (this.value()).clean("");
				$('#selectedUsers').val(selectedUsers)
			},

		}).data("kendoMultiSelect");
		//$("#remindToUser").focus();

		var roleSource = new kendo.data.DataSource({

			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_registered_roles',
						},
						success: function (result) {
							//console.log(result);
							$("#remindToGroup").data("kendoMultiSelect").dataSource.data(result.roles);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		$('#remindToGroup').kendoMultiSelect({
			placeholder: "<?php _e('Wähle Rolle aus','cpsmartcrm') ?>...",
			dataTextField: "name",
			dataValueField: "role",
			autoBind: true,
			dataSource: roleSource,
			change: function (e) {
				var selectedGroups = (this.value()).clean("");
				$('#selectedGroups').val(selectedGroups)
			},

		});


		var validator = $("form").kendoValidator({
			rules: {
				hasDays: function (input) {
					if (input.is("[name=ruleStep]")) {

						if (jQuery('#ruleStep').val() == "") {
								jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2")
            					return false;
            				}

            			}

            			return true;
            		},

				hasClients: function (input) {
					if (input.is("[name=fk_clienti]")) {

						var kb = $("#fk_clienti").data("kendoDropDownList").value();
						if (kb.length == "") {
							jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
							$("#fk_clienti").focus();
							return false;
						}
					}

					return true;
				},
				hasObject: function (input) {
					if (input.is("[name=oggetto]")) {
						var kb = $("#oggetto").val();
						if (kb == "") {
							jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
							return false;
						}
					}
					return true;
				},
				hasNoty: function (input) {
	            	if (input.is("[name=remindToUser]") ) {
	            			var kb = jQuery("#remindToUser").data("kendoMultiSelect").value();
	            			var kb1 = jQuery("#remindToGroup").data("kendoMultiSelect").value();

	            			if (kb == "" && kb1 == "") {
								jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");

	            				return false;
	            			}

	            	}

					if (input.is("[name=remindToGroup]") ) {
	            			var kb = jQuery("#remindToUser").data("kendoMultiSelect").value();
	            			var kb1 = jQuery("#remindToGroup").data("kendoMultiSelect").value();

	            			if (kb == "" && kb1 == "") {
                            	jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");

	            				return false;
	            			}

	            	}

	            	return true;
	            },

			},

			messages: {
				hasDays:"<?php _e('Du solltest auswählen, wie viele Tage im Voraus Du die Benachrichtigung aktivieren möchtest','cpsmartcrm')?>",
				hasNoty:"<?php _e('Du solltest einen Benutzer oder eine Gruppe von Benutzern auswählen, die benachrichtigt werden sollen','cpsmartcrm')?>",
				hasClients: "<?php _e('Du solltest einen Kunden auswählen','cpsmartcrm')."."; $tipo_agenda==1 ? "<br /> "._e('Um eine interne Mitteilung zu versenden, wähle Dein Unternehmen aus','cpsmartcrm') :null ?>",
				hasObject: "<?php _e('Du solltest einen Betreff für dieses Ereignis eingeben','cpsmartcrm')?>",
				//hasUsers: "<?php _e('Du solltest mindestens einen Benutzer auswählen','cpsmartcrm')?>",
			}
		}).data("kendoValidator");

		$("input", users.wrapper).on("blur", function () {
			validator.validate();
		});
		$("form").validate({
			submitHandler: function () {
				showMouseLoader();
				$('#btn_save b').html("<?php _e('Speichern...','cpsmartcrm')?>");
				id_cliente = $("#fk_clienti").data("kendoDropDownList").value();
				tipo_agenda = '<?php echo $tipo_agenda?>';
				scadenza_inizio = $("#data_scadenza_inizio").val();
				if ($("#data_scadenza_fine").length)
					scadenza_fine = $("#data_scadenza_fine").val();
				else
					scadenza_fine = $("#data_scadenza_inizio").val();
				scadenzaTimestamp = $("#data_scadenza_inizio").data('kendoDateTimePicker').value();

				annotazioni = $("#annotazioni").val();
				oggetto = $("#oggetto").val();
				priorita = $("#priorita").val();
				if ($('#instantNotification').prop('checked'))
					instantNotification = 1;
				else
					instantNotification = 0;
				mailToRecipients = $("#mailToRecipients").prop('checked');
				//alert(mailToRecipients);return false;
				users = $("#selectedUsers").val();
				groups = $("#selectedGroups").val();
				//alert(users); return false;
				days = $("#ruleStep").val();
				var s = "[";
				s += '{"ruleStep":"' + days + '" ,"remindToCustomer":';
				if ($('#remindToCustomer').prop('checked'))
					s += '"on"';
				else
					s += '""';
				s += ',"selectedUsers":"' + users + '"';
				s += ',"selectedGroups":"' + groups + '"';
				s += ',"userDashboard":';
				if ($('#userDashboard').prop('checked'))
					s += '"on"';
				else
					s += '""';
				s += ',"groupDashboard":';
				if ($('#groupDashboard').prop('checked'))
					s += '"on"';
				else
					s += '""';
				s += ',"mailToRecipients":';
				if ($('#mailToRecipients').prop('checked'))
					s += '"on"';
				else
					s += '""';
				s += '}'
				s += ']';
				$.ajax({
					url: ajaxurl,
					data: {
						action: 'WPsCRM_save_todo',

						id_cliente: id_cliente,
						tipo_agenda: tipo_agenda,
						scadenza_inizio: scadenza_inizio,
						scadenza_fine: scadenza_fine,
						scadenza_timestamp: scadenzaTimestamp,
						annotazioni: annotazioni,
						oggetto: oggetto,
						priorita: priorita,
						mail_destinatari: mailToRecipients,
						steps: encodeURIComponent(s),
						instantNotification:instantNotification,
						security:'<?php echo $update_nonce?>'
					},
					type: "POST",
					success: function (response) {
						console.log(response)
						window.location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=scheduler/list.php')?>";
					}
				})
			}
		});


		$("#btn_save").on('click', function () {
			//validator.validate();
			$('form').find(':submit').click();


		});
		users.value([<?php echo wp_get_current_user()->ID ?>]);
		$('#selectedUsers').val(users.value());

<?php if($tipo_agenda==2) {?>

		var _dateIni = $("#data_scadenza_inizio").kendoDateTimePicker({
			value: new Date(),
			format: $format,
			change:set_end_min
		}).data("kendoDateTimePicker");

		if ($("#data_scadenza_fine").length)
		{

		}
		var _dateEnd = $("#data_scadenza_fine").kendoDateTimePicker({
			value: new Date(),
			format: $format,
			width: 300
		}).data("kendoDateTimePicker");
		function set_end_min() {
			//alert();
			var iniDate = _dateIni.value();
			console.log(iniDate);
			console.log(_dateEnd.value());
			_dateEnd.min(kendo.parseDate(iniDate, "yyyy-MM-dd HH:mm"), $format);
			_dateEnd.value(iniDate)
		}
<?php } else {?>
		var _dateIni = $("#data_scadenza_inizio").kendoDateTimePicker({
			value: new Date(),
			format: $format,
			width: 300
		});

		if ($("#data_scadenza_fine").length) {
			var _dateEnd = $("#data_scadenza_fine").kendoDateTimePicker({
				value: new Date(),
				format: $format,
				width: 300
			});
			//_dateEnd.setOptions({
			//	value: new Date(),
			//	format: $format,
			//	width: 200
			//});
		}
		<?php } ?>
});
	//function annulla()
	//{
	//	location.href="index2.php?page=todo/mostra.php";
	//}

</script>
<form name="form_insert">
    <div style="margin-top:14px;background-color: #fafafa;" class="col-md-12">
        <h3><?php echo $icon?></h3>
    <!-- TAB 1 -->
 
        <div id="d_anagrafica">

            <div class="row form-group">
                <label class="col-sm-2 control-label"><?php _e('Kunde','cpsmartcrm')?> *</label>
                
                <div class="col-md-4">
	                  <select id="fk_clienti" name="fk_clienti" class="form-control" data-parsley-hasclients></select>
                </div>                   

            </div>
            <?php if ($tipo_agenda==2) {?>
            <div class="row form-group">
                <label class="col-sm-4 control-label"><?php _e('Start', 'cpsmartcrm'); ?>
                    <input name="data_scadenza_inizio" id='data_scadenza_inizio'  value="<?php echo $data_scadenza?>" class="" required data-parsley-hasexpiration validationMessage="<?php _e('Du solltest ein Startdatum/eine Startzeit für diesen Termin auswählen','cpsmartcrm')?>">
                </label> 
                <label class="col-sm-4 control-label"><?php _e('Ende', 'cpsmartcrm'); ?>
                    <input name="data_scadenza_fine" id='data_scadenza_fine'  value="<?php echo $data_scadenza?>" class="" required data-parsley-hasexpiration validationMessage="<?php _e('Du solltest ein Enddatum/eine Endzeit für diesen Termin auswählen','cpsmartcrm')?>">
                </label>
            </div>
            <?php } ?>
            <?php if ($tipo_agenda==1) { ?>
            <div class="row form-group">
                <label class="col-sm-2 control-label"><?php _e('TODO Datum','cpsmartcrm')?> *</label>
                <div class="col-sm-4">
                    <input type="text" name="data_scadenza_inizio" id='data_scadenza_inizio' value="<?php echo $data_scadenza?>"  required data-parsley-hasexpiration validationMessage="<?php _e('Du solltest ein Ablaufdatum für dieses Ereignis auswählen','cpsmartcrm')?>">
                </div>
            </div>
            <?php } ?>
        <h4 class="page-header" style="background:#e2e2e2;padding:15px"><?php _e('Inhalt','cpsmartcrm')?></h4>
            <div class="row form-group">
	            <label class="col-sm-2 control-label"><?php _e('Betreff','cpsmartcrm')?> *</label>
	            <div class="col-sm-4">
                    <input type="text" value="<?php if(isset($oggetto)) echo $oggetto?>" name="oggetto" id="oggetto" class="form-control  k-textbox _m" placeholder="<?php _e('Gib einen Betreff für dieses Ereignis ein','cpsmartcrm')?>" >
	            </div>
	        
            </div>
            <div class="row form-group">
                <label class="col-sm-2 control-label"><?php _e('Beschreibung','cpsmartcrm')?></label>
	            <div class="col-sm-4">
                    <textarea  class="col-md-12" id="annotazioni" name="annotazioni" rows="5" cols="50"><?php if(isset($annotazioni)) echo $annotazioni?></textarea>
	            </div>
            </div>
            <div class="row form-group">
                <label class="col-sm-2 control-label"><?php _e('Priorität','cpsmartcrm')?></label>
                <div class="col-sm-4">
                <?php if(isset($riga["priorita"])) WPsCRM_priorita($riga["priorita"]); else WPsCRM_priorita()?> 
                </div>
            </div>

            <h4 class="page-header" style="background:#e2e2e2;padding:15px"><?php _e('Benachrichtigungsregeln','cpsmartcrm')?><span class="crmHelp" data-help="notification-rules"></span></h4>


            <div class="row form group" style="padding-bottom:20px;border-bottom:1px solid #ccc">
               <label class="col-sm-2 control-label"><?php _e('Tage im Voraus','cpsmartcrm')?> *</label>
                <div class="col-sm-4">
                    <select class="form-control ruleActions _m k-dropdown _flat" style="width:150px" id="ruleStep" name="ruleStep">
                        <option value=""><?php _e("Select","cpsmartcrm")?></option><?php for($k=0;$k<31;$k++){echo '<option value="'.$k.'">'.$k.'</option>'; } ?>

                    </select>
                </div>
                <label class="col-sm-2 control-label"><?php _e('Sende auch eine Sofortbenachrichtigung','cpsmartcrm')?></label>
                <div class="col-sm-4">
                    <input type="checkbox" class="ruleActions " id="instantNotification" name="instantNotification" />
                    <small style="line-height:.8em"><?php _e('Eine E-Mail wird sofort an alle ausgewählten Benutzer/Gruppen gesendet, wenn die Option "E-Mail an Empfänger senden" unten aktiv ist','cpsmartcrm');?></small>
                </div>
            </div>
            <!--<div class="row form group" style="padding-bottom:20px;border-bottom:1px solid #ccc">
               
            </div>-->
            <div class="row form-group" style="border:1px solid red;line-height: 3.2em;<?php echo $tipo_agenda==1 ? "display:none" : false ?>">
                <label class="col-sm-2 control-label" style="font-size:1.2em"><?php _e('E-Mail an den Kunden senden','cpsmartcrm')?></label>
                <div class="col-md-4">
                <input type="checkbox" class="ruleActions " id="remindToCustomer" name="remindToCustomer"/> 
                </div>
            </div>
            <div class="row for-group">
              <label class="col-sm-2 control-label"  style="line-height:20px"><?php _e('Sende E-Mails an Empfänger','cpsmartcrm')?></label>
                <div class="col-md-4">
                <input type="checkbox" class="ruleActions " id="mailToRecipients" name="mailToRecipients"/>
                </div>
            </div>
            <div class="row form-group" style="margin-top:10px">
                <label class="col-sm-2 control-label" style="line-height:20px"><?php if($tipo_agenda==2) _e('Wähle Konto für diesen Termin aus','cpsmartcrm'); else _e('An Benutzer senden','cpsmartcrm')?></label>
                <div class="col-md-4">
                    <input class="ruleActions" id="remindToUser" name="remindToUser" />
                </div>

                 <label class="col-sm-2 control-label" style="line-height:20px"><?php if($tipo_agenda==2) _e('Im Konto-Dashboard veröffentlichen','cpsmartcrm'); else _e('Im Benutzer-Dashboard veröffentlichen','cpsmartcrm')?>?</label> 
                 <div class="col-md-4">
                <input type="checkbox" class="ruleActions" name="userDashboard" id="userDashboard" />
                 </div>
            </div>
            <div class="row form-group"  <?php if($tipo_agenda==2) echo ' style="display:none"'?> >
                <label class="col-sm-2 control-label"><?php _e('An Gruppe senden','cpsmartcrm')?></label>
                <div class="col-md-4">
                    <input class="ruleActions" id="remindToGroup" name="remindToGroup">
                </div>
                    <label class="col-sm-2 control-label"><?php _e('Im Gruppen-Dashboard veröffentlichen','cpsmartcrm')?>?</label>
                <div class="col-md-4">
                    <input type="checkbox" class="ruleActions" name="groupDashboard" id="groupDashboard"/>
                </div>
            </div>
                <input type="hidden" id="selectedUsers" name="selectedUsers"  class="ruleActions"value=""/>
                <input type="hidden" id="selectedGroups" name="selectedGroups"  class="ruleActions"value=""/>
                
             <div class="row form-group">
                 <ul class="select-action" style="margin-left:8px">
                    <li class="btn btn-success btn-sm _flat" id="btn_save"><i class="glyphicon glyphicon-floppy-disk"></i> 
                        <b onClick="return false;"> <?php _e('Speichern','cpsmartcrm')?></b>
                    </li>
                    <li class="btn btn-warning btn-sm _flat"><i class="glyphicon glyphicon-floppy-remove"></i>
                        <b onClick="window.location.replace('<?php echo admin_url('admin.php?page=smart-crm&p=scheduler/list.php')?>');return false;"> <?php _e('Zurücksetzen','cpsmartcrm')?></b>
                    </li>
                     
                </ul>
		    </div>      
	    </div>

    </div>
<input type="submit"  id="submit_form" style="display:none"/>
</form>
