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

<div class="wrap">
    <h2><?php echo $icon; ?></h2>

    <div id="scheduler-inline-form" data-from="scheduler">
        <?php
        switch ($tipo_agenda) {
            case 1:
                include(dirname(__FILE__).'/../kunde/form_todo.php');
                break;
            case 2:
                include(dirname(__FILE__).'/../kunde/form_appuntamento.php');
                break;
            case 3:
                include(dirname(__FILE__).'/../kunde/form_attivita.php');
                break;
            default:
                echo '<p>'.__('Ungültiger Typ','cpsmartcrm').'</p>';
                break;
        }
        ?>
    </div>
</div>

<!-- Form Scripts (inline mode, kein Modal) -->
<script type="text/javascript">
// Inline-Flag: Skripte sollen ohne PSCRM.Modal arbeiten
window.PSCRM_INLINE_FORM = true;
window.PSCRM_TIPO_AGENDA = <?php echo intval($tipo_agenda); ?>;

if (typeof jQuery !== 'undefined') { window.$ = window.jQuery; }
<?php 
switch ($tipo_agenda) {
    case 1:
        include(dirname(__FILE__).'/../kunde/script_todo.php');
        break;
    case 2:
        include(dirname(__FILE__).'/../kunde/script_appuntamento.php');
        break;
    case 3:
        include(dirname(__FILE__).'/../kunde/script_attivita.php');
        break;
    default:
        break;
}
?>
</script>
