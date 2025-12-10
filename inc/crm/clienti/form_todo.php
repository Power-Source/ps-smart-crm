<?php
if ( ! defined( 'ABSPATH' ) ) exit;

?>
<form id="new_todo" class="modal_form">
    <div class="col-md-12 panel panel-primary _flat" style="padding:0!important">
        <div class="panel-body" style="padding:20px">
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">
                <div class="col-md-12 form-group">
                    <label class="col-sm-1 control-label"><?php _e( 'Ablauf', 'cpsmartcrm'); ?></label>
                    <div class="col-md-4">
                        <input type="text" name="t_data_scadenza" id='t_data_scadenza' value="<?php if (isset($data_scadenza)) echo $data_scadenza?>">

                    </div>
                    <label class="col-sm-1 control-label"><?php _e('Priorität','cpsmartcrm')?></label>
                    <div class="col-md-4">
                        <?php WPsCRM_priorita()?>
                    </div>
                </div>

            </div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;">
                <div class="col-md-5 form-group">
                    <label><?php _e( 'Betreff', 'cpsmartcrm'); ?> </label>
                    <input type="text" name="t_oggetto" id='t_oggetto' class="form-control _m k-textbox" placeholder="<?php _e('Gib einen Betreff für dieses Element ein','cpsmartcrm')?>">
                </div>

                <div class="col-md-6 form-group">
                    <label><?php _e( 'Anmerkungen', 'cpsmartcrm'); ?></label>
                    <textarea id="t_annotazioni" name="t_annotazioni" class="form-control _m k-textbox _flat" style="height:30px"></textarea>
                </div>
            </div>
            <div class="row col-md-12" style="background:#e2e2e2;padding:1px 10px;margin-bottom:10px">
                <div class="col-md-12"><h2><?php _e( 'Benachrichtigungsregeln für dieses TODO', 'cpsmartcrm'); ?></h2></div>
            </div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">
                <div class="col-md-4 form-group">
                    <label><?php _e( 'Tage im Voraus', 'cpsmartcrm'); ?></label>
                    <select class="form-control _m ruleActions k-dropdown _flat" style="width:100px" id="t_ruleStep" name="t_ruleStep">
                        <option value=""><?php _e( 'Wählen', 'cpsmartcrm'); ?></option>
                        <?php for($k=0;$k<61;$k++){echo '<option value="'.$k.'">'.$k.'</option>'.PHP_EOL; } ?>
                    </select>
                </div>

                <div class="col-sm-7">
                    <label><?php _e('Sende auch eine Sofortbenachrichtigung','cpsmartcrm')?></label>
                    <input type="checkbox" class="ruleActions " id="instantNotification" name="instantNotification" />
                    <br /><small style="line-height:.8em">Eine E-Mail wird sofort an alle ausgewählten Benutzer/Gruppen gesendet, wenn die Option „E-Mail an Empfänger senden“ unten aktiv ist</small>
                </div>
            </div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">
                <div class="col-md-4">
                    <label style="line-height: 1em;"><?php _e( 'Benutzer benachrichtigen', 'cpsmartcrm'); ?></label>
                    <input class="ruleActions" id="t_remindToUser" name="t_remindToUser" />
                </div>
                <div class="col-md-7">
                    <label>
                        <?php _e( 'Im Benutzer-Dashboard veröffentlichen', 'cpsmartcrm'); ?>?<br />
                        <input type="checkbox" class="ruleActions" name="t_userDashboard" id="t_userDashboard" />
                    </label>
                </div>
            </div>
            <div class="row" style="padding-bottom:2px;padding-top:0px;border-bottom:1px solid #ccc">
                <div class="col-md-4">
                    <label style="line-height: 1em;"><?php _e( 'Gruppe benachrichtigen', 'cpsmartcrm'); ?></label>
                    <input class="ruleActions" id="t_remindToGroup" name="t_remindToGroup">
                </div>
                <div class="col-md-7">
                    <label style="line-height: 1em;">
                        <?php _e( 'Im Gruppen-Dashboard veröffentlichen', 'cpsmartcrm'); ?>?<br />
                        <input type="checkbox" class="ruleActions" name="t_groupDashboard" id="t_groupDashboard" />
                    </label>
                </div>
            </div>
            <div class="row" style="background:#f7f2d9;padding-bottom:4px">
                <div class="col-md-4" style="text-align:right; visibility:hidden">
                    <label>
                        <?php _e( 'E-Mail an den Kunden senden', 'cpsmartcrm'); ?><br />
                        <input type="checkbox" class="ruleActions col-sm-2 alignright" id="t_remindToCustomer" name="t_remindToCustomer" disabled />
                    </label>
                </div>
                <div class="col-md-7">
                    <label>
                        <?php _e( 'Sende E-Mails an ausgewählte Empfänger', 'cpsmartcrm'); ?><br />
                        <input type="checkbox" class="ruleActions" id="t_mailToRecipients" name="t_mailToRecipients" />
                    </label>
                </div>
            </div>
            <div class="row" style="padding:16px">
                <span class="btn btn-success _flat" id="t_saveStep"><?php _e( 'Speichern', 'cpsmartcrm'); ?></span>
                <span class="btn btn-warning _flat _reset" id="t_configreset"><?php _e( 'Zurücksetzen', 'cpsmartcrm'); ?></span>
            </div>
        </div>       
    </div>
    <input type="hidden" id="t_selectedUsers" name="t_selectedUsers" class="ruleActions" value="" />
    <input type="hidden" id="t_selectedGroups" name="t_selectedGroups" class="ruleActions" value="" />
    <input type="submit" id="submit_t_form" style="display:none" />
    <input type="reset" id="reset_t_form" style="display:none" />
</form>
