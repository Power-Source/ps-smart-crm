<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Nur JavaScript Code - ohne <script> Tags!
?>
var PSCRM_INLINE_MODE = !!window.PSCRM_INLINE_FORM;

// ===== ATTIVITA (Aktivität) Modal Handler =====
let attivitaModal = null;

const initAttivitaModal = function() {
	if (PSCRM_INLINE_MODE) return; // kein Modal im Inline-Modus
	if (attivitaModal) return;
	const contentEl = document.getElementById('dialog_attivita');
	attivitaModal = new PSCRM.Modal({
		title: "<?php _e('Aktivität für Kunden hinzufügen:','cpsmartcrm') ?>",
		width: '86%',
		height: '80%',
		content: contentEl,
		destroyOnClose: false,
		buttons: {
			cancel: {
				text: "<?php _e('Abbrechen', 'cpsmartcrm') ?>",
				click: function() {
					attivitaModal.close();
				}
			},
			save: {
				text: "<?php _e('Speichern', 'cpsmartcrm') ?>",
				click: function() {
					if ($('#new_attivita').parsley && $('#new_attivita').parsley().validate()) {
						saveAttivita();
					} else {
						saveAttivita();
					}
				},
				primary: true
			}
		}
	});
};

if (!PSCRM_INLINE_MODE) {
    $(document).on('click', '.btn-activity, .btn-attivita', function() {
		var id = $(this).data('id');
		var name = $(this).data('name');
		$('#dialog_attivita').attr('data-fkcliente', id);
		$('.name_cliente').html(name || '');
		initAttivitaModal();
		attivitaModal.open();
    });
}

function saveAttivita() {
	// Editor-Inhalt in Hidden Textarea kopieren
	var editorContent = $('#attivita_editor').html();
	$('#attivita_note_hidden').val(editorContent);
	
	var formData = $('#new_attivita').serialize();
	
	$.ajax({
		url: ajaxurl,
		type: 'POST',
		data: formData,
		success: function(response) {
			if (response && response.success) {
				if (typeof PSCRM !== 'undefined' && PSCRM.notify) {
					PSCRM.notify('<?php _e('Notiz wurde gespeichert', 'cpsmartcrm') ?>', 'success');
				}
				if (attivitaModal) attivitaModal.close();
				// Setze Hash für Tab 4 und lade neu
				setTimeout(function() {
					window.location.hash = 'tab-3'; // Tab 4 ist Index 3
					location.reload();
				}, 500);
			} else {
				if (typeof PSCRM !== 'undefined' && PSCRM.notify) {
					PSCRM.notify('<?php _e('Fehler beim Speichern', 'cpsmartcrm') ?>', 'error');
				} else {
					alert('<?php _e('Fehler beim Speichern', 'cpsmartcrm') ?>');
				}
			}
		},
		error: function() {
			if (typeof PSCRM !== 'undefined' && PSCRM.notify) {
				PSCRM.notify('<?php _e('Fehler beim Speichern', 'cpsmartcrm') ?>', 'error');
			} else {
				alert('<?php _e('Fehler beim Speichern', 'cpsmartcrm') ?>');
			}
		}
	});
}
