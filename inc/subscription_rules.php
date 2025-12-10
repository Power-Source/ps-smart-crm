<?php
if ( ! defined( 'ABSPATH' ) ) 
	exit;
$inc_dir  = dirname(__FILE__);
global $wpdb;
$table=WPsCRM_TABLE."subscriptionrules";
$sql="SELECT * FROM $table WHERE isActive=1 AND s_specific=0 ORDER BY ID desc";
$query=$wpdb->get_results( $sql );
$num=$wpdb->num_rows;
//echo $num

?>
<script>
	var CRM_users;

    var _rules ='{"activeRules":[';
    <?php
    $index=1;
    foreach( $query as $record){
    if($record->steps=="")
        $steps='[]';
    else
        $steps=$record->steps
    ?>
    _rules += '{"ID": <?php echo $record->ID ?>, "Name": "<?php echo $record->name ?>", "Length": <?php echo $record->length ?>,"Steps":<?php echo $steps ?>}';
    <?php if($index < $num) { ?>
    _rules += ','; <?php } ?>
    <?php
        $index++;
    } ?>
    _rules += ']}';
    sessionStorage.setItem('savedRules', _rules);

</script>
<div class="wrap" style="height:100%!important">
    <h1 class="WPsCRM_plugin_title" style="text-align:center">PS Smart CRM <?php if(! isset($_GET['p'])){ ?><?php } ?></h1>
    <?php include($inc_dir."/crm/c_menu.php")?>
    
    <div class="page-header" style="background-color:lightgrey;margin: 10px 0 20px;border-bottom:none"><span class="crmHelp" data-help="subscription-rules" style="margin-top:8px"></span>
        <h1><?php _e( 'PS Smart CRM-Abonnementregeln', 'cpsmartcrm'); ?><small id="addRule" class="btn _flat" style="margin-left:100px;background-color:#393939;color:#fafafa"><?php _e( 'Regel hinzufügen', 'cpsmartcrm'); ?></small></h1>
    </div>
    <div class="panel panel-default col-md-12" style="border:none">

        <div id="newRule" style="display:none" data-edit="0">
            <h3><?php _e( 'Neue Regel hinzufügen', 'cpsmartcrm'); ?></h3>
            <form id="addNewRule">
                <div class="col-md-4">
                    <label><?php _e( 'Regelname', 'cpsmartcrm'); ?></label><input class="form-control _m col-md-6" type="text" id="newRuleName" name="newRuleName" />
                </div>
                <div class="col-md-4">
                    <label><?php _e( 'Monate Länge', 'cpsmartcrm'); ?></label>
                    <select class="form-control _m _flat" style="width:100px" id="newRuleLength" name="newRuleLength"><option value="0"><?php _e( 'Wählen', 'cpsmartcrm'); ?></option><?php for($k=1;$k<61;$k++){echo '<option value="'.$k.'">'.$k.'</option>'; } ?></select>
                </div>
                <input type="hidden" id="" name="ruleSteps" />
                <span class="btn btn-warning _flat reset" style="margin:30px"><?php _e( 'Zurücksetzen', 'cpsmartcrm'); ?></span>
                <span class="btn btn-success _flat" id="saveNewRule" style="display:none;margin:30px"><?php _e( 'Regel speichern', 'cpsmartcrm'); ?></span>
            </form>
            <hr />
            <div class="row">
                <div class="panel panel-default col-md-9" style="margin-top:30px;display:none" id="newRuleStepContainer">
                    <h3 class="panel-heading"><?php _e( 'Regelschritte', 'cpsmartcrm'); ?><small id="addStep" class="button button-secondary" style="margin-left:100px;background-color:#393939;color:#fafafa"><?php _e( 'Schritt hinzufügen', 'cpsmartcrm'); ?></small></h3>
                    <div id="existingSteps" style="display:none">
                        <h4><?php _e( 'Vorhandene Schritte', 'cpsmartcrm'); ?></h4>
                        <ul>

                        </ul>
                    </div>
                    <div id="addRuleStep" style="display:none" data-step="">

                        <label> <?php _e( 'Tage im Voraus', 'cpsmartcrm'); ?></label><select class="form-control ruleActions" style="width:50px" id="ruleStep" name="ruleStep"><option value=""><?php _e( 'Wählen', 'cpsmartcrm'); ?></option><?php for($k=1;$k<61;$k++){echo '<option value="'.$k.'">'.$k.'</option>'; } ?></select>
                        <h4><?php _e( 'Aktionen', 'cpsmartcrm'); ?>: </h4>
                        <label><?php _e( 'E-Mail an den Kunden senden', 'cpsmartcrm'); ?></label><input type="checkbox" class="ruleActions" id="remindToCustomer" name="remindToCustomer" /> <br /><hr />
                        <div class="row" style="padding-bottom:20px;border-bottom:1px solid #ccc"><div class="col-md-6"><label><?php _e( 'E-Mails an Benutzer senden', 'cpsmartcrm'); ?></label><input class="ruleActions" id="remindToUser" name="remindToUser" /></div><div class="col-md-4"><label><?php _e( 'Im Dashboard veröffentlichen', 'cpsmartcrm'); ?>?</label> <input type="checkbox" class="ruleActions" name="userDashboard" id="userDashboard" /></div></div>
                        <div class="row" style="padding-bottom:20px;border-bottom:1px solid #ccc"><div class="col-md-6"><label><?php _e( 'E-Mails an Benutzer senden', 'cpsmartcrm'); ?></label><input class="ruleActions" id="remindToGroup" name="remindToGroup" /></div><div class="col-md-4"><label><?php _e( 'Im Dashboard veröffentlichen', 'cpsmartcrm'); ?>?</label><input type="checkbox" class="ruleActions" name="groupDashboard" id="groupDashboard" />   </div></div>
                        <input type="hidden" id="selectedUsers" name="selectedUsers" class="ruleActions" value="" />
                        <input type="hidden" id="selectedGroups" name="selectedGroups" class="ruleActions" value="" />
                        <div class="row">
                            <span class="btn btn-success _flat" id="saveStep" onclick="check_form()"><?php _e( 'Schritt speichern', 'cpsmartcrm'); ?></span>
                            <input type="reset" id="configreset" value="Reset" style="display:none">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">

            </div>
        </div>
        <div id="existingRules" style="display:none">
            <div class="_loader"></div>
            <div class="col-md-3"><h3 class="panel-heading"><?php _e( 'Bestehende Regeln', 'cpsmartcrm'); ?></h3></div>
            <div class="col-md-9" ><p style="display:none" class="icon_legend"><b><?php _e( 'Legende', 'cpsmartcrm'); ?>:</b><br /> <i class="glyphicon glyphicon-calendar"></i>=<?php _e( 'Tage im Voraus über den Ablauf informieren', 'cpsmartcrm'); ?>; <i class="glyphicon glyphicon-envelope"></i>=<?php _e( 'E-Mail-Benachrichtigung an Kunden und/oder CRM-Benutzer ausgewählt', 'cpsmartcrm'); ?>; <i class="glyphicon glyphicon-user"></i>=<?php _e( 'Geplant für ausgewählte CRM-Benutzer/-Gruppen', 'cpsmartcrm'); ?>; <i class="glyphicon glyphicon-dashboard"></i>=<?php _e( 'Zur schnellen Erinnerung im Dashboard ausgewählter CRM-Benutzer/-Gruppen veröffentlicht', 'cpsmartcrm'); ?></p></div>
            <ul></ul>

        </div>


    </div>
