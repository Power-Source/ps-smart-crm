<?php
if ( ! defined( 'ABSPATH' ) ) exit;

?>
<style>
	<?php if(isset($_GET['layout']) && $_GET['layout']=="iframe") { ?>
	#wpadminbar, #adminmenumain, #mainMenu,.wrap h1,.btn-warning,.select-action:first-of-type {
        display: none;
    }
	#wpcontent, #wpfooter {
    margin-left: 0;
}
		<?php } ?>
</style>
<?php
$print_nonce=wp_create_nonce( "print_document" );
$ID=$_GET['id_invoice'];
global $document;
$document_numbering=$document->numbering();
$document_messages=$document->messages();
$signature=$document->signature();
$formatted_signature=html_entity_decode($document->formatted_signature());

$c_table=WPsCRM_TABLE."clienti";
$d_table=WPsCRM_TABLE."documenti";
$dd_table=WPsCRM_TABLE."documenti_dettaglio";

$general_options=get_option('CRM_general_settings');
$document_options=get_option('CRM_documents_settings');
$def_iva=$document_options['default_vat'];
$accOptions = get_option( "CRM_acc_settings" );


if( isset($general_options['print_logo']) && $general_options['print_logo'] =='1')
    $logo='<img src="'.$general_options['company_logo'].'" class="WPsCRM_companylogo" />';
else
    $logo="";

//document header
$header =	'<section class="WPsCRM_pdf-header">
				<div style="height:100%!important;width:100%;padding:6px;display:inline-block">
					<div class="col-md-6" style="'.$document->alignHeader(0).';height:200px">'.$logo.'</div>
					<div class="col-md-6" style="'.$document->alignHeader(1).';min-height:200px">

					<ul style="list-style:none">';
foreach($document->master_data() as $data =>$val){
	$val1 = array_values($val);
	if($val['show']==1)
	{
		if(isset($val['show_label']) && $val['show_label']==1 && html_entity_decode($val1[0]) !="")
		{

			$header.='<li class="WPsCRM_headerlines"><small>'. key($val) .'</small>: '. html_entity_decode($val1[0]).'</li>';
			if(key($val)=="full_header"){}

		}
		else if( $val1[0] !="" ){
			$header.='<li class="WPsCRM_headerlines">'. html_entity_decode($val1[0]).'</li><br>';
		}
	}
}
$header.=			'</ul>
				</div>
			</div>
		</section>';

