<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Nur JavaScript Code - ohne <script> Tags und HTML-Blöcke!
?>
// ===== MAIL Modal Handler =====
let mailModal = null;

const initMailModal = function() {
	if (mailModal) return;
	
	mailModal = new PSCRM.Modal('dialog_mail', {
		title: "<?php _e('E-Mail an Kunden senden:','cpsmartcrm') ?>",
		width: '86%',
		height: '80%',
		buttons: {
			cancel: {
				text: "<?php _e('Abbrechen', 'cpsmartcrm') ?>",
				click: function() {
					mailModal.close();
				}
			},
			send: {
				text: "<?php _e('Senden', 'cpsmartcrm') ?>",
				click: function() {
					sendMail();
				},
				primary: true
			}
		}
	});
};

$(document).on('click', '.btn-mail, .btn-email', function() {
	initMailModal();
	mailModal.open();
});

function drawMailAttachmentList(id_cliente) {
	jQuery('.attachments').html('');
	jQuery.ajax({
		url: ajaxurl,
		data: {
			action: 'WPsCRM_get_documents_for_customer',
			id_cliente: id_cliente,
			security: "<?php echo isset($mail_nonce) ? esc_attr($mail_nonce) : '' ?>",
		},
		success: function (result) {
			console.log('Mail attachments loaded:', result);
			// Attachments rendering wird hier implementiert
		},
		error: function (errorThrown) {
			console.error('Fehler beim Laden der Anhänge:', errorThrown);
		}
	});
}

$('#mailToUsers').on('change', function () {
	$('._users').toggle();
});

function sendMail() {
	// E-Mail senden
	$('.modal_loader').show();
	PSCRM.notify('E-Mail wird versendet...', 'info');
	// AJAX wird später implementiert
	setTimeout(function() {
		mailModal.close();
		$('.modal_loader').hide();
	}, 500);
}
