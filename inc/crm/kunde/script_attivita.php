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
		$('.nome_cliente').html(name || '');
		initAttivitaModal();
		attivitaModal.open();
    });
}

function saveAttivita() {
	// Attività speichern
	$('.modal_loader').show();
	PSCRM.notify('Aktivität wird gespeichert...', 'info');
	// AJAX wird später implementiert
	setTimeout(function() {
		attivitaModal.close();
		$('.modal_loader').hide();
	}, 500);
}
