<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$delete_nonce = wp_create_nonce( "delete_document" );
$update_nonce= wp_create_nonce( "update_document" );
$scheduler_nonce= wp_create_nonce( "update_scheduler" );
global $document;
$generalOptions=get_option('CRM_general_settings');
$documentOptions=get_option('CRM_documents_settings');
$payOptions=get_option('CRM_documents_settings');
$arr_payments=maybe_unserialize($payOptions['delayedPayments']);
$def_iva=$documentOptions['default_vat'];
$accOptions = get_option( "CRM_acc_settings" );
if ($ID=$_GET["id_invoice"])
{
	$plugin_dir  = dirname(dirname(dirname(dirname(__FILE__))));
}
else
{
    $ID=$_REQUEST["ID"];
    $type=$_REQUEST["type"];
    $a_table=WPsCRM_TABLE."agenda";
    $d_table=WPsCRM_TABLE."documenti";
    $dd_table=WPsCRM_TABLE."documenti_dettaglio";
    $c_table=WPsCRM_TABLE."clienti";
    $s_table=WPsCRM_TABLE."subscriptionrules";
    if ($ID)
    {
        $sql="select * from $d_table where id=$ID";
        $riga=$wpdb->get_row($sql, ARRAY_A);
        $type=$riga["tipo"];
        $data=WPsCRM_culture_date_format($riga["data"]);
		$payment=$riga["modalita_pagamento"];
        $data_scadenza=WPsCRM_culture_date_format($riga["data_scadenza"]);
		//echo "db: ". $payment;
        $giorni_pagamento=$riga["giorni_pagamento"];
        //	$data=date("d-m-Y");
        //$data_consegna=WPsCRM_inverti_data($riga["data_consegna"]);
        $tempi_chiusura_dal=WPsCRM_inverti_data($riga["tempi_chiusura_dal"]);
        $oggetto=$riga["oggetto"];
        $iva=$riga["iva"];
        $tot_imp=sprintf("%01.2f", $riga["totale_imponibile"]);
    	$totale_imposta=sprintf("%01.2f", $riga["totale_imposta"]);
    	$tot_cassa=sprintf("%01.2f", $riga["tot_cassa_inps"]);
    	$ritenuta_acconto=sprintf("%01.2f", $riga["ritenuta_acconto"]);
	    $totale=$riga["totale"];
	    $totale_netto=$riga["totale_netto"];

        $FK_contatti=$riga["FK_contatti"];
        if ($fk_clienti=$riga["fk_clienti"])
        {
            $sql="select ragione_sociale, nome, cognome, indirizzo, cap, localita, provincia, cod_fis, p_iva, tipo_cliente from $c_table where ID_clienti=".$fk_clienti;
            $rigac=$wpdb->get_row($sql, ARRAY_A);
            $cliente=$rigac["ragione_sociale"] ? $rigac["ragione_sociale"] : $rigac["nome"]." ".$rigac["cognome"];
			$cliente=stripslashes($cliente);
            $indirizzo=stripslashes($rigac["indirizzo"]);
            $cap=$rigac["cap"];
            $localita=stripslashes($rigac["localita"]);
            $provincia=$rigac["provincia"];
			$cod_fis=$rigac["cod_fis"];
			$p_iva=$rigac["p_iva"];
			$tipo_cliente=$rigac["tipo_cliente"];
        }
        if ($riga["FK_contatti"])
        {
            $sql="select concat(nome,' ', cognome) as contatto from ana_contatti where ID_contatti=".$riga["FK_contatti"];
            $rigac=$wpdb->get_row($sql, ARRAY_A);
            $contatto=$rigac["contatto"];
        }
    	$wpdb->update(
    	  $dd_table,
    	  array('eliminato' => 0),
    	array('fk_documenti'=>$ID),
    	  array('%d')
    	);
        $sql="select * from $dd_table where fk_documenti=$ID order by n_riga";
        $qd=$wpdb->get_results( $sql, ARRAY_A);
        $sql="select $s_table.* from $s_table, $a_table where fk_documenti=$ID and $s_table.ID =$a_table.fk_subscriptionrules and $a_table.fk_documenti_dettaglio=0";
        if ($record=$wpdb->get_row( $sql ))
        {
            $steps=json_decode($record->steps);
            foreach ($steps as $step)
            {
                $users=$step->selectedUsers;
                $groups= $step->selectedGroups;
            }
        }
    }
    else
    {
        $data=WPsCRM_culture_date_format(date("d-m-Y") );
        $oggetto=$type==1?"Preventivo":"Fattura";
		$iva=$documentOptions['default_vat'];
        $tempi_chiusura_dal=WPsCRM_culture_date_format(date("d-m-Y") );
        $FK_clienti=0;
        $FK_contatti=0;
        $giorni_pagamento=$documentOptions['invoice_noty_days'];

    }?>

<?php
    $where="FK_aziende=$ID_azienda";

?>
<style>
    
    h4.page-header{background:gainsboro;padding:10px 4px}
	._forminvoice li{padding:2px!important}
	<?php if(isset($_GET['layout']) && $_GET['layout']=="iframe") { ?>
	#wpadminbar, #adminmenumain, #mainMenu,.wrap h1,.btn-warning,.select-action:first-of-type {
        display: none;
    }
	#wpcontent, #wpfooter {
    margin-left: 0;
}
		<?php } ?>
