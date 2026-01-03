<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$update_nonce= wp_create_nonce( "update_scheduler" );

$tipo_agenda=$_GET["tipo_agenda"];
$a_table=WPsCRM_TABLE."agenda";
$c_table=WPsCRM_TABLE."kunde";
$s_table=WPsCRM_TABLE."subscriptionrules";
$data_scadenza=date("d-m-Y");

$where="1";
switch ($tipo_agenda)
{
    case 1:
        
        $icon='<i class="glyphicon glyphicon-tag"></i> '.__('NEUES TODO','cpsmartcrm');
        break;
    case 2:
        $icon='<i class="glyphicon glyphicon-pushpin"></i> '.__('NEUER TERMIN','cpsmartcrm');
        break;
    case 3:
        $icon='<i class="glyphicon glyphicon-option-horizontal"></i> '.__('NEUE AKTIVITÄT','cpsmartcrm');
        break;
    default:
        $tipo="";
        break;
}
?>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		var $format = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
