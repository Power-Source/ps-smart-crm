<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$random_name=WPsCRM_gen_random_code(30).'.pdf';
$filename=WPsCRM_UPLOADS."/".$random_name.".pdf";
$serverName=site_url();
?>
<div id="makePDF" style="position:absolute;left:-3000px;top:-3000px"></div>
<script>

function draw_list(id_cliente) {
	jQuery('.attachments').html('');
		jQuery.ajax({
			url: ajaxurl,
			data: {
				action: 'WPsCRM_get_documents_for_customer',
				id_cliente: id_cliente,
				security: "<?php echo $mail_nonce?>",
			},
			success: function (result) {
				console.log(result);
				var documents = result.documents, html = "", tipo = "", icon = "";
				var files = result.files;
				if(documents.length > 0 || files.length > 0)
				{
					html += "<div class=\"documentsContainer col-md-12\"><h4><?php _e( 'Dateien anhängen', 'cpsmartcrm'); ?></h4>";
					html += "<ul class=\"documentsList\">";
					// documents
					for (var k = 0; k <documents.length ; k++)
					{ 

						documents[k].tipo == 1 ? tipo = "<?php _e( 'Angebot', 'cpsmartcrm'); ?>" : tipo = "<?php _e( 'Rechnung', 'cpsmartcrm'); ?>";
						documents[k].filename == "" ? icon = "<span style=\"text-decoration:underline;cursor:pointer\" class=\"generatepdf\"><?php _e( 'PDF generieren', 'cpsmartcrm'); ?> &raquo;</span>" : icon = "<a href=\"<?php echo content_url() ?>/uploads/CRMdocuments/" + documents[k].filename + "\" target=\"_blank\"><img src=\"<?php echo WPsCRM_URL.'css/img/pdf.png'?>\" alt=\"<?php _e( 'Dokument ansehen', 'cpsmartcrm')?>\" title=\"<?php _e( 'Dokument ansehen', 'cpsmartcrm')?>\" style=\"height:30px\"/></a> <small><?php _e( 'Anfügen', 'cpsmartcrm')?></small> <input type=\"checkbox\" class=\"to_attach\">";
						html += "<li title=\"" + documents[k].testo_libero + "\" style=\"line-height:30px\" data-url=\"\" data-index=\"" + k + "\" data-document=\"" + documents[k].id + "\" data-filename=\"" + documents[k].filename + "\">";
						html += "<span class=\"col-md-3\">" + tipo + " #" + documents[k].progressivo + "</span>&nbsp;<span class=\"col-md-2\">" + " <?php _e( 'Betrag', 'cpsmartcrm')?>: " + documents[k].totale + " <?php echo WPsCRM_get_currency()->symbol?> </span>&nbsp;<span class=\"col-md-2\"> <?php _e( 'Datum', 'cpsmartcrm'); ?>: " + documents[k].culture_data_inserimento + "</span>&nbsp;<span class=\"col-md-3\">" + icon + "</span>";
						html += "</li>";
					}
					// files
					for (var f = 0; f <files.length ; f++)
					{
                        k ++;
						// files[f].tipo == 1 ? tipo = "<?php _e( 'Angebot', 'cpsmartcrm'); ?>" : tipo = "<?php _e( 'Rechnung', 'cpsmartcrm'); ?>";
						//files[f].filename == "" ? icon = "<span style=\"text-decoration:underline;cursor:pointer\" class=\"generatepdf\"><?php _e( 'PDF generieren', 'cpsmartcrm'); ?> &raquo;</span>" : icon = "<a href=\"<?php echo content_url() ?>/uploads/CRMdocuments/" + files[k].filename + "\" target=\"_blank\"><img src=\"<?php echo WPsCRM_URL.'css/img/pdf.png'?>\" alt=\"<?php _e( 'Dokument ansehen', 'cpsmartcrm')?>\" title=\"<?php _e( 'Dokument ansehen', 'cpsmartcrm')?>\" style=\"height:30px\"/></a> <small>Attach</small> <input type=\"checkbox\" class=\"to_attach\">";
						icon = '<img src="'+files[f].icon+'" style="height:30px">';
						html += "<li style=\"line-height:30px\" data-index=\"" + k + "\" data-url=\""+ files[f].url +"\" data-filename=\""+ files[f].filename +"\">";
						html += "<span class=\"col-md-5\" style=\"display: flex;justify-content: flex-start;padding-right:45px\"><a href=\"" + files[f].url + "\"  target=\"_blank\" style=\"overflow:hidden;white-space: nowrap;text-overflow:ellipsis;\"><i style=\"text-overflow:ellipsis; font-size:.8em;max-width:100%;overflow:hidden;white-space: nowrap;\">" + files[f].url + "</i></a></span>&nbsp;<span class=\"col-md-2\"> <?php _e( 'Datum', 'cpsmartcrm'); ?>: " + kendo.toString(new Date(files[f].date), $format) + "</span>&nbsp;<span class=\"col-md-3\"><img src=\"" + files[f].thumbnail + "\" style=\"height:30px\"><small><?php _e( 'Anfügen', 'cpsmartcrm')?></small> <input type=\"checkbox\" class=\"to_attach\"></span>";
						html += "</li>";
					}
					html += "</ul></div>";
				}

				jQuery('.attachments').html(html);
			},
			error: function (errorThrown) {
				console.log(errorThrown);
			}
	})
}

	jQuery(document).ready(function ($) {
		$('#mailToUsers').on('change', function () {
			$('._users').toggle();
		})
			var m_userSource = new kendo.data.DataSource({
			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_CRM_users',

						},
						success: function (result) {
							//console.log(result);
							$("#m_users").data("kendoMultiSelect").dataSource.data(result);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});
		var m_roleSource = new kendo.data.DataSource({
			transport: {
				read: function (options) {
					$.ajax({
						url: ajaxurl,
						data: {
							'action': 'WPsCRM_get_registered_roles',
						},
						success: function (result) {
							//console.log(result);
							$("#m_groups").data("kendoMultiSelect").dataSource.data(result.roles);
						},
						error: function (errorThrown) {
							console.log(errorThrown);
						}
					})
				}
			}
		});

		var M_users = $('#m_users').kendoMultiSelect({
			placeholder: "<?php _e( 'Benutzer wählen', 'cpsmartcrm'); ?>...",
			dataTextField: "display_name",
			dataValueField: "ID",
			autoBind: false,
			dataSource: m_userSource,
			change: function (e) {
				//var m_selectedUsers = (this.value()).clean("");
				//$('#m_users').val(m_selectedUsers)
			},
			dataBound: function (e) {
				//var m_selectedUsers = (this.value()).clean("");
				//jQuery('#t_selectedUsers').val(m_selectedUsers)
			}
		}).data("kendoMultiSelect")

		var M_groups = $('#m_groups').kendoMultiSelect({
			placeholder: "<?php _e( 'Wähle Gruppe', 'cpsmartcrm'); ?>...",
			dataTextField: "name",
			dataValueField: "role",
			autoBind: false,
			dataSource: m_roleSource,
			change: function (e) {
				//var m_selectedGroups = ( this.value() ).clean("");
				//$('#m_groups').val(m_selectedGroups)
			},
			dataBound: function (e) {
				//var m_selectedGroups = (this.value()).clean("");
				//$('#m_groups').val(m_selectedGroups)
			}
		});

		var attachments = {}

	$('.attachments').on('change', '.to_attach', function () {
		if ($(this).is(':checked')) {
			if ($(this).closest('li').data('url') == undefined) {
				attachments[$(this).closest('li').data('index')] = $(this).closest('li').data('filename');
			}
			else {
				attachments[$(this).closest('li').data('index')] = $(this).closest('li').data('filename');
			}
		}

		else {
			delete attachments[$(this).closest('li').data('index')];
		}
		console.log(JSON.stringify(attachments));
		$('#_attachments').val(JSON.stringify(attachments))
	});

//generate PDF from document list
	$('.attachments').on('click', '.generatepdf', function (e) {
		showMouseLoader();
		$('#mouse_loader').offset({ left: e.pageX, top: e.pageY });
		var PDF;
		var id = $(this).closest('li').data('document')

		jQuery.ajax({
			url: "<?php echo admin_url('admin.php?page=smart-crm&p=documenti/document_print.php&id_invoice=')?>"+id + "&layout=iframe",
			success: function (result) {
				$('#makePDF').html($("<html/>").html(result) )
				kendo.drawing.drawDOM($('.WPsCRM_pdf-page'))
			.then(function (group) {
				// Render the result as a PDF file
				return kendo.drawing.exportPDF(group, {
					paperSize: "auto",
					multiPage: true,
					margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
				});
			})
	    .done(function (data) {
	    	// Save the PDF file
			PDF = data;
        	kendo.saveAs({
        		dataURI: data,
        		fileName: "<?php echo $random_name?>",
        	});
        	$.ajax({
        		url: ajaxurl,
				method:'POST',
				data: {
					action: 'WPsCRM_save_pdf_document',
	        		fileName: "<?php echo $random_name?>",
					doc_id: id,
					PDF: PDF,
					security:"<?php echo $print_nonce?>",
					},
        		success: function (result) {
						$('#makePDF').html('');
						draw_list(parseInt($("#dialog_mail").data('fkcliente')))
						hideMouseLoader();
					},
					error: function (errorThrown) {
						console.log(errorThrown);
				}
			})

		});
			},
			error: function (errorThrown) {
				console.log(errorThrown);
			}
		})

	});
	//var mailWindow = $("#dialog_mail").data("kendoWindow");
	$("#dialog_mail").kendoWindow({
		width: "900px",
		height: "86%",
		title: "<?php _e('E-Mail an den Kunden senden:','cpsmartcrm') ?>",
		visible: false,
		modal: true,
		draggable: false,
		pinned: true,
		actions: [

			"Close"
		],
		close: function () {
			this.title("<?php _e('E-Mail an den Kunden senden:','cpsmartcrm') ?>");
			$('#new_mail').find(':reset').click();
			$('._schedule').hide();
			$("#schedule").data("kendoDateTimePicker").value(new Date())
			$('.attachments').html('');
			setTimeout(function(){$('.k-overlay').hide()},100);
		}
	});
	var mailWindow = $("#dialog_mail").data("kendoWindow");
	$("#createPdf").kendoWindow({
		width: "90%",
		height: "90%",
		title: "<?php _e( 'PDF generieren', 'cpsmartcrm'); ?>",
		iframe: true,
		visible: false,
		modal: true,
		draggable: false,
		actions: [

			"Close"
		],
		close: function () {
			var customer = $('#dialog_mail').data('fkcliente')
			draw_list(customer)
		}
	})


	$('input[name=sendNow]').change(function () {

		if (this.value == 0) {
			$('._schedule').show();
		}
		else  {
			$('._schedule').hide();
		}
	})
	var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
	var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
	var now = new Date();
	var _after = new Date(now);
	_after.setHours(now.getHours() + 1);
	_after.toLocaleDateString();
	$('#schedule').kendoDateTimePicker({
		value: new Date(),
		format: $formatTime,
		interval: 60,
		min:_after
	});
    $('._reset').on('click', function () {
        mailWindow.close();

    })
	var m_validator = $("#new_mail").kendoValidator({
		rules: {
			hasObject: function (input) {
				if (input.is("[name=m_oggetto]")) {
					var kb = $("#m_oggetto").val();
					if (kb == "") {
						$("#m_oggetto").focus();
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2")
						return false;
					};
				}
				return true;
			},
			hasContent: function (input) {
				if (input.is("[name=m_messaggio]")) {

					var kb = $("#m_messaggio").val();
					if (kb == "") {
						$("#m_messaggio").focus();
						jQuery.playSound("<?php echo WPsCRM_URL?>inc/audio/double-alert-2")
						return false;
					}

				}
				return true;
			},
		},

		messages: {
			hasObject: "<?php _e('Du solltest einen Betreff für dieses Element eingeben','cpsmartcrm')?>",
			hasContent:"<?php _e('Du solltest eine Inhaltsnachricht schreiben','cpsmartcrm')?>"

		}
	}).data("kendoValidator");
    $('#saveMail').on('click', function () {
        if(m_validator.validate())
        	sendMail();
    })

    function sendMail() {
		var opener = $('#dialog_mail').data('from')

		if (opener == "clienti")
        	id_cliente = '<?php if(isset($ID)) echo $ID?>'
		else if (opener == 'documenti')
        	id_cliente = '<?php if(isset($fk_clienti)) echo $fk_clienti?>'
		else if (opener == 'list')
        	id_cliente = $('#dialog_mail').data('fkcliente');
		var schedule = $("#schedule").data("kendoDateTimePicker");
		var scheduledGMT = schedule.value();

		var now = new Date();
		console.log(scheduledGMT, now, scheduledGMT - now);

	    jQuery.ajax({
			url: ajaxurl,
			data: {
				action: 'WPsCRM_mail_to_customer',
				id_cliente: id_cliente,
				security: "<?php echo $mail_nonce?>",
				subject: $('#m_oggetto').val(),
				message: $('#m_messaggio').val(),
				mailNow: $('input[name=sendNow]:checked').val(),
				toCustomer: $('#mailToCustomer').val(),
				toUsers: $('#mailToUsers').val(),
				Users: $('#m_users').data('kendoMultiSelect').value(),
				Groups: $('#m_groups').data('kendoMultiSelect').value(),
				scheduled: $('#schedule').val(),
				scheduledGMT:scheduledGMT,
				attachments: $('#_attachments').val(),
				timediff: (scheduledGMT - now) / 1000
			},
			success: function (result) {
				console.log(result);
				var $text
				$('input[name=sendNow]:checked').val() == 0 ? $text = "<?php _e('E-Mail wurde geplant','cpsmartcrm')?>" : $text = "<?php _e('Die Email wurde verschickt','cpsmartcrm')?>";
				noty({
					text: $text,
					layout: 'center',
					type: 'success',
					template: '<div class="noty_message"><span class="noty_text"></span></div>',
					//closeWith: ['button'],
					timeout: 1000
				});

				$('#new_mail').find(':reset').click();
				$('#mailToUsers').attr('checked', false);
				$('._users').hide();
				var mailWindow = $("#dialog_mail").data("kendoWindow");
				mailWindow.close();
				if ($('.documentGrid').length > 0) {
					$('.documentGrid').each(function () {
						var g = $(this).data('kendoGrid');
						console.log(g)
						g.dataSource.read();
					})

				}

			},
			error: function (errorThrown) {
				console.log(errorThrown);
			}
		})
    }
})
</script>
