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
if (isset($_GET["id_invoice"])&&$ID=$_GET["id_invoice"])
{
	$plugin_dir  = dirname(dirname(dirname(dirname(__FILE__))));
}
else
{
    $ID=isset($_REQUEST["ID"])?$_REQUEST["ID"]:0;
    //$type=isset($_REQUEST["$type"])?$_REQUEST["$type"]:0;
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
        isset( $riga["tempi_chiusura_dal"] ) ? $tempi_chiusura_dal=WPsCRM_inverti_data($riga["tempi_chiusura_dal"]) : "";
        $oggetto=$riga["oggetto"];
        $iva=isset($riga["iva"]) ? $riga["iva"] : "";
        $tot_imp=sprintf("%01.2f", $riga["totale_imponibile"]);
        $tipo_sconto=$riga["tipo_sconto"];
    	$totale_imposta=sprintf("%01.2f", $riga["totale_imposta"]);
    	$tot_cassa=sprintf("%01.2f", $riga["tot_cassa_inps"]);
    	$ritenuta_acconto=sprintf("%01.2f", $riga["ritenuta_acconto"]);
        $totale=$riga["totale"];
        $totale_netto=$riga["totale_netto"];

       // $FK_contatti=$riga["FK_contatti"];
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
    /*    if ($riga["FK_contatti"])
        {
            $sql="select concat(nome,' ', cognome) as contatto from ana_contatti where ID_contatti=".$riga["FK_contatti"];
            $rigac=$wpdb->get_row($sql, ARRAY_A);
            $contatto=$rigac["contatto"];
        }*/
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
      //  $oggetto=__("Angebot","cpsmartcrm");
        $iva=$documentOptions['default_vat'];
        $tempi_chiusura_dal=WPsCRM_culture_date_format(date("d-m-Y") );
        $FK_clienti=0;
        $FK_contatti=0;
        $giorni_pagamento=$documentOptions['invoice_noty_days'];
        $tipo_sconto=0;
        $tot_imp=0;
        $tot_cassa=0;
        $totale_imposta=0;
        $totale=0;
        $ritenuta_acconto=0;
        $totale_netto=0;
    }?>

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
	var cliente = "<?php if (isset($cliente))echo $cliente ?>";
