<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	$import_nonce= wp_create_nonce( "import_customers" );
	$revert_nonce= wp_create_nonce( "revert_import" );
?>
<script type="text/javascript">

jQuery(document).ready(function ($) {
//	$("#file_csv").kendoUpload();
	$("#btn_save").on('click', function () {
		var formData = new FormData();
		var file = jQuery(document).find('input[type="file"]');
		var lists=[];
		$('.lists').each(function() {
			var list = $(this);
			if(list.is(':checked')) {
				lists[lists.length]=list.attr("id");
			}
		});
		if($("#override").is(':checked'))
            var chk="1";
        else
            var chk="0";
		if($("#newsletter_flag").is(':checked'))
            var newsletter_flag="1";
        else
            var newsletter_flag="0";
		var csv_file = file[0].files[0];
		formData.append("file", csv_file);
		formData.append("newsletter_flag", newsletter_flag);
		formData.append("override", chk);
		formData.append("lists", lists);
		formData.append('action', 'WPsCRM_import_customers');
		formData.append('security','<?php echo $import_nonce ?>');

		//console.log(formData);
            $.ajax({
                url: ajaxurl,
                data: formData,
                type: "POST",
                contentType: false,
                processData: false,
                success: function (response) {
                    console.log(response);
//                    var err_obj = JSON.parse(response);
//                    var err_obj = $.parseJSON(response);
                      var obj=response;
                    if (obj)
                    {
                        if (obj.mess_id==2)
                        {
                            var str_errore="<?php _e('Beim Import sind folgende Fehler aufgetreten','cpsmartcrm')?>:<br />";
                            var err_obj=obj.errore;    
                            for(var i = 0; i < err_obj.length; i++) {
                                str_errore+="#"+err_obj[i].riga+": "+err_obj[i].errore+"<br />";
    }
                            $("#err").html(str_errore);
                        }
                        if (obj.mess_id==1 || obj.mess_id==3)
                        {
                            $("#err").html(obj.msg);
                        }
                    }
                }
            })
    });

});



