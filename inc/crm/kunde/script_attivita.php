<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Nur JavaScript Code - ohne <script> Tags!
?>
// ===== ATTIVITA (Aktivität) Modal Handler =====
let attivitaModal = null;

const initAttivitaModal = function() {
	if (attivitaModal) return;
	
	attivitaModal = new PSCRM.Modal('dialog_attivita', {
		title: "<?php _e('Aktivität für Kunden hinzufügen:','cpsmartcrm') ?>",
		width: '86%',
		height: '80%',
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

$(document).on('click', '.btn-activity, .btn-attivita', function() {
	initAttivitaModal();
	attivitaModal.open();
});

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
