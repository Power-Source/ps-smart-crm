<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$nonce = $_REQUEST['security'];
if ( ! wp_verify_nonce( $nonce, 'update_customer' ) || ! current_user_can('manage_crm')) {

	die( 'Security issue' );

}
else {
	$table=WPsCRM_TABLE."clienti";
	$ID_azienda="1";
	//$post_sanitized=WPsCRM_sanitize($_POST);
	foreach ( $_POST as $chiave => $valore )
		${$chiave}=$valore;
	foreach ( $_GET as $chiave => $valore )
		${$chiave}=$valore;

	$data_inserimento=date("Y-m-d");
	$data_modifica=date("Y-m-d");
	$data_nascita=WPsCRM_inverti_data($_POST["data_nascita"]);
	$cur_year=date("Y");
	$current_user = wp_get_current_user();
    if ( WPsCRM_is_agent() && !WPsCRM_agent_can())
        $selectAgent=$current_user->ID;

	if ($ID=$_GET["ID"])
	{
		$wpdb->update(
			  $table,
			  array('FK_aziende' => "$ID_azienda",'nome' => "$nome",'cognome' => "$cognome",'categoria' => "$customerCategory",'ragione_sociale' => "$ragione_sociale",'indirizzo' => "$indirizzo",'cap' => "$cap", 'localita' => "$localita", 'provincia' => "$provincia", 'nazione' => "$nazione", 'telefono1' => "$telefono1",'telefono2' => "$telefono2",'fax' => $fax, 'email' => $email, 'sitoweb' => $sitoweb, 'skype' => $skype, 'p_iva' => $p_iva, 'cod_fis' => $cod_fis, 'annotazioni' => $annotazioni,'data_modifica'=>$data_modifica,'provenienza'=>$customerComesfrom,'agente'=>$selectAgent,'data_nascita'=>"$data_nascita",'luogo_nascita'=>"$luogo_nascita",'tipo_cliente'=>"$tipo_cliente", 'interessi'=>"$customerInterests"),
			array('ID_clienti'=>$ID),
			  array('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d', '%s', '%s','%d','%s')
		);
	}
	else
	{
		$wpdb->insert(
			  $table,
			  array('FK_aziende' => "$ID_azienda",'nome' => "$nome",'cognome' => "$cognome",'categoria' => "$customerCategory",'ragione_sociale' => "$ragione_sociale",'indirizzo' => "$indirizzo",'cap' => "$cap", 'localita' => "$localita", 'provincia' => "$provincia", 'nazione' => "$nazione", 'telefono1' => "$telefono1",'telefono2' => "$telefono2",'fax' => $fax, 'email' => $email, 'sitoweb' => $sitoweb, 'skype' => $skype, 'p_iva' => $p_iva, 'cod_fis' => $cod_fis, 'annotazioni' => $annotazioni,'data_modifica'=>"$data_modifica",'data_inserimento'=>"$data_inserimento",'provenienza'=>"$customerComesfrom",'agente'=>$selectAgent,'data_nascita'=>"$data_nascita",'luogo_nascita'=>"$luogo_nascita",'tipo_cliente'=>"$tipo_cliente", 'interessi'=>"$customerInterests"),
			  array('%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%d', '%s','%s','%d','%s')
		);
	}
	//echo $wpdb->last_query;
	$ID_ret=$ID ? $ID :  $wpdb->insert_id;
	//echo "<pre>";
	//var_dump($_POST);
	//echo "</pre>";
	//exit;
	//custom fields
	$f_table=WPsCRM_TABLE."fields";
	$v_table=WPsCRM_TABLE."values";
	$sql="select id, field_name, field_type from $f_table where table_name='clienti'";
	foreach( $wpdb->get_results( $sql ) as $record){
		$fk_fields=$record->id;
		$field_name=$record->field_name;
		$field_type=$record->field_type;
		$valore=$_POST[$field_name];
		$sql="select * from $v_table where fk_fields='$fk_fields' and fk_table_name='$ID_ret'";
		if ($wpdb->get_results( $sql ))
		{
			$result=$wpdb->query(
			$wpdb->prepare(
				"UPDATE $v_table SET value=%s WHERE fk_fields=%d and fk_table_name=%d",
				$valore, $fk_fields ,$ID_ret
				)
			);
		}
		else
		{
			$result=$wpdb->query(
			$wpdb->prepare(
				"Insert into $v_table SET value=%s, fk_fields=%d, fk_table_name=%d",
				$valore, $fk_fields ,$ID_ret
				)
			);
		}
		//echo $sql;
	}

	//creo utente
	if ($_POST["crea_utente"] && $_POST["email"])
	{
		$userdata=array();
		$userdata['CRM_username']=$_POST["username"];
		$userdata['CRM_password']= $_POST["password"];
		$userdata['CRM_firstname']=$nome;
		$userdata['CRM_lastname']=$cognome;
		$userdata['CRM_ID']=$ID_ret;
		$userdata['CRM_email']=$_POST['email'];
		$user_id=apply_filters( 'WPsCRM_add_User',$userdata);
			$result=$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table SET user_id=%d WHERE ID_clienti=%d",
				$user_id, $ID_ret
				)
			);
	}
}
header("location: ".admin_url('admin.php?page=smart-crm&p=clienti/form.php')."&ID=$ID_ret&saved=1");
exit;
?>

