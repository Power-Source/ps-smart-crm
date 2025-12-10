<?php
if (!defined('ABSPATH'))
  exit;
$delete_nonce = wp_create_nonce("delete_document");
$update_nonce = wp_create_nonce("update_document");
$scheduler_nonce = wp_create_nonce("update_scheduler");

global $document;
$generalOptions = get_option('CRM_general_settings');
$documentOptions = get_option('CRM_documents_settings');
//echo $documentOptions['default_vat'];
$def_iva = $documentOptions['default_vat'];
$accOptions = get_option("CRM_acc_settings");
if (isset($_GET["id_invoice"]) && ($ID = $_GET["id_invoice"])) {


//	include(WPsCRM_DIR."/inc/crm/mpdf/mpdf.php");
//	$stylesheet = file_get_contents(WPsCRM_DIR.'/css/documents/pdf_documents.css');
//	include(WPsCRM_DIR."/inc/templates/print_invoice.php");
} else {

  $ID = isset($_REQUEST["ID"]) ? $_REQUEST["ID"] : 0;
//$type=$_REQUEST["type"];
  $d_table = WPsCRM_TABLE . "documenti";
  $dd_table = WPsCRM_TABLE . "documenti_dettaglio";
  $c_table = WPsCRM_TABLE . "clienti";
  if ($ID) {
    $sql = "select * from $d_table where id=$ID";
    $riga = $wpdb->get_row($sql, ARRAY_A);
    $riga = stripslashes_deep($riga);
    $type = $riga["tipo"];
    $data = WPsCRM_culture_date_format($riga["data"]);
    $data_scadenza = WPsCRM_culture_date_format($riga["data_scadenza"]);
    $oggetto = $riga["oggetto"];
    $iva = $riga["iva"];
    $tot_imp = sprintf("%01.2f", $riga["totale_imponibile"]);
    $tipo_sconto = $riga["tipo_sconto"];
    $totale_imposta = sprintf("%01.2f", $riga["totale_imposta"]);
    $tot_cassa = sprintf("%01.2f", $riga["tot_cassa_inps"]);
    $ritenuta_acconto = sprintf("%01.2f", $riga["ritenuta_acconto"]);
    $totale = $riga["totale"];
    $totale_netto = $riga["totale_netto"];
    $FK_contatti = $riga["FK_contatti"];
    if ($fk_clienti = $riga["fk_clienti"]) {
      $sql = "select ragione_sociale, nome, cognome, indirizzo, cap, localita, provincia, cod_fis, p_iva, tipo_cliente from $c_table where ID_clienti=" . $fk_clienti;
      $rigac = $wpdb->get_row($sql, ARRAY_A);
      $cliente = $rigac["ragione_sociale"] ? $rigac["ragione_sociale"] : $rigac["nome"] . " " . $rigac["cognome"];
      $cliente = stripslashes($cliente);
      $indirizzo = stripslashes($rigac["indirizzo"]);
      $cap = $rigac["cap"];
      $localita = stripslashes($rigac["localita"]);
      $provincia = $rigac["provincia"];
      $cod_fis = $rigac["cod_fis"];
      $p_iva = $rigac["p_iva"];
      $tipo_cliente = $rigac["tipo_cliente"];
    }
    if ($riga["FK_contatti"]) {
      $sql = "select concat(nome,' ', cognome) as contatto from ana_contatti where ID_contatti=" . $riga["FK_contatti"];
      $rigac = $wpdb->get_row($sql, ARRAY_A);
      $contatto = $rigac["contatto"];
    }
    $wpdb->update(
            $dd_table, array('eliminato' => 0), array('fk_documenti' => $ID), array('%d')
    );
    $sql = "select * from $dd_table where fk_documenti=$ID order by n_riga";
    $qd = $wpdb->get_results($sql, ARRAY_A);
  } else {
    $data = date("d-m-Y");
    $oggetto = __("Angebot", "cpsmartcrm");
    $iva = $documentOptions['default_vat'];
    $FK_clienti = 0;
    $FK_contatti = 0;
    $tipo_sconto = 0;
    $tot_imp = 0;
    $tot_cassa = 0;
    $totale_imposta = 0;
    $totale = 0;
    $ritenuta_acconto = 0;
    $totale_netto = 0;
  }
  ?>

  <?php
//$where="FK_aziende=$ID_azienda";
  ?>
  <script>
    var $format = "<?php echo WPsCRM_DATEFORMAT ?>";
    var $formatTime = "<?php echo WPsCRM_DATETIMEFORMAT ?>";
    var cliente = "<?php if (isset($cliente)) echo $cliente ?>";
  </script>
  <form name="form_insert" action="" method="post" id="form_insert">
      <input type="hidden" name="num_righe" id="num_righe" value="">
      <input type="hidden" name="ID" id="ID" value="<?php echo $ID ?>">
      <input type="hidden" name="type" id="type" value="1">

      <h1 style="text-align:center"><?php _e('ANGEBOT ERSTELLEN/BEARBEITEN', 'cpsmartcrm') ?> <i class="glyphicon glyphicon-send"></i></h1>
      <div id="tabstrip">
          <ul>
              <li id="tab1"><?php _e('DOKUMENT', 'cpsmartcrm') ?></li>
              <!--<li><?php _e('BODY', 'cpsmartcrm') ?></li>-->
              <?php //if($generalOptions['services']==1){   ?>
              <!--<li><?php _e('DIENSTLEISTUNGEN/PRODUKTE', 'cpsmartcrm') ?></li>-->
              <?php // }    ?>
              <li  id="tab2" onclick="aggiornatot();"><?php _e('KOMMENTARE UND INTERNE DATEN', 'cpsmartcrm') ?></li>
          </ul>
          <!--PRIMO TAB -->
          <div>

              <h4 class="page-header" style="margin: 10px 0 20px;"><?php _e('DOKUMENTENDATEN', 'cpsmartcrm') ?><span class="crmHelp" data-help="document-data"></span>
                  <span style="float:right;margin-top: -7px;">
                      <label class="col-sm-2 control-label"><?php _e('Nummer', 'cpsmartcrm') ?></label><span class="col-sm-2"><input id="progressivo" name="progressivo" class="form-control" data-placement="bottom" title="<?php _e('Nummer', 'cpsmartcrm') ?>" value="<?php if (isset($riga)) echo $riga["progressivo"] ?>" readonly disabled/>
                      </span></span></h4>

              <div class="row form-group">
                  <label class="col-sm-1 control-label"><?php _e('Datum', 'cpsmartcrm') ?></label>
                  <div class="col-sm-2"><input name="data" id="data" class="_m" data-placement="bottom" title="<?php _e('Datum', 'cpsmartcrm') ?>" value="<?php echo $data ?>"/>
                  </div>
                  <!--<div class="col-sm-2 hide_sm"></div>-->
                  <label class="col-sm-2 control-label" style="color:firebrick"><?php _e('Verfallsdatum', 'cpsmartcrm') ?></label>
                  <div class="col-sm-2"><input type="text" class="_m" name="data_scadenza" value="<?php if (isset($data_scadenza)) echo $data_scadenza ?>"  id='data_scadenza'>
                  </div>
                  <label class="control-label" style="margin-left:20px"><?php _e('Akzeptiert', 'cpsmartcrm') ?>?</label>
                  <div class="col-sm-1">
                      <input type="checkbox" name="pagato" value="1" <?php if (isset($riga)) echo $riga["pagato"] ? "checked" : "" ?>>
                  </div>
              </div>
              <div class="row form-group">
                  <label class="col-sm-1 control-label"><?php _e('Betreff', 'cpsmartcrm') ?></label>
                  <div class="col-sm-2"><input type="text" class="form-control col-md-10" name="oggetto" id="oggetto"  value="<?php if (isset($riga)) echo $oggetto ?>">
                  </div>
                  <!--<div class="col-sm-2 hide_sm"></div>-->
                  <label class="col-sm-2 control-label"><?php _e('Referenz', 'cpsmartcrm') ?></label>
                  <div class="col-sm-4"><input type="text" class="form-control" name="riferimento" id="riferimento" maxlength='55' value="<?php if (isset($riga)) echo $riga["riferimento"] ?>">
                  </div>
              </div>
              <div class="row form-group">
                  <label class="col-sm-2 control-label"><?php _e('Anmerkungen', 'cpsmartcrm') ?></label><br />
                  <div class="col-sm-8"><textarea id="annotazioni" style="width:100%" name="annotazioni" rows="5"><?php if (isset($riga)) echo stripslashes($riga["annotazioni"]) ?></textarea></div>
              </div>
              <h4 class="page-header"><?php _e('KUNDENDATEN', 'cpsmartcrm') ?><span class="crmHelp" data-help="customer-data"></span>
                  <?php
                  if (isset($fk_clienti)) {
                    echo "<a href=\"" . admin_url('admin.php?page=smart-crm&p=clienti/form.php&ID=' . $fk_clienti) . "\" target=\"_blank\"><span class=\"header_customer\" >" . $cliente . "</span></a>";
                  }$fk_clienti
                  ?>
                  <ul class="select-action" style="float:right;transform:scale(.8);background-color:transparent;margin-top:-10px;width:inherit">
                      <?php if ($ID) { ?>
                        <li class="btn _edit _white btn-sm _flat"><i class="glyphicon glyphicon-pencil"></i>
                            <b> <?php _e('KUNDENDATEN BEARBEITEN', 'cpsmartcrm') ?></b>
                        </li>
                        <li  style="display:none" class="btn btn-danger _quitEdit btn-sm _flat"><i class="glyphicon glyphicon-close"></i>
                            <b> <?php _e('EDITIEREN BEENDEN', 'cpsmartcrm') ?></b>
                        </li>
                        <li class="btn"><i class="_tooltip glyphicon glyphicon-menu-right"></i></li>
                        <li class="btn btn-info btn-sm _flat btn_todo" style="margin-left:10px" title="<?php _e('NEUES TODO', 'cpsmartcrm') ?>">
                            <i class="glyphicon glyphicon-tag"></i>
                            <b> </b>
                        </li>
                        <li class="btn  btn-sm _flat btn_appuntamento" title="<?php _e('NEUER TERMIN', 'cpsmartcrm') ?>">
                            <i class="glyphicon glyphicon-pushpin"></i>
                            <b> </b>
                        </li>
                        <li class="btn btn-primary btn-sm _flat btn_activity" title="<?php _e('NEUE ANMERKUNG', 'cpsmartcrm') ?>">
                            <i class="glyphicon glyphicon-option-horizontal"></i>
                            <b> </b>
                        </li>
                        <?php do_action('WpsCRM_advanced_document_buttons'); ?>
                      <?php } ?>        
                  </ul>
              </h4>
              <div class="customer_data_partial" data-customer="<?php if (isset($fk_clinti)) echo $fk_clienti ?>">
                  <input type="hidden" id="tipo_cliente" name="tipo_cliente" value="<?php if (isset($tipo_cliente)) echo $tipo_cliente ?>" data-value="<?php if (isset($tipo_cliente)) echo $tipo_cliente ?>" />                  <div class="row form-group">
                      <label class="col-sm-1 control-label"><?php _e('Kunde', 'cpsmartcrm') ?></label>
                      <div class="col-sm-3">
                          <?php
                          if (isset($fk_clienti)) {
                            $disabled = "disabled readonly";
                          } else
                            $disabled = "";
                          ?>
                          <select id="fk_clienti" name="fk_clienti" data-parsley-hasclient></select>
                          <input type="hidden" name="hidden_fk_clienti" value="<?php if (isset($fk_clienti)) echo $fk_clienti ?>">

                      </div>
                      <!--<label class="col-sm-1 control-label"><?php _e('Kontakt', 'cpsmartcrm') ?></label>
                      <div class="col-sm-3">
                          <input type="hidden" id="FK_contatti" name="FK_contatti" value="<?php if (isset($FK_contatti)) echo $FK_contatti ?>" data-value="<?php if (isset($FK_contatti)) echo $FK_contatti ?>" />
                      </div>-->
                      <div class="col-sm-2">
                          <input type="button" class="btn btn-sm btn-success _flat" id="save_client_data" name="save_client_data" value="<?php _e('Speichern', 'cpsmartcrm') ?>" style="display:none" />
                      </div>
                  </div>
                  <div class="row form-group">
                      <label class="col-sm-1 control-label"><?php _e('Addresse', 'wp-smart-crm-invoices-pro') ?></label>
                      <div class="col-sm-2 col-md-2 col-lg-3">

                          <input type="text" class="form-control _editable" name="indirizzo" id="indirizzo" maxlength='50' value="<?php if (isset($indirizzo)) echo $indirizzo ?>" <?php echo $disabled ?> data-value="<?php if (isset($indirizzo)) echo $indirizzo ?>" />

                      </div>
                      <label class="col-sm-1 control-label"><?php _e('PLZ', 'wp-smart-crm-invoices-pro') ?></label>
                      <div class="col-sm-2">

                          <input type="text" class="form-control _editable" name="cap" id="cap" maxlength='10' value="<?php if (isset($cap)) echo $cap ?>" <?php echo $disabled ?> data-value="<?php if (isset($cap)) echo $cap ?>">

                      </div>
                      <label class="col-sm-1 control-label"><?php _e('C.F.', 'wp-smart-crm-invoices-pro') ?></label>
                      <div class="col-md-2">
                          <input type="text" class="form-control _editable" name="cod_fis" id="cod_fis" maxlength='20' value="<?php if (isset($cod_fis)) echo $cod_fis ?>" <?php echo $disabled ?> data-value="<?php if (isset($cod_fis)) echo $cod_fis ?>">
                      </div>

                  </div>
                  <div class="row form-group">
                      <label class="col-sm-1 control-label"><?php _e('Stadt', 'wp-smart-crm-invoices-pro') ?></label>
                      <div class="col-sm-2 col-md-2 col-lg-3">

                          <input type="text" class="form-control _editable" name="localita" id="localita" maxlength='50' value="<?php if (isset($localita)) echo $localita ?>" <?php echo $disabled ?> data-value="<?php if (isset($localita)) echo $localita ?>">

                      </div>
                      <label class="col-sm-1 control-label"><?php _e('Staat/Prov.', 'wp-smart-crm-invoices-pro') ?></label>
                      <div class="col-sm-2">

                          <input type="text" class="form-control _editable" name="provincia" id="provincia" maxlength='20' value="<?php if (isset($provincia)) echo $provincia ?>" <?php echo $disabled ?> data-value="<?php if (isset($provincia)) echo $provincia ?>">

                      </div>
                      <label class="col-sm-1 control-label"><?php _e('Umsatzsteuer-ID', 'wp-smart-crm-invoices-pro') ?></label>
                      <div class="col-md-2">
                          <input type="text" class="form-control _editable" name="p_iva" id="p_iva" maxlength='20' value="<?php if (isset($p_iva)) echo $p_iva ?>" <?php echo $disabled ?> data-value="<?php if (isset($p_iva)) echo $p_iva ?>">
                      </div>

                  </div>
              </div>

              <div class="_meta-box-sortables _ui-sortable">
                  <div class="_postbox">
                      <!--<button type="button" class="handlediv button-link" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>-->

                    <h4 class="page-header"><?php _e('ANGEBOT TEXT', 'cpsmartcrm') ?> <span class="crmHelp" data-help="quotation-text"></span></h4>
                    <div class="_inside">
                        <?php
                        $content = isset($riga["testo_libero"]) ? $riga["testo_libero"] : "";
                        $editor_id = 'editor';
                        $settings = array(
                            'media_buttons' => false,
                            'quicktags' => true,
                            'textarea_name' => 'testo_libero',
                            'wpautop' => false,
                            'textarea_rows' => 10
                        );
                        wp_editor(stripslashes($content), $editor_id, $settings);
                        ?>
                    </div>
                    <input type="hidden" id="testo_libero" name="testo_libero" value="" />
                  </div>
              </div>
              <!-- </div>
              
              <div>-->

              <h4 class="page-header"><?php _e('Füge Produkte zum Angebot hinzu', 'cpsmartcrm') ?><span class="crmHelp" data-help="quotation-products"></span>
                  <?php do_action("WPsCRM_show_WOO_products"); ?>
              </h4>
              <?php
              $accontOptions = get_option("CRM_acc_settings");

              switch ($accontOptions['accountability']) {
                case 0:
                  include ('accountabilities/accountability_0.php');
                  break;
                case "1":
                  include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_1.php');
                  break;
                case "2":
                  include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_2.php');
                  break;
                case "3":
                  include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_3.php');
                  break;
                case "4":
                  include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_4.php');
                  break;
              }
              ?>
          </div>
          <!-- fine PRIMO TAB-->
          <!-- SECONDO TAB-->
          <div>
              <div class="row form-group">
                  <label class="col-sm-2 control-label"><?php _e('Angebotswert (erforderlich)', 'cpsmartcrm') ?> *</label>
                  <div class="col-sm-6">
                      <input class="numeric" id="quotation_value" name="quotation_value" value="<?php if (isset($riga)) echo $riga["valore_preventivo"] ?>" />

                  </div>
              </div>
              <div class="row form-group">
                  <label class="col-sm-2 control-label"><?php _e('Kommentare', 'cpsmartcrm') ?></label>
                  <div class="col-sm-6"><textarea class="_form-control" id="commento" name="commento" rows="10" cols="50"><?php if (isset($riga)) echo stripslashes($riga["commento"]) ?></textarea>
                  </div>
              </div>
              <div class="row form-group">
                  <label class="col-sm-2 control-label"><?php _e('Prognostizierter Erfolgsprozentsatz', 'cpsmartcrm') ?> % </label>
                  <div class="col-sm-4">
                      <select name="perc_realizzo">
                          <option value=""></option>
                          <option value="0-25" <?php if (isset($riga)) echo $riga["perc_realizzo"] == "0-25" ? "selected" : "" ?>>0-25</option>
                          <option value="25-50" <?php if (isset($riga)) echo $riga["perc_realizzo"] == "25-50" ? "selected" : "" ?>>25-50</option>
                          <option value="50-75" <?php if (isset($riga)) echo $riga["perc_realizzo"] == "50-75" ? "selected" : "" ?>>50-75</option>
                          <option value="75-100" <?php if (isset($riga)) echo $riga["perc_realizzo"] == "75-100" ? "selected" : "" ?>>75-100</option>
                      </select>
                  </div>

              </div>
          </div>

      </div>

    </form>    
  <ul class="select-action">

      <li onClick="aggiornatot();return false;" class="btn btn-sm btn-success _flat" id="_submit">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          <b> <?php _e('Speichern', 'cpsmartcrm') ?></b>
      </li>
      <li class="btn btn-warning btn-sm _flat" onClick="annulla();return false;">
          <i class="glyphicon glyphicon-floppy-remove"></i>
          <b> <?php _e('Abbrechen', 'cpsmartcrm') ?></b>
      </li>
      <?php
      if ($ID) {
        if (WPsCRM_advanced_print()) {
          ?>
          <li class="btn btn-sm btn-info _flat" onclick="location.replace('?page=smart-crm&p=documenti/advanced_print.php&id_invoice=<?php echo $ID ?>')">
              <i class="glyphicon glyphicon-print"></i>
              <b> <?php _e('Druckbare Version', 'cpsmartcrm') ?></b>
          </li>
        <?php } else { ?>
          <li class="btn btn-sm btn-info _flat" onclick="location.replace('?page=smart-crm&p=documenti/document_print.php&id_invoice=<?php echo $ID ?>')">
              <i class="glyphicon glyphicon-print"></i>
              <b> <?php _e('Druckbare Version', 'cpsmartcrm') ?></b>
          </li>

          <?php
        }
      }
      ?>
  </ul>
  <div id="dialog"></div>
  <div id="dialog_todo" style="display:none;" data-from="documenti" data-fkcliente="<?php if (isset($fk_clienti)) echo $fk_clienti ?>">
      <?php
      include ( WPsCRM_DIR . "/inc/crm/clienti/form_todo.php" )
      ?>
  </div>
  <?php
  include (WPsCRM_DIR . "/inc/crm/clienti/script_todo.php" )
  ?>
  <div id="dialog_appuntamento" style="display:none;" data-from="documenti" data-fkcliente="<?php if (isset($fk_clienti)) echo $fk_clienti ?>">
      <?php
      include (WPsCRM_DIR . "/inc/crm/clienti/form_appuntamento.php" )
      ?>
  </div>
  <?php
  include (WPsCRM_DIR . "/inc/crm/clienti/script_appuntamento.php" )
  ?>
  <div id="dialog_attivita" style="display:none;"  data-from="documenti" data-fkcliente="<?php if (isset($fk_clienti)) echo $fk_clienti ?>">
      <?php
      include (WPsCRM_DIR . "/inc/crm/clienti/form_attivita.php" )
      ?>
  </div>
  <?php
  include (WPsCRM_DIR . "/inc/crm/clienti/script_attivita.php" )
  ?>
  <div id="dialog_mail" style="display:none;" data-from="documenti" data-fkcliente="<?php if (isset($fk_clienti)) echo $fk_clienti ?>">
      <?php
      include (WPsCRM_DIR . "/inc/crm/clienti/form_mail.php" )
      ?>    
  </div>
  <?php
  include (WPsCRM_DIR . "/inc/crm/clienti/script_mail.php" )
  ?>

 <script type="text/javascript">
jQuery(document).ready(function ($) {
    // Tooltip bleibt wie gehabt
    $("._tooltip").tooltip({
        content: "<h4><?php _e('BUTTONS LEGENDE', 'cpsmartcrm') ?>:</h4>\
        <ul>\
            <li class='no-link'>\
                <span class='btn btn-info _flat'><i class='glyphicon glyphicon-tag'></i> = <?php _e('NEUE TODO', 'cpsmartcrm') ?></span>\
                <span class='btn btn_appuntamento_1 _flat'><i class='glyphicon glyphicon-pushpin'></i> = <?php _e('NEUER TERMIN', 'cpsmartcrm') ?></span>\
                <span class='btn btn-primary _flat'><i class='glyphicon glyphicon-option-horizontal'></i> = <?php _e('NEUE AKTIVITÄT', 'cpsmartcrm') ?></span>\
                <span class='btn btn-warning _flat'><i class='glyphicon glyphicon-envelope'></i> = <?php _e('NEUE MAIL', 'cpsmartcrm') ?></span>\
            </li>\
        </ul>"
    });

    // Datepicker für Datum und Verfallsdatum
    $("#data, #data_scadenza").datepicker({
        dateFormat: "dd-mm-yy"
    });

    // Select2 für Kunden
    $("#fk_clienti").select2({
        width: '100%',
        placeholder: "<?php _e('Wähle Kunde aus', 'cpsmartcrm') ?>...",
        minimumInputLength: 3,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'WPsCRM_get_clients2',
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: $.map(data.clients, function (obj) {
                        return {
                            id: obj.ID_clienti,
                            text: obj.ragione_sociale ? obj.ragione_sociale : (obj.nome + " " + obj.cognome)
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Kunde vorauswählen, falls vorhanden
    <?php if (isset($fk_clienti) && $fk_clienti) : ?>
        $("#fk_clienti").append(new Option("<?php echo esc_js($cliente); ?>", "<?php echo esc_js($fk_clienti); ?>", true, true)).trigger('change');
        $("#fk_clienti").prop("disabled", true);
        $('#hidden_fk_clienti').val('<?php echo $fk_clienti ?>');
    <?php endif; ?>
    <?php if (isset($_GET['cliente'])) : ?>
        $("#fk_clienti").val("<?php echo esc_js($_GET['cliente']); ?>").trigger('change');
    <?php endif; ?>

    // Automatisches Ausfüllen der Kundendaten
    $("#fk_clienti").on('select2:select', function (e) {
        var id_clienti = e.params.data.id;
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'WPsCRM_get_client_info',
                id_clienti: id_clienti
            },
            success: function (result) {
                var parseData = result.info || result;
                if (parseData && parseData[0]) {
                    $("#indirizzo").val(parseData[0].indirizzo);
                    $("#cap").val(parseData[0].cap);
                    $("#localita").val(parseData[0].localita);
                    $("#provincia").val(parseData[0].provincia);
                    $("#cod_fis").val(parseData[0].cod_fis);
                    $("#p_iva").val(parseData[0].p_iva);
                    $("#tipo_cliente").val(parseData[0].tipo_cliente);
                }
            }
        });
    });

    // Select2 für Benutzer (MultiSelect)
    $("#remindToUser").select2({
        width: '100%',
        placeholder: "<?php _e('Wähle Benutzer', 'cpsmartcrm') ?>...",
        multiple: true,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { action: 'WPsCRM_get_CRM_users', q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data, function (obj) {
                        return { id: obj.ID, text: obj.display_name };
                    })
                };
            },
            cache: true
        }
    });

    // Select2 für Rollen (MultiSelect)
    $("#remindToGroup").select2({
        width: '100%',
        placeholder: "<?php _e('Wähle Rolle', 'cpsmartcrm') ?>...",
        multiple: true,
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { action: 'WPsCRM_get_registered_roles', q: params.term };
            },
            processResults: function (data) {
                return {
                    results: $.map(data.roles, function (obj) {
                        return { id: obj.name, text: obj.name };
                    })
                };
            },
            cache: true
        }
    });

    // Editierbare Kundendaten
    $('._edit').on('click', function () {
        $(this).hide();
        $('._quitEdit').show();
        $('._editable').attr('readonly', false).attr('disabled', false);
        $('#_submit').css('visibility', 'hidden');
        $('#save_client_data').show();
        $('#save_client_data').parent().append("<br><small class='_notice notice notice-error '><?php _e("Du bearbeitest die Stammdaten für diesen Kunden", 'cpsmartcrm') ?></small>");
        $('.customer_data_partial').addClass('edit_active');
    });

    $('._quitEdit').on('click', function () {
        $(this).hide();
        $('._notice').hide().remove();
        $('._edit').show();
        $('._editable').attr('readonly', 'readonly').attr('disabled', 'disabled');
        $('._editable').each(function () {
            $(this).val($(this).data('value'));
        });
        $('#_submit').css('visibility', 'visible');
        $('#save_client_data').hide();
        $('.customer_data_partial').removeClass('edit_active');
    });

    $('#save_client_data').on('click', function () {
        var inputs = $('.customer_data_partial :input').serialize();
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'WPsCRM_save_client_partial',
                values: inputs,
                security: '<?php echo $update_nonce ?>'
            },
            success: function () {
                noty({
                    text: "<?php _e('Daten wurden gespeichert', 'cpsmartcrm') ?>",
                    layout: 'center',
                    type: 'success',
                    template: '<div class="noty_message"><span class="noty_text"></span></div>',
                    timeout: 1000
                });
                setTimeout(function () {
                    $('._quitEdit').hide();
                    $('._notice').hide().remove();
                    $('._edit').show();
                    $('._editable').attr('readonly', 'readonly').attr('disabled', 'disabled');
                    $('#_submit').css('visibility', 'visible');
                    $('#save_client_data').hide();
                    $('.customer_data_partial').removeClass('edit_active');
                }, 200);
                $('.customer_data_partial :input').each(function () {
                    $(this).attr('data-value', $(this).val());
                });
            }
        });
    });

    // Angebotswert automatisch übernehmen
    if ($('#totale_imponibile').val() > 0)
        $('#quotation_value').val($('#totale_imponibile').val());
    $('#totale_imponibile').on('change', function () {
        if ($(this).val() > 0)
            $('#quotation_value').val($(this).val());
    });

    // Tabs (Bootstrap oder eigenes System)
    $('.nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
    });

    // Formular-Validierung
    $('#_submit').on('click', function (e) {
        aggiornatot();
        var valid = true;
        if ($("#fk_clienti").val() == "" || $("#fk_clienti").val() == null) {
            alert("<?php _e('Du solltest einen Kunden auswählen', 'cpsmartcrm') ?>");
            $("#fk_clienti").focus();
            valid = false;
        }
        if ($("#quotation_value").val() == "" || $("#quotation_value").val() == "0") {
            alert("<?php _e('Dieses Zitat sollte einen Wert haben', 'cpsmartcrm') ?>");
            $("#quotation_value").focus();
            valid = false;
        }
        if (!valid) return;

        $("#progressivo").prop("disabled", false);
        showMouseLoader();
        jQuery('#mouse_loader').offset({left: e.pageX, top: e.pageY});
        var n_row = $('#t_art > tbody > tr').length ? parseInt($('#t_art > tbody > tr').last().attr("id").split("_")[1]) : 0;
        $('#num_righe').val(n_row);

        // Editor-Inhalt übernehmen (falls TinyMCE)
        if (typeof tinymce !== "undefined" && tinymce.get("editor")) {
            $('#testo_libero').val(tinymce.get("editor").getContent());
        } else {
            $('#testo_libero').val($('#editor').val());
        }

        var form = $('#form_insert');
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'WPsCRM_save_document',
                fields: form.serialize(),
                security: '<?php echo $update_nonce; ?>'
            },
            type: "POST",
            success: function (response) {
                $("#progressivo").prop("disabled", true);
                if (response.indexOf('OK') != -1) {
                    var tmp = response.split("~");
                    var id_cli = tmp[1];
                    hideMouseLoader();
                    noty({
                        text: "<?php _e('Dokument wurde gespeichert', 'cpsmartcrm') ?>",
                        layout: 'center',
                        type: 'success',
                        template: '<div class="noty_message"><span class="noty_text"></span></div>',
                        timeout: 1000
                    });
                    $("#ID").val(id_cli);
                    <?php if (isset($_REQUEST["layout"]) && $_REQUEST["layout"] == "iframe") { ?>
                        $(window.parent.document).find(".k-i-close").trigger("click");
                    <?php } else if (!$ID) { ?>
                        setTimeout(function () {
                            location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&ID=') ?>" + id_cli;
                        }, 1000);
                    <?php } ?>
                } else {
                    noty({
                        text: "<?php _e('Etwas war falsch', 'cpsmartcrm') ?>" + ": " + response,
                        layout: 'center',
                        type: 'error',
                        template: '<div class="noty_message"><span class="noty_text"></span></div>',
                        closeWith: ['button']
                    });
                }
            }
        });
    });

    // Reset-Button
    $(document).on('click', '._reset', function () {
        $('._modal').fadeOut('fast');
        $('input[type="reset"]').trigger('click');
    });

    // Abbrechen
    window.annulla = function () {
        location.href = "<?php echo admin_url('admin.php?page=smart-crm&p=documenti/list.php') ?>";
    };
});
</script>
  <?php
}
?>
<style>
<?php if (isset($_GET['layout']) && $_GET['layout'] == "iframe") { ?>
      #wpadminbar, #adminmenumain, #mainMenu,.wrap h1,.btn-warning,.select-action:first-of-type  {
          display: none;
      }
      #wpcontent, #wpfooter {
          margin-left: 0;
      }
<?php } ?>
</style>
