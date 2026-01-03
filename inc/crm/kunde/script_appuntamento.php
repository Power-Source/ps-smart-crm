<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Nur JavaScript Code - ohne <script> Tags!
?>
var PSCRM_INLINE_MODE = !!window.PSCRM_INLINE_FORM;

// ===== APPUNTAMENTO (Termin) Modal Handler =====
let appuntamentoModal = null;

const initAppuntamentoModal = function() {
	if (PSCRM_INLINE_MODE) return; // kein Modal in Inline-Modus
	if (appuntamentoModal) return;
	const contentEl = document.getElementById('dialog_appuntamento');
	appuntamentoModal = new PSCRM.Modal({
		title: "<?php _e('Termin für Kunden hinzufügen:','cpsmartcrm') ?>",
		width: '86%',
		height: '80%',
		content: contentEl,
		destroyOnClose: false
	});
};

if (!PSCRM_INLINE_MODE) {
    $(document).on('click', '.btn_appuntamento, .btn-appointment', function() {
		var id = $(this).data('id');
		var name = $(this).data('name');
		$('#dialog_appuntamento').attr('data-fkcliente', id);
		$('.name_cliente').html(name || '');
		initAppuntamentoModal();
		appuntamentoModal.open();
    });
}

// Datepicker für Termin initialisieren (TERMIN hat nur EIN Feld!)
(function(){
    var inputStart = document.getElementById('a_data_agenda');
    if (!inputStart) return;

    function toIsoLocal(val) {
        if (!val || typeof val !== 'string') return '';
        var clean = val.replace(/[.]/g,'-');
        var parts = clean.split('-');
        if (parts.length < 3) return '';
        var d = parseInt(parts[0],10);
        var m = parseInt(parts[1],10) - 1;
        var y = parseInt(parts[2],10);
        if (isNaN(d)||isNaN(m)||isNaN(y)) return '';
        var dt = new Date(y, m, d, 0, 0, 0, 0);
        if (isNaN(dt.getTime())) return '';
        var pad = function(n){ return (n<10?'0':'') + n; };
        return dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate()) + 'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
    }

    if (typeof flatpickr === 'function') {
        flatpickr(inputStart, {
            enableTime: true,
            dateFormat: "d.m.Y H:i",
            defaultDate: inputStart.value || new Date()
        });
    } else {
        inputStart.type = 'datetime-local';
        var iso = toIsoLocal(inputStart.value);
        if (iso) inputStart.value = iso;
    }
})();

// Benutzer für Termin laden
function loadAppuntamentoUsers() {
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
            $("#a_remindToUser").select2({
                data: users,
                placeholder: "<?php _e( 'Benutzer wählen', 'cpsmartcrm'); ?>...",
                width: '100%',
                multiple: true
            });
            $("#a_remindToUser").val(["<?php echo wp_get_current_user()->ID ?>"]).trigger('change');
        }
    });
}
loadAppuntamentoUsers();

// Gruppen für Termin laden
function loadAppuntamentoGroups() {
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
            $("#a_remindToGroup").select2({
                data: groups,
                placeholder: "<?php _e( 'Wähle Gruppe', 'cpsmartcrm'); ?>...",
                width: '100%',
                multiple: true
            });
        }
    });
}
loadAppuntamentoGroups();

// Select2 Event-Handler
$("#a_remindToUser").on('change', function () {
    $('#a_selectedUsers').val($(this).val());
});
$("#a_remindToGroup").on('change', function () {
    $('#a_selectedGroups').val($(this).val());
});

// Parsley Validierung
$('#new_appuntamento').parsley({
    errorsWrapper: '<div class="parsley-errors-list"></div>',
    errorTemplate: '<div></div>',
    trigger: 'change'
});

function saveAppuntamento() {
	var id_cliente = '';
	var opener = $('#dialog_appuntamento').data('from') || 'kunde';
	if(opener == "kunde")
		id_cliente = '<?php if (isset($ID)) echo $ID?>';
	else if (opener == 'dokumente')
		id_cliente = '<?php if (isset($fk_kunde)) echo $fk_kunde?>';
	else if (opener == 'list')
		id_cliente = $('#dialog_appuntamento').data('fkcliente');
	
	// Füge id_cliente zu den versteckten Feldern hinzu (nur wenn neu)
	if (!$('#id_agenda').val()) {
		$('#new_appuntamento').append('<input type="hidden" name="id_cliente" value="' + id_cliente + '" />');
		$('#new_appuntamento').append('<input type="hidden" name="tipo_agenda" value="2" />');
	}
	
	$('.modal_loader').show();

	$.ajax({
		url: ajaxurl,
		data: $('#new_appuntamento').serialize(),
		type: "POST",
		success: function (response) {
			var msg = $('#id_agenda').val() ? '<?php _e('Termin wurde aktualisiert','cpsmartcrm')?>' : '<?php _e('Termin wurde hinzugefügt','cpsmartcrm')?>';
			PSCRM.notify(msg, 'success');
			if (appuntamentoModal) { appuntamentoModal.close(); }
			$('#new_appuntamento').find(':reset').click();
			$('#id_agenda').val(''); // Reset ID
			$('.modal_loader').hide();
			
			// Schedule Grid neu laden wenn vorhanden
			if (typeof initScheduleGrid === 'function') {
				initScheduleGrid();
			}
		},
		error: function(xhr, status, error) {
			console.error('Fehler beim Speichern des Termins:', error, xhr.responseText);
			PSCRM.notify("<?php _e('Fehler beim Speichern','cpsmartcrm')?>", 'error');
			$('.modal_loader').hide();
		}
	});
}

$("#a_saveStep").on('click', function (e) {
    e.preventDefault();
    var form = $('#new_appuntamento');
    // Check if parsley exists
    if (form.parsley && form.parsley()) {
        if (form.parsley().validate()) {
            saveAppuntamento();
        }
    } else {
        // No parsley validation, save anyway
        saveAppuntamento();
    }
});

$('._reset').on('click', function () {
    if (appuntamentoModal) appuntamentoModal.close();
});

setTimeout(function () {
    $('.modal_loader').hide()
},200)
