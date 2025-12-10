<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/*ob_start();
$content=WPsCRM_generate_document_HTML($_GET['id_invoice']);
$options=get_option('CRM_business_settings');
ob_end_clean();
ob_clean();
$html2pdf = new Html2Pdf('P', 'A4', 'it');
$html2pdf->pdf->SetDisplayMode('fullpage');
try
{
    $html2pdf->writeHTML($content, false);
    $html2pdf->Output('invoice.pdf');	
}
catch(Html2Pdf_exception $e) {
    echo $e;
    exit;
}*/

$id_invoice=$_GET['id_invoice'];
$table=WPsCRM_TABLE."documenti";
$sql="select filename, tipo,fk_clienti from $table where id=$id_invoice";
$qf=$wpdb->get_row( $sql );
$old_file=$qf->filename;
$type=$qf->tipo;
$client_id=$qf->fk_clienti;
ob_start();


$content= WPsCRM_generate_document_HTML($id_invoice);

ob_end_clean();
ob_clean();
$html2pdf = new Html2Pdf('P', 'A4', 'it');
$html2pdf->pdf->SetDisplayMode('fullpage');

$html2pdf->writeHTML($content, false);

$save_to_path = WPsCRM_UPLOADS;
if(!file_exists($save_to_path)) wp_mkdir_p($save_to_path);
if (file_exists($save_to_path."/".$old_file))
    unlink($save_to_path."/".$old_file);
$random_name=WPsCRM_gen_random_code(20);
$document_name=$client_id."_".$type."_".$id_invoice."_".$random_name;
$filename=$save_to_path."/".$document_name.".pdf";
//  echo $filename;
$html2pdf->Output($filename,'F');
$wpdb->update( 
$table, 
array('filename' => "$document_name"),
array('id'=>$id_invoice), 
array('%s') 
);
$upload_dir = wp_upload_dir();
$document = $upload_dir['baseurl'] . "/CRMdocuments/".$document_name.".pdf";
//echo $filename;
//echo $document;
?>
<script type="text/javascript">
    location.href="<?php echo $document?>";
</script>