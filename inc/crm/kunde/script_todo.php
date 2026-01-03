<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Nur JavaScript Code - ohne <script> Tags!
// Dies wird per include() in den Haupt-Script-Block eingefügt
?>
var PSCRM_INLINE_MODE = !!window.PSCRM_INLINE_FORM;

// ===== TODO Modal Handler =====
let todoModal = null;

const initTodoModal = function() {
    if (PSCRM_INLINE_MODE) return; // In inline mode kein Modal
    if (todoModal) return;
    const contentEl = document.getElementById('dialog_todo');
    todoModal = new PSCRM.Modal({
        title: "<?php _e('Aufgaben für den Kunden hinzufügen:','cpsmartcrm') ?>",
        width: '86%',
        height: 600,
        content: contentEl,
        destroyOnClose: false
    });
};

if (!PSCRM_INLINE_MODE) {
    $(document).on('click', '.btn_todo', function () {
        var id = $(this).data('id') || $('#dialog_todo').data('fkcliente');
        var name = $(this).data('name');
        if (id) $('#dialog_todo').attr('data-fkcliente', id);
        if (name) $('.name_cliente').html(name);
        initTodoModal();
        todoModal.open();
    });
}

// TODO Modal Funktionen
function loadTodoUsers() {
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
            $("#t_remindToUser").val(["<?php echo wp_get_current_user()->ID ?>"]).trigger('change');
        }
    });
}
loadTodoUsers();

function loadTodoGroups() {
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
loadTodoGroups();

// TODO Datepicker initialisieren (TODO hat VON-BIS, also zwei Felder!)
(function(){
    var inputStart = document.getElementById('t_data_inizio');
    var inputEnd = document.getElementById('t_data_fine');
    if (!inputStart || !inputEnd) return;

    // helper: parse dd-mm-YYYY or dd.mm.YYYY to ISO datetime-local string
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
        flatpickr(inputEnd, {
            enableTime: true,
            dateFormat: "d.m.Y H:i",
            defaultDate: inputEnd.value || new Date()
        });
    } else {
        // Fallback: native control
        inputStart.type = 'datetime-local';
        inputEnd.type = 'datetime-local';
        var iso = toIsoLocal(inputStart.value);
        if (iso) inputStart.value = iso;
        var iso2 = toIsoLocal(inputEnd.value);
        if (iso2) inputEnd.value = iso2;
    }
})();

$("#t_remindToUser").on('change', function () {
    $('#t_selectedUsers').val($(this).val());
});
$("#t_remindToGroup").on('change', function () {
    $('#t_selectedGroups').val($(this).val());
});

$('#new_todo').parsley({
    errorsWrapper: '<div class="parsley-errors-list"></div>',
    errorTemplate: '<div></div>',
    trigger: 'change'
});

function saveTodo() {
    var opener = $('#dialog_todo').data('from') || $('#scheduler-inline-form').data('from') || 'list';
    var id_cliente = '';
    if(opener =="kunde")
        id_cliente ='<?php if (isset($ID)) echo $ID?>'
    else if (opener == 'dokumente')
        id_cliente = '<?php if (isset($fk_kunde)) echo $fk_kunde?>';
    else if (opener == 'list')
        id_cliente = $('#dialog_todo').data('fkcliente');
    
    var id_agenda = $("#id_agenda_todo").val() || '';
    
    // Füge id_cliente nur hinzu wenn neu
    if (!id_agenda) {
        $('#new_todo').append('<input type="hidden" name="id_cliente" value="' + id_cliente + '" />');
    }
    
    $('.modal_loader').show();

    $.ajax({
        url: ajaxurl,
        data: $('#new_todo').serialize(),
        type: "POST",
        success: function (response) {
            var msg = id_agenda ? '<?php _e('TODO wurde aktualisiert','cpsmartcrm')?>' : '<?php _e('TODO wurde hinzugefügt','cpsmartcrm')?>';
            PSCRM.notify(msg, 'success');
            if (todoModal) { todoModal.close(); }
            $('#new_todo').find(':reset').click();
            $('#id_agenda_todo').val(''); // Reset ID
            $('.modal_loader').hide();
            
            // Schedule Grid neu laden wenn vorhanden
            if (typeof initScheduleGrid === 'function') {
                initScheduleGrid();
            }
        },
        error: function(xhr, status, error) {
            console.error('Fehler beim Speichern des Todos:', error, xhr.responseText);
            PSCRM.notify("<?php _e('Fehler beim Speichern','cpsmartcrm')?>", 'error');
            $('.modal_loader').hide();
        }
    });
}

$("#t_saveStep").on('click', function (e) {
    e.preventDefault();
    if ($('#new_todo').parsley().validate()) {
        saveTodo();
    }
});
$('._reset').on('click', function () {
    if (todoModal) todoModal.close();
});

setTimeout(function () {
    $('.modal_loader').hide()
},200)
