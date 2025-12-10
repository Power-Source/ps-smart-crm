<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$id_invoice=$_GET["id_invoice"];
$options= get_option( "CRM_adv_settings" );
$adv_print=isset($options['advanced_print'])?$options['advanced_print']:0;
$d_table=WPsCRM_TABLE."documenti";
$sql="select registrato, tipo from $d_table where id=$id_invoice"; 
$riga=$wpdb->get_row($sql, ARRAY_A); 
switch ($tipo=$riga["tipo"])
{
        case 1:
                $edit_url=admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&ID='.$id_invoice);
                break;
        case 2:
                $edit_url=admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php&ID='.$id_invoice);
                break;
        case 3:
                $edit_url=admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php&ID='.$id_invoice);

                break;
}
$html1='<iframe id="i_print" name="i_print" width="100%" height="800" src="'.admin_url('admin.php?page=smart-crm&p=documenti/document_print.php&layout=iframe&id_invoice='.$id_invoice).'"></iframe>';
$html2='<div class="col-md-3" style="margin:0">';
if ($riga["registrato"]==0) {
    $html2.='<a href="'.$edit_url.'" target="_parent"><span class="btn _flat btn-info">'.__('Bearbeiten','cpsmartcrm').'</span></a>';
}
$html2.='</div><div class="col-md-9" style="margin:0"><iframe id="i_print" name="i_print" width="100%" height="800" src="'.admin_url('admin.php?page=smart-crm&p=documenti/document_print2.php&id_invoice='.$id_invoice).'"></iframe></div>';
?>
<?php _e('Nutze mehrseitigen Rechnungsdruck', 'cpsmartcrm')?>:
<label><?php _e('NEIN', 'cpsmartcrm')?><input type="radio" name="tipost" id="0" value="0" <?php echo checked( $adv_print, 0, false) ?> /></label>
<label style="margin:0px 30px 5px 30px"><?php _e('JA', 'cpsmartcrm')?><input type="radio" name="tipost" id="1" value="1" <?php echo checked( $adv_print, 1, false) ?> /></label>
<div id="i_container"> 
<?php 
if ($adv_print==0){
    echo $html1;
} 
else {
    echo $html2;
}
?>
 
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('input[type=radio][name=tipost]').change(function() {
            var _html1='<?php echo $html1?>';
            var _html2='<?php echo $html2?>';
            var tipost=this.value;
            if (tipost==0)
                $("#i_container").html(_html1);
                //$("#i_print").attr('src', "<?php echo admin_url('admin.php?page=smart-crm&p=documenti/document_print.php&layout=iframe&id_invoice='.$id_invoice)?>");
            else{
                $("#i_container").html(_html2);
                //$("#i_print").attr('src', "<?php echo admin_url('admin.php?page=smart-crm&p=documenti/document_print2.php&id_invoice='.$id_invoice)?>");
            }
        });
    })
</script>