</style>
<script>
	var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
	var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
	var cliente = "<?php echo $cliente ?>";
</script>
<form name="form_insert" action="" method="post" id="form_insert">
    <!--<div class="modal_loader" style="background:#fff url(<?php echo WPsCRM_URL?>/css/img/loading-image.gif);background-repeat:no-repeat;background-position:center center"></div>-->
	<input type="hidden" name="num_righe" id="num_righe" value="">
    <h1 style="text-align:center"><?php _e('GUTSCHRIFT ERSTELLEN/BEARBEITEN','cpsmartcrm')?> <i class="glyphicon glyphicon-fire"></i></h1>
    <div id="tabstrip">
        <ul class="tabstrip">
            <li id="tab1"><?php _e('GUTSCHRIFT','cpsmartcrm')?></li>
            <li onclick="aggiornatot();"><?php _e('KOMMENTARE UND INTERNE DATEN','cpsmartcrm')?></li>
        </ul>
        <div class="tab-content" style="display:block;">
            <h4 class="page-header" style="margin: 10px 0 20px;">
                <?php _e('GUTSCHRIFTSDATEN','cpsmartcrm')?><span class="crmHelp" data-help="document-data"></span>
                <?php if ($ID) {?>
                <span style="float:right;font-size:.8em;text-decoration:underline;cursor:pointer" class="_edit_header"><i class="glyphicon glyphicon-pencil"></i> <?php _e('Bearbeiten','cpsmartcrm')?></span>
                <?php } ?>
                <span style="float:right;margin-top: -7px;">
                    <label class="col-sm-2 control-label"><?php _e('Nummer','cpsmartcrm')?></label>
                    <span class="col-sm-2">
                        <input name="progressivo" id="progressivo" class="form-control" data-placement="bottom" title="<?php _e('Nummer','cpsmartcrm')?>" value="<?php echo $riga["progressivo"]?>" readonly disabled />
                    </span>
                </span>
            <span style="float:right;margin-top: -7px;" class="col-md-4">
                <label class="control-label"><?php _e('Ausgabedatum','cpsmartcrm')?></label>
				<?php if ($ID) {?>
	            <span class="col-sm-4" style="margin-top: -4px;">
					<input name="data" id="data" class="form-control  _m" data-placement="bottom" title="<?php _e('Datum','cpsmartcrm')?>" value="<?php echo  $data ?>"/>
	            </span>
				<?php } else {?>
                <span class="col-sm-4" style="margin-top: -4px;">
                    <input name="data" id="data" class="form-control _m" data-placement="bottom" title="<?php _e('Datum','cpsmartcrm')?>" value=""/>
                    
                </span>
				<?php } ?>
			</span>
                <div class="row" id="edit_warning" style="display:none;font-size:.8em;color:red;margin-top:20px"><div class="col-md-4 pull-right"><?php _e('ACHTUNG: Das Bearbeiten von Datum und Nummer kann zu Unstimmigkeiten in Deiner Buchhaltung führen','cpsmartcrm')?></div></div>
            </h4>
            <div class="row form-group">
                <!--<label class="col-sm-1 control-label"><?php //_e('Object','cpsmartcrm')?></label>
                <div class="col-sm-3"><input type="text" class="form-control " name="oggetto" id="oggetto"  maxlength='50' value="<?php echo $oggetto?>">
                </div>-->
                <label class="col-sm-1 control-label"><?php _e('Referenz','cpsmartcrm')?></label>
                <div class="col-sm-3">
                    <input type="text" class="form-control" name="riferimento" id="riferimento" maxlength='55' value="<?php echo $riga["riferimento"]?>">
                </div>
                <label class="col-sm-2 control  -label"><?php _e('Anmerkungen','cpsmartcrm')?></label>
                <div class="col-sm-4">
                    <textarea class="_form-control col-md-12" id="annotazioni" name="annotazioni" rows="5"><?php echo stripslashes($riga["annotazioni"])?></textarea><br />
                    <small><i>(<?php _e('Wird im Dokument angezeigt','cpsmartcrm')?>)</i></small>
                </div>
            </div>
            <div class="row form-group">
                <hr />
                <label class="col-sm-1 control-label"><?php _e('Zahlungsarten','cpsmartcrm')?></label>
                <div class="col-sm-2">

                    <select name="modalita_pagamento" id="modalita_pagamento" class="_form-control col-md-12">
                        <option value="0" <?php if($payment==0) echo "selected"?>><?php _e('Wählen','cpsmartcrm')?></option>
                        <?php
					foreach($arr_payments as $pay)
					{
						$pay_label=explode('~',$pay);
						if( !empty($pay_label[1])) $_pay_label=$pay_label[0]." (".$pay_label[1]." ".__('dd','cpsmartcrm').")";
						else $_pay_label=$pay_label[0];
						if (strstr($pay,$payment) && $payment !="0")
							$selected=" selected";
						else
							$selected= "";
				
                    ?>
                    <option value="<?php echo str_replace("  "," ",$pay)?>" <?php echo $selected ?>><?php echo $_pay_label?></option>
                    <?php } ?>
                    </select>
                </div>
                <label class="control-label"><?php _e('Zahlung exp. Datum','cpsmartcrm')?></label>
                <div class="col-sm-2">
                    <input name="data_scadenza" id="data_scadenza" class="_m" data-placement="bottom"  value="<?php echo  $data_scadenza  ?>"/>
                </div>
                <?php
                if ($ID)
                {
                ?>
                <label class="control-label" style="margin-left:20px"><?php _e('Bezahlt','cpsmartcrm')?>?</label>
                <div class="col-sm-1">
                    <input type="checkbox" name="pagato" value="1" <?php echo $riga["pagato"]?"checked":""?>>
                </div>
                <?php
                }
                ?>
                <label class="control-label" style="margin-left:20px"><?php _e('Benachrichtigen','cpsmartcrm')?>? </label>
                <div class="col-sm-1">
                    <input type="checkbox" name="notify_payment" id="notify_payment" value="1" <?php echo $riga["notifica_pagamento"] ? "checked" : ""?>>
					<span class="crmHelp crmHelp-dark" data-help="payment-notification"></span>
                </div>
            </div>
            <section id="notifications" style="display:none!important">
                <h4 class="page-header"><?php _e('Zahlungserinnerung für die Gutschrift','cpsmartcrm')?> </h4>

                <div class="row form-group">
                    <label class="col-sm-1"><?php _e('An Benutzer senden','cpsmartcrm')?></label><div class="col-sm-2"><input class="ruleActions" id="remindToUser" name="remindToUser" /></div>
                    <label class="col-sm-1"><?php _e('An Gruppe senden','cpsmartcrm')?></label><div class="col-sm-2"><input class="ruleActions" id="remindToGroup" name="remindToGroup"></div>
                    <label class="col-sm-1"><?php _e('Tage nach Ablauf','cpsmartcrm')?></label><div class="col-sm-2"><input class="ruleActions" id="notificationDays" name="notificationDays" type="number" value="<?php echo $giorni_pagamento?>"><small id="changeNoty"><a href="#" onclick="return false;"><?php _e('Standardwert bearbeiten','cpsmartcrm')?>&raquo;</a></small></div>
                    <input type="hidden" id="selectedUsers" name="selectedUsers" class="ruleActions" value="" />
                    <input type="hidden" id="selectedGroups" name="selectedGroups" class="ruleActions" value="" />

                </div>
            </section>

            <h4 class="page-header">
                <?php _e('KUNDENDATEN','cpsmartcrm')?><span class="crmHelp" data-help="customer-data"></span>
                <?php
			if ($fk_clienti)
			{
				echo "<a href=\"".admin_url('admin.php?page=smart-crm&p=clienti/form.php&ID='.$fk_clienti)."\" target=\"_blank\"><span class=\"header_customer\" >".$cliente."</span></a>";
			}
                ?>
                <ul class="select-action _forminvoice" style="float:right;/*transform:scale(.8);*/background-color:transparent;margin-top:-10px;width:inherit">
                    <?php if ($ID) {?>
                    <li class="btn _edit _white btn-sm _flat">
                        <i class="glyphicon glyphicon-pencil"></i>
                        <b> <?php _e('KUNDENDATEN BEARBEITEN','cpsmartcrm')?></b>
                    </li>
                    <li style="display:none" class="btn btn-danger _quitEdit btn-sm _flat">
                        <i class="glyphicon glyphicon-close"></i>
                        <b> <?php _e('EDITIEREN BEENDEN','cpsmartcrm')?></b>
                    </li>
                    <li class="btn"><i class="_tooltip glyphicon glyphicon-menu-right"></i></li>
                    <li class="btn btn-info btn-sm _flat btn_todo" style="margin-left:10px" title="<?php _e('NEUES TODO','cpsmartcrm')?>">
                        <i class="glyphicon glyphicon-tag"></i>
                        <b> </b>
                    </li>
                    <li class="btn  btn-sm _flat btn_appuntamento" title="<?php _e('NEUER TERMIN','cpsmartcrm')?>">
                        <i class="glyphicon glyphicon-pushpin"></i>
                        <b> </b>
                    </li>
                    <li class="btn btn-primary btn-sm _flat btn_activity" title="<?php _e('NEUE ANMERKUNG','cpsmartcrm')?>">
                        <i class="glyphicon glyphicon-option-horizontal"></i>
                        <b> </b>
                    </li>
					<?php do_action('WpsCRM_advanced_document_buttons');?>
                    <?php }?>

                </ul>
            </h4>
            <div class="customer_data_partial" data-customer="<?php echo $fk_clienti?>">
                <input type="hidden" id="tipo_cliente" name="tipo_cliente" value="<?php echo $tipo_cliente?>" data-value="<?php echo $tipo_cliente?>" />
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Kunde','cpsmartcrm')?></label>
                    <div class="col-sm-3">
                        <?php
						if ($fk_clienti)
						{
						$disabled="disabled readonly";

						} 
						?>
                        <select id="fk_clienti" name="fk_clienti" data-parsley-hasclient></select>
						<input type="hidden" name="hidden_fk_clienti" id="hidden_fk_clienti" value="<?php echo $fk_clienti?>">

                    </div>
                    <!--<label class="col-sm-1 control-label"><?php _e('Contact','cpsmartcrm')?></label>
                    <div class="col-sm-3">
                        <input type="hidden" id="FK_contatti" name="FK_contatti" value="<?php echo $FK_contatti?>" data-value="<?php echo $FK_contatti?>" />
            
                    </div>-->
                    <div class="col-sm-2">
                        <input type="button" class="btn btn-sm btn-success _flat" id="save_client_data" name="save_client_data" value="<?php _e('Speichern','cpsmartcrm')?>" style="display:none" />
                    </div>
                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Addresse','cpsmartcrm')?></label>
                    <div class="col-sm-3">

                        <input type="text" class="form-control _editable" name="indirizzo" id="indirizzo" maxlength='50' value="<?php echo $indirizzo?>" <?php echo $disabled?> data-value="<?php echo $indirizzo?>" />

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Postleitzahl','cpsmartcrm')?></label>
                    <div class="col-sm-2">

                        <input type="text" class="form-control _editable" name="cap" id="cap" maxlength='10' value="<?php echo $cap?>" <?php echo $disabled?> data-value="<?php echo $cap?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('C.F.','cpsmartcrm')?></label>
                    <div class="col-md-2">
                        <input type="text" class="form-control _editable" name="cod_fis" id="cod_fis" maxlength='5' value="<?php echo $cod_fis?>" <?php echo $disabled?> data-value="<?php echo $cod_fis?>">
                    </div>

                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Stadt','cpsmartcrm')?></label>
                    <div class="col-sm-3">

                        <input type="text" class="form-control _editable" name="localita" id="localita" maxlength='50' value="<?php echo $localita?>" <?php echo $disabled?> data-value="<?php echo $localita?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Staat/Prov.','cpsmartcrm')?></label>
                    <div class="col-sm-2">

                        <input type="text" class="form-control _editable" name="provincia" id="provincia" maxlength='5' value="<?php echo $provincia?>" <?php echo $disabled?> data-value="<?php echo $provincia?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Umsatzsteuer-ID','cpsmartcrm')?></label>
                    <div class="col-md-2">
                        <input type="text" class="form-control _editable" name="p_iva" id="p_iva" maxlength='5' value="<?php echo $p_iva?>" <?php echo $disabled?> data-value="<?php echo $p_iva?>">
                    </div>

                </div>
            </div>
            <h4 class="page-header"><?php _e('Produkte zur Gutschrift hinzufügen','cpsmartcrm')?><span class="crmHelp" data-help="invoice-products"></span>

				<?php do_action("WPsCRM_show_WOO_products");?>
				
			</h4>

			<?php 
			$accontOptions=get_option( "CRM_acc_settings" );

			switch ($accontOptions['accountability']){
				case 0:
					include ('accountabilities/accountability_0.php');
					break;
				case "1":
					include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_1.php');
					break;
				case "2":
					include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_2.php');
					break;
				case "3":
					include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_3.php');
					break;
				case "4":
					include (ACCsCRM_DIR.'/inc/crm/documenti/accountabilities/accountability_4.php');
					break;
			}
			?>


        </div>
        <!--fine primo tab -->
        <!-- inizio secondo tab -->
        <div class="tab-content" style="display:none;">
            <div class="row form-group">
                <label class="col-sm-2 control-label"><?php _e('Kommentare','cpsmartcrm')?></label>
                <div class="col-sm-6">
                    <textarea class="_form-control" id="commento" name="commento" rows="10" cols="50"><?php echo stripslashes($riga["commento"])?></textarea>
                </div>
            </div>
        </div>
        <!--fine secondo tab -->
    </div>
	<input name="check" style="visibility:hidden" />
    <input type="submit" style="display:none" />
    <ul class="select-action">
        <li class="btn btn-sm btn-success _flat" id="_submit">
            <i class="glyphicon glyphicon-floppy-disk"></i>
            <b> <?php _e('Speichern','cpsmartcrm')?></b>
        </li>
        <li class="btn btn-warning btn-sm _flat" onClick="annulla();return false;">
            <i class="glyphicon glyphicon-floppy-remove"></i>
            <b> <?php _e('Zurücksetzen','cpsmartcrm')?></b>
        </li>
        <?php
	if ($ID)
	{
		$upload_dir = wp_upload_dir();
		$document = $upload_dir['baseurl'] . "/CRMdocuments/".$filename.".pdf";
        ?>
        <li class="btn btn-sm btn-info _flat" onclick="location.replace('?page=smart-crm&p=documenti/document_print.php&id_invoice=<?php echo $ID?>')">
            <i class="glyphicon glyphicon-print"></i>
            <b> <?php _e('Druckbare Version','cpsmartcrm')?></b>
        </li>
        <?php
	}
        ?>
    </ul>