if ($ID)
{
	$sql="select * from $d_table where id=$ID";
	$riga=$wpdb->get_row($sql, ARRAY_A);
  $riga= stripslashes_deep($riga);
	switch ($tipo=$riga["tipo"])
	{
		case 1:
			$progressivo=$riga["progressivo"];
			$document_name=__("Angebot",'cpsmartcrm');
			$text_before=$document_messages['offers_before'];
			$text_after=$document_messages['offers_after'];
			$document_prefix=$document_numbering['offers_prefix'];
			$document_suffix=$document_numbering['offers_suffix'];
			$document_dear=$document_messages['offers_dear'];
			$edit_url=admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&ID='.$ID);
			break;
		case 2:
			$progressivo=$riga["progressivo"];
			$document_name=  __("Rechnung",'cpsmartcrm');
			$text_before=$document_messages['invoices_before'];
			$text_after=$document_messages['invoices_after'];
			$document_prefix=$document_numbering['invoices_prefix'];
			$document_suffix=$document_numbering['invoices_suffix'];
			$document_dear=$document_messages['invoices_dear'];
			$edit_url=admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php&ID='.$ID);
			break;
		case 3:
			$progressivo=$riga["id"];
			$document_name=  __("Informelle Rechnung",'cpsmartcrm');
			$text_before=$document_messages['invoices_before'];
			$text_after=$document_messages['invoices_after'];
			$document_prefix=$document_numbering['invoices_prefix'];
			$document_suffix=$document_numbering['invoices_suffix'];
			$document_dear=$document_messages['invoices_dear'];
			$edit_url=admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php&ID='.$ID);

			break;
	}
	$tipo_sconto=$riga["tipo_sconto"];
	$riferimento=$riga["riferimento"];
	$oggetto = $tipo ==1 ? $riga["oggetto"] : "";
	if ($FK_clienti=$riga["fk_clienti"])
	{
		$sql="select ragione_sociale, nome, cognome, email, indirizzo, cap, localita, provincia, p_iva, cod_fis, tipo_cliente from $c_table where ID_clienti=".$FK_clienti;
		//  	echo $sql;
		$rigac=$wpdb->get_row($sql, ARRAY_A);
		$cliente=$rigac["ragione_sociale"]?$rigac["ragione_sociale"]:$rigac["nome"]." ".$rigac["cognome"];
		$cliente=stripslashes($cliente);
		$indirizzo=stripslashes($rigac["indirizzo"]);
		$tipo_cliente=$rigac["tipo_cliente"];
		$cap=$rigac["cap"];
		$localita=stripslashes($rigac["localita"]);
		$provincia=$rigac["provincia"];
		$email=$rigac["email"];
		$p_iva=$rigac["p_iva"];
		$cod_fis=$rigac["cod_fis"];
	}
  $pagamento = $riga["modalita_pagamento"];
  $data_scadenza = $riga["data_scadenza"]!="0000-00-00"?WPsCRM_culture_date_format($riga["data_scadenza"]):"";
    $n_offerta=$document_prefix;
    $n_offerta.=$progressivo."/".date("Y", strtotime($riga["data"]));
    $n_offerta.=$document_suffix;
}
//document sub-header
$subheader  ='<section class="WPsCRM_subheader">';
$subheader .='<div class="col-md-6 WPsCRM_customerData">'.$document_dear.' <b>'.$cliente.'</b><br>'.$indirizzo.'<br>'.$cap.'  '.$localita.'     ( '.$provincia.' )';
if ($p_iva)
	$subheader.='<br>'.__('Umsatzsteuer-ID','cpsmartcrm').': '.$p_iva;
if ($cod_fis)
	$subheader.='<br>'.__('Steuernummer','cpsmartcrm').': '.$cod_fis;
if ($riferimento)
	$subheader.='<br>'.__('Referenz','cpsmartcrm').': '.$riferimento;
$subheader.='</div>';
$subheader .='<div class="col-md-6 WPsCRM_documentData"><b>'.$document_name.' # '.$n_offerta.' '.__("ausgegeben am",'cpsmartcrm').' '.WPsCRM_culture_date_format($riga["data"]).'</b></div>';
$subheader .='</section>';
//document body
$doc_body='<section class="  ">';
if ($oggetto)
	$doc_body.='<h4 class="WPsCRM_document_subject">'.__('Betreff','cpsmartcrm').': '.$oggetto.'</h4>';
//$doc_body.='<p>'.$document->opening_text[1].'</p>';
if ($text_before)
	$doc_body.='<table class="WPsCRM_text-before"><tr><td>'. stripslashes($text_before).'</td></tr></table>';
if ($testo_libero=$riga["testo_libero"]){
	$doc_body.='<table class="WPsCRM_text-free"><tr><td>'. stripslashes($testo_libero).'</td></tr></table>';
}
$accontOptions=get_option( "CRM_acc_settings" );
/**
 * //inclusione a seconda del tipo di contabilit�
 */
switch ($accontOptions['accountability']){
	case 0:
		include ('accountabilities/accountability_print_0.php');
		break;
	case "1":
		include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_print_1.php');
		break;
	case "2":
		include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_print_2.php');
		break;
	case "3":
		include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_print_3.php');
		break;
	case "4":
		include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_print_4.php');
		break;
}
$doc_body.=$t_articoli;
//if ($tab_tot && $tipo==2)
//if ($tab_tot)
//{
$doc_body.='<div class="col-md-8 pull-right _total" style="padding:0;">
  <table class="table WPsCRM-total">
  <tbody>'.$tab_tot.'</tbody></table></div>
  ';
