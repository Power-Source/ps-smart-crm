<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'update_document' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

} else {
	$table=WPsCRM_TABLE."documenti";
	$d_table=WPsCRM_TABLE."documenti_dettaglio";
	$s_table=WPsCRM_TABLE."subscriptionrules";
	$a_table=WPsCRM_TABLE."agenda";
//	var_dump($_POST);
//echo $_POST["totale"];
	$totale=$_POST["totale"];
	if (!isset($_POST["totale_netto"]) || !$_POST["totale_netto"])
		$totale_netto=$totale;
	if (isset($_POST["modalita_pagamento"]))
	{
		$_modalita_pagamento=explode("~",$_POST["modalita_pagamento"]);
		isset($_modalita_pagamento[1])  ? $modalita_pagamento=$_modalita_pagamento[0] : $modalita_pagamento=$_POST["modalita_pagamento"];
	}
	else
		$modalita_pagamento="";
	$current_user = wp_get_current_user();
	$user_id=$current_user->ID;
	$type=$_GET["type"];
	$document_options=get_option('CRM_documents_settings');
	if ($type==1)
		$document_start=isset($document_options['offers_start']) ? $document_options['offers_start'] : "";
	elseif ($type==2)
		$document_start=isset($document_options['invoices_start']) ? $document_options['invoices_start'] : "";
	if ($type==3)
		$origine_proforma=1;
	else
		$origine_proforma=0;
	$data=WPsCRM_sanitize_date_format($_POST["data"]);

	$data_scadenza=$_POST["data_scadenza"] ? WPsCRM_sanitize_date_format($_POST["data_scadenza"]) : "";
	$data_scadenza_timestamp=strtotime(WPsCRM_sanitize_date_format($_POST["data_scadenza"]));
	$data_inserimento=date("Y-m-d");

	$data_timestamp=strtotime($data) ;
	$testo_libero=isset($_POST["testo_libero"]) ? str_replace(array("\r\n", "\r"), "<br />", $_POST["testo_libero"]) : "";
	$notify_payment=isset($_POST["notify_payment"])?(int)$_POST["notify_payment"]:0;
	$notificationDays=isset($_POST["notificationDays"])?(int)$_POST["notificationDays"]:0;
	$annotazioni=isset($_POST["annotazioni"])?$_POST["annotazioni"]:"";
	$commento=isset($_POST["commento"])?$_POST["commento"]:"";
	$oggetto=isset($_POST["oggetto"])?$_POST["oggetto"]:"";
	$riferimento=isset($_POST["riferimento"])?$_POST["riferimento"]:"";
	$totale_imponibile=isset($_POST["totale_imponibile"])?$_POST["totale_imponibile"]:0;
	$totale_imposta=isset($_POST["totale_imposta"])?$_POST["totale_imposta"]:0;
	$totale_cassa=isset($_POST["totale_cassa"])?$_POST["totale_cassa"]:0;
	$ritenuta_acconto=isset($_POST["ritenuta_acconto"])?$_POST["ritenuta_acconto"]:0;
	$pagato=isset($_POST["pagato"])?$_POST["pagato"]:0;
	$perc_realizzo=isset($_POST["perc_realizzo"])?$_POST["perc_realizzo"]:0;
	$quotation_value=isset($_POST["quotation_value"]) ? (int)$_POST["quotation_value"] : 0;
	$num_righe=$_POST["num_righe"];
	$tipo_sconto=isset($_POST["tipo_sconto"]) ? (int)$_POST["tipo_sconto"] : 0;
	$fk_clienti=isset($_POST["fk_clienti"])?$_POST["fk_clienti"]:0;
	$hidden_fk_clienti=isset($_POST["hidden_fk_clienti"])?$_POST["hidden_fk_clienti"]:0;
	if ($ID=$_GET["ID"])
	{
		//delete scheduler, rules, emails associated to document
		$wpdb->delete( WPsCRM_TABLE."emails", array( 'fk_documenti' => $ID ) );
		$result=$wpdb->query(
			$wpdb->prepare(
				"delete $s_table.* from $s_table inner join $a_table on $s_table.ID= $a_table.fk_subscriptionrules where s_specific<>0 and $a_table.fk_documenti=%d",
				$ID
				)
			);
		$wpdb->delete( WPsCRM_TABLE."agenda", array( 'fk_documenti' => $ID ) );

		$wpdb->update(
		  $table,
		  array('data' => "$data",'oggetto' => "$oggetto",'riferimento' => "$riferimento",'data' => "$data",'modalita_pagamento' => "$modalita_pagamento",'annotazioni' => "$annotazioni", 'totale_imponibile' => "$totale_imponibile", 'totale_imposta' => "$totale_imposta", 'totale' => "$totale", 'tot_cassa_inps' => "$totale_cassa", 'ritenuta_acconto' => "$ritenuta_acconto", 'totale_netto' => "$totale_netto",'commento' => "$commento",'testo_libero' => $testo_libero, 'giorni_pagamento' => $notificationDays, 'pagato' => $pagato, 'notifica_pagamento' => $notify_payment, 'perc_realizzo' => $perc_realizzo, 'valore_preventivo' => $quotation_value, 'data_scadenza' => $data_scadenza,'data_scadenza_timestamp'=>$data_scadenza_timestamp, 'tipo_sconto'=>$tipo_sconto),
		array('id'=>$ID),
		  array('%s','%s','%s','%s','%s','%f','%f','%f','%f','%f','%f','%s','%s','%d','%d','%d','%s','%f','%s','%d', '%d')
	  );

	}
	else
	{
		if ($type==4)
			$where="tipo=2";
		else
			$where="tipo=$type";
		$cur_year=date("Y");
		$sql="select data_timestamp from $table where $where order by data_timestamp desc limit 0,1";
		$rigad=$wpdb->get_row($sql, ARRAY_A);
		$anno_ultima= date('Y', $rigad["data_timestamp"]);
		if ($cur_year==$anno_ultima)
		{
			$sql="select max(progressivo) as last_reg from $table where $where and year(data_inserimento)='$cur_year'";
			$riga=$wpdb->get_row($sql, ARRAY_A);
			if ($document_start>$riga["last_reg"])
			{
				$new_reg=$document_start+1;
				$document_start=$new_reg;
			}
			else
			{
				$new_reg=$riga["last_reg"]+1;
				$document_start=$new_reg;
			}
		}
		else
		{
			$new_reg=1;
			$document_start=$new_reg;
		}
		if ($type==1)
			$document_options['offers_start']=$document_start;
		elseif ($type==2)
			$document_options['invoices_start']=$document_start;
		update_option('CRM_documents_settings', $document_options);
		$wpdb->insert(
		$table,
		array('progressivo' => "$new_reg",'tipo' => "$type",'fk_clienti' => "$fk_clienti",'data' => "$data",'fk_utenti_ins' => "$user_id", 'oggetto' => "$oggetto",'riferimento' => "$riferimento",'modalita_pagamento' => "$modalita_pagamento",'annotazioni' => "$annotazioni", 'totale_imponibile' => "$totale_imponibile", 'totale_imposta' => "$totale_imposta", 'totale' => "$totale", 'tot_cassa_inps' => "$totale_cassa", 'ritenuta_acconto' => "$ritenuta_acconto", 'totale_netto' => "$totale_netto", 'data_inserimento' => "$data_inserimento",'commento' => "$commento",'testo_libero' => $testo_libero, 'giorni_pagamento' => $notificationDays, 'perc_realizzo' => $perc_realizzo, 'notifica_pagamento' => $notify_payment, 'valore_preventivo' => $quotation_value, 'data_scadenza' => $data_scadenza, 'data_timestamp'=>$data_timestamp,'data_scadenza_timestamp'=>$data_scadenza_timestamp,'origine_proforma'=>$origine_proforma, 'tipo_sconto'=>$tipo_sconto),
		array('%d','%d','%d','%s','%d','%d','%s','%s','%s','%s','%f','%f','%f','%f','%f','%f','%s','%s','%s','%d','%s','%d','%f','%d','%d', '%d', '%d')
	);
	}
	//var_dump( $wpdb->last_query );
	$ID_ret=$ID?$ID:$wpdb->insert_id;
	//articoli
	$wpdb->delete( $d_table, array( 'fk_documenti' => $ID_ret, 'eliminato'=>1 ) );
	$i=1;
	$c_riga=1;
	while (true)
	{
		$id_art=isset($_POST["id_".$i])?$_POST["id_".$i]:0;
		$idd=isset($_POST["idd_".$i])?$_POST["idd_".$i]:0;
		//	echo "riga n $i - idriga= $idd<br>";
		$tipo=$_POST["tipo_".$i];
		$codice=(string)$_POST["codice_".$i];
		$descrizione=(string)("".$_POST["descrizione_".$i]);
		$subscriptionrules=isset($_POST["subscriptionrules_".$i])?(int)$_POST["subscriptionrules_".$i]:0;
		$prezzo=(float)$_POST["prezzo_".$i];
		$qta=(float)$_POST["qta_".$i];
		$sconto=(float)$_POST["sconto_".$i];
		$totale=(float)$_POST["totale_".$i];
		$iva=(int)$_POST["iva_".$i];
		$delete=isset($_POST["delete_".$i])?(int)$_POST["delete_".$i]:0;	
		//echo "id_art=".$id_art."<br>";
		if ($id_art)
		{
			//se nuovo lo aggiungo, altrimenti lo modifico
			if ($idd)
			{
				$wpdb->update(
			  $d_table,
			  array('fk_documenti' => $ID_ret,'fk_articoli' => $id_art, 'prezzo' => $prezzo, 'sconto' => $sconto, 'qta' => $qta, 'totale' => $totale, 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva),
			array('id'=>$idd),
			  array('%d','%d','%f','%f','%d','%f','%d','%s','%s','%d')
		);
				$id_dd=$idd;
			}
			else
			{
				$wpdb->insert(
			  $d_table,
			  array('fk_documenti' => $ID_ret,'fk_articoli' => $id_art, 'prezzo' => $prezzo, 'sconto' => $sconto, 'qta' => $qta, 'totale' => $totale, 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva, 'tipo' => $tipo, 'fk_subscriptionrules' => $subscriptionrules),
			  array('%d','%d','%f','%f','%d','%f','%d','%s','%s','%d','%d','%d')
		);
//		var_dump( $wpdb->last_query );

				$id_dd=$wpdb->insert_id;
			}
			if ($subscriptionrules)
			{
				WPsCRM_insert_notification($subscriptionrules, $ID_ret, $id_dd, 0);
			}
			$c_riga++;
		}
		else
		{
			//se nuovo lo aggiungo, altrimenti lo modifico
			$sql="select * from $d_table where id='$idd'";
			$res=$wpdb->get_results( $sql, ARRAY_A);
			//  		$q=mysql_query($sql);
			if ($res)
			{
				$wpdb->update(
			  $d_table,
			  array('fk_documenti' => $ID_ret, 'prezzo' => $prezzo, 'sconto' => $sconto, 'qta' => $qta, 'totale' => $totale, 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva),
			array('id'=>$idd),
			  array('%d','%f','%d','%d','%f','%d','%s','%s','%d')
		);
				$id_dd=$idd;
			}
			else
			{
				$wpdb->insert(
			  $d_table,
			  array('fk_documenti' => $ID_ret, 'prezzo' => $prezzo, 'sconto' => $sconto, 'qta' => $qta, 'totale' => $totale, 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva, 'tipo' => $tipo, 'fk_subscriptionrules' => $subscriptionrules),
			  array('%d','%f','%d','%d','%f','%d','%s','%s','%d','%d','%d')
				);
//		var_dump( $wpdb->last_query );
				$id_dd=$wpdb->insert_id;
				//exit;
			}
			if ($subscriptionrules)
			{
				WPsCRM_insert_notification($subscriptionrules, $ID_ret, $id_dd, 0);
			}
			$c_riga++;
		}
		if ($c_riga>$num_righe)
			break;
		$i++;
	}
	//die;
	//echo "salvato<br>";
	//subscription rules
	$client_id=$fk_clienti?$fk_clienti:$hidden_fk_clienti;
	if ( $notify_payment && $type ==2 )
	{
		$s_table=WPsCRM_TABLE."subscriptionrules";
		$a_table=WPsCRM_TABLE."agenda";
		$users=$_POST["selectedUsers"];
		$groups=$_POST["selectedGroups"];
		$days=-$notificationDays;
		$giorno=date("d", strtotime($data_scadenza));
		$mese=date("m", strtotime($data_scadenza));
		$anno=date("Y", strtotime($data_scadenza));
		$oggetto="Fattura scaduta";
		$annotazioni="Contattare il cliente";
		//	$data_scadenza=date("Y-m-d",mktime(0,0,0,$mese,$giorno-$days,$anno));
		//  $data_agenda=date("Y-m-d",mktime(0,0,0,$mese,$giorno-$days,$anno));
		$data_scadenza=date("Y-m-d",mktime(0,0,0,$mese,$giorno,$anno));
		$data_agenda=date("Y-m-d",mktime(0,0,0,$mese,$giorno,$anno));
		$s = "[";
		$s.= '{"ruleStep":"' .$days. '" ,"remindToCustomer":""';
		$s.= ',"selectedUsers":"' .$users. '"';
		$s.= ',"selectedGroups":"' .$groups. '"';
		$s.= ',"userDashboard":"on"';
		$s.= ',"groupDashboard":"on"';
		$s.= ',"mailToRecipients":"on"';
		$s.= '}';
		$s.= ']';

		if ($res=$wpdb->get_row("select * from $a_table where fk_documenti=$ID_ret AND fk_documenti_dettaglio = 0 "))
		{
			//modify records in $a_table and $s_table
			$wpdb->update(
			$a_table,
			array(
				'start_date'=>$data_scadenza,
				'end_date'=>$data_scadenza,
				'data_agenda'=>$data_agenda,
				'data_inserimento'=>date("Y-m-d H:i")
			),
			array('id_agenda'=>$res->id_agenda),
			array('%s', '%s', '%s', '%s')
			);
			$wpdb->update(
			$s_table,
			array(
				'steps'=>$s,
			),
			array('ID'=>$res->fk_subscriptionrules),
			array('%s')
			);

		}
		else
		{
			$wpdb->insert(
			  $s_table,
			  array(
				  'steps'=>$s,
				  'name'=>'Todo',
				  's_specific'=>1,
				  's_type'=>1,
				  's_email'=>0,
			  ),
			  array(
			  '%s',
			  '%s',
			  '%d',
			  '%d',
			  '%d',
			  )
			);
			$id_sr=$wpdb->insert_id;
			//  var_dump( $wpdb->last_query );

			$wpdb->insert(
			  $a_table,
			  array(
				  'oggetto'=>sanitize_text_field($oggetto),
				  'fk_clienti'=>$client_id,
				  'annotazioni'=>sanitize_text_field($annotazioni),
				  'start_date'=>$data_scadenza,
				  'end_date'=>$data_scadenza,
				  'data_agenda'=>$data_agenda,
				  'priorita'=>3,
				  'urgente'=>'Si',
				  'importante'=>'Si',
				  'data_inserimento'=>date("Y-m-d H:i"),
				  'fk_subscriptionrules'=>$id_sr,
				  'tipo_agenda'=>3,
				  'fk_documenti'=>$ID_ret,
				  'fk_documenti_dettaglio'=>0
			  ),
			  array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s',  '%s','%d',  '%d','%d')
			);
		}
		//exit( var_dump( $wpdb->last_query ) );

	}
	$mail= new CRM_mail(array("ID_doc"=> $ID_ret) );
	
}
if (WPsCRM_advanced_print()) {
	$sql="select filename from $table where id=$ID_ret";
	$qf=$wpdb->get_row( $sql );
	$old_file=$qf->filename;
	$WOOmail= new CRM_mail(array("ID_doc"=> $ID_ret) );
	$attachment=0;
	ob_start();


	$content= WPsCRM_generate_document_HTML($ID_ret);
	//echo $content;exit;
	$options=get_option('CRM_business_settings');

	ob_end_clean();
	ob_clean();
	$html2pdf = new Html2Pdf('P', 'A4', 'it');
	//$html2pdf->setModeDebug();
	$html2pdf->pdf->SetDisplayMode('fullpage');
	
	$html2pdf->writeHTML($content, false);

	$save_to_path = WPsCRM_UPLOADS;
	if(!file_exists($save_to_path)) wp_mkdir_p($save_to_path);
	if (file_exists($save_to_path."/".$old_file.".pdf"))
	unlink($save_to_path."/".$old_file.".pdf");
	$random_name=WPsCRM_gen_random_code(20);
	$document_name=$client_id."_".$type."_".$ID_ret."_".$random_name;
	$filename=$save_to_path."/".$document_name.".pdf";
	//  echo $filename;
	$html2pdf->Output($filename,'F');
	$wpdb->update( 
	$table, 
	array('filename' => "$document_name"),
	array('id'=>$ID_ret), 
	array('%s') 
	);
//	$ret= WPsCRM_create_pdf_document($ID_ret, $content, $attachment, $old_file, $type, $WOOmail);
	//include($plugin_dir."/inc/crm/documenti/document_print2.php");
}
ob_flush();
if( isset($_REQUEST['layout'] ) && $_REQUEST['layout']=="iframe")
    $layout="&layout=iframe";
if ($type==1)
{
    header("location: ".admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php')."&ID=$ID_ret&type=$type&saved=1");
}
elseif ($type==2)
{
    header("location: ".admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php')."&ID=$ID_ret&type=$type&saved=1");
}
elseif ($type==3)
{
    header("location: ".admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php')."&ID=$ID_ret&type=$type&saved=1");
}
else
{
    header("location: ".admin_url('admin.php?page=smart-crm&p=documenti/form_credit_note.php')."&ID=$ID_ret&type=$type&saved=1");
}
exit;
?>