</script>
<form name="form_insert" action="" method="post" id="form_insert">
    <!--<div class="modal_loader" style="background:#fff url(<?php echo WPsCRM_URL?>/css/img/loading-image.gif);background-repeat:no-repeat;background-position:center center"></div>-->
        <input type="hidden" name="num_righe" id="num_righe" value="">
	<input type="hidden" name="ID" id="ID" value="<?php echo $ID?>">
	<input type="hidden" name="type" id="type" value="3">
    <h1 style="text-align:center;background-color:#ffd7d7"><?php _e('INFORMELLE RECHNUNG ERSTELLEN/BEARBEITEN','cpsmartcrm')?> <i class="glyphicon glyphicon-list-alt"></i></h1>
    <div id="tabstrip">
        <ul>
            <li id="tab1"><?php _e('RECHNUNG','cpsmartcrm')?></li>
            <li onclick="aggiornatot();"><?php _e('KOMMENTARE UND INTERNE DATEN','cpsmartcrm')?></li>
        </ul>
        <div>
            <h4 class="page-header" style="margin: 10px 0 20px;">
                <?php _e('INVOICE DATA','cpsmartcrm')?><span class="crmHelp" data-help="document-data"></span>
                <?php if ($ID) {?>
                <span style="float:right;font-size:.8em;text-decoration:underline;cursor:pointer" class="_edit_header"><i class="glyphicon glyphicon-pencil"></i> <?php _e('Bearbeiten','cpsmartcrm')?></span>
                <?php } ?>
                <span style="float:right;margin-top: -7px;">
                    <label class="col-sm-2 control-label"><?php _e('Nummer','cpsmartcrm')?></label>
                    <span class="col-sm-2">
                        <input name="progressivo" id="progressivo" class="form-control" data-placement="bottom" title="<?php _e('Nummer','cpsmartcrm')?>" value="<?php if (isset($riga)) echo $riga["progressivo"]?>" readonly disabled />
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
                    <input type="text" class="form-control" name="riferimento" id="riferimento" maxlength='55' value="<?php if (isset($riga)) echo $riga["riferimento"]?>">
                </div>
                <label class="col-sm-2 control  -label"><?php _e('Anmerkungen','cpsmartcrm')?></label>
                <div class="col-sm-4">
                    <textarea class="_form-control col-md-12" id="annotazioni" name="annotazioni" rows="5"><?php if (isset($riga)) echo stripslashes($riga["annotazioni"])?></textarea><br />
                    <small><i>(<?php _e('Wird im Dokument angezeigt','cpsmartcrm')?>)</i></small>
                </div>
            </div>
            <div class="row form-group">
                <hr />
                <label class="col-sm-1 control-label"><?php _e('Zahlungsarten','cpsmartcrm')?></label>
                <div class="col-sm-2">

                    <select name="modalita_pagamento" id="modalita_pagamento" class="_form-control col-md-12">
                        <option value="0" <?php if (isset($riga) && $payment==0) echo "selected"?>><?php _e('Wählen','cpsmartcrm')?></option>
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
                <label class="control-label"><?php _e('Zahlungsexp. Datum','cpsmartcrm')?></label>
                <div class="col-sm-2">
                    <input name="data_scadenza" id="data_scadenza" class="_m" data-placement="bottom"  value="<?php if (isset($riga)) echo  $data_scadenza  ?>"/>
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
                    <input type="checkbox" name="notify_payment" id="notify_payment" value="1" <?php if (isset($riga)) echo $riga["notifica_pagamento"] ? "checked" : ""?>>
					<span class="crmHelp crmHelp-dark" data-help="payment-notification"></span>
                </div>
            </div>
            <section id="notifications" style="display:none!important">
                <h4 class="page-header"><?php _e('Zahlungserinnerung','cpsmartcrm')?> </h4>

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
			if (isset($fk_clienti))
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
            <div class="customer_data_partial" data-customer="<?php if (isset($fk_clienti)) echo $fk_clienti?>">
                <input type="hidden" id="tipo_cliente" name="tipo_cliente" value="<?php if (isset($tipo_cliente)) echo $tipo_cliente?>" data-value="<?php if (isset($tipo_cliente)) echo $tipo_cliente?>" />
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Kunde','cpsmartcrm')?></label>
                    <div class="col-sm-3">
                        <?php
						if (isset($fk_clienti))
						{
						$disabled="disabled readonly";
						} 
                                                else
							$disabled="";
						?>
                        <select id="fk_clienti" name="fk_clienti" data-parsley-hasclient></select>
						<input type="hidden" name="hidden_fk_clienti" id="hidden_fk_clienti" value="<?php if (isset($fk_clienti)) echo $fk_clienti?>">

                    </div>
                    <!--<label class="col-sm-1 control-label"><?php _e('Kontakt','cpsmartcrm')?></label>
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

                        <input type="text" class="form-control _editable" name="indirizzo" id="indirizzo" maxlength='50' value="<?php if (isset($indirizzo)) echo $indirizzo?>" <?php echo $disabled?> data-value="<?php if (isset($indirizzo)) echo $indirizzo?>" />

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('PLZ','cpsmartcrm')?></label>
                    <div class="col-sm-2">

                        <input type="text" class="form-control _editable" name="cap" id="cap" maxlength='10' value="<?php if (isset($cap)) echo $cap?>" <?php echo $disabled?> data-value="<?php if (isset($cap)) echo $cap?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('C.F.','cpsmartcrm')?></label>
                    <div class="col-md-2">
                        <input type="text" class="form-control _editable" name="cod_fis" id="cod_fis" maxlength='5' value="<?php if (isset($cod_fis)) echo $cod_fis?>" <?php echo $disabled?> data-value="<?php if (isset($cod_fis)) echo $cod_fis?>">
                    </div>

                </div>
                <div class="row form-group">
                    <label class="col-sm-1 control-label"><?php _e('Stadt','cpsmartcrm')?></label>
                    <div class="col-sm-3">

                        <input type="text" class="form-control _editable" name="localita" id="localita" maxlength='50' value="<?php if (isset($localita)) echo $localita?>" <?php echo $disabled?> data-value="<?php if (isset($localita)) echo $localita?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Staat/Prov.','cpsmartcrm')?></label>
                    <div class="col-sm-2">

                        <input type="text" class="form-control _editable" name="provincia" id="provincia" maxlength='5' value="<?php if (isset($provincia)) echo $provincia?>" <?php echo $disabled?> data-value="<?php if (isset($provincia)) echo $provincia?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Umsatzsteuer-ID','cpsmartcrm')?></label>
                    <div class="col-md-2">
                        <input type="text" class="form-control _editable" name="p_iva" id="p_iva" maxlength='5' value="<?php if (isset($p_iva)) echo $p_iva?>" <?php echo $disabled?> data-value="<?php if (isset($p_iva)) echo $p_iva?>">
                    </div>

                </div>
            </div>
            <h4 class="page-header"><?php _e('Produkte zur Rechnung hinzufügen','cpsmartcrm')?><span class="crmHelp" data-help="invoice-products"></span>

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
        <div>
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
		//$document = $upload_dir['baseurl'] . "/CRMdocuments/".$filename.".pdf";
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
<!-- Overlay für das Modal -->
<div id="reverseOverlay" style="display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:9998;"></div>