</form> 
<div id="dialog-view" style="display:none;margin: 0 auto; text-align: center; z-index: 1000; width: 100%; height: 100%; background: url('<?php echo str_replace("inc/crm/documenti/","",plugin_dir_url( __FILE__ ))?>css/img/bg_w_tr.png');position: absolute;left: 0;top:0;"  class="_modal" data-from="documenti">
    <div class="col-md-6 panel panel-primary _flat modal_inner" style="border:1px solid #666;text-align:left;background:#fff;padding-bottom:20px;margin: 46px auto;float: none;padding:0;position:relative">
    <div class="panel-heading" style="padding: 3px 10px;">
        <h3 style="text-align:center;margin-top: 8px;"><?php _e('Standardtage ändern','cpsmartcrm')?><span class="crmHelp" data-help="deafult-invoice-payment-noty"></span></h3>
    </div>
    <div class="panel-body" style="padding:50px">
        <label><?php  _e('Standardwert ändern','cpsmartcrm')?></label><input class="ruleActions" name="new_default_noty" id="new_default_noty" type="number" value="<?php echo $documentOptions['invoice_noty_days']?>">
        <span class="btn btn-success btn-sm _flat" id="notyConfirm"><?php _e('Bestätigen','cpsmartcrm')?></span>
        <span class="btn btn-warning btn-sm _flat _reset" ><?php _e('Zurücksetzen','cpsmartcrm')?></span>
    </div>
    </div>
