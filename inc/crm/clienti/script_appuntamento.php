<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<script>
	jQuery(document).ready(function ($) {
		$("#dialog_appuntamento").kendoWindow({
			width: "86%",
			height: "80%",
			title: "<?php _e('Termin für Kunden hinzufügen:','cpsmartcrm') ?>",
			visible: false,
			modal: true,
			draggable: false,
			resizable:false,
			pinned: true,
			actions: [

				"Close"
			]
			, close: function () { setTimeout(function () { $('.k-overlay').hide() }, 100); }
		});
		var a_userSource = new kendo.data.DataSource({

			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_CRM_users',

						},
						success: function (result) {
							//console.log(result);
							$("#a_remindToUser").data("kendoMultiSelect").dataSource.data(result);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		var a_roleSource = new kendo.data.DataSource({

			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_registered_roles',
						},
						success: function (result) {
							//console.log(result);
							$("#a_remindToGroup").data("kendoMultiSelect").dataSource.data(result.roles);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
	$("#a_data_scadenza_inizio").kendoDateTimePicker({
		value: new Date(), format: $formatTime
	});
	$("#a_data_scadenza_fine").kendoDateTimePicker({
		value: new Date(), format: $formatTime
	});
	var a_users = $('#a_remindToUser').kendoMultiSelect({
		placeholder: "<?php _e( 'Nutzer wählen', 'cpsmartcrm'); ?>...",
		dataTextField: "display_name",
		dataValueField: "ID",
		autoBind: false,
		dataSource: a_userSource,
		change: function (e) {
			var selectedUsers = (this.value()).clean("");
			$('#a_selectedUsers').val(selectedUsers)
		},
		dataBound: function (e) {
			var selectedUsers = (this.value()).clean("");
			$('#a_selectedUsers').val(selectedUsers)
		}
	}).data("kendoMultiSelect")

	$('#a_remindToGroup').kendoMultiSelect({
		placeholder: "<?php _e( 'Wähle die Gruppe', 'cpsmartcrm'); ?>...",
		dataTextField: "name",
		dataValueField: "role",
		autoBind: false,
		dataSource: a_roleSource,
		change: function (e) {
			var a_selectedGroups = (this.value()).clean("");
			$('#a_selectedGroups').val(a_selectedGroups)
		},
		dataBound: function (e) {
			var a_selectedGroups = (this.value()).clean("");
			$('#a_selectedGroups').val(a_selectedGroups)
		}

	});
	a_users.value([<?php echo wp_get_current_user()->ID ?>]);
	function saveAppointment(){
		var opener = $('#dialog_appuntamento').data('from')

		if(opener =="clienti")
			id_cliente ='<?php if (isset($ID)) echo $ID?>'
		else if (opener == 'documenti')
			id_cliente = '<?php if (isset($fk_clienti)) echo $fk_clienti?>';
		else if (opener == 'list')
			id_cliente = $('#dialog_appuntamento').data('fkcliente');
        tipo_agenda = '2';
        scadenza_inizio = $("#a_data_scadenza_inizio").val();
        scadenza_fine = $("#a_data_scadenza_fine").val();
        scadenzaTimestamp = $("#a_data_scadenza_inizio").data('kendoDateTimePicker').value();
        annotazioni = $("#a_annotazioni").val();
        oggetto = $("#a_oggetto").val();
        priorita = $("#priorita").val();
        users = $("#a_selectedUsers").val();
        groups = $("#a_selectedGroups").val();
        days = $("#a_ruleStep").val();
        var s = "[";
        s += '{"ruleStep":"' + days + '" ,"remindToCustomer":';
        if ($('#a_remindToCustomer').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += ',"selectedUsers":"' + users + '"';
        s += ',"selectedGroups":"' + groups + '"';
        s += ',"userDashboard":';
        if ($('#a_userDashboard').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += ',"groupDashboard":';
        if ($('#a_groupDashboard').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += ',"mailToRecipients":';
        if ($('#a_mailToRecipients').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += '}'
        s += ']';
        var grid = $('#grid').data("kendoGrid");
        $('.modal_loader').show();
        $.ajax({
            url: ajaxurl,
            data: {
            'action': 'WPsCRM_save_todo',
            'id_cliente': id_cliente,
            tipo_agenda: tipo_agenda,
            scadenza_inizio: scadenza_inizio,
            scadenza_fine: scadenza_fine,
            scadenza_timestamp: scadenzaTimestamp,
            annotazioni: annotazioni,
            oggetto: oggetto,
            priorita: priorita,
            'steps': encodeURIComponent(s),
			'security':'<?php echo $scheduler_nonce; ?>'
			},
            type: "POST",
            success: function (response) {
            if (opener == "clienti") {//ricarico la grid solo se aperto da form clienti
              	var newDatasource = new kendo.data.DataSource({
              		transport: {
              			read: function (options) {
              				jQuery.ajax({
              					url: ajaxurl,
              					data: {
              						'action': 'WPsCRM_get_client_scheduler',
              						'id_cliente': id_cliente
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
              		$("#tabstrip").kendoTabStrip().data("kendoTabStrip").activateTab("#tab4");
              	}, 500);

              	//
              	setTimeout(function () {
              		grid.setDataSource(newDatasource);
              		grid.dataSource.read();
              	}, 600);

              	setTimeout(function () { grid.refresh() }, 700);
            }
            else {
				noty({
	                text: "<?php _e('Termin wurde hinzugefügt','cpsmartcrm')?>",
	                layout: 'center',
	                type: 'success',
	                template: '<div class="noty_message"><span class="noty_text"></span></div>',
	                //closeWith: ['button'],
	                timeout: 1000
				});
            }
            $("#dialog_appuntamento").data('kendoWindow').close();

            $('#new_appointment').find(':reset').click();

            }
        })
	}

		jQuery(document).ready(function ($) {

			// Parsley initialisieren
			var a_validator = $('#new_appointment').parsley();

			// Eigene Parsley-Validatoren
			window.Parsley.addValidator('hasclients', {
				validateString: function(value) {
					if (!value || value === "0") {
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
						return false;
					}
					return true;
				},
				messages: {
					de: "<?php _e('Du solltest einen Kunden auswählen','cpsmartcrm')?>"
				}
			});

			window.Parsley.addValidator('hasexpiration', {
				validateString: function(value, requirement, instance) {
					if (!value || value === "") {
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
						return false;
					}
					return true;
				},
				messages: {
					de: "<?php _e('Du solltest ein Datum für diese Veranstaltung auswählen','cpsmartcrm')?>"
				}
			});

			window.Parsley.addValidator('hasobject', {
				validateString: function(value) {
					if (!value || value === "") {
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
						return false;
					}
					return true;
				},
				messages: {
					de: "<?php _e('Du solltest einen Betreff für dieses Element eingeben','cpsmartcrm')?>"
				}
			});

			window.Parsley.addValidator('hasdays', {
				validateString: function(value) {
					if (!value || value === "") {
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
						return false;
					}
					return true;
				},
				messages: {
					de: "<?php _e('Du solltest auswählen, wie viele Tage im Voraus Du die Benachrichtigung aktivieren möchtest','cpsmartcrm')?>"
				}
			});

			window.Parsley.addValidator('hasnoty', {
				validateString: function(value, requirement, instance) {
					// Für Mehrfachauswahl mit Select2
					var kb = $("#a_remindToUser").val();
					var kb1 = $("#a_remindToGroup").val();
					if ((!kb || kb.length === 0) && (!kb1 || kb1.length === 0)) {
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2");
						return false;
					}
					return true;
				},
				messages: {
					de: "<?php _e('Du solltest einen Benutzer oder eine Gruppe von Benutzern auswählen, die benachrichtigt werden sollen','cpsmartcrm')?>"
				}
			});

			// Speichern-Button
			$("#a_saveStep").on("click", function () {
				if ($("#new_appointment").parsley().validate()) {
					saveAppointment();
				}
			});

			// Reset-Button
			$('._reset').on("click", function () {
				$("#dialog_appuntamento").data('kendoWindow').close();
			});
		});
})
</script>