</div>
<div id="editStep" style="display:none;margin: 0px auto; text-align: center; z-index: 1000; width: 100%; height: 100%; position: absolute; left: 0px; top: 0px; background: url('<?php echo str_replace("inc","",plugin_dir_url( __FILE__ ))?>css/img/bg_w_tr.png');" class="_modal" data-step="">
    <form id="formEditStep">
        <div class="col-lg-10 col-xl-8 col-md-10 panel panel-primary _flat modal_inner" style="border: 1px solid rgb(102, 102, 102); text-align: left; margin: 50px auto; float: none; padding: 0px; top: 168px; background: rgb(255, 255, 255);">
            <div class="panel-heading" style="min-height:90px">
                <h3 class="col-md-6"><div class="crmHelp" data-help="notification-steps"></div><b><?php _e( 'Schritt bearbeiten', 'cpsmartcrm'); ?></b><span style="display:none"></span> <?php _e( 'für Regel', 'cpsmartcrm'); ?>: <span></span></h3>
				<div style="float:right;margin-top:16px" class="col-md-5"></div>
            </div>
            <div class="panel-body" style="padding:20px">

                
                <h2><?php _e('Aktionen','cpsmartcrm')?>: </h2>
                <!--<label>Send mail to customer</label><input type="checkbox" class="ruleActions" id="editRemindToCustomer" name="editRemindToCustomer" /> <br /><hr />-->
               <div class="row" style="padding-bottom:20px;border-bottom:1px solid #ccc">
                   <div class="col-md-6 form-group">
                       <label><?php _e('Tage im Voraus','cpsmartcrm')?>
                       <select class="form-control ruleActions _m _flat" style="width:100px" id="editRuleStep" name="editRuleStep"><option value=""><?php _e('Wählen','cpsmartcrm')?></option><?php for($k=0;$k<31;$k++){echo '<option value="'.$k.'">'.$k.'</option>'; } ?></select>
                        </label>
                   </div>
				</div>
				<div class="row" style="padding-bottom:20px;border-bottom:1px solid #ccc"><div class="col-md-6"><label><?php _e( 'Notify to Users', 'cpsmartcrm'); ?></label><input class="ruleActions" id="editRemindToUser" name="editRemindToUser" /></div><div class="col-md-4"><label><?php _e('Im Benutzer-Dashboard veröffentlichen','cpsmartcrm')?>?</label><br /><input type="checkbox" class="ruleActions" name="editUserDashboard" id="editUserDashboard" /></div></div>
                <div class="row" style="padding-bottom:20px;border-bottom:1px solid #ccc"><div class="col-md-6"><label><?php _e( 'Notify to Groups', 'cpsmartcrm'); ?></label><input class="ruleActions" id="editRemindToGroup" name="editRemindToGroup" /></div><div class="col-md-4"><label><?php _e('Im Gruppen-Dashboard veröffentlichen','cpsmartcrm')?>?</label><br /><input type="checkbox" class="ruleActions" name="editGroupDashboard" id="editGroupDashboard" />   </div></div>
                <div class="row" style="background:#f7f2d9;padding-bottom:4px">
                    <div class="col-md-12 form-group">
                        <div class="col-md-6" style="text-align:right">
                            <label>
                                <?php _e( 'E-Mail an den Kunden senden', 'cpsmartcrm'); ?><br>
                                <input type="checkbox" class="ruleActions col-sm-2 alignright" id="editRemindToCustomer" name="editRemindToCustomer">
                            </label>
                        </div>

                        <div class="col-md-6">
                            <label>
                                <?php _e( 'Sende E-Mails an ausgewählte Empfänger', 'cpsmartcrm'); ?><br>
                                <input type="checkbox" class="ruleActions" id="editMailToRecipients" name="editMailToRecipients">
                            </label>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="editSelectedUsers" name="editSelectedUsers" class="ruleActions" value="" />
                <input type="hidden" id="editSelectedGroups" name="editSelectedGroups" class="ruleActions" value="" />
                <div class="row" style="padding:20px;background:#fafafa">
                    <span class="btn btn-success _flat" id="confirmEditStep" onclick="check_form()"><?php _e( 'Schritt speichern', 'cpsmartcrm'); ?></span>
                    <span class="btn btn-warning _flat reset"><?php _e( 'Zurücksetzen', 'cpsmartcrm'); ?></span>
                </div>

            </div>

        </div>
    </form>