</div>
<div id="dialog_todo" style="display:none;" data-from="documenti" data-fkcliente="<?php echo $fk_clienti?>">
	<?php
	include ( WPsCRM_DIR."/inc/crm/clienti/form_todo.php" )
	?>
</div>
<?php 
	include (WPsCRM_DIR."/inc/crm/clienti/script_todo.php" )
?>
<div id="dialog_appuntamento" style="display:none;" data-from="documenti" data-fkcliente="<?php echo $fk_clienti?>">
	<?php
	include (WPsCRM_DIR."/inc/crm/clienti/form_appuntamento.php" )
	?>
</div>
<?php 
	include (WPsCRM_DIR."/inc/crm/clienti/script_appuntamento.php" )
?>
<div id="dialog_attivita" style="display:none;"  data-from="documenti" data-fkcliente="<?php echo $fk_clienti?>">
	<?php
	include (WPsCRM_DIR."/inc/crm/clienti/form_attivita.php" )
	?>
</div>
<?php
	include (WPsCRM_DIR."/inc/crm/clienti/script_attivita.php" )
?>
<div id="dialog_mail" style="display:none;" data-from="documenti" data-fkcliente="<?php echo $fk_clienti?>">
	<?php
	include (WPsCRM_DIR."/inc/crm/clienti/form_mail.php" )
    ?>    
