<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<script>
jQuery(document).ready(function ($) {
    // Dialog als jQuery UI Dialog
    $("#dialog_todo").dialog({
        autoOpen: false,
        width: "86%",
        height: 600,
        modal: true,
        title: "<?php _e('Aufgaben für den Kunden hinzufügen:','cpsmartcrm') ?>",
        close: function () {
            $('.modal_loader').hide();
        }
    });

    // Öffnen des Dialogs (z.B. über Button)
    $('.btn_todo').on('click', function () {
        $("#dialog_todo").dialog("open");
    });

    // Select2 für Benutzer
    function loadUsers() {
        $.ajax({
            url: ajaxurl,
            data: { 'action': 'WPsCRM_get_CRM_users' },
            success: function (result) {
                var users = [];
                if (Array.isArray(result)) {
                    users = result.map(function(user) {
                        return { id: user.ID, text: user.display_name };
                    });
                }
                $("#t_remindToUser").select2({
                    data: users,
                    placeholder: "<?php _e( 'Benutzer wählen', 'cpsmartcrm'); ?>...",
                    width: '100%',
                    multiple: true
                });
                // Standardwert: aktueller User
                $("#t_remindToUser").val(["<?php echo wp_get_current_user()->ID ?>"]).trigger('change');
            }
        });
    }
    loadUsers();

    // Select2 für Gruppen
    function loadGroups() {
        $.ajax({
            url: ajaxurl,
            data: { 'action': 'WPsCRM_get_registered_roles' },
            success: function (result) {
                var groups = [];
                if (result && result.roles) {
                    groups = result.roles.map(function(role) {
                        return { id: role.role, text: role.name };
                    });
                }
                $("#t_remindToGroup").select2({
                    data: groups,
                    placeholder: "<?php _e( 'Wähle Gruppe', 'cpsmartcrm'); ?>...",
                    width: '100%',
                    multiple: true
                });
            }
        });
    }
    loadGroups();

    // jQuery UI DateTimePicker (z.B. mit datetimepicker Addon)
    $("#t_data_scadenza").datetimepicker({
        dateFormat: "dd.mm.yy",
        timeFormat: "HH:mm",
        defaultDate: new Date()
    });

    // Felder synchronisieren
    $("#t_remindToUser").on('change', function () {
        $('#t_selectedUsers').val($(this).val());
    });
    $("#t_remindToGroup").on('change', function () {
        $('#t_selectedGroups').val($(this).val());
    });

    // Validierung (Parsley)
    $('#new_todo').parsley({
        errorsWrapper: '<div class="parsley-errors-list"></div>',
        errorTemplate: '<div></div>',
        trigger: 'change'
    });

    // Speichern
    function saveTodo() {
        var opener = $('#dialog_todo').data('from');
        var id_cliente = '';
        if(opener =="clienti")
            id_cliente ='<?php if (isset($ID)) echo $ID?>'
        else if (opener == 'documenti')
            id_cliente = '<?php if (isset($fk_clienti)) echo $fk_clienti?>';
        else if (opener == 'list')
            id_cliente = $('#dialog_todo').data('fkcliente');
        var tipo_agenda = '1';
        var scadenza_inizio = $("#t_data_scadenza").val();
        var scadenza_fine = $("#t_data_scadenza").val();
        var annotazioni = $("#t_annotazioni").val();
        var oggetto = $("#t_oggetto").val();
        var priorita = $("#priorita").val();
        var users = $("#t_selectedUsers").val();
        var groups = $("#t_selectedGroups").val();
        var days = $("#t_ruleStep").val();
        var s = "[";
        s += '{"ruleStep":"' + days + '" ,"remindToCustomer":';
        if ($('#t_remindToCustomer').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += ',"selectedUsers":"' + users + '"';
        s += ',"selectedGroups":"' + groups + '"';
        s += ',"userDashboard":';
        if ($('#t_userDashboard').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += ',"groupDashboard":';
        if ($('#t_groupDashboard').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += ',"mailToRecipients":';
        if ($('#t_mailToRecipients').prop('checked'))
            s += '"on"';
        else
            s += '""';
        s += '}';
        s += ']';

        $('.modal_loader').show();

        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'WPsCRM_save_todo',
                id_cliente: id_cliente,
                tipo_agenda: tipo_agenda,
                scadenza_inizio: scadenza_inizio,
                scadenza_fine: scadenza_fine,
                annotazioni: annotazioni,
                oggetto: oggetto,
                priorita: priorita,
                'steps': encodeURIComponent(s),
                'security':'<?php echo $scheduler_nonce; ?>'
            },
            type: "POST",
            success: function (response) {
                noty({
                    text: "<?php _e('TODO wurde hinzugefügt','cpsmartcrm')?>",
                    layout: 'center',
                    type: 'success',
                    template: '<div class="noty_message"><span class="noty_text"></span></div>',
                    timeout: 1000
                });
                $("#dialog_todo").dialog("close");
                $('#new_todo').find(':reset').click();
                $('.modal_loader').hide();
                // Optional: Tabelle neu laden, falls vorhanden
                if ($('#grid').length && $.fn.DataTable) {
                    $('#grid').DataTable().ajax.reload();
                }
            }
        });
    }

    // Button-Handler
    $("#t_saveStep").on('click', function (e) {
        e.preventDefault();
        if ($('#new_todo').parsley().validate()) {
            saveTodo();
        }
    });
    $('._reset').on('click', function () {
        $("#dialog_todo").dialog("close");
    });

    setTimeout(function () {
        $('.modal_loader').hide()
    },200)

	// Parsley Custom-Validatoren mit Sound und Meldung
	window.Parsley.addValidator('days', {
		validateString: function(value) {
			if (value === "" || value === null) {
				new Audio("<?php echo WPsCRM_URL?>inc/audio/double-alert-2.mp3").play();
				return false;
			}
			return true;
		},
		messages: {
			de: "<?php _e('Du solltest auswählen, wie viele Tage im Voraus Du die Benachrichtigung aktivieren möchtest','cpsmartcrm')?>"
		}
	});

	window.Parsley.addValidator('noty', {
		validateString: function(value) {
			var users = $('#t_remindToUser').val();
			var groups = $('#t_remindToGroup').val();
			if ((!users || users.length === 0) && (!groups || groups.length === 0)) {
				new Audio("<?php echo WPsCRM_URL?>inc/audio/double-alert-2.mp3").play();
				return false;
			}
			return true;
		},
		messages: {
			de: "<?php _e('Du solltest einen Benutzer oder eine Gruppe von Benutzern auswählen, die benachrichtigt werden sollen','cpsmartcrm')?>"
		}
	});

	window.Parsley.addValidator('object', {
		validateString: function(value) {
			if (value === "" || value === null) {
				new Audio("<?php echo WPsCRM_URL?>inc/audio/double-alert-2.mp3").play();
				return false;
			}
			return true;
		},
		messages: {
			de: "<?php _e('Du solltest einen Betreff für dieses Element eingeben','cpsmartcrm')?>"
		}
	});

	window.Parsley.addValidator('expiration', {
		validateString: function(value) {
			if (value === "" || value === null) {
				new Audio("<?php echo WPsCRM_URL?>inc/audio/double-alert-2.mp3").play();
				return false;
			}
			return true;
		},
		messages: {
			de: "<?php _e('Du solltest ein Datum für diese Veranstaltung auswählen','cpsmartcrm')?>"
		}
	});
});
</script>