//}
  if ($pagamento)
    $tab_cond .= "<p style='margin-top:20px'>" . __("Zahlung", 'cpsmartcrm') . ": " . $pagamento . "</p>";
  if ($data_scadenza && $tipo==1)
    $tab_cond .= "<p style='margin-top:20px'>" . __("Verfallsdatum", 'cpsmartcrm') . ": " . $data_scadenza . "</p>";
  if ($riga["annotazioni"]){
//    $tab_cond .= "<p style='margin-top:20px'><b>" . __("Notes", 'cpsmartcrm') . "</b>: <i>" . stripslashes($riga["annotazioni"]) . "</i></p>";
    $tab_cond .= "<p style='margin-top:20px;font-size:.9em;font-style:italic'>".stripslashes($riga["annotazioni"]) . "</p>";
  }
  if ($tab_cond) {
    $doc_body .= '<div class="col-md-12 pull-left" style="padding:0"><h4>' . __("Bedingungen", 'cpsmartcrm') . '</h4>'.$tab_cond.'</div>';
  }
if ($riga["pagato"] && $tipo==2)
{
	$doc_body.='<div class="row"></div><div class="col-md-5 pull-left" style="padding:0"><table class="table _paid"><tr><td><img src="'.WPsCRM_URL.'css/img/paid_'.WPsCRM_CULTURE.'.png" class="WPsCRM_paid"/></h5></td></tr></table></div>';
}
if ($tipo ==3)
{
	$doc_body.='<div class="row"></div><div class="col-md-5 pull-left" style="padding:0"><table class="table _informal"><tr><td><img src="'.WPsCRM_URL.'css/img/informal_'.WPsCRM_CULTURE.'.png" class="WPsCRM_paid"/></h5></td></tr></table></div>';
}
//$doc_body.="<p><br>".$document->closing_text[1]."</p>";
if ($text_after)
    $doc_body.='<table class="WPsCRM_text-after"><tr><td>'. stripslashes($text_after).'</td></tr></table>';
$doc_body.="<div class=\"WPsCRM_signatures\">";
if ($tipo==1 && $document->use_signature()==true)
	$doc_body.=$signature;
if($tipo==1 && $document->use_formatted_signature()==true)
	$doc_body.='<table class="WPsCRM_formatted-signature"><tr><td>'.$formatted_signature.'</td></tr></table>';
$doc_body.="</div>";
$doc_body.='</section>';
//saving functions
if(!file_exists(WPsCRM_UPLOADS))
	wp_mkdir_p(WPsCRM_UPLOADS);
if (isset($old_file) && file_exists(WPsCRM_UPLOADS."/".$old_file.".pdf"))
	unlink(WPsCRM_UPLOADS."/".$old_file.".pdf");
$random_name=WPsCRM_gen_random_code(20);
$filename=$FK_clienti."_".$tipo."_".$ID."_".$random_name;

$serverName=site_url();
?>

<div class="box wide hidden-on-narrow">

	<div class="box-col row">
        <span class="crmHelp crmHelp-dark" data-help="document-print"></span>
		<div class="col-md-3" style="margin:0">
			<h4 >
				<?php _e('Hole PDF','cpsmartcrm')?>
			</h4>
			<button class="export-pdf btn _flat btn-success">
				<?php _e('Download','cpsmartcrm')?>
			</button>
			<?php if ($riga["registrato"]==0)
			{
			?>
                    <a href="<?php echo $edit_url ?>" target="_parent">
				<span class="btn _flat btn-info">
					<?php _e('Bearbeiten','cpsmartcrm')?>
				</span>
			</a>
			<?php
			}?>
		</div>
		<div class="col-md-4 option_box"style="margin:0">
			<h4>
				<?php _e('Druckoptionen','cpsmartcrm') ?>
			</h4>
			<?php  do_action ('WPsCRM_totalBox', $tipo ) ?>
		</div>
		<div class="col-md-3" style="margin:0">
            <div class="export-info" style="display:none">
            </div>
		</div>

	</div>
