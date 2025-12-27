<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$mail_nonce = wp_create_nonce( "mailToCustomer" );
$print_nonce=wp_create_nonce( "print_document" );

?>
<form id="new_mail" class="modal_form">
	<div class="col-md-12 panel panel-primary _flat" style="padding:0!important">
		<div class="panel-body" style="padding:20px">
			<div class="row">
				<div class="col-md-11 form-group">
					<label>
						<?php _e( 'Betreff', 'cpsmartcrm'); ?>
					</label>
					<input type="text" name="m_oggetto" id='m_oggetto' class="form-control _m k-textbox" placeholder="<?php _e('Gib einen Betreff für diese E-Mail ein','cpsmartcrm')?>" />
				</div>
			</div>
			<div class="row">
				<div class="col-md-11 form-group">
					<label>
						<?php _e( 'Nachricht', 'cpsmartcrm'); ?>
					</label>
					<textarea id="m_messaggio" name="m_messaggio" class="form-control _m k-textbox _flat" style="height:100px"></textarea>
				</div>
			</div>
			<div class="row attachments"></div>
			<div class="row" style="display:none">
				<div class="col-md-3 form-group _flat">
					<label><?php _e( 'Jetzt E-Mail senden', 'cpsmartcrm'); ?> </label><input type="radio" name="sendNow" value="1" checked />
				</div>
				<div class="col-md-3 form-group _flat">
					<label><?php _e( 'Geplante E-Mails', 'cpsmartcrm'); ?></label><input type="radio" name="sendNow" value="0" />
				</div>
				<div class="col-md-4 _schedule" style="display:none">
					<input id="schedule" onkeydown="return false;" /><br />
					<small><?php _e( 'Bitte beachte, dass es zu erheblichen Verzögerungen bei der Genauigkeit der geplanten Sendung kommen kann. Wenn Du eine dringende Sendung benötigst, wähle „Jetzt E-Mail senden“ ', 'cpsmartcrm'); ?></small>
				</div>
			</div>
            <div class="row">
                <div class="col-md-4 form-group _flat">
                    <label><?php _e( 'E-Mail an den Kunden senden', 'cpsmartcrm'); ?> </label>
					<input type="checkbox" id="mailToCustomer" name="mailToCustomer" value="1" checked />
                </div>
                <div class="col-md-4 form-group _flat">
                    <label><?php _e( 'E-Mails an Benutzer senden', 'cpsmartcrm'); ?></label>
					<input type="checkbox" id="mailToUsers" name="mailToUsers" value="1" />
                </div>

            </div>
			<div class="row _users" style="display:none">
                <div class="col-md-4 form-group _flat">
                    <label><?php _e( 'Wähle Benutzer aus', 'cpsmartcrm'); ?> </label>
					<input id="m_users" name="m_users" />
                </div>
                <div class="col-md-4 form-group _flat">
                    <label><?php _e( 'Wähle Gruppen aus', 'cpsmartcrm'); ?></label>
					<input id="m_groups"name="m_groups" />
                </div>
			</div>
			<input type="hidden" id="_attachments" name="_attachments" />
			<div class="row" style="padding:16px">
				<span class="btn btn-success _flat" id="saveMail">
					<?php _e( 'Senden', 'cpsmartcrm'); ?>
				</span>
				<span class="btn btn-warning _flat _reset">
					<?php _e( 'Zurücksetzen', 'cpsmartcrm'); ?>
				</span>
			</div>
		</div>
	</div>
	<input type="reset" id="reset_m_form" style="display:none" />
</form>