</div>
<?php
	include (WPsCRM_DIR."/inc/crm/clienti/script_mail.php" )
?>
<style>
	.customer_data_partial{padding-top:6px;padding-bottom:6px}
	.edit_active{border:1px dashed red;background:#ccc}
</style>
<div class="modal fade" id="reverseCalculator" tabindex="-1" role="dialog" aria-labelledby="reverseCalculatorLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="reverseCalculatorLabel"><?php _e('Umgekehrte Berechnung','cpsmartcrm') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Schließen">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label><?php _e('Gib den vollen Betrag für die umgekehrte Berechnung ein:','cpsmartcrm') ?></label>
          <input class="form-control" type="number" id="reverseAmount" />
        </div>
        <div class="form-group">
          <label><?php _e('Eingabe der Erstattung für die umgekehrte Berechnung:','cpsmartcrm') ?></label>
          <input class="form-control" type="number" id="reverseRefund" />
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn _flat btn-success" type="button" id="calculate"><?php _e('Berechnung:','cpsmartcrm') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php _e('Schließen','cpsmartcrm') ?></button>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
jQuery(document).ready(function ($) {
    sessionStorage.removeItem('tmp_amount');

    // MODAL: reverseCalculator (Bootstrap Modal)
    $('.reverseCalulator').on('click', function () {
        if ($('#form_insert').parsley().validate() && !$(this).hasClass('disabled')) {
            $('#reverseCalculator').modal('show');
        }
    });

    // TOOLTIP: Bootstrap Tooltip
    $("._tooltip").tooltip({
        html: true,
        placement: "top",
        title: `<h4><?php _e('TASTEN-LEGENDE','cpsmartcrm')?>:</h4>
        <ul>
            <li class="no-link">
                <span class="btn btn-info _flat"><i class="glyphicon glyphicon-tag"></i> = <?php _e('NEUES TODO','cpsmartcrm')?></span>
                <span class="btn btn_appuntamento_1 _flat"><i class="glyphicon glyphicon-pushpin"></i> = <?php _e('NEUER TERMIN','cpsmartcrm')?></span>
                <span class="btn btn-primary _flat"><i class="glyphicon glyphicon-option-horizontal"></i> = <?php _e('NEUE AKTIVITÄT','cpsmartcrm')?></span>
                <span class="btn btn-warning _flat"><i class="glyphicon glyphicon-envelope"></i> = <?php _e('NEUE MAIL','cpsmartcrm')?></span>
            </li>
        </ul>`
    });

    // VALIDATOR: Parsley.js
    $('#form_insert').parsley({
        errorsContainer: function (ParsleyField) {
            return ParsleyField.$element.closest('.form-group');
        },
        errorClass: 'is-invalid',
        successClass: 'is-valid'
    });

    // Custom Parsley validators
    window.Parsley.addValidator('hasclient', {
        validateString: function(value) {
            return value && value !== '';
        },
        messages: {
            de: "<?php _e('Du solltest einen Kunden auswählen','cpsmartcrm')?>"
        }
    });

    window.Parsley.addValidator('hasnoty', {
        validateString: function(value) {
            return $('#notify_payment').is(':checked') ? (value && value !== '') : true;
        },
        messages: {
            de: "<?php _e('Du solltest einen Benutzer oder eine Gruppe von Benutzern auswählen, die benachrichtigt werden sollen','cpsmartcrm')?>"
        }
    });

    // DATEPICKER: jQuery UI
    $("#data").datepicker({
        dateFormat: "<?php echo WPsCRM_DATEFORMAT_JS ?>",
        <?php if(!$ID) { ?> defaultDate: new Date(), <?php } ?>
        onSelect: function(dateText) {
            updateExpiration();
        }
    });
    $("#data_scadenza").datepicker({
        dateFormat: "<?php echo WPsCRM_DATEFORMAT_JS ?>",
        <?php if($data_scadenza =="") { ?> defaultDate: new Date(), <?php } ?>
    });

    function updateExpiration() {
        var payment = $('#modalita_pagamento').val();
        var parts = payment.split('~');
        if (parts[1]) {
            var baseDate = $("#data").datepicker('getDate');
            if (baseDate) {
                var days = parseInt(parts[1], 10);
                var future = new Date(baseDate.getTime() + days * 86400000);
                $("#data_scadenza").datepicker('setDate', future);
            }
        }
    }

    $('#modalita_pagamento').on('change', updateExpiration);

    // Select2 für Kunden
    $("#fk_clienti").select2({
        width: '100%',
        placeholder: "<?php _e('Wähle Kunde aus','cpsmartcrm')?>...",
        minimumInputLength: 3,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { action: 'WPsCRM_get_clients2', q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data.clients, function (obj) {
                        return { id: obj.ID_clienti, text: obj.ragione_sociale ? obj.ragione_sociale : (obj.nome + " " + obj.cognome) };
                    })
                };
            },
            cache: true
        }
    }).on('select2:select', function (e) {
        var id_clienti = e.params.data.id;
        $.ajax({
            url: ajaxurl,
            data: {
                'action': 'WPsCRM_get_client_info',
                'id_clienti': id_clienti
            },
            success: function (result) {
                var parseData = result.info ? result.info[0] : {};
                $("#indirizzo").val(parseData.indirizzo || '');
                $("#cap").val(parseData.cap || '');
                $("#localita").val(parseData.localita || '');
                $("#provincia").val(parseData.provincia || '');
                $("#cod_fis").val(parseData.cod_fis || '');
                $("#p_iva").val(parseData.p_iva || '');
                $("#tipo_cliente").val(parseData.tipo_cliente || '');
            }
        });
    });

    <?php if ($ID){ ?>
    $("#fk_clienti").prop("disabled", true);
    <?php } ?>

    // MultiSelect für Benutzer und Gruppen (Select2)
    $("#remindToUser").select2({
        width: '100%',
        placeholder: "<?php _e('Benutzer wählen','cpsmartcrm')?>...",
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { action: 'WPsCRM_get_CRM_users', q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (obj) {
                        return { id: obj.ID, text: obj.display_name };
                    })
                };
            },
            cache: true
        },
        multiple: true
    }).on('change', function () {
        $('#selectedUsers').val($(this).val());
    });

    $("#remindToGroup").select2({
        width: '100%',
        placeholder: "<?php _e('Wähle Rolle aus','cpsmartcrm')?>...",
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { action: 'WPsCRM_get_registered_roles', q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data.roles, function (obj) {
                        return { id: obj.role, text: obj.name };
                    })
                };
            },
            cache: true
        },
        multiple: true
    }).on('change', function () {
        $('#selectedGroups').val($(this).val());
    });

    // Vorbelegen falls vorhanden
    <?php if (!empty($users)) { ?>
        $("#remindToUser").val([<?php echo json_encode(explode(',', $users)); ?>]).trigger('change');
    <?php } ?>
    <?php if (!empty($groups)) { ?>
        $("#remindToGroup").val([<?php echo json_encode(explode(',', $groups)); ?>]).trigger('change');
    <?php } ?>

    // Notifications anzeigen/verstecken
    if ($('input[name="notify_payment"]:checked').length) {
        $('#notifications').show();
    } else {
        $('#notifications').hide();
    }
    $('#notify_payment').on('click', function () {
        $('#notifications').is(':visible') ? $('#notifications').fadeOut(200) : $('#notifications').fadeIn(200)
    });

    // Editier-Logik
    $('._edit').on('click', function () {
        $(this).hide();
        $('._quitEdit').show();
        $('._editable').attr('readonly', false).attr('disabled', false);
        $('#_submit').css('visibility', 'hidden');
        $('#save_client_data').show();
        $('#save_client_data').parent().append("<br><small class=\"_notice notice notice-error \"><?php _e("Du bearbeitest die Stammdaten für diesen Kunden",'cpsmartcrm')?></small>")
        $('.customer_data_partial').addClass('edit_active');
    });

    $('._quitEdit').on('click', function () {
        var $this = $(this);
        $this.hide();
        $('._notice').hide().remove();
        $('._edit').show();
        $('._editable').attr('readonly', 'readonly').attr('disabled', 'disabled');
        $('._editable').each(function () {
            $(this).val($(this).data('value'));
        });
        $('#_submit').css('visibility','visible');
        $('#save_client_data').hide();
        $('.customer_data_partial').removeClass('edit_active');
    });

    $('#save_client_data').on('click', function () {
        var inputs = $('.customer_data_partial :input').serialize();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'WPsCRM_save_client_partial',
                values: inputs,
                security: '<?php echo $update_nonce?>'
            },
            success: function (result) {
                noty({
                    text: "<?php _e('Daten wurden gespeichert','cpsmartcrm')?>",
                    layout: 'center',
                    type: 'success',
                    template: '<div class="noty_message"><span class="noty_text"></span></div>',
                    timeout: 1000
                });
                setTimeout(function () {
                    $('._quitEdit').hide();
                    $('._notice').hide().remove();
                    $('._edit').show();
                    $('._editable').attr('readonly', 'readonly').attr('disabled', 'disabled');
                    $('#_submit').css('visibility', 'visible');
                    $('#save_client_data').hide();
                    $('.customer_data_partial').removeClass('edit_active');
                }, 200)
                $('.customer_data_partial :input').each(function () {
                    $(this).attr('data-value', $(this).val())
                })
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        })
    });

    // Noty-Dialog
    $('#changeNoty').on('click', function (e) {
        var position = $(e.target).offset();
        $('#dialog-view').show();
        $('.modal_inner').animate({
            'top': position.top - 320 + 'px',
        }, 1000);
    });
    $(document).on('click', '#notyConfirm', function () {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'WPsCRM_update_options_modal',
                option_section: 'CRM_documents_settings',
                option: 'invoice_noty_days',
                val: $('#new_default_noty').val(),
                security:'<?php echo $update_nonce?>'
            },
            success: function (result) {
                $('#dialog-view').slideToggle();
                if (isNaN(result) == false) {
                    $('#notificationDays').val(result);
                    noty({
                        text: "<?php _e('Option gespeichert','cpsmartcrm')?>",
                        layout: 'center',
                        type: 'success',
                        template: '<div class="noty_message"><span class="noty_text"></span><span class="noty_close glyphicons gypicons-close"></span></div>',
                        timeout: 1500
                    });
                }
                else {
                    noty({
                        text: "<?php _e('Ein Fehler ist aufgetreten','cpsmartcrm')?>",
                        layout: 'center',
                        type: 'error',
                        template: '<div class="noty_message"><span class="noty_text"></span><span class="noty_close glyphicons gypicons-close"></span></div>',
                        closeWith: ['button'],
                    });
                }
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        })
    });

    // UI-Logik für Buttons
    if ($('.reverse_row').length) {
        $('#btn_manual').addClass('disabled').attr('title', 'Questa fattura e\' stata calcolata mit Werten scorporati und es können keine weiteren Zeilen hinzugefügt werden');
        $('.reverseCalulator').addClass('disabled').attr('title', 'Diese Funktion ist nur verfügbar, wenn keine weiteren Zeilen vorhanden sind');
        $('#btn_refund').addClass('disabled').attr('title', 'Diese Funktion ist nur verfügbar, wenn keine weiteren Zeilen vorhanden sind');
    }
    if ($('.manual_row').length) {
        $('.reverseCalulator').addClass('disabled').attr('title', 'Diese Funktion ist nur verfügbar, wenn keine weiteren Zeilen vorhanden sind');
    }

	$('.tabstrip li').on('click', function() {
    var idx = $(this).index();
    $('.tabstrip li').removeClass('active');
    $(this).addClass('active');
    $('.tab-content').hide().eq(idx).show();
});
});
</script>
<?php } ?>

