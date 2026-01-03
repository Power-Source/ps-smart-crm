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
		destroyOnClose: false,
		buttons: {
			cancel: {
				text: "<?php _e('Abbrechen', 'cpsmartcrm') ?>",
				click: function() {
					appuntamentoModal.close();
				}
			},
			save: {
				text: "<?php _e('Speichern', 'cpsmartcrm') ?>",
				click: function() {
					if ($('#new_appuntamento').parsley().validate()) {
						saveAppuntamento();
					}
				},
				primary: true
			}
		}
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

function saveAppuntamento() {
	// Appuntamento speichern
	$('.modal_loader').show();
	PSCRM.notify('Termin wird gespeichert...', 'info');
	// AJAX wird später implementiert
	setTimeout(function() {
		appuntamentoModal.close();
		$('.modal_loader').hide();
	}, 500);
}