</div>
<div id="editRule" style="display:none;margin: 0 auto; text-align: center; z-index: 1000; width: 100%; height: 100%; background: url('<?php echo str_replace("inc","",plugin_dir_url( __FILE__ ))?>css/img/bg_w_tr.png');position: absolute;left: 0;top:0;padding-top: 100px;" data-rule="" data-step="" class="col-md-9 _modal">
    <div class="col-lg-8 col-xl-6 col-md-8 panel panel-primary _flat modal_inner" style="border: 1px solid rgb(102, 102, 102); text-align: left; margin: 50px auto; float: none; padding: 0px; top: 168px; background: rgb(255, 255, 255);">
        <div class="panel-heading" style="min-height:90px">
            <h3><?php _e( 'Regel bearbeiten', 'cpsmartcrm'); ?> <span></span></h3>
		</div>
        <div class="panel-body" style="padding:20px">
            <form id="formEditRule">
                <label><?php _e( 'Regelname', 'cpsmartcrm'); ?></label><input class="form-control _m" type="text" id="editRuleName" name="editRuleName" />
                <label><?php _e( 'Monate Länge', 'cpsmartcrm'); ?></label><select class="form-control _m _flat" style="width:100px" id="editRuleLength" name="editRuleLength"><option value="0"><?php _e( 'Unlimited', 'cpsmartcrm'); ?></option><?php for($k=1;$k<61;$k++){echo '<option value="'.$k.'">'.$k.'</option>'; } ?></select>
                <div class="row" style="padding:20px;background:#fafafa">
                    <span class="btn btn-success _flat" id="confirmEditRule"><?php _e( 'Regel speichern', 'cpsmartcrm'); ?></span>
                    <span class="btn btn-warning _flat reset"><?php _e( 'Zurücksetzen', 'cpsmartcrm'); ?></span>
                </div>
            </form>
		</div>
    </div>
</div>

