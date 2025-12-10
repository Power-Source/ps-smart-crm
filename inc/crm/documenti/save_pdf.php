<?php

if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'print_document' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {

		$fileName = $_POST['fileName'].".pdf";
		$contentType = $_POST['contentType'];
		$base64 = $_POST['base64'];
		$pdf = base64_decode($base64);
		header('Content-Type:' . $contentType);
		header('Content-Length:' . strlen($pdf));
		header('Content-Disposition: attachment; filename=' . $fileName);
		$file=WPsCRM_UPLOADS."/".$fileName;
		$pdfFile = fopen($file, "w") or die("Unable to open file!");
		fwrite($pdfFile, $pdf);
		fclose($pdfFile);
		echo $pdf;

}
?>