</div>

<div class="page-container hidden-on-narrow row" style="height:auto;">

	<div class="WPsCRM_pdf-page size-a4" style="display:block">
		<div class="WPsCRM_pdf-wrapper">
			<div class="WPsCRM_document-header">
				<?php echo $header?>
				<div class="WPsCRM_for">
					<?php echo $subheader; ?>
				</div>
			</div>

			<div class="WPsCRM_pdf-body">

				<?php echo $doc_body?>

			</div>
		</div>
		<!--<span id="marker" style="position:absolute;display:inline-block;float:left;width:100%;height:2px;background:#000"></span>
        <span id="boundMarker" style="position:absolute;display:inline-block;float:left;width:100%;height:2px;background:crimson"></span>-->
	</div>
</div>

<div class="responsive-message"></div>


<script>
        // Import DejaVu Sans font for embedding

        // NOTE: Only required if the Kendo UI stylesheets are loaded
        // from a different origin, e.g. cdn.kendostatic.com
        kendo.pdf.defineFont({
            "DejaVu Sans"             : "<?php echo WPsCRM_URL ?>css/fonts/DejaVu/DejaVuSans.ttf",
            "DejaVu Sans|Bold": "<?php echo WPsCRM_URL ?>css/fonts/DejaVu/DejaVuSans-Bold.ttf",
            "DejaVu Sans|Bold|Italic": "<?php echo WPsCRM_URL ?>css/fonts/DejaVu/DejaVuSans-Oblique.ttf",
            "DejaVu Sans|Italic": "<?php echo WPsCRM_URL ?>css/fonts/DejaVu/DejaVuSans-Oblique.ttf"
        });
</script>


<script>
	jQuery.fn.closestToOffset = function (offset) {
		var el = null, elOffset, x = offset.left, y = offset.top, distance, dx, dy, minDistance;
		this.each(function () {
			elOffset = jQuery(this).offset();

			if (
			(x >= elOffset.left) && (x <= elOffset.right) &&
			(y >= elOffset.top) && (y <= elOffset.bottom)
			) {
				el = jQuery(this);
				return false;
			}

			var offsets = [[elOffset.left, elOffset.top], [elOffset.right, elOffset.top], [elOffset.left, elOffset.bottom], [elOffset.right, elOffset.bottom]];
			for (off in offsets) {
				dx = offsets[off][0] - x;
				dy = offsets[off][1] - y;
				distance = Math.sqrt((dx * dx) + (dy * dy));
				if (minDistance === undefined || distance < minDistance) {
					minDistance = distance;
					el = jQuery(this);
				}
			}
		});
		return el;
	}
	jQuery(document).ready(function ($) {

		function WPsCRM_paging() {
			var totalHeight = parseInt($('.WPsCRM_document-header').height() + $('.WPsCRM_pdf-body').height());
			var rows = $('tr.WPsCRM_item').length;
			var boundRow = $('tr.WPsCRM_item').closestToOffset({ left: 100, top: totalHeight + $('.WPsCRM_pdf-page').offset().top })
			var $header = $('.WPsCRM_document-header');
			var $tableRows = $('.WPsCRM_document-table');
			var $tableHead=$('.WPsCRM_document-table thead');
			var $total = $('.WPsCRM-total');
			var $signature = $('.WPsCRM_signatures');

			var hasMoreRows = false;
			console.log( $('.WPsCRM_document-header').height(), $('.WPsCRM_pdf-body').height() , totalHeight, rows );
			console.log(boundRow[0], $(boundRow[0]).attr('id').split('-')[1], boundRow.next('tr.WPsCRM_item')[0], boundRow.prev('tr.WPsCRM_item')[0]);
			var boundRowIndex = $(boundRow[0]).attr('id').split('-')[1];
			console.log(boundRowIndex);
			if (boundRow.next('tr.WPsCRM_item')[0] != "undefined")
				hasMoreRows = true;
			if (hasMoreRows==true) {
				for ($k = boundRowIndex; $k < rows; $k++) {
					$('#riga-'+ boundRowIndex).remove();
					
				}
				$('.WPsCRM-total').remove();
				$('.WPsCRM_signatures').remove();
				$('<i class="glyphicon glyphicon-menu-right" style="float:right;font-size:3em;color:#f0f0f0;margin-left:-24px"></i><i class="glyphicon glyphicon-menu-right" style="float:right;font-size:3em;color:#f0f0f0;"></i><span class="page-break"></span>').insertAfter('.WPsCRM_document-table')
				$('<div class="WPsCRM_pdf-page size-a4" style="display:block;margin-top:0;">\n\
							<div class="WPsCRM_pdf-wrapper">\n\
								<div class="WPsCRM_document-header">\n\
									' + $header.html() + '\n\
									<table class="table table-bordered WPsCRM_document-table">\n\
										<thead>\n\
										' + $tableHead.html() + '\n\
										</thead>\n\
										<tbody>\n\
										<tr><td>aaaaaa</td></tr>\n\
										</tbody>\n\
									</table>\n\
									<div class="col-md-8 pull-right _total" style="padding:0;">\n\
										<table class="table WPsCRM-total">\n\
										' + $total.html() + '\n\
										</table>\n\
									</div>\n\
									<div class="WPsCRM_signatures">\n\
									' + $signature.html()+ '\n\
									</div>\n\
								</div>\n\
							</div>\n\
						</div>\n\
						'
					).insertAfter('.WPsCRM_pdf-page')
			}

			$('#boundMarker').offset( { top: parseInt(boundRow.offset().top), left: 0 } );
			$('#marker').offset( { top: parseInt(totalHeight + $('.WPsCRM_pdf-page').offset().top), left: 0 } );
			
		}

		//WPsCRM_paging();

		$('#printTotalTable').on('change', function () {
			$('.WPsCRM-total').toggle()
		})
    		$('input[type=radio][name=printTotal]').change(function () {
    			if (this.value == 'all') {

    				$('.print_tax').show()
    				$('.total_net').toggle();
    				$('.total_gros').toggle();
    				$('.WPsCRM_item').each(function () {
    					$(this).find('.tot_riga').html($(this).data('totalnet'))
    					$(this).find('.row_amount').html($(this).data('net'))
    				})
    			}
    			else if (this.value == 'total') {
    				$('.print_tax').hide()
    				$('.total_net').toggle();
    				$('.total_gros').toggle();
    				$('.WPsCRM_item').each(function () {
    					$(this).find('.tot_riga').html($(this).data('totalgros'))
    					$(this).find('.row_amount').html($(this).data('gros'))

    				})
    			}
    			else if (this.value == 'none') {
    				$('.WPsCRM-total').hide()
    				$('.print_tax').hide()
    			}
    		});
    		$('#printPaid').on('click', function () {
    			//alert();
    			$("._paid").toggle(this.checked);
    			$("._informal").toggle(this.checked);
    		})


    		var PDF;
    		$(".export-pdf").on('click', function (e) {
    			showMouseLoader();
	     		$('#mouse_loader').offset({ left: e.pageX, top: e.pageY });
    		$('.export-info').show();
	     		kendo.drawing.drawDOM($('.page-container'), {
    			//paperSize: "auto",
    			//multiPage: true,
    			forcePageBreak: '.page-break'
    		})
			.then(function (group) {
				console.log(group)
        	// Render the result as a PDF file
				return kendo.drawing.exportPDF(group, {
				//paperSize: "a4",
        		//multiPage: true, forcePageBreak: '.page-break',
        		margin: { left: "2cm", top: "1cm", right: "2cm", bottom: "1cm" }
        	});
        })
        .done(function (data) {
        	// Save the PDF file
        	PDF=data;
        	kendo.saveAs({
        		dataURI: data,
        		fileName: "<?php echo $filename?>",
				//proxyURL:"<?php echo admin_url('admin.php?page=smart-crm&p='.urlencode('documenti/save_pdf.php').'&security='.$print_nonce ) ?>",
        		//forceProxy: true
        	});
        	$.ajax({
        		url: ajaxurl,
				method:'POST',
				data: {
					'action': 'WPsCRM_save_pdf_document',
	        		fileName: "<?php echo $filename?>",
					doc_id: "<?php echo $ID?>",
					PDF: PDF,
					security:"<?php echo $print_nonce?>",
					},
					success: function (result) {
						console.log(result);

					},
					error: function (errorThrown) {
						console.log(errorThrown);
				}
			})
        	setTimeout(function () {
        		//$('.export-info img').fadeOut('400')
        		$('.export-info').html("<br><?php _e('Das aktuelle Dokument wurde auf Ihren PC heruntergeladen und auf dem Server gespeichert','cpsmartcrm')?>:<br><small style=\"background:gold;font-size:small;padding:3px\"><a href=\"<?php echo content_url() ?>/uploads/CRMdocuments/<?php echo $filename?>.pdf\" target=\"_blank\"><?php echo content_url() ?>/uploads/CRMdocuments/<?php echo $filename?>.pdf</a></small>")
        		//$('.export-info').append('<br><br><span class="btn btn-info _flat"><?php _e('Send to customer','cpsmartcrm')?></span>')
        		hideMouseLoader()
        	}, 700)

        });

      })

        $("#paper").select2({
          change: function() {
            $(".pdf-page")
              .removeClass("size-a4")
              .removeClass("size-letter")
              .removeClass("size-executive")
              .addClass(this.value());
          }
        });
    });