<!-- ReverseCalculator als zentriertes Modal -->
<div id="reverseCalculator" style="display:none; position:fixed; left:50%; top:50%; transform:translate(-50%,-50%); z-index:9999; background:#fff; border:1px solid #ccc; padding:30px; box-shadow:0 0 20px #0008; min-width:300px;">
    <button id="closeReverseCalc" style="position:absolute; top:10px; right:10px; font-size:1.5em; background:none; border:none;">&times;</button>
    <div class="col-md-11">
        <label><?php _e('Gib den Gesamtbetrag für die umgekehrte Berechnung ein:','cpsmartcrm') ?></label>
        <input class="form-control" type="number" id="reverseAmount" />
    </div>
    <div class="col-md-11">
        <label><?php _e('Eingaberückerstattung für Rückrechnung:','cpsmartcrm') ?></label>
        <input class="form-control" type="number" id="reverseRefund" />
    </div>
    <div class="col-md-11"><br />
        <input class="btn _flat btn-success" type="button" id="calculate" value="<?php _e('Berechnung:','cpsmartcrm') ?>" />
    </div>
</div>
<span id="informalNotification" style="display:none;width:100%!important"></span>

<script type="text/javascript">
jQuery(document).ready(function ($) {
    // ReverseCalculator-Popup initial ausblenden
    $('#reverseCalculator, #reverseOverlay').hide();

    // Öffnen (Button braucht die Klasse .reverseCalulator)
    $('.reverseCalulator').on('click', function (e) {
        e.preventDefault();
        $('#reverseOverlay').fadeIn(100);
        $('#reverseCalculator').fadeIn(200);
    });

    // Schließen
    $('#closeReverseCalc, #reverseOverlay').on('click', function () {
        $('#reverseCalculator').fadeOut(200);
        $('#reverseOverlay').fadeOut(100);
    });

    // ESC schließt das Popup
    $(document).on('keydown', function(e) {
        if (e.key === "Escape") {
            $('#reverseCalculator').fadeOut(200);
            $('#reverseOverlay').fadeOut(100);
        }
    });

    sessionStorage.removeItem('tmp_amount');

    // --- Fehlermeldungen zentral ---
    var validationMessages = {
        hasNoty: "<?php _e('Du solltest einen Benutzer oder eine Gruppe von Benutzern auswählen, die Du benachrichtigen möchtest', 'cpsmartcrm') ?>",
        hasClient: "<?php _e('Du solltest einen Kunden auswählen', 'cpsmartcrm') ?>",
        hasRows: "<?php _e('Du solltest dieser Rechnung mindestens eine Zeile hinzufügen', 'cpsmartcrm') ?>",
        hasDescription: "<?php _e('Beschreibung ist obligatorisch', 'cpsmartcrm') ?>"
    };

    // Kunden-Auswahl (Select2)
    $("#fk_clienti").select2({
        width: '100%', // <-- Breite auf 100% setzen
        placeholder: "<?php _e('Wähle Kunde aus', 'cpsmartcrm') ?>...",
        minimumInputLength: 3,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'WPsCRM_get_clients2',
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data.clients, function (obj) {
                        return {
                            id: obj.ID_clienti,
                            text: obj.ragione_sociale ? obj.ragione_sociale : (obj.nome + " " + obj.cognome)
                        };
                    })
                };
            },
            cache: true
        }
    });

    <?php if (isset($fk_clienti)) { ?>
    $("#fk_clienti").append(new Option("<?php echo addslashes($cliente); ?>", "<?php echo $fk_clienti; ?>", true, true)).trigger('change');
    <?php } ?>

    $("#fk_clienti").on('select2:select', function (e) {
        var id_clienti = $(this).val();
        if (id_clienti) {
            $.ajax({
                url: ajaxurl,
                data: {
                    'action': 'WPsCRM_get_client_info',
                    'id_clienti': id_clienti
                },
                success: function (result) {
                    var parseData = result.info[0];
                    $("#indirizzo").val(parseData.indirizzo);
                    $("#cap").val(parseData.cap);
                    $("#localita").val(parseData.localita);
                    $("#provincia").val(parseData.provincia);
                    $("#cod_fis").val(parseData.cod_fis);
                    $("#p_iva").val(parseData.p_iva);
                    $("#tipo_cliente").val(parseData.tipo_cliente);
                }
            });
        }
    });

    // Benutzer-Auswahl (Select2 Multiple)
    $("#remindToUser").select2({
        placeholder: "<?php _e('Benutzer wählen', 'cpsmartcrm') ?>...",
        multiple: true,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'WPsCRM_get_CRM_users',
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (obj) {
                        return {
                            id: obj.ID,
                            text: obj.display_name
                        };
                    })
                };
            },
            cache: true
        }
    });
    $("#remindToUser").on('change', function () {
        $('#selectedUsers').val($(this).val() ? $(this).val().join(",") : "");
    });

    // Rollen-Auswahl (Select2 Multiple)
    $("#remindToGroup").select2({
        placeholder: "<?php _e('Wähle Rolle aus', 'cpsmartcrm') ?>...",
        multiple: true,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'WPsCRM_get_registered_roles',
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data.roles, function (obj) {
                        return {
                            id: obj.role,
                            text: obj.name
                        };
                    })
                };
            },
            cache: true
        }
    });
    $("#remindToGroup").on('change', function () {
        $('#selectedGroups').val($(this).val() ? $(this).val().join(",") : "");
    });

    // Validator (ohne Kendo)
    function validateForm() {
        var valid = true;
        var msg = "";

        // Kunden-Auswahl prüfen
        if (!$("#fk_clienti").val()) {
            jQuery.playSound("<?php echo WPsCRM_URL ?>inc/audio/double-alert-2");
            msg = validationMessages.hasClient;
            valid = false;
        }
        // Benachrichtigung prüfen
        if ($('input[name="notify_payment"]:checked').length) {
            var users = $("#remindToUser").val();
            var groups = $("#remindToGroup").val();
            if ((!users || users.length === 0) && (!groups || groups.length === 0)) {
                jQuery.playSound("<?php echo WPsCRM_URL ?>inc/audio/double-alert-2");
                msg = validationMessages.hasNoty;
                valid = false;
            }
        }
        // Mindestens eine Rechnungszeile?
        if ($('.riga').length == 0) {
            jQuery.playSound("<?php echo WPsCRM_URL ?>inc/audio/double-alert-2");
            msg = validationMessages.hasRows;
            valid = false;
        }
        // Beschreibung Pflichtfeld?
        var descValid = true;
        $('.descriptive_row').each(function(){
            if ($(this).val() == "") {
                descValid = false;
            }
        });
        if (!descValid) {
            jQuery.playSound("<?php echo WPsCRM_URL ?>inc/audio/double-alert-2");
            msg = validationMessages.hasDescription;
            valid = false;
        }

        if (!valid && msg) {
            noty({
                text: msg,
                layout: 'center',
                type: 'error',
                timeout: 2000
            });
        }

        return valid;
    }

    // Formular absenden
    $('#_submit').on('click', function (e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        showMouseLoader();
        var form = jQuery('form');
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: 'WPsCRM_save_document',
                fields: form.serialize(),
                security: '<?php echo $update_nonce; ?>'
            },
            type: "POST",
            success: function (response) {
                hideMouseLoader();
                if (response.indexOf('OK') != -1) {
                    var tmp = response.split("~");
                    var id_cli = tmp[1];
                    noty({
                        text: "<?php _e('Dokument wurde gespeichert', 'cpsmartcrm') ?>",
                        layout: 'center',
                        type: 'success',
                        timeout: 1000
                    });
                    $("#ID").val(id_cli);
                    setTimeout(function () {
                        location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php&ID=') ?>" + id_cli;
                    }, 1000)
                } else {
                    noty({
                        text: "<?php _e('Etwas war falsch', 'cpsmartcrm') ?>" + ": " + response,
                        layout: 'center',
                        type: 'error',
                        closeWith: ['button']
                    });
                }
            }
        });
    });

    // Benachrichtigungsbereich anzeigen/verstecken
    $('#notify_payment').on('click', function () {
        $('#notifications').is(':visible') ? $('#notifications').fadeOut(200) : $('#notifications').fadeIn(200)
    });
});
</script>
<?php } ?>