<script>

    function check_form() {
        // 1. Tage im Voraus prüfen
        if ($('#editRuleStep').val() === "" || $('#editRuleStep').val() === null) {
            new Audio("<?php echo WPsCRM_URL?>inc/audio/double-alert-2.mp3").play();
            alert("<?php _e('Du solltest Tage im Voraus auswählen','cpsmartcrm')?>");
            return false;
        }
        // 2. Mindestens ein Benutzer oder eine Gruppe prüfen
        var users = $('#editRemindToUser').val();
        var groups = $('#editRemindToGroup').val();
        if ((!users || users.length === 0) && (!groups || groups.length === 0)) {
            new Audio("<?php echo WPsCRM_URL?>inc/audio/double-alert-2.mp3").play();
            alert("<?php _e('Du solltest mindestens einen Benutzer oder eine Gruppe auswählen','cpsmartcrm')?>");
            return false;
        }

        // Wenn alles ok, wie gehabt weiter:
        jQuery('._loader').show();
        var currentStep = jQuery('#formEditStep').serializeObject();
        currentStep.ID = jQuery('#editStep h3 span:last').text();
        var stepID = jQuery('#editStep h3 span:first').text();
        console.log(currentStep);

            var _html = "";
            var s = "[";
            var jQuerycount = 0;
            _html += "<li class=\"sub_rule widget\" id=\"rule_" + currentStep.ID + "-step_" + stepID + "\">- <i class=\"fa fa-calendar\"></i><?php _e( "Tage im Voraus", "cpsmartcrm"); ?>: <span class=\"days\">" + currentStep.editRuleStep + "</span> - ";
            if (currentStep.editRemindToCustomer == "on")
            	_html +="<?php _e( "Sende eine E-Mail an den Kunden", "cpsmartcrm"); ?>: <span class=\"_customer\">on</span> - ";
            else
            	_html += "<?php _e( "E-Mail an den Kunden", "cpsmartcrm"); ?>=<span class=\"_customer\">off</span> - ";
            _html += "<?php _e( "Benutzer benachrichtigen", "cpsmartcrm"); ?>:<span class=\"_users\">" + currentStep.editSelectedUsers + "</span>";
            if (currentStep.editUserDashboard == "on")
            	_html += " - <?php _e( "Dashboard-Benutzerbenachrichtigung", "cpsmartcrm"); ?>=<span class=\"dashUser\">on</span> - ";
            else
            	_html += " - <?php _e( "Dashboard-Benutzerbenachrichtigung", "cpsmartcrm"); ?>=<span class=\"dashUser\">off</span> - ";
            _html += "<?php _e( "Gruppen benachrichtigen", "cpsmartcrm"); ?>: <span class=\"_groups\">" + currentStep.editSelectedGroups + "</span>";
            if (currentStep.editGroupDashboard == "on")
            	_html += " - <?php _e( "Dashboard-Gruppenbenachrichtigung", "cpsmartcrm"); ?>=<span class=\"dashGroup\">on</span>";
            else
            	_html += " - <?php _e( "Dashboard-Gruppenbenachrichtigung", "cpsmartcrm"); ?>=<span class=\"dashGroup\">off</span>";
            if (currentStep.editMailToRecipients == "on")
            	_html += " - <?php _e( "E-Mail an Empfänger", "cpsmartcrm"); ?>=<span class=\"emailRecipients\">on</span> ";
            else
            	_html += " - <?php _e( "E-Mail an Empfänger", "cpsmartcrm"); ?>=<span class=\"emailRecipients\">off</span>";
            _html += " <span class=\"editSavedStep btn btn-info btn-sm _flat\" data-rule=\"" + currentStep.ID + "\" data-step=\"" + stepID + "\"><?php _e( "Bearbeiten", "cpsmartcrm"); ?> &raquo;</span> <span class=\"deleteSavedStep btn btn-danger btn-sm _flat\" data-rule=\"" + currentStep.ID + "\" data-step=\"" + stepID + "\"> <?php _e( "Löschen", "cpsmartcrm"); ?> X </span><input type=\"hidden\"  value=\"" + encodeURIComponent(currentStep) + "\"/></li>";

            jQuery("#rule_" + currentStep.ID + "-step_" + stepID).remove();
            jQuery("#rule-" + currentStep.ID).find("ul").append(_html).hide();
            console.log("qui" +_html)
            jQuery('#editStep').hide()
            setTimeout(function () {
            	var rule = jQuery('#existingRules').find('li[id="rule-' + currentStep.ID + '"] ul li');
            	console.log(rule)
            	for (var l = 0; l < rule.length; l++) {

            		s += '{"ruleStep":"' + jQuery(rule[l]).find('.days').text() + '" ,"remindToCustomer":';
            		if (jQuery(rule[l]).find('._customer').text() == "on")
            			s += '"on"';
            		else
            			s += '""';
            		s += ',"selectedUsers":"' + jQuery(rule[l]).find('._users').text() + '"';
            		s += ',"selectedGroups":"' + jQuery(rule[l]).find('._groups').text() + '"';
            		s += ',"userDashboard":';
            		if (jQuery(rule[l]).find('.dashUser').text() == "on")
            			s += '"on"';
            		else
            			s += '""';
            		s += ',"groupDashboard":';
            		if (jQuery(rule[l]).find('.dashGroup').text() == "on")
            			s += '"on"';
            		else
            			s += '""';
            		s += ',"mailToRecipients":';
            		if (jQuery(rule[l]).find('.emailRecipients').text() == "on")
            			s += '"on"';
            		else
            			s += '""';
            		s += '}'
            		jQuerycount++;
            		if (jQuerycount < rule.length)
            			s += ',';
            	}

            	s += ']';

            	//console.log(s)
            	jQuery.ajax({
            		url: ajaxurl,
            		data: {
            			'action': 'WPsCRM_edit_step',
            			'rule': currentStep.ID,
            			'steps': encodeURIComponent(s)
            		},
            		type: "POST",
            		success: function (response) {
            			console.log(response);
            			window.location.reload();
            		}
            	})
            }, 100);

		}

	jQuery(document).ready(function ($) {
		var complete = false;
		var userNames="";
		(function runOnComplete() {
			if (complete) {

				setTimeout(function () {
					$('._users').each(function () {
						var userNames = "";

						var _userNames = $(this).html().split(',');

						for (var _l = 0; _l < _userNames.length; _l++) {
							for (var _k = 0; _k < CRM_users.length; _k++) {
								if (CRM_users[_k]['ID'] == _userNames[_l ]) {
									userNames += CRM_users[_k]['display_name'] + " ";
								}
							}
						}

						$(this).next('._username').html(userNames)
					})
					$(".widget span").each(function () {
						if ($(this).html() == "on")
							$(this).next('b').html('<i class="glyphicon glyphicon-ok-sign" style="color:green"></i>');
						if ($(this).html() == "off")
							$(this).next('b').html('<i class="glyphicon glyphicon-remove-sign" style="color:red"></i>');

					});
					if ($('#existingRules .widget').length) {
						//$('#existingSteps').show();
						$('.icon_legend').show();
					}
				}, 100);
			}
			else {
				setTimeout(runOnComplete, 20);
			}

		})();

		$.ajax({
			url: ajaxurl,
			data: {
				'action': 'WPsCRM_get_CRM_users',
			},
			type: 'POST',
			success: function (result) {
				CRM_users = result;
				complete = true;
			},
			error: function (errorThrown) {
				console.log(errorThrown);
			}
		});

		setTimeout(function () {
			$('._users').each(function () {
				$(this).parent().find('._username').html('<img src=\"<?php echo plugins_url( 'css/img/loading-image.gif', str_replace("/inc","",__FILE__ ) )?>\" style=\"transform:scale(.5)\">');
			});
		}, 100);


    if ($('#existingRules ul li').length)
        $('#existingRules').show();
    
    //if ($('#newRuleName').val() != "")
    //    $('#saveStep').show();
    $('#addRule').on('click', function () {
        $('#newRule').fadeIn('fast');
        $(this).hide()

    })
    $('.reset').on('click', function () {
        $('._modal').hide();
        $('form')[0].reset();
        $('form')[1].reset();
        $('#newRule').hide();
        $('#addRule').show();
    })

    $('#existingSteps').on('click', '.editStep', function () {
        var _text = "<h4 class=\"editTitle\">Editing step" + $(this).attr('data-edit') + "</h4>";
        $('#addRuleStep').attr('data-edit', $(this).attr('data-edit'));
        $('#addRuleStep').show().prepend(_text);
        var _obj = decodeURIComponent($(this).parent().find('input').val())
        _obj = JSON.parse(_obj);
        $('#ruleStep').val(_obj.ruleStep);
        if (_obj.remindToCustomer == "on")
            $('#remindToCustomer').attr('checked', 'checked')
        else
            $('#remindToCustomer').prop('checked', false);

        // Select2 statt Kendo
        if (_obj.selectedUsers && _obj.selectedUsers.length) {
            var users = _obj.selectedUsers.split(",");
            $("#remindToUser").val(users).trigger('change');
        } else {
            $("#remindToUser").val(null).trigger('change');
        }
        if (_obj.selectedGroups && _obj.selectedGroups.length) {
            var groups = _obj.selectedGroups.split(",");
            $("#remindToGroup").val(groups).trigger('change');
        } else {
            $("#remindToGroup").val(null).trigger('change');
        }
        if (_obj.userDashboard == "on")
            $('#userDashboard').attr('checked', 'checked')
        else
            $('#userDashboard').prop('checked', false);
        if (_obj.groupDashboard == "on")
            $('#groupDashboard').attr('checked', 'checked')
        else
            $('#groupDashboard').prop('checked', false);
    });

    $('#existingSteps').on('click', '.deleteStep', function () {
        $(this).parent().remove();
    })
    $('#existingRules').on('click', '.editRule', function (e) {
		var position = $(e.target).offset();
        //$('#newRule h3:first').text('Edit Rule');
        $('#editRule h3 span').text($(this).attr('data-id').replace('rule-', ''))
        $('#editRule').attr('data-rule', $(this).attr('data-id'));
        $('#editRule').show();
		$('.modal_inner').animate({
            'top': position.top - 420 + 'px',
        }, 800);
        fillRuleForm($(this).attr('data-id').replace('rule-', ''), _rules);
    });
/*
*salva nuova rule
*/
    $('#saveNewRule').on('click', function () {
    	jQuery('._loader').show();
        var newRule=$('#addNewRule').serializeObject();
        $('#newRule').hide();
        $.ajax({
            url: ajaxurl,
            data: {
            	'action': 'WPsCRM_add_rule',
                'rule': newRule

            },
            type: "POST",
            success: function (response) {
                //console.log(response);
                window.location.reload();
            }

        })
    })
/*
*cancella la rule
*/
    $('#existingRules').on('click', '.deleteRule', function () {
        var ruleToDelete = $(this).attr('data-id').replace('rule-', '');
        var sure = new noty({
        	text: "<?php _e('Bitte beachte, dass das Löschen einer bestehenden Regel zu Problemen bei der Rechnungserstellung führen kann, wenn Du Produkte und Dienstleistungen hast, die diese bereits verwenden: Überprüfe unbedingt die fehlenden Regeln und weise ihnen gegebenenfalls eine neue Regel zu','cpsmartcrm') ?>",
        	type: 'warning',
        	dismissQueue: true,
        	layout: 'center',
        	theme: 'defaultTheme',
        	buttons: [
                {
                	addClass: 'btn btn-success _flat', text: '<?php _e('Löschen','cpsmartcrm')?>', onClick: function ($noty) {
                		jQuery('._loader').show();
                		$.ajax({
                			url: ajaxurl,
                			data: {
                				'action': 'WPsCRM_delete_rule',
                				'rule': ruleToDelete
                			},
                			type: "POST",
                			success: function (response) {
                				//console.log(response);
                				window.location.reload();
                			}

                		})
                		$noty.close();
                		noty({ dismissQueue: true, force: true, layout: layout, theme: 'defaultTheme', text: '<?php _e('Regel gelöscht','cpsmartcrm')?>', type: 'success' });
                	}
                },
                {
                	addClass: 'btn btn-danger _flat', text: '<?php _e('Reset','cpsmartcrm')?>', onClick: function ($noty) {
                		$noty.close();
                		//noty({ dismissQueue: true, force: true, layout: layout, theme: 'defaultTheme', text: 'You clicked "Cancel" button', type: 'error' });
                	}
                }
        	]
        });

        //if (confirm('delete rule' + ruleToDelete + '?')) {
        //    $.ajax({
        //        url: ajaxurl,
        //        data: {
        //            'action': 'CRM_delete_rule',
        //            'rule': ruleToDelete
        //        },
        //        type: "POST",
        //        success: function (response) {
        //            console.log(response);
        //            window.location.reload();
        //        }

        //    })
        //}

    });
        /*
        *edit rule
        */
		$('#confirmEditRule').on('click', function () {
			jQuery('._loader').show();
            var currentRule = $('#formEditRule').serializeObject();
            currentRule.ID = $('#editRule h3 span').text();
            $('#editRule').hide();
            $.ajax({
                url: ajaxurl,
                data: {
                	'action': 'WPsCRM_edit_rule',
                    'obj': currentRule
                },
                type: "POST",
                success: function (response) {

                    window.location.reload();
                }
            })
        })
/*
*cancella step
*/
		$('#existingRules').on('click', '.deleteSavedStep', function () {
			jQuery('._loader').show();
        var stepToDelete = $(this).attr('data-step');
        var ruleForStep = $(this).attr('data-rule');
        var s = '[';
        var $count = 0;

        if (confirm('delete step?')) {
            $(this).parent().parent().remove();
            var rule = $('#existingRules').find('li[id="rule-' + ruleForStep + '"] ul li');
            for (var l = 0; l < rule.length; l++) {

                s += '{"ruleStep":"' + $(rule[l]).find('.days').text() + '" ,"remindToCustomer":';
                if ($(rule[l]).find('._customer').text() == "on")
                    s += '"on"';
                else
                    s += '""';
                s += ',"selectedUsers":"' + $(rule[l]).find('._users').text() + '"';
                s += ',"selectedGroups":"' + $(rule[l]).find('._groups').text() + '"';
                s += ',"userDashboard":';
                if ($(rule[l]).find('.dashUser').text() == "on")
                    s += '"on"';
                else
                    s += '""';
                s += ',"groupDashboard":';
                if ($(rule[l]).find('.dashGroup').text() == "on")
                    s += '"on"';
                else
                    s += '""';
                s += ',"emailRecipients":';
                if ($(rule[l]).find('.dashGroup').text() == "on")
                    s += '"on"';
                else
                    s += '""';
                s += '}'
                $count++;
                if ($count < rule.length)
                    s += ',';
            }

            s += ']';
            console.log(s);
            $.ajax({
                url: ajaxurl,
                data: {
                	'action': 'WPsCRM_edit_step',
                    'rule': ruleForStep,
                    'steps': encodeURIComponent(s)

                },
                type: "POST",
                success: function (response) {
                    console.log(response);
                    window.location.reload();
                }

            })
        }

    });
    /*
    *open and populate step editor
    */
    $('#existingRules').on('click', '.editSavedStep', function (e) {
        var position = $(e.target).offset();
        $('#editStep h3 b').text('<?php _e('Edit step','cpsmartcrm') ?>');
        $('#editStep h3 span:first').text($(this).attr('data-step'))
        $('#editStep h3 span:last').text($(this).attr('data-rule'));
        $('#addRuleStep').attr('data-edit', $(this).attr('data-edit'));

        $('#editStep').show();
        $('.modal_inner').animate({
            'top': position.top - 420 + 'px',
        }, 1000);
        var _obj = decodeURIComponent($(this).parent().find('input').val())
        _obj = JSON.parse(_obj);
        $('#editRuleStep').val(_obj.ruleStep);
        if (_obj.remindToCustomer == "on")
            $('#editRemindToCustomer').attr('checked', 'checked')
        else
            $('#editRemindToCustomer').prop('checked', false);

        // Select2 MultiSelects setzen
        if (_obj.selectedUsers && _obj.selectedUsers.length) {
            var users = _obj.selectedUsers.split(",");
            $("#editRemindToUser").val(users).trigger('change');
        } else {
            $("#editRemindToUser").val(null).trigger('change');
        }
        if (_obj.selectedGroups && _obj.selectedGroups.length) {
            var groups = _obj.selectedGroups.split(",");
            $("#editRemindToGroup").val(groups).trigger('change');
        } else {
            $("#editRemindToGroup").val(null).trigger('change');
        }

        if (_obj.userDashboard == "on")
            $('#editUserDashboard').attr('checked', 'checked')
        else
            $('#editUserDashboard').prop('checked', false);
        if (_obj.groupDashboard == "on")
            $('#editGroupDashboard').attr('checked', 'checked')
        else
            $('#editGroupDashboard').prop('checked', false);
        if (_obj.mailToRecipients == "on")
            $('#editMailToRecipients').attr('checked', 'checked')
        else
            $('#editMailToRecipients').prop('checked', false);
    });
    /*
    *open  step editor for new step
    */
    $('#existingRules').on('click', '.addStep', function (e) {
        var position = $(e.target).offset();
        $('#editStep h3 b').text("<?php _e('New Notification Step','cpsmartcrm')?>")
        $('#editStep h3 span:first').text($(this).attr('data-step'))
        $('#editStep h3 span:last').text($(this).attr('data-rule'));
        $('#addRuleStep').attr('data-edit', $(this).attr('data-edit'));
        $('#editStep').show();
        $('.modal_inner').animate({
            'top': position.top - 420 + 'px',
        }, 1000);
    })

    $('#newRuleName').on('input', function () {
        if ($(this).val() != "" && $('#newRuleLength').val() !=0 )
            $('#saveNewRule').show();
        else
            $('#saveNewRule').hide();
    })
    $('#newRuleLength').on('change', function () {
        if ($(this).val() != 0 && $('#newRuleName').val() !="")
            $('#saveNewRule').show();
        else
            $('#saveNewRule').hide();
    })

    function loadUsers() {
        $.ajax({
            url: ajaxurl,
            data: { 'action': 'WPsCRM_get_CRM_users' },
            success: function (result) {
                var users = [];
                if (Array.isArray(result)) {
                    users = result.map(function(user) {
                        return { id: user.ID, text: user.display_name };
                    });
                }
                $("#remindToUser, #editRemindToUser").select2({
                    data: users,
                    placeholder: "<?php _e('Benutzer wählen','cpsmartcrm')?>...",
                    width: '100%',
                    multiple: true
                });
            }
        });
    }
    function loadGroups() {
        $.ajax({
            url: ajaxurl,
            data: { 'action': 'WPsCRM_get_registered_roles' },
            success: function (result) {
                var groups = [];
                if (result && result.roles) {
                    groups = result.roles.map(function(role) {
                        return { id: role.name, text: role.name };
                    });
                }
                $("#remindToGroup, #editRemindToGroup").select2({
                    data: groups,
                    placeholder: "<?php _e('Rolle auswählen','cpsmartcrm')?>...",
                    width: '100%',
                    multiple: true
                });
            }
        });
    }
    loadUsers();
    loadGroups();

    // Synchronisieren der Hidden-Felder
    $("#remindToUser").on('change', function () {
        $('#selectedUsers').val($(this).val());
    });
    $("#remindToGroup").on('change', function () {
        $('#selectedGroups').val($(this).val());
    });
    $("#editRemindToUser").on('change', function () {
        $('#editSelectedUsers').val($(this).val());
    });
    $("#editRemindToGroup").on('change', function () {
        $('#editSelectedGroups').val($(this).val());
    });
/*
*disegna la'elenco delle rule completo
*/
function drawRules(rules) {

    rules = JSON.parse(rules);
    rules = rules.activeRules;
	var userNames = "";
	var _html = "";
	for (var i = 0; i < rules.length; i++) {

    	_html += '<li id="rule-' + rules[i].ID + '"><div class=\"col-md-12\"><h4 style=\"width:100%;float:left;border-bottom:1px solid lavender;padding-bottom:4px\"><?php _e( "Abonnementregel", "cpsmartcrm");?>: ' + rules[i].Name + '<small> - <?php _e( "Länge", "cpsmartcrm");?>: ' + rules[i].Length + ' <?php _e( "Monate", "cpsmartcrm");?></small><b class="deleteRule btn btn-danger _flat" style="margin:0 40px" data-id="rule-' + rules[i].ID + '"><?php _e( "Löschen", "cpsmartcrm");?> X</b> <b class="editRule btn btn-info _flat" data-id="rule-' + rules[i].ID + '"><?php _e( "Bearbeiten", "cpsmartcrm");?> &raquo;</b></h4></div>\n\
			<div class=\"col-md-12\"> <h4><strong><?php _e( "Benachrichtigungsschritte", "cpsmartcrm");?>:</strong></h4></div><div class=\"row\"><ul class=\"list_items\"  style=\"display: inline-flex;\">';
        for(var k=0;k<rules[i].Steps.length;k++)
        {
            var _users = rules[i].Steps[k].selectedUsers;
            var indexing = rules[i].Steps[k].ruleStep;

            _html += "<li class=\"widget\" style=\"\" id=\"rule_" + rules[i].ID + "-step_"+ k +"\" data-position=\""+ indexing +"\"><span class=\"col-md-12\">  <i class=\"glyphicon glyphicon-calendar\"></i> <?php _e( "Tage im Voraus", "cpsmartcrm"); ?>: <span class=\"days\">" + rules[i].Steps[k].ruleStep + "</span><br/> ";
            if (rules[i].Steps[k].remindToCustomer == "on")
            	_html += "<i class=\"glyphicon glyphicon-envelope\"></i> <?php _e( "E-Mail an den Kunden", "cpsmartcrm");?>: <span class=\"_customer\" style=\"display:none\">on</span><b></b><hr />";
            else
            	_html += "<div style=\"text-decoration:line-through\"><i class=\"glyphicon glyphicon-envelope\"></i> <?php _e( "E-Mail an den Kunden", "cpsmartcrm"); ?>: <span class=\"_customer\"  style=\"display:none\">off</span><b></b></div><hr />";
			if(_users !="")
				_html += "<i class=\"glyphicon glyphicon-user\"></i> <?php _e( "Benutzer benachrichtigen", "cpsmartcrm"); ?>: <span class=\"_users\" style=\"display:none\">" + _users + "</span><span class=\"_username\" ></span><br/>";
			else
				_html += "<div style=\"text-decoration:line-through\"><i class=\"glyphicon glyphicon-user\"></i> <?php _e( "Benutzer benachrichtigen", "cpsmartcrm"); ?>: <span class=\"_users\" style=\"display:none\">" + _users + "</span><span class=\"_username\" ></span><b></b></div>";
            if (rules[i].Steps[k].userDashboard == "on")
            	_html += "<i class=\"glyphicon glyphicon-dashboard\"></i> <?php _e( "Benachrichtigung für Dashboard-Benutzer", "cpsmartcrm"); ?>: <span class=\"dashUser\" style=\"display:none\">on</span><br/>";
            else
            	_html += "<div style=\"text-decoration:line-through\"><i class=\"glyphicon glyphicon-dashboard\"></i> <?php _e( "Benachrichtigung für Dashboard-Benutzer", "cpsmartcrm"); ?>: <span class=\"dashUser\" style=\"display:none\">off</span><b></b></div>";
        	if (rules[i].Steps[k].selectedGroups != "")
				_html += "<i class=\"glyphicon glyphicon-user\"></i><i class=\"glyphicon glyphicon-user\"></i>  <?php _e( "Gruppen benachrichtigen", "cpsmartcrm"); ?>: <span class=\"_groups\">" + rules[i].Steps[k].selectedGroups + "</span><br/>";
			else
				_html += "<div style=\"text-decoration:line-through\"><i class=\"glyphicon glyphicon-user\"></i><i class=\"glyphicon glyphicon-user\"></i>  <?php _e( "Gruppen benachrichtigen", "cpsmartcrm"); ?>:<span class=\"_groups\">" + rules[i].Steps[k].selectedGroups + "</span></div>";
            if (rules[i].Steps[k].groupDashboard == "on")
            	_html += "<i class=\"glyphicon glyphicon-dashboard\"></i> <?php _e( "Dashboard-Gruppenbenachrichtigung", "cpsmartcrm"); ?>: <span class=\"dashGroup\" style=\"display:none\">on</span><b></b><br/>";
            else
            	_html += "<div style=\"text-decoration:line-through\"><i class=\"glyphicon glyphicon-dashboard\"></i> <?php _e( "Dashboard-Gruppenbenachrichtigung", "cpsmartcrm"); ?>: <span class=\"dashGroup\" style=\"display:none\">off</span><b></b></div>";

            if (rules[i].Steps[k].mailToRecipients == "on")
            	_html += "<i class=\"glyphicon glyphicon-envelope\"></i> <?php _e( "Email Benachrichtigung", "cpsmartcrm"); ?>: <span class=\"emailRecipients\" style=\"display:none\">on</span><b></b><br/>";
            else
            	_html += "<div style=\"text-decoration:line-through\"><i class=\"glyphicon glyphicon-envelope\"></i> <?php _e( "Email Benachrichtigung", "cpsmartcrm"); ?>: <span class=\"emailRecipients\" style=\"display:none\">off</span><b></b></div>";

            _html += " <span class=\"editSavedStep btn btn-info btn-sm _flat\" data-rule=\"" + rules[i].ID + "\" data-step=\"" + k + "\"><?php _e( "Bearbeiten", "cpsmartcrm"); ?> &raquo;</span> <span class=\"deleteSavedStep btn btn-danger btn-sm _flat\" data-rule=\"" + rules[i].ID + "\" data-step=\"" + k + "\"> <?php _e( "Löschen", "cpsmartcrm"); ?> X </span><input type=\"hidden\"  value=\"" + encodeURIComponent(JSON.stringify(rules[i].Steps[k])) + "\"/></span></li>";
        }
        _html += "</ul></div>"
        _html += "<div class=\"col-md-12\"><small class=\"btn _flat addStep\" style=\"background-color:#393939;color:#fafafa;margin-top:24px\" data-rule=\"" + rules[i].ID + "\" data-step=\"" + k + "\"><?php _e( "Schritt hinzufügen", "cpsmartcrm"); ?></small></div>";
		_html += "</li>";

    }

			$('#existingRules ul').append(_html);
			setTimeout(function () {
				$(".list_items").each(function () {
					var widget=$(this).find('.widget');
					widget.sort(sort_li).appendTo($(this).closest('.list_items'));
				})
			},20)

    if ($('#existingRules ul li').length)
        $('#existingRules').show();
}

/*
*compila il form per l'editing della rule
*/
function fillRuleForm(id, rules) {
    rules = JSON.parse(rules);
    rules = rules.activeRules;
    for (var i = 0; i < rules.length; i++) {
        if (rules[i].ID == id) {
            var singleRule = {};
            singleRule.Name = rules[i].Name;
            singleRule.Length = rules[i].Length;
            singleRule.Steps = rules[i].Steps;
            break;
        }
    }
    //console.log(singleRule);
    $('#editRuleName').val(singleRule.Name);
    $('#editRuleLength').val(singleRule.Length);
}

function getRule(id,rules) {
    rules = JSON.parse(rules);
    rules = rules.activeRules;
    for (var i = 0; i < rules.length; i++) {
        if(rules[i].ID==id)
        {
            var singleRule = {};
            singleRule.Name = rules[i].Name;
            singleRule.Length = rules[i].Length;
            singleRule.Steps = rules[i].Steps;
        }

        break;
        return(singleRule);
        }
}

drawRules(_rules);

});
</script>