</script>
<div id="err"></div>
<form name="form_insert" method="post" class="form" role="form" enctype="multipart/form-data">
    <div style="margin-top:14px;background-color: #fafafa;" class="col-md-12">

    <!-- TAB 1 -->
 
        <div id="d_anagrafica">
        <h4 class="page-header" style="background:#e2e2e2;padding:15px"><?php _e('Importiere Kunden aus einer CSV-Datei','cpsmartcrm')?><span class="crmHelp crmHelp-dark" data-help="import-customers"></span></h4>
        <p><?php _e('Wähle eine CVS-Datei aus, um Deine Kunden zu importieren. Beachte dass die erste Zeile der Datei die folgenden Header enthalten muss (genau so benannt), wie sie sind:','cpsmartcrm')?></p>
            <label> firstname ; lastname ; company ; address ; zip ; city ; province ; vat ; taxcode ; phone1 ; mobile ; fax ; email ; website ; skype; birthdate ; birthplace ; country ; invoiceable ; type ; categories ; interests ; origins</label>
		<p>
			<?php _e('Die Felder müssen durch Semikolon (;) getrennt werden..','cpsmartcrm')?>
		</p>
        <p><?php _e('Die einzigen Pflichtfelder sind: "Vorname"+"Nachname" oder alternativ "Firma".','cpsmartcrm')?></p>
        <p><?php _e('Das Geburtsdatum muss im Format jjjj-mm-tt vorliegen. fakturierbar kann 1 oder 0 sein (für Ja/Nein); Typ kann 1 oder 2 sein (für privat/geschäftlich); Kategorien, Interessen und Herkunft können durch Kommas getrennte Zeichenfolgen enthalten (siehe Beispieldatei) ','cpsmartcrm')?></p>
        <?php
			is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
			if ( in_array( 'newsletter/plugin.php', apply_filters( 'active_plugins', $filter) ) ) 
			{?>
		<p><?php _e('Optional kannst Du Newsletter-Abonnenten in eine oder mehrere Listen importieren. Zu diesem Zweck musst Du Deiner CSV-Datei eine Spalte mit dem Namen "Newsletter" hinzufügen (mit den Werten 1/0) und optional die Liste(n) markieren, in die Du importieren möchtest','cpsmartcrm')?></p>
		<?php }?>
		<p><?php _e('Ein Bericht zeigt Deine Importfehler. Fehlerprotokolle findest Du unter wp-content/plugins/ps-smart-crm/logs','cpsmartcrm')?></p>
        <p style="color:red">***<?php _e('Wenn Du die Option "Überschreiben" auswählst, werden die vorhandenen Datensätze gelöscht. Sei vorsichtig, wenn Du Operationen an Deinen Kunden durchgeführt hast: Alle zuvor gespeicherten Aktivitäten verlieren ihre Kundenzuordnung.','cpsmartcrm')?></p>
			<p></p>
			<p><a href="<?php echo WPsCRM_URL."examples/import.csv"?>"><?php _e('Lade eine CSV-Beispieldatei herunter','cpsmartcrm')?></a></p>
            <div class="row form-group">
                <label class="col-sm-3 control-label"><?php _e('CSV hochladen','cpsmartcrm')?> *</label>
                <div class="col-sm-4">
                    <input type="file" name="file_csv" id='file_csv' class="_m form-control">
                </div>
            </div>
            <div class="row form-group">
                <label class="col-sm-3 control-label" ><?php _e('Vorhandene Datensätze überschreiben','cpsmartcrm')?> </label>
                <div class="col-sm-4">
                    <input type="checkbox" name="override" value="1" id='override' class="_m form-control">
					<small style="color:red">
						***<?php _e('Achtung: oben lesen','cpsmartcrm')?>
					</small>
                </div>
            </div>
			<?php
			is_multisite() ? $filter=get_blog_option(get_current_blog_id(), 'active_plugins' ) : $filter=get_option('active_plugins' );
			if ( in_array( 'newsletter/plugin.php', apply_filters( 'active_plugins', $filter) ) ) 
			{?>
			<div class="row form-group">
				<label class="col-sm-3 control-label"><?php _e('Im Newsletter einfügen','cpsmartcrm')?>?</label>
				<div class="col-sm-1">
					<input type="checkbox" name="newsletter_flag" id="newsletter_flag" value="1">
				</div>
			</div>
			<?php
			$options_profile = get_option('newsletter_profile');
			echo '<div id="listCheck" style="display:none"><label class="col-sm-3 control-label"><small>'.__('Wähle optional die Liste(n) aus, in die Du Datensätze importieren möchtest','cpsmartcrm').'</small></label><div class="newsletter-preferences-group col-sm-4">';

			for ($i = 1; $i <= 20; $i++) {
				if (empty($options_profile['list_' . $i])) {
					continue;
				}
				?>
				<div class="newsletter-preferences-item">
					<label>
					<input type="checkbox" class="lists" id="list_<?php echo $i?>" name="list_<?php echo $i?>" value="1">
						<?php echo esc_html($options_profile['list_' . $i])?>
					</label>
				</div>
			<?php
			}
			echo '<div style="clear: both"></div></div></div>';
			}
			?>
             <div class="row form-group">
                 <ul class="select-action" style="margin-left:8px">
                    <li class="btn btn-success btn-sm _flat" id="btn_save"><i class="glyphicon glyphicon-import"></i> 
                        <b onClick="return false;"> <?php _e('Importieren','cpsmartcrm')?></b>
                    </li>
                    <li class="btn btn-warning btn-sm _flat"><i class="glyphicon glyphicon-floppy-remove"></i>
						<b onclick="window.location.replace('<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/list.php' )?>');return false;">
							<?php _e('Zurücksetzen','cpsmartcrm')?>
						</b>
                    </li>
                     
                </ul>
		    </div>      
	    </div>

    </div>
<input type="submit"  id="submit_form" style="display:none"/>

</form>
<script>
	jQuery(document).ready(function ($) {
		$('#newsletter_flag').on('click', function () {
			$('#listCheck').toggle()
		})
	})
</script>
<?php 
	do_action('WPsCRM_users_import',$import_nonce, $revert_nonce);
?>
