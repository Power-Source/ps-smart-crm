<?php if ( ! defined( 'ABSPATH' ) ) exit;
is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
if ( in_array( 'wp-smart-crm-advanced/wp-smart-crm-advanced.php', apply_filters( 'active_plugins', $filter) ) ) {
	$p="dashboard-scheduler.php";
}
else{
	$p="dashboard.php";
}
is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
    $agent_obj=new AGsCRM_agent();
    $privileges=$agent_obj->getAllPrivileges();
}
else 
    $privileges=null;
//var_dump($privileges);
$document_options=get_option('CRM_documents_settings');

?>
<script>
<?php
if ( in_array( 'wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters( 'active_plugins', $filter) ) ) {
    $agent_obj=new AGsCRM_agent();
?>
    var privileges = <?php echo json_encode($agent_obj->getAllPrivileges()) ?>;
<?php
} else{?>
    var privileges=null;
<?php } ?>
    </script>
<div class="wrap">
<h1 style="text-align:center">PS Smart CRM<?php if(! isset($_GET['p'])){ ?><?php } ?></h1>
		<?php include("c_menu.php")?>
	<?php
    if(isset($_GET['p']))
		$p=$_GET['p'];

    include(plugin_dir_path(__FILE__ ))."$p";
    echo '<small style="text-align:center;top:30px;position:relative">ENTWICKELT VON PSOURCE <a href="https://cp-psource.github.io/ps-smart-crm">https://cp-psource.github.io/ps-smart-crm</a></small></div>';
	?>

<!--CUSTOM POPUP EDITOR TEMPLATE-->
<script type="text/template" id="customEditor" style="width:960px;height:760px!important">
    <form name="form_insert" style="max-width:940px">
    <section class="eventPopup container-fluid" >
        <div class="row">
            <label class="col-md-1"><?php _e('Kunde','cpsmartcrm') ?></label>
            <div class="col-md-3">
                <select id="customers" name="customers" class="form-control">
                    <option value=""><?php _e('Wählen', 'cpsmartcrm'); ?></option>
                    <!-- Hier dynamisch Kundenoptionen per JS/PHP einfügen -->
                </select>
            </div>
            <label class="col-md-1" for="Title"><?php _e('Betreff','cpsmartcrm') ?></label>
            <div class="col-md-5" style="padding-left:0">
                <input class="form-control col-md-12" name="Title" id="Title" type="text" />
            </div>
        </div>
        <div class="row">
            <label class="col-md-1" for="Start"><?php _e('Start','cpsmartcrm') ?></label>
            <div class="col-md-3">
                <input name="start" id="dateTimeStart" required="required" type="datetime-local" class="form-control" />
            </div>
            <label class="col-md-1" for="End"><?php _e('Ende','cpsmartcrm') ?></label>
            <div class="col-md-5">
                <input name="end" id="dateTimeEnd" required="required" type="datetime-local" class="form-control" />
            </div>
        </div>
        <div class="row">
            <label for="description" class="col-md-1"><?php _e('Beschreibung','cpsmartcrm') ?></label>
            <div class="col-md-11">
                <textarea class="form-control" cols="20" id="description" name="description" rows="2"></textarea>
            </div>
        </div>
        <!--Rules-->
        <div class="col-md-12" style="padding-left:0">
        <h4 class="page-header"><?php _e('Benachrichtigungsregeln','cpsmartcrm')?></h4>
            <div class="row">
                <label for="rulestep" class="col-md-2"><?php _e('Tage im Voraus','cpsmartcrm') ?></label>
                <div class="col-md-2">
                    <select class="form-control col-md-2" id="ruleStep" name="ruleStep">
                        <option value=""><?php _e( 'Wählen', 'cpsmartcrm'); ?></option>
                        <?php for($k=0;$k<61;$k++){echo '<option value="'.$k.'">'.$k.'</option>'.PHP_EOL; } ?>
                    </select>
                </div>
                <label class="col-md-2"><?php _e('E-Mail an den Kunden senden','cpsmartcrm') ?></label>
                <div class="col-md-4"><input type="checkbox" name="remindToCustomer" id="remindToCustomer" /></div>
            </div>
            <div class="row">
                <label class="col-md-4"><?php _e('Sende E-Mails an Empfänger','cpsmartcrm') ?> </label> 
                <div class="col-md-1">
                    <input type="checkbox" name="mailToRecipients" id="mailToRecipients" />
                </div>
                <div class="col-md-4" style="line-height:.8em">
                    <div class="row">
                        <label class="col-sm-8 control-label"><?php _e('Sende auch eine Sofortbenachrichtigung','cpsmartcrm')?></label>
                        <div class="col-md-1">
                            <input type="checkbox" class="ruleActions" id="instantNotification" name="instantNotification" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <small><?php _e('Eine E-Mail wird sofort an alle ausgewählten Benutzer/Gruppen gesendet, wenn die Option „E-Mail an Empfänger senden“ unten aktiv ist','cpsmartcrm');?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <label class="col-md-2" for="users" style="float:left"><?php _e('Benutzer benachrichtigen','cpsmartcrm') ?></label>
                <div class="col-md-3">
                    <select id="users" name="users[]" class="form-control" multiple>
                        <!-- Dynamisch Benutzeroptionen einfügen -->
                    </select>
                </div>
                <label class="col-md-2" for="group" style="float:left"><?php _e('Gruppen benachrichtigen','cpsmartcrm') ?></label>
                <div class="col-md-3">
                    <select id="group" name="group[]" class="form-control" multiple>
                        <!-- Dynamisch Gruppenoptionen einfügen -->
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-12" style="padding-left:0">
        <h4 class="page-header"><?php _e('Ergebnis','cpsmartcrm')?></h4>
        <div class="row" style="padding-left:0">
            <div>
                <label style="float:left;width:30%"><?php _e('Noch zu erledigen','cpsmartcrm') ?><input type="radio" name="status" value="1" /></label>
                <label style="float:left;width:30%"><?php _e('Erledigt','cpsmartcrm') ?><input type="radio" name="status" value="2" /></label>
                <label style="float:left;width:30%"><?php _e('Abgesagt','cpsmartcrm') ?><input type="radio" name="status" value="3" /></label>
            </div>
        </div>
        <div class="row" style="padding-left:0">
            <label for="esito" class="col-md-1"><?php _e('Angeboten','cpsmartcrm') ?></label>
            <div>
                <textarea class="form-control" cols="20" id="esito" name="esito" rows="2"></textarea>
            </div>
        </div>
        </div>
    </section>
    </form>
</script>
<!--/CUSTOM POPUP EDITOR TEMPLATE-->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var startInput = document.getElementById('dateTimeStart');
    var endInput = document.getElementById('dateTimeEnd');
    if(startInput && endInput) {
        startInput.addEventListener('change', function() {
            if (startInput.value) {
                // Datum und Uhrzeit aus dem Startfeld holen
                var startDate = new Date(startInput.value);
                // +1 Stunde
                startDate.setHours(startDate.getHours() + 1);
                // ISO-String für input[type=datetime-local] formatieren
                var local = startDate.toISOString().slice(0,16);
                endInput.value = local;
            }
        });
    }
});
</script>

<style>
	.k-window>div.k-popup-edit-form {
    padding: 6px 0;
}
	.eventPopup .col-md-12{padding-right:0}
    .k-edit-form-container {
        width: 920px !important;
        height: 768px !important;
        min-height: 768px !important;
    }

    #noty_bottomRight_layout_container li, #noty_topRight_layout_container li{
        border-radius: 0 !important
    }
    .mask {
        position: absolute;
        top: 36px;
        left: 0;
        width: 100%;
        height: calc(100% - 36px);
        background-color: rgba(0,0,0,.16);
    }
    .k-window .k-edit-buttons {
    background: #fafafa;
    }
</style>
<style>
		<?php echo isset($document_options['document_custom_css']) ? $document_options['document_custom_css'] : null?>
</style>