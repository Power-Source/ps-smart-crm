<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	$update_nonce= wp_create_nonce( "update_document" );
?>
<script type="text/javascript">
jQuery(document).ready(function ($) {

	var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
	var todayDate = kendo.toString(new Date(), $format, localCulture);
	$("#data_from").kendoDatePicker({
		value: todayDate,
		format: $format,
	})
	$("#data_to").kendoDatePicker({
		value: todayDate,
		format: $format,
	})

    var validator = $("form").kendoValidator({

    }).data("kendoValidator");

    $("form").validate({
        submitHandler: function () {

            var data_from = $("#data_from").val();
            var data_to = $("#data_to").val();
            console.log(data_from);
            $.ajax({
                url: ajaxurl,
                data: {
                	action: 'WPsCRM_register_invoices',
                	data_from: data_from,
                	data_to: data_to,
					security:'<?php echo $update_nonce ?>'
                },
                type: "POST",
                success: function (response) {
                    console.log(response);
                    alert("Fatture registrate con successo");
                    //window.location.reload();
                    //location.href = "?page=smart-crm&p=scheduler/list.php";
                }
            })
        }
    });

    $("#btn_save").on('click', function () {
            //validator.validate();
        $('form').find(':submit').click();

    });

});



</script>
<form name="form_insert" method="post" class="form-horizontal" role="form">
    <div style="margin-top:14px;background-color: #fafafa;" class="col-md-12">

    <!-- TAB 1 -->
 
        <div id="d_anagrafica">


        <h4 class="page-header" style="background:#e2e2e2;padding:15px"><?php _e('Register all invoices between two dates','cpsmartcrm')?><span class="crmHelp crmHelp-dark" data-help="registe-invoces"></span></h4>
            <div class="row form-group">
                <label class="col-sm-1 control-label"><?php _e('From','cpsmartcrm')?> *</label>
                <div class="col-md-6">
                    <input type="text" name="data_from" id='data_from' maxlength='10' size="10" value="" class=" form-control" required validationMessage="<?php _e('You should select a date from','cpsmartcrm')?>">
                </div>
            </div>
            <div class="row form-group">
                <label class="col-sm-1 control-label"><?php _e('To','cpsmartcrm')?> *</label>
                <div class="col-md-6">
                    <input type="text" name="data_to" id='data_to' maxlength='10' size="10" value="" class=" form-control" required validationMessage="<?php _e('You should select a date to','cpsmartcrm')?>">
                </div>
            </div>

             <div class="row form-group">
				 <p><?php _e('WARNING: registered invoices cannot be edited any longer. These invoices exists as.pdf files in the folder: uploads/CRMdocuments','cpsmartcrm')?></p>
                 <ul class="select-action" style="margin-left:8px">
                    <li class="btn btn-success btn-sm _flat" id="btn_save"><i class="glyphicon glyphicon-floppy-disk"></i> 
                        <b onClick="return false;"> <?php _e('Speichern','cpsmartcrm')?></b>
                    </li>
                    <li class="btn btn-warning btn-sm _flat"><i class="glyphicon glyphicon-floppy-remove"></i>
						<b onclick="window.location.replace('<?php echo admin_url( 'admin.php?page=smart-crm&p=scheduler/list.php' )?>');return false;">
							<?php _e('Reset','cpsmartcrm')?>
						</b>
                    </li>
                     
                </ul>
		    </div>      
	    </div>

    </div>
<input type="submit"  id="submit_form" style="display:none"/>

</form>