<style>
        #existingRules li {
            border: 1px solid gold;
            border-radius: 2px;
            padding: 10px;
			float:left;
			width:100%
        }
		#existingRules li.widget {
            border: 1px solid #ccc;
			margin-left:20px;
			padding:10px 4px

        }

        .sub_rule {
            border-radius: 0 !important;
            border: none !important;
            padding: 6px !important;
            background-color: #d0e6f2;
            margin-bottom: 8px;
            line-height: 2.2em;
            min-height: 5em;
        }

        .editSavedStep, .deleteSavedStep {
            cursor: pointer;
            padding: 2px 8px;
            margin-right: 7px;
            /*margin-left: 50px;*/
            border: 1px solid #ccc;
            
            float: right;
			    margin-top: 6px;
    opacity: 0.6;
    color: black;
    font-weight: bold;
        }
	.days{color:#fafafa;font-weight:bold;color:cornflowerblue}
	._users,._groups,._username{font-size:smaller;font-style:italic}
	.widget .glyphicon{margin:6px 2px}
	._loader{
	background: rgba(255,255,255,.75) url(<?php echo str_replace("/inc/crm","",dirname(plugin_dir_url(plugin_basename( __FILE__ ) ) ) )?>/css/img/loading-image.gif);
    background-repeat: no-repeat;
    background-position: center center;
    width: 100%;
    height: 100%;
    position: absolute;
    display: block;
    float: left;
    z-index: 100;
	display:none
	}
</style>