</script>
<style>
    .option_box label {
    line-height:1em
	}
	.option_box{padding:0}
	.option_box .col-md-3,.option_box .col-md-4,.option_box .col-md-6{
		padding:0
	}
	.option_box h4{margin-bottom:0}
        table.WPsCRM-total td{padding:4px!important}

		 .pdf-page {
            margin: 0 auto;
            box-sizing: border-box;
            box-shadow: 0 5px 10px 0 rgba(0,0,0,.3);
            background-color: #fff;
            color: #333;
            position: relative;
        }
        .pdf-header {
            position: absolute;
            top: .5in;
            height: .6in;
            left: .5in;
            right: .5in;
            border-bottom: 1px solid #e5e5e5;
        }
        .invoice-number {
            padding-top: .17in;
            float: right;
        }
        .pdf-footer {
            position: absolute;
            bottom: .5in;
            height: .6in;
            left: .5in;
            right: .5in;
            padding-top: 10px;
            border-top: 1px solid #e5e5e5;
            text-align: left;
            color: #787878;
            font-size: 12px;
        }
        /*.pdf-body {
            position: absolute;
            top: 3.7in;
            bottom: 1.2in;
            left: .5in;
            right: .5in;
        }*/

        .size-a4 { width: 8.3in; height: 11.7in; }
        .size-letter { width: 8.5in; height: 11in; }
        .size-executive { width: 7.25in; height: 10.5in; }

        .company-logo {
            font-size: 30px;
            font-weight: bold;
            color: #3aabf0;
        }
         /*.for {
            position: relative;
            top: 1.5in;
            left: .5in;
            width: 2.5in;
        }
       .from {
            position: absolute;
            top: 1.5in;
            right: .5in;
            width: 2.5in;
        }*/
        .from p, .for p {
            color: #787878;
        }
        .signature {
            padding-top: .5in;
        }
        /*
            Use the DejaVu Sans font for display and embedding in the PDF file.
            The standard PDF fonts have no support for Unicode characters.
        */
        .pdf-page {
            font-family: "DejaVu Sans", "Arial", sans-serif;
        }

	<?php if(isset($_GET['layout']) && $_GET['layout']=="iframe") { ?>
		#wpadminbar, #adminmenumain, #mainMenu {
            display: none;
        }
        #wpcontent, #wpfooter {
            margin-left: 0;
        }
		<?php } ?>
</style>
<style>
		<?php echo isset($document_options['document_custom_css']) ? $document_options['document_custom_css'] : null?>
</style>
