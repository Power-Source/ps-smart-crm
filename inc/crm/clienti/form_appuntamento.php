<?php
if ( ! defined( 'ABSPATH' ) ) exit;
  $tipo_agenda=2;
  $giorno=date("d");
  $mese=date("m");
  $anno=date("Y");
  $ora_i=date("H");
  $minuto_i=date("i");
  $ora_f=date("H");
  $minuto_f=date("i");
  $oggi=date("d-m-Y");
  list($giorno,$mese,$anno) = explode("-",$oggi);
  $data_scadenza=date("d-m-Y");
  $data_agenda=date("d-m-Y");

//echo $data_agenda;
?>

<form id="new_appointment" class="modal_form">
    <div class="col-md-12 panel panel-primary _flat" style="padding:0!important">
        <div class="panel-body" style="padding:20px">
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">

                    <label class="col-sm-1 control-label"><?php _e( 'Start', 'cpsmartcrm'); ?></label>
                    <div class="col-md-3">
                        <input name="a_data_scadenza_inizio" id='a_data_scadenza_inizio' value="<?php echo $data_scadenza?>" data-parsley-hasexpiration>
                    </div>
                    <label class="col-sm-1 control-label"><?php _e( 'Ende', 'cpsmartcrm'); ?></label>
                    <div class="col-md-3">
                        <input name="a_data_scadenza_fine" id='a_data_scadenza_fine' value="<?php echo $data_scadenza?>" data-parsley-hasexpiration>
                    </div>
				<div class="clear"></div>
                    <label class="col-sm-1 control-label"><?php _e('Priorität','cpsmartcrm')?></label>
                    <div class="col-sm-3">
                        <?php WPsCRM_priorita()?>
                    </div>
            </div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;">
                <div class="col-md-5 form-group">
                    <label><?php _e( 'Betreff', 'cpsmartcrm'); ?> </label><input type="text" name="a_oggetto" id='a_oggetto' class="form-control _m k-textbox" placeholder="<?php _e('Gib einen Betreff für dieses Element ein','cpsmartcrm')?>" data-parsley-hasobject>
                </div>
                <div class="col-md-6 form-group">
                    <label><?php _e( 'Anmerkungen', 'cpsmartcrm'); ?></label><textarea id="a_annotazioni" name="a_annotazioni" class="form-control _m k-textbox _flat" style="height:30px"></textarea>

                </div>

            </div>

            <div class="row" style="background:#e2e2e2;padding-bottom:10px;margin-bottom:10px">
                <div class="col-md-11"><h3><?php _e( 'Benachrichtigungsregeln für diesen Termin', 'cpsmartcrm'); ?> </h3></div>

            </div>

            <div style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">
                <div class="col-md-4 form-group">
                    <label><?php _e( 'Tage im Voraus', 'cpsmartcrm'); ?></label>
                    <select class="form-control _m ruleActions k-dropdown _flat" style="width:100px" id="a_ruleStep" name="a_ruleStep" data-parsley-hasdays>
                        <option value=""><?php _e( 'Wählen', 'cpsmartcrm'); ?></option>
                        <?php for($k=0;$k<61;$k++){echo '<option value="'.$k.'">'.$k.'</option>'.PHP_EOL; } ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label><?php _e('Sende auch eine Sofortbenachrichtigung','cpsmartcrm')?></label>
                    <input type="checkbox" class="ruleActions " id="instantNotification" name="instantNotification" />
                    <small style="line-height:.8em">Eine E-Mail wird sofort an alle ausgewählten Benutzer/Gruppen gesendet, wenn die Option „E-Mail an Empfänger senden“ unten aktiv ist</small>
                </div>

            </div>
			<div class="clear"></div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">
                <div class="col-md-6">
                    <label><?php _e( 'Wähle Konto für diesen Termin aus', 'cpsmartcrm'); ?></label>
                    <select class="ruleActions" id="a_remindToUser" name="a_remindToUser[]" multiple></select>
                </div>
                <div class="col-md-4">
                    <label>
                        <?php _e( 'Im Konto-Dashboard veröffentlichen', 'cpsmartcrm'); ?>?<br />
                        <input type="checkbox" class="ruleActions" name="a_userDashboard" id="a_userDashboard" />
                    </label>
                </div>
            </div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc;display:none">
                <div class="col-md-6">
                    <label><?php _e( 'An Gruppe senden', 'cpsmartcrm'); ?></label>
                    <select class="ruleActions" id="a_remindToGroup" name="a_remindToGroup[]" multiple></select>
                </div>
                <div class="col-md-4">
                    <label>
                        <?php _e( 'Im Gruppen-Dashboard veröffentlichen', 'cpsmartcrm'); ?>?<br />
                        <input type="checkbox" class="ruleActions" name="a_groupDashboard" id="a_groupDashboard" />
                    </label>
                </div>
            </div>
            <div class="row" style="background:#f7f2d9;padding-bottom:4px">
                    <div class="col-md-6">
                        <label>
                            <?php _e( 'E-Mail an den Kunden senden', 'cpsmartcrm'); ?><br />
                            <input type="checkbox" class="ruleActions col-sm-2 alignright" id="a_remindToCustomer" name="a_remindToCustomer" />
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label>
                            <?php _e( 'Sende E-Mails an ausgewählte Konten', 'cpsmartcrm'); ?><br />
                            <input type="checkbox" class="ruleActions" id="a_mailToRecipients" name="a_mailToRecipients" />
                        </label>
                    </div>

            </div>
            <div class="row" style="padding:16px">
                <span class="btn btn-success _flat" id="a_saveStep"><?php _e( 'Speichern', 'cpsmartcrm'); ?></span>
                <span class="btn btn-warning _flat _reset" id="a_configreset"><?php _e( 'Zurücksetzen', 'cpsmartcrm'); ?></span>
            </div>
        </div>
        
    </div>
    <input type="hidden" id="a_selectedUsers" name="a_selectedUsers"  class="ruleActions"value=""/>
    <input type="hidden" id="a_selectedGroups" name="a_selectedGroups"  class="ruleActions"value=""/>
    <input type="submit"  id="submit_a_form" style="display:none"/>
    <input type="reset"  id="reset_a_form" style="display:none"/>
</form>
