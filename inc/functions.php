<?php
if (!defined('ABSPATH'))
  exit;

class WPsCRM_crmObject {

    // Vorab deklarierte Eigenschaften
    public $print_logo;
    public $master_data;
    public $numbering;
    public $messages;
    public $signature;
    public $use_signature;
    public $formatted_signature;
    public $use_formatted_signature;
    public $alignHeader;

  public function __construct(array $arguments = array()) {
    if (!empty($arguments)) {
      foreach ($arguments as $property => $argument) {
        if ($argument instanceOf Closure) {
          $this->{$property} = $argument;
        } else {
          $this->{$property} = $argument;
        }
      }
    }
  }

  public function __call($method, $arguments) {
    if (isset($this->{$method}) && is_callable($this->{$method})) {
      return call_user_func_array($this->{$method}, $arguments);
    } else {
      throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
    }
  }

}

$document = new WPsCRM_crmObject(array());

$document->print_logo = function() {
  $options = get_option('CRM_general_settings');
  $logo = (string) $options['print_logo'];

  if (!$logo || $logo == 0)
    return false;
  else
    return true;
};
$document->master_data = function() {
    $options = get_option('CRM_business_settings');
    $number = isset($options['business_number']) ? $options['business_number'] : "";
    $prov  = isset($options['crm_business_provincia']) ? " (".$options['crm_business_provincia'].")" : "";

    $master_data = array(
        array('full_header' => "<h2 class=\"WPsCRM_businessName\">" . html_entity_decode($options['business_name']) . "</h2>" . $extraName . "<div class=\"WPsCRM_businessAddress\"> " . html_entity_decode($options['business_address']) . $number." <br /> " . html_entity_decode($options['business_zip']) . ", " . html_entity_decode($options['business_town']) .$prov. "</div>", 'show' => 1),
        array(__('Name', 'cpsmartcrm') => html_entity_decode($options['business_name']), 'show' => 0, 'show_label' => 0, ''),
        array(__('Addresse', 'cpsmartcrm') => html_entity_decode($options['business_address']), 'show' => 0, 'show_label' => 0),
        array(__('Stadt', 'cpsmartcrm') => html_entity_decode($options['business_town']), 'show' => 0, 'show_label' => 0),
        array(__('PLZ', 'cpsmartcrm') => html_entity_decode($options['business_zip']), 'show' => 0, 'show_label' => 0),
        array(__('Staat/Prov', 'wp-smart-crm-invoices-pro') => $prov, 'show' => 0, 'show_label' => 0),
    );

    // Kleinunternehmer-Regelung oder USt-ID
    if (!empty($options['crm_kleinunternehmer'])) {
        $master_data[] = array(
            'value' => __('Kleinunternehmer nach §19 UStG (keine Umsatzsteuer-ID)', 'wp-smart-crm-invoices-pro'),
            'show' => 1,
            'show_label' => 0,
        );
    } else {
        $master_data[] = array(
            'label' => __('Umsatzsteuer-ID', 'cpsmartcrm'),
            'value' => !empty($options['business_ustid']) ? esc_html($options['business_ustid']) : '<span style="color:red;">*</span>',
            'show' => 1,
            'show_label' => 1,
        );
    }

    $master_data[] = array(
        __('Steuernummer', 'cpsmartcrm') => html_entity_decode(isset($options['business_taxid']) ? $options['business_taxid'] : ''),
        'show' => 1,
        'show_label' => 1
    );
    $master_data[] = array(__('Telefon', 'cpsmartcrm') => html_entity_decode($options['business_phone']), 'show' => isset($options['show_phone']) ? $options['show_phone'] : 0, 'show_label' => 1);
    $master_data[] = array(__('Fax', 'cpsmartcrm') => html_entity_decode($options['business_fax']), 'show' => isset($options['show_fax']) ? $options['show_fax'] : 0, 'show_label' => 1);
    $master_data[] = array(__('Email', 'cpsmartcrm') => html_entity_decode($options['business_email']), 'show' => isset($options['show_email']) ? $options['show_email'] : 0, 'show_label' => 1);
    $master_data[] = array(__('Webseite', 'cpsmartcrm') => html_entity_decode($options['business_web']), 'show' => isset($options['show_web']) ? $options['show_web'] : 0, 'show_label' => 1);
    $master_data[] = array(__('IBAN', 'cpsmartcrm') => html_entity_decode($options['business_iban']), 'show' => isset($options['show_iban']) ? $options['show_iban'] : 0, 'show_label' => 1);
    $master_data[] = array(__('SWIFT', 'cpsmartcrm') => html_entity_decode($options['business_swift']), 'show' => isset($options['show_swift']) ? $options['show_swift'] : 0, 'show_label' => 1);

    return $master_data;
};
$document->numbering = function() {
  $options = get_option('CRM_documents_settings');
  return array(
      'invoices_prefix' => isset($options['invoices_prefix']) ? $options['invoices_prefix'] : "",
      'invoices_suffix' => isset($options['invoices_suffix']) ? $options['invoices_suffix'] : "",
      'invoices_start' => isset($options['invoices_start']) ? $options['invoices_start'] : "",
      'offers_prefix' => isset($options['offers_prefix']) ? $options['offers_prefix'] : "",
      'offers_suffix' => isset($options['offers_suffix']) ? $options['offers_suffix'] : "",
      'offers_start' => isset($options['offers_start']) ? $options['offers_start'] : "",
  );
};
$document->messages = function() {
  $options = get_option('CRM_documents_settings');
  return array(
      'invoices_dear' => isset($options['invoices_dear']) ? $options['invoices_dear'] : "",
      'invoices_before' => isset($options['invoices_before']) ? $options['invoices_before'] : "",
      'invoices_after' => isset($options['invoices_after']) ? $options['invoices_after'] : "",
      'offers_dear' => isset($options['offers_dear']) ? $options['offers_dear'] : "",
      'offers_before' => isset($options['offers_before']) ? $options['offers_before'] : "",
      'offers_after' => isset($options['offers_after']) ? $options['offers_after'] : "",
  );
};
$document->signature = function() {
  $options = get_option('CRM_documents_settings');
  return isset($options['crm_signature']) ? '<div><img src="' . $options['crm_signature'] . '" style="max-width:300px"></div>' : "";
};
$document->use_signature = function() {
  $options = get_option('CRM_documents_settings');
  return $options['use_crm_signature'] == 1 ? true : false;
};
$document->formatted_signature = function() {
  $options = get_option('CRM_documents_settings');
  return isset($options['crm_formatted_signature']) ? '<div class="formatted_signature" style="width:300px;display:inline-block;margin-top:20px;border-top:1px solid #ccc">' . $options['crm_formatted_signature'] . '</div>' : "";
};
$document->use_formatted_signature = function() {
  $options = get_option('CRM_documents_settings');
  return $options['use_crm_formatted_signature'] == 1 ? true : false;
};
$document->alignHeader = function($index) {
  $options = get_option('CRM_documents_settings');
  $headerStyles = array();
  if (isset($options['header_alignment'])) {
    $options['header_alignment'] == "logo,text" ? (array_push($headerStyles, array(0 => "float:left;width:59%", 1 => "display:block;float:right;width:39%;display:inline-block;text-align:left")) ) : (array_push($headerStyles, array(0 => "float:right;width:39%;text-align:right", 1 => "display:block;float:left;text-align:left;width:59%")) );
  } else {
    array_push($headerStyles, array(0 => "float:left;width:59%", 1 => "display:block;float:right;width:39%;display:inline-block;text-align:left"));
  }
  return $headerStyles[0][$index];
};

//END DOCUMENT FUNCTIONS

function WPsCRM_get_clients_array($exclude_self = null) {
  global $wpdb;
  $arr = array();
  $table = WPsCRM_TABLE . "clienti";
  $ID_azienda = 1;
  $client = $exclude_self;

  $where = "eliminato=0";
  if ($exclude_self == null)
    $where .= " AND ID_clienti <> 1";

  $sql = "select distinct ID_clienti, ragione_sociale, nome, cognome, indirizzo, telefono1, $table.email from $table where $where";


  foreach ($wpdb->get_results($sql) as $record) {
    $cliente = $record->ragione_sociale ? $record->ragione_sociale : $record->nome . " " . $record->cognome;
    $arr[] = array("ID_clienti" => $record->ID_clienti, "ragione_sociale" => $cliente, "indirizzo" => $record->indirizzo, "telefono1" => $record->telefono1, "email" => $record->email);
  }
  return $arr;
}

//get json clients for grid in clients/list.php
function WPsCRM_get_clients2() {
  is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
  if (in_array('wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters('active_plugins', $filter))) {
    do_action('AGsCRM_get_clients_hook');
  } else {
    global $wpdb;
    $table = WPsCRM_TABLE . "clienti";
    $f_table = WPsCRM_TABLE . "fields";
    $ID_azienda = 1;
    $arr = array();
    $client = isset($_REQUEST["self_client"]) ? $_REQUEST["self_client"] : false;
    //$where="$table.FK_aziende=$ID_azienda and eliminato=0";
    $where = "eliminato=0";
    if (!$client)
      $where .= " AND ID_clienti <> 1";
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    if (WPsCRM_is_agent() && !WPsCRM_agent_can())
      $where .= " and agente=$user_id";
    if (isset($_REQUEST["ragione_sociale"])) {
      $ragione_sociale = $_REQUEST["ragione_sociale"];
      $where .= " and ragione_sociale like '%$ragione_sociale%'";
    }
    $sql = "SELECT * from $table WHERE $where order by ragione_sociale";
    $index = 0;
    foreach ($wpdb->get_results($sql) as $record) {
      $cats = array();
      $provs = array();
      $ints = array();
      if ($record->categoria != "") {
        $_cats = explode(',', $record->categoria);
        if (count($_cats) > 0) {
          foreach ($_cats as $_cat) {
            $term = get_term_by('id', (int) $_cat, 'WPsCRM_customersCat');
            if ($term !== false) {
                $cats[] = $term->name;
            }
          }
        }
      }
      if ($record->interessi != "") {
        $_ints = explode(',', $record->interessi);
        if (count($_ints) > 0) {
          foreach ($_ints as $_cat) {
            $ints[] = get_term_by('id', (int) $_cat, 'WPsCRM_customersInt')->name;
          }
        }
      }
      if ($record->provenienza != "") {
        $_provs = explode(',', $record->provenienza);
        if (count($_provs) > 0) {
          foreach ($_provs as $_cat) {
            $provs[] = get_term_by('id', (int) $_cat, 'WPsCRM_customersProv')->name;
          }
        }
      }

      $cliente = $record->ragione_sociale ? $record->ragione_sociale : $record->nome . " " . $record->cognome;
      $arr_custom_fields = maybe_unserialize($record->custom_fields);
      $arr[$index] = array(
          "ID_clienti" => $record->ID_clienti,
          "ragione_sociale" => stripslashes($cliente),
          "p_iva" => $record->p_iva,
          "cod_fis" => $record->cod_fis,
          "nazione" => $record->nazione,
          "indirizzo" => stripslashes($record->indirizzo),
          "localita" => stripslashes($record->localita),
          "provincia" => strtoupper(stripslashes($record->provincia)),
          "telefono1" => $record->telefono1,
          "email" => $record->email,
          "categoria" => join(',', $cats),
          "provenienza" => join(',', $provs),
          "interessi" => join(',', $ints),
              //"custom_fields"=>$customerfields
      );

      $sql = "select id, field_name, field_type from $f_table where table_name='clienti'";
      foreach ($wpdb->get_results($sql) as $rec) {
        $fk_fields = $rec->id;
        $field_name = $rec->field_name;
        $date_field_name = "date_" . $field_name;
        $field_type = $rec->field_type;
        $key = array_search($fk_fields, array_column($arr_custom_fields, 'id_fields'));
        if ($key !== false) {
          if ($field_type == "datetime") {
            $value = $arr_custom_fields[$key]['value'];
            $date_value = $arr_custom_fields[$key]['datevalue'];
            $arr[$index][$field_name] = $value;
            $arr[$index][$date_field_name] = $date_value;
          } else {
            $value = is_array($arr_custom_fields[$key]['value']) ? join(',', $arr_custom_fields[$key]['value']) : $arr_custom_fields[$key]['value'];
            $arr[$index][$field_name] = $value;
          }
        } else {
          $arr[$index][$field_name] = "";
          $arr[$index][$date_field_name] = "";
        }
      }



      /* if( $arr_custom_fields != NULL ){
        foreach($arr_custom_fields as $custom_field){
        $sql="select * from $f_table where table_name='clienti' AND id =".(int)$custom_field['id_fields'];
        $row=$wpdb->get_row( $sql );
        $value = is_array($custom_field['value']) ? join(',',$custom_field['value']) : $custom_field['value'] ;
        $arr[$index][$row->field_name ] = $value ;
        }
        } */
      $index ++;
    }
    header("Content-type: application/json");
    echo "{\"clients\":" . json_encode($arr, JSON_UNESCAPED_SLASHES) . "}";
    die();
  }
}

add_action('wp_ajax_WPsCRM_get_clients2', 'WPsCRM_get_clients2');

function WPsCRM_get_clients3() {
  global $wpdb;
  $table = WPsCRM_TABLE . "clienti";
  $f_table = WPsCRM_TABLE . "fields";
  $ID_azienda = 1;
  $arr = array();
  $client = isset($_REQUEST["self_client"]) ? $_REQUEST["self_client"] : false;
  //$where="$table.FK_aziende=$ID_azienda and eliminato=0";
  $where = "eliminato=0";
  if (!$client)
    $where .= " AND ID_clienti <> 1";
  $current_user = wp_get_current_user();
  $user_id = $current_user->ID;
  if (WPsCRM_is_agent() && !WPsCRM_agent_can())
    $where .= " and agente=$user_id";
  if (isset($_REQUEST["ragione_sociale"])) {
    $ragione_sociale = $_REQUEST["ragione_sociale"];
    $where .= " and ragione_sociale like '%$ragione_sociale%'";
  }
  $sql = "SELECT * from $table WHERE $where order by ragione_sociale";
  $index = 0;
  foreach ($wpdb->get_results($sql) as $record) {
    $cats = array();
    $provs = array();
    $ints = array();
    if ($record->categoria != "") {
      $_cats = explode(',', $record->categoria);
      if (count($_cats) > 0) {
        foreach ($_cats as $_cat) {
          $cats[] = get_term_by('id', (int) $_cat, 'WPsCRM_customersCat')->name;
        }
      }
    }
    if ($record->interessi != "") {
      $_ints = explode(',', $record->interessi);
      if (count($_ints) > 0) {
        foreach ($_ints as $_cat) {
          $ints[] = get_term_by('id', (int) $_cat, 'WPsCRM_customersInt')->name;
        }
      }
    }
    if ($record->provenienza != "") {
      $_provs = explode(',', $record->provenienza);
      if (count($_provs) > 0) {
        foreach ($_provs as $_cat) {
          $provs[] = get_term_by('id', (int) $_cat, 'WPsCRM_customersProv')->name;
        }
      }
    }

    $cliente = $record->ragione_sociale ? $record->ragione_sociale : $record->nome . " " . $record->cognome;
    $arr_custom_fields = maybe_unserialize($record->custom_fields);
    $arr[$index] = array(
        "ID_clienti" => $record->ID_clienti,
        "ragione_sociale" => stripslashes($cliente),
        "p_iva" => $record->p_iva,
        "cod_fis" => $record->cod_fis,
        "nazione" => $record->nazione,
        "indirizzo" => stripslashes($record->indirizzo),
        "localita" => stripslashes($record->localita),
        "provincia" => strtoupper(stripslashes($record->provincia)),
        "telefono1" => $record->telefono1,
        "email" => $record->email,
        "categoria" => join(',', $cats),
        "provenienza" => join(',', $provs),
        "interessi" => join(',', $ints),
            //"custom_fields"=>$customerfields
    );

    $sql = "select id, field_name, field_type from $f_table where table_name='clienti'";
    foreach ($wpdb->get_results($sql) as $rec) {
      $fk_fields = $rec->id;
      $field_name = $rec->field_name;
      $date_field_name = "date_" . $field_name;
      $field_type = $rec->field_type;
      $key = array_search($fk_fields, array_column($arr_custom_fields, 'id_fields'));
      if ($key !== false) {
        if ($field_type == "datetime") {
          $value = $arr_custom_fields[$key]['value'];
          $date_value = $arr_custom_fields[$key]['datevalue'];
          $arr[$index][$field_name] = $value;
          $arr[$index][$date_field_name] = $date_value;
        } else {
          $value = is_array($arr_custom_fields[$key]['value']) ? join(',', $arr_custom_fields[$key]['value']) : $arr_custom_fields[$key]['value'];
          $arr[$index][$field_name] = $value;
        }
      } else {
        $arr[$index][$field_name] = "";
        $arr[$index][$date_field_name] = "";
      }
    }



    /* if( $arr_custom_fields != NULL ){
      foreach($arr_custom_fields as $custom_field){
      $sql="select * from $f_table where table_name='clienti' AND id =".(int)$custom_field['id_fields'];
      $row=$wpdb->get_row( $sql );
      $value = is_array($custom_field['value']) ? join(',',$custom_field['value']) : $custom_field['value'] ;
      $arr[$index][$row->field_name ] = $value ;
      }
      } */
    $index ++;
  }
  header("Content-type: application/json");
  echo "{\"clients\":" . json_encode($arr, JSON_UNESCAPED_SLASHES) . "}";
  die();
}

add_action('wp_ajax_WPsCRM_get_clients3', 'WPsCRM_get_clients3');

//get json client contacts for grid in clients/form.php
function WPsCRM_get_client_contacts() {
  global $wpdb;
  $table = WPsCRM_TABLE . "contatti";
  $client_id = $_REQUEST["client_id"];
  $where = "fk_clienti=$client_id";
  $arr = array();

  $sql = "select id, nome, cognome, email, telefono, qualifica from $table where $where order by cognome";

  foreach ($wpdb->get_results($sql) as $record) {
    $arr[] = $record;
  }

  header("Content-type: application/json");
  echo "{\"contacts\":" . json_encode($arr) . "}";
  die();
}

add_action('wp_ajax_WPsCRM_get_client_contacts', 'WPsCRM_get_client_contacts');

//save client contacts for grid in clients/form.php
function WPsCRM_save_client_contact() {
  if (check_ajax_referer('update_customer', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "contatti";
    $client_id = $_REQUEST["client_id"];
    $row = $_REQUEST["row"];
    $id_c = $row["id"];
    $nome = $row["nome"];
    $cognome = $row["cognome"];
    $email = $row["email"];
    $telefono = $row["telefono"];
    $qualifica = $row["qualifica"];
    if ($id_c)
      $wpdb->update(
              $table, array(
          'nome' => $nome,
          'cognome' => $cognome,
          'email' => $email,
          'telefono' => $telefono,
          'qualifica' => $qualifica
              ), array('id' => $id_c), array('%s', '%s', '%s', '%s', '%s')
      );
    else {
      $wpdb->insert(
              $table, array(
          'fk_clienti' => $client_id,
          'nome' => $nome,
          'cognome' => $cognome,
          'email' => $email,
          'telefono' => $telefono,
          'qualifica' => $qualifica
              ), array('%d', '%s', '%s', '%s', '%s', '%s')
      );

      $last_id = $wpdb->insert_id;
      $sqlc = "SELECT * FROM $table where id=$last_id";
      $qc = $wpdb->get_row($sqlc);
      echo (json_encode($qc));
    }
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_save_client_contact', 'WPsCRM_save_client_contact');

//delete client contacts for grid in clients/form.php
function WPsCRM_delete_client_contact() {
  if (check_ajax_referer('delete_customer', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "contatti";
    $client_id = $_REQUEST["client_id"];
    $row = $_REQUEST["row"];
    $id_c = $row["id"];
    $wpdb->delete($table, array('id' => $id_c));
    //echo $wpdb->last_query;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_delete_client_contact', 'WPsCRM_delete_client_contact');

//delete activity in clients/form.php
function WPsCRM_delete_activity() {
  if (check_ajax_referer('delete_customer', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $a_table = WPsCRM_TABLE . "agenda";
    $s_table = WPsCRM_TABLE . "subscriptionrules";
    $ID = $_GET['id'];
    $sql = "SELECT fk_subscriptionrules FROM " . $a_table . " where id_agenda=$ID";
    $qa = $wpdb->get_row($sql);
    if ($fsr = $qa->fk_subscriptionrules) {
      $wpdb->delete($s_table, array('ID' => $fsr));
    }
    $wpdb->delete($a_table, array('id_agenda' => $ID));
    //echo $wpdb->last_query;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_delete_activity', 'WPsCRM_delete_activity');

//get products for articoli/list.php
function WPsCRM_get_products() {
  global $wpdb;
  $sql = "SELECT * FROM " . $wpdb->posts . "  WHERE  post_type LIKE 'services'  AND (post_status LIKE 'publish')";
  foreach ($wpdb->get_results($sql) as $record) {
    $id = $record->ID;
    $code = get_post_meta($id, 'SOFT_service_code', true);
    $short_desc = get_post_meta($id, 'SOFT_service_short_desc', true);
    $list_price_1 = get_post_meta($id, 'SOFT_service_price_1', true);
    $terms = get_the_terms($id, 'services_cat');
    foreach ($terms as $term) {
      $term_link = get_term_link($term);
      if (is_wp_error($term_link)) {
        continue;
      }
    }
    $arr[] = array("ID" => $id, "codice" => $code, "descrizione" => $short_desc, "listino1" => $list_price_1);
  }

  header("Content-type: application/json");
  echo "{\"products\":" . json_encode($arr) . "}";
  die();
}

add_action('wp_ajax_WPsCRM_get_products', 'WPsCRM_get_products');

function WPsCRM_get_products_document() {
  global $wpdb;
  $sql = "SELECT * FROM " . $wpdb->posts . "  WHERE  post_type LIKE 'services'  AND (post_status LIKE 'publish')";
  //  echo $sql;
  foreach ($wpdb->get_results($sql) as $record) {
    $id = $record->ID;
    $code = get_post_meta($id, 'SOFT_service_code', true);
    $short_desc = get_post_meta($id, 'SOFT_service_short_desc', true);
    $list_price_1 = get_post_meta($id, 'SOFT_service_price_1', true);

    $arr[] = array("descrizione" => $id . "|" . $short_desc);
  }

  header("Content-type: application/json");
  echo json_encode($arr);
  die();
}

add_action('wp_ajax_WPsCRM_get_products_document', 'WPsCRM_get_products_document');

//get products for documenti/list.php
function WPsCRM_get_documents() {
  is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
  if (in_array('wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters('active_plugins', $filter))) {
    do_action('AGsCRM_get_documents_hook');
  } else {
    global $wpdb;
    $arr = array();
    $table = WPsCRM_TABLE . "documenti";
    $c_table = WPsCRM_TABLE . "clienti";
    $m_table = "email";
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $tipo = $_REQUEST['type'];
    $where = "tipo=" . $tipo;
    if (WPsCRM_is_agent() && !WPsCRM_agent_can()) {
      $from = ", $c_table";
      $where .= " and fk_clienti=ID_clienti and agente=$user_id";
    } else {
      $from = ", $c_table";
      $where .= " and fk_clienti=ID_clienti";
    }
    $sql = "SELECT * FROM " . $table . " $from where $where order by data desc";
    foreach ($wpdb->get_results($sql) as $record) {
      if ($record->agente != 0) {
        $user = get_userdata((int) $record->agente);
        $agenteID = (int) $record->agente;
        $agenteName = isset($user->display_name) ? $user->display_name : $user->nickname;
      } else {
        $agenteID = "";
        $agenteName = "";
      }
      $id = $record->id;
      $data = $record->data;
      $data_scadenza = $record->data_scadenza;
      $oggetto = $record->oggetto;
      $progressivo = $record->progressivo;
      $totale = $record->totale_imponibile;
      $registrato = $record->registrato;
      $origine_proforma = $record->origine_proforma;
      $pagato = $record->pagato ? __('Yes', 'cpsmartcrm') : "";
      $fk_clienti = $record->fk_clienti;
      if ($_email = $record->email)
        $email = $record->email;
      else
        $email = "";

      if ($filename = $record->filename) {
        //echo $filename;
        $documentSent = 0;
        $upload_dir = wp_upload_dir();
        $document = $upload_dir['baseurl'] . "/CRMdocuments/" . $filename;
        //echo $document;
        //if (!file_exists($document))
        //  $document="";
        if ($email != "") {
          $sqlm = "SELECT COUNT(*) FROM " . WPsCRM_TABLE . "emails  where e_to = '$email' AND attachments LIKE '%" . str_replace(".pdf", "", $filename) . "%' AND e_sent > 0";
          //echo $sqlm.PHP_EOL;
          if ($wpdb->get_var($sqlm) > 0)
            $documentSent = 1;
        } else
          $documentSent = -1;
      }
      else {
        $document = "";
        $documentSent = 0;
      }
      if (WPsCRM_advanced_print() == false) {
        $document = "";
      }
      $tipo = "";
      if ($record->tipo == 1) {
        $tipo = "P";
        // $pagato="";
      } elseif ($record->tipo == 2)
        $tipo = "F";
      elseif ($record->tipo == 3)
        $tipo = "N";
      if ($fk_clienti) {
        $sqlc = "SELECT nome, cognome, ragione_sociale FROM " . WPsCRM_TABLE . "clienti  where ID_clienti=$fk_clienti";
        $qc = $wpdb->get_row($sqlc);
        if ($qc->ragione_sociale)
          $cliente = $qc->ragione_sociale;
        else
          $cliente = $qc->nome . " " . $qc->cognome;

        //echo $sqlm;
      }

      $arr[] = array("ID" => $id, "tipo" => $tipo, "progressivo" => (int) $progressivo, "datao" => $data, "cliente" => stripslashes($cliente), "email" => $email, "documentSent" => $documentSent, "ID_clienti" => (int) $fk_clienti, "agente" => $agenteID, "agenteName" => $agenteName, "oggetto" => stripslashes($oggetto), "importo" => floatval($totale), "filename" => $document, "pagato" => $pagato, "registrato" => $registrato, 'data_scadenza' => $data_scadenza, 'origine_proforma' => $origine_proforma);
    }
    //var_dump(json_encode($arr ));
    header("Content-type: application/json");
    echo "{\"documents\":" . json_encode($arr) . "}";
    //echo json_encode($arr );
    die();
  }
}

add_action('wp_ajax_WPsCRM_get_documents', 'WPsCRM_get_documents');

/**
 * Summary of WPsCRM_get_documents_for_customer
 */
function WPsCRM_get_documents_for_customer() {
  if (check_ajax_referer('mailToCustomer', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $customer = $_REQUEST['id_cliente'];
    $oDocument = new CRM_document;
    $documents = $oDocument->get_documentsbyCustomerID($customer);
    foreach ($documents as $document) {
      //var_dump( WPsCRM_culture_date_format($document->data_inserimento));
      $date = WPsCRM_culture_date_format($document->data_inserimento);
      //unset($document->data_inserimento);
      $document->culture_data_inserimento = $date;
    }

    $table = WPsCRM_TABLE . "clienti";
    $SQL = "SELECT uploads FROM $table WHERE ID_clienti=$customer";
    $customerFiles = maybe_unserialize($wpdb->get_var($SQL));
    $uploads = array();
    foreach ($customerFiles as $file) {
      $meta = wp_get_attachment_metadata($file['id']);
      $date = get_post($file['id'])->post_modified;
      if ($file['thumbnail'] != null)
        $thumb = $file['thumbnail'];
      else
        $thumb = $file['icon'];
      $uploads[] = array(
          'id' => $file['id'],
          'url' => $file['url'],
          'icon' => $file['icon'],
          'description' => $file['description'],
          'caption' => $file['caption'],
          'thumbnail' => $thumb,
          //'date'=>$meta['image_meta']['created_timestamp']*1000
          'date' => $date,
          'filename' => $meta['file']
      );
    }
    header("Content-type: application/json");
    echo "{\"documents\":" . json_encode($documents) . ",\"files\": " . json_encode($uploads) . "}";
    die();
  } else
    die('security issue');
  die();
}

add_action('wp_ajax_WPsCRM_get_documents_for_customer', 'WPsCRM_get_documents_for_customer');

//delete document row in documenti/form.php
function WPsCRM_delete_document_row() {
  if (check_ajax_referer('delete_document', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "documenti_dettaglio";
    $row_id = $_REQUEST["row_id"];
    $wpdb->update(
            $table, array(
        'eliminato' => 1
            ), array('id' => $row_id), array('%d')
    );
    echo $wpdb->last_query;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_delete_document_row', 'WPsCRM_delete_document_row');

//save pdf document
function WPsCRM_save_pdf_document() {
  if (check_ajax_referer('print_document', 'security', false) && current_user_can('manage_crm')) {

    global $wpdb;
    $d_table = WPsCRM_TABLE . "documenti";
    $fileName = $_REQUEST["fileName"];
    $doc_id = $_REQUEST["doc_id"];
    $pdf_content = $_REQUEST['PDF'];
    $arr_pdf = explode(',', $pdf_content);
    $pdf = base64_decode($arr_pdf[1]);



    $file = WPsCRM_UPLOADS . "/" . $fileName . ".pdf";
    $pdfFile = fopen($file, "w") or die("Unable to open file!");
    fwrite($pdfFile, $pdf);
    fclose($pdfFile);

    $sql = "select filename from $d_table where id=$doc_id";
    $qf = $wpdb->get_row($sql);
    if ($qf->filename) {
      $old_file = $qf->filename;
      $upload_dir = wp_upload_dir();
      $save_to_path = $upload_dir['basedir'] . '/CRMdocuments';

      if (file_exists($save_to_path . "/" . $old_file . ".pdf"))
        unlink($save_to_path . "/" . $old_file . ".pdf");
    }
    $wpdb->update(
            $d_table, array('filename' => "$fileName"), array('id' => $doc_id), array('%s')
    );
    echo $wpdb->last_query;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_save_pdf_document', 'WPsCRM_save_pdf_document');

//make invoice from informal invoice
function WPsCRM_make_invoice() {
  if (check_ajax_referer('delete_document', 'security', false) && current_user_can('manage_crm')) {

    global $wpdb;
    $d_table = WPsCRM_TABLE . "documenti";
    $doc_id = $_REQUEST["doc_id"];
    $cur_year = date("Y");
    $sql = "select data_timestamp from $d_table where tipo=2 order by data_timestamp desc limit 0,1";
    $rigad = $wpdb->get_row($sql, ARRAY_A);
    $document_options = get_option('CRM_documents_settings');
    $document_start = isset($document_options['invoices_start']) ? $document_options['invoices_start'] : "";
    $anno_ultima = date('Y', $rigad["data_timestamp"]);
    $data_fattura = date("Y-m-d");

    if ($cur_year == $anno_ultima) {
      $sql = "select max(progressivo) as last_reg from $d_table where tipo=2 and year(data_inserimento)='$cur_year'";
      $riga = $wpdb->get_row($sql, ARRAY_A);
      if ($document_start > $riga["last_reg"]) {
        $new_reg = $document_start + 1;
        $document_start = $new_reg;
      } else {
        $new_reg = $riga["last_reg"] + 1;
        $document_start = $new_reg;
      }
    } else {
      if (isset($document_start) && $document_start > 0) {
        $new_reg = $document_start + 1;
        $document_start = $new_reg;
      } else {
        $new_reg = 1;
        $document_start = $new_reg;
      }
    }
    $document_options['invoices_start'] = $document_start;
    update_option('CRM_documents_settings', $document_options);
    $wpdb->update(
            $d_table, array('data' => "$data_fattura", 'progressivo' => "$new_reg", 'tipo' => '2'), array('id' => $doc_id), array('%s', '%d', '%d')
    );
    echo $wpdb->last_query;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_make_invoice', 'WPsCRM_make_invoice');

//get records for scheduler/list.php
function WPsCRM_get_scheduler() {
  is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
  if (in_array('wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters('active_plugins', $filter))) {
    do_action('AGsCRM_get_scheduler_hook');
  } else {
    global $wpdb;
    $options = get_option('CRM_general_settings');
    $wp_user_search = $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID");
    $adminArray = array();
    foreach ($wp_user_search as $userid) {
      $curID = $userid->ID;
      $curuser = get_userdata($curID);
      $user_level = $curuser->user_level;
      //Only look for admins
      if ($user_level >= 8)//levels 8, 9 and 10 are admin
        $adminArray[] = (string) $curID;
    }
    $tipo = isset($_REQUEST["type"]) ? $_REQUEST["type"] : null;
    $view = isset($_REQUEST["view"]) ? $_REQUEST["view"] : null;
    $client = isset($_REQUEST["self_client"]) ? $_REQUEST["self_client"] : null;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_info = get_userdata($user_id);
    $user_role = implode(',', $user_info->roles);
    $a_table = WPsCRM_TABLE . "agenda";
    $s_table = WPsCRM_TABLE . "subscriptionrules";
    $arr_results = array();
    $where = "1";

    $where .= $tipo ? " and tipo_agenda=$tipo" : " and tipo_agenda<>0";
    if (!$client)
      $where .= " AND fk_clienti <> 1";

    $sql = "SELECT id_agenda, fk_clienti, fk_utenti_ins, oggetto, annotazioni, start_date, end_date, tipo_agenda, fk_subscriptionrules,esito, fatto FROM " . $a_table . " where $where and eliminato=0 order by fatto, start_date asc";
    //echo $sql;
    foreach ($wpdb->get_results($sql) as $r_scheduler) {
      switch ($tipo_agenda = $r_scheduler->tipo_agenda) {
        case 1:
          $tipo = __('TODO', 'cpsmartcrm');
          $rowClass = "row_todo";
          break;
        case 2:
          $tipo = __('Termin', 'cpsmartcrm');
          $rowClass = "row_appuntamento";
          break;
        case 3:
          $tipo = __('Rechnungszahlung abgelaufen', 'cpsmartcrm');
          break;
        case 4:
          $tipo = __('Kaufen', 'cpsmartcrm');
          break;
        case 5:
          $tipo = __('Auslaufender Dienst', 'cpsmartcrm');
          break;
        case 6:
          $tipo = __('Frist', 'cpsmartcrm');
          break;
        default:
          $tipo = "";
          break;
      }

      $id_s = $r_scheduler->fk_subscriptionrules;
      $fk_clienti = $r_scheduler->fk_clienti;
      $start_date = $r_scheduler->start_date;
      $end_date = $r_scheduler->end_date;
      list ($anno, $mese, $giorno) = explode("-", $start_date);
      if ($fk_clienti) {
        $sqlc = "SELECT nome, cognome, ragione_sociale FROM " . WPsCRM_TABLE . "clienti  where ID_clienti=$fk_clienti";
        $qc = $wpdb->get_row($sqlc);
        if ($qc->ragione_sociale)
          $cliente = $qc->ragione_sociale;
        else
          $cliente = $qc->nome . " " . $qc->cognome;
      }

      $sql = "SELECT * FROM " . $s_table . " where ID=$id_s";
      //echo $sql;
      if ($record = $wpdb->get_row($sql)) {
        $steps = json_decode($record->steps);
        foreach ($steps as $step) {
          $arr_users = array();
          $arr_usersgroup = array();

          //  print_r($step);echo "<br>";
          $rulestep = $step->ruleStep;
          $users = $step->selectedUsers;
          $groups = $step->selectedGroups;
          if ($users) {
            $arr_users = explode(",", $users);
            $destinatari = "";
            foreach ($arr_users as $user) {
              $user_info = get_userdata($user);
              //var_dump($user_info);
              ($user_info->first_name != "" && $user_info->last_name != "") ? $u_name = $user_info->first_name . " " . $user_info->last_name : $u_name = $user_info->user_nicename;
              $destinatari .= $u_name . ", ";
            }
            $destinatari = substr($destinatari, 0, -2);
          }
          if ($groups) {
            $arr_groups = explode(",", $groups);
            foreach ($arr_groups as $group) {
              $arr_usersgroup += get_users(array('fields' => 'ID', 'role' => $group));
            }
          }
          $total_users = array_unique(array_merge($arr_usersgroup, $arr_users));
          $data_agenda = date("Y-m-d", mktime(0, 0, 0, $mese, $giorno - $rulestep, $anno));
          if ($view == "day")
            $cond = ($data_agenda <= date("Y-m-d"));
          elseif ($view == "week") {
            $sett = date("w");
            $primo = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - ($sett - 1), date("Y")));
            $ultimo = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + (5 - $sett), date("Y")));
            $cond = ($data_agenda <= $ultimo);
          } else
            $cond = true;
          $activity_owner = array();
          if ((!in_array($r_scheduler->fk_utenti_ins, $adminArray) ) && $options['deletion_privileges'] == "1")
            $activity_owner[] = (string) $r_scheduler->fk_utenti_ins;
        }
        //      if (in_array($user_id, $arr_users) || strpos($user_role, $arr_groups))
        if ((in_array($user_id, $total_users) || ($user_role == 'administrator' && $options['administrator_all'] == 1) || $user_id == $r_scheduler->fk_utenti_ins ) && $cond) {
          $arr_results[] = array("id_agenda" => $r_scheduler->id_agenda, "cliente" => stripslashes($cliente), "oggetto" => stripslashes($r_scheduler->oggetto), "annotazioni" => stripslashes(html_entity_decode($r_scheduler->annotazioni)), "esito" => stripslashes($r_scheduler->esito), "data_scadenza" => $end_date, "status" => $r_scheduler->fatto, "tipo_agenda" => $tipo, "class" => $rowClass . " " . strtolower(str_replace(" ", "_", $r_scheduler->fatto)), "destinatari" => $destinatari . " - " . $groups, "fk_utenti_ins" => implode(',', array_unique(array_merge($adminArray, $activity_owner))));
        }
      }
    }
    // }
    //usort($arr_results, "WPsCRM_compare_date");
    foreach ($arr_results as $key => $record) {
      $date = strtotime($record["data_scadenza"]);
      if (WPsCRM_show_only_future_activity() && ( $record['status'] == 2 || $record['status'] == 3)) {
        if ($date < time() - 86400) {
          unset($arr_results[$key]);
        } else
          $arr_results[$key]['data_scadenza'] = $record["data_scadenza"];
      } else
        $arr_results[$key]['data_scadenza'] = $record["data_scadenza"];
    }
    $arr_results = array_values($arr_results);

    header("Content-type: application/json");
    echo "{\"scheduler\":" . json_encode($arr_results) . "}";
    die();
  }
}

add_action('wp_ajax_WPsCRM_get_scheduler', 'WPsCRM_get_scheduler');

//get records for client scheduler
function WPsCRM_get_client_scheduler() {
  global $wpdb;
  $options = get_option('CRM_general_settings');
  $wp_user_search = $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID");
  $adminArray = array();
  foreach ($wp_user_search as $userid) {
    $curID = $userid->ID;
    $curuser = get_userdata($curID);
    $user_level = $curuser->user_level;
    //Only look for admins
    if ($user_level >= 8) {//levels 8, 9 and 10 are admin
      $adminArray[] = (string) $curID;
    }
    $id_cliente = $_GET["id_cliente"];
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_info = get_userdata($user_id);
    $user_role = implode(', ', $user_info->roles);
    $a_table = WPsCRM_TABLE . "agenda";
    $s_table = WPsCRM_TABLE . "subscriptionrules";
    $arr_results = array();
    $where = " tipo_agenda<>0";
    $where .= $id_cliente ? " and fk_clienti=$id_cliente" : "";

    $sql = "SELECT id_agenda, fk_clienti, oggetto, annotazioni, start_date, tipo_agenda, fk_subscriptionrules, fatto, esito FROM " . $a_table . " where $where and eliminato=0 order by start_date asc";
    foreach ($wpdb->get_results($sql) as $r_scheduler) {
      switch ($tipo_agenda = $r_scheduler->tipo_agenda) {
        case 1:
          $tipo = "TODO";
          $rowClass = "row_todo";
          break;
        case 2:
          $tipo = "Appuntamento";
          $rowClass = "row_appuntamento";
          break;
        case 3:
          $tipo = "Scadenza pagamento fattura";
          break;
        case 4:
          $tipo = "Acquisto";
          break;
        case 5:
          $tipo = "Scadenza servizio";
          break;
        default:
          $tipo = "";
          break;
      }
      $id_s = $r_scheduler->fk_subscriptionrules;
      $fk_clienti = $r_scheduler->fk_clienti;
      $start_date = $r_scheduler->start_date;
      list ($anno, $mese, $giorno) = explode("-", $start_date);
      if ($fk_clienti) {
        $sqlc = "SELECT nome, cognome, ragione_sociale FROM " . WPsCRM_TABLE . "clienti  where ID_clienti=$fk_clienti";
        $qc = $wpdb->get_row($sqlc);
        if ($qc->ragione_sociale)
          $cliente = $qc->ragione_sociale;
        else
          $cliente = $qc->nome . " " . $qc->cognome;
      }
      $sql = "SELECT * FROM " . $s_table . " where ID=$id_s";
      //echo $sql;
      if ($record = $wpdb->get_row($sql)) {
        $steps = json_decode($record->steps);
        foreach ($steps as $step) {
          $arr_users = array();
          $arr_usersgroup = array();

          //  print_r($step);echo "<br>";
          $rulestep = $step->ruleStep;
          $users = $step->selectedUsers;
          $groups = $step->selectedGroups;
          if ($users) {
            $arr_users = explode(",", $users);

            $destinatari = "";

            foreach ($arr_users as $user) {
              $user_info = get_userdata($user);
              //var_dump($user_info);
              ($user_info->first_name != "" && $user_info->last_name != "") ? $u_name = $user_info->first_name . " " . $user_info->last_name : $u_name = $user_info->user_nicename;
              $destinatari .= $u_name . ", ";
            }
            $destinatari = substr($destinatari, 0, -2);
          }
          if ($groups) {
            $arr_groups = explode(",", $groups);
            foreach ($arr_groups as $group) {
              $arr_usersgroup += get_users(array('fields' => 'ID', 'role' => $group));
            }
          }
          $total_users = array_unique(array_merge($arr_usersgroup, $arr_users));
          $data_agenda = date("Y-m-d", mktime(0, 0, 0, $mese, $giorno - $rulestep, $anno));
          $view = "";
          if ($view == "day")
            $cond = ($data_agenda <= date("Y-m-d"));
          elseif ($view == "week") {
            $sett = date("w");
            $primo = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - ($sett - 1), date("Y")));
            $ultimo = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + (5 - $sett), date("Y")));
            $cond = ($data_agenda <= $ultimo);
          } else
            $cond = true;
          $activity_owner = array();
          if ((!in_array($r_scheduler->fk_utenti_ins, $adminArray) ) && (isset($options['deletion_privileges']) && $options['deletion_privileges'] == "1"))
            $activity_owner[] = (string) $r_scheduler->fk_utenti_ins;
        }

        //      if (in_array($user_id, $arr_users) || strpos($user_role, $arr_groups))
        if ((in_array($user_id, $total_users) || ($user_role == 'administrator' && (isset($options['administrator_all']) && $options['administrator_all'] == 1)) || $user_id == $r_scheduler->fk_utenti_ins ) && $cond) {
          $arr_results[] = array("id_agenda" => $r_scheduler->id_agenda, "tipo" => $tipo, "oggetto" => stripslashes($r_scheduler->oggetto), "annotazioni" => stripslashes(html_entity_decode($r_scheduler->annotazioni)), "esito" => stripslashes($r_scheduler->esito), "data_scadenza" => $r_scheduler->start_date, "status" => $r_scheduler->fatto, "class" => $rowClass . " " . strtolower(str_replace(" ", "_", $r_scheduler->fatto)), "destinatari" => $destinatari . " " . $groups, "fk_utenti_ins" => implode(',', array_unique(array_merge($adminArray, $activity_owner))));
        }
      }
    }
  }


 // usort($arr_results, "WPsCRM_compare_date");

  foreach ($arr_results as $key => $record) {
    $date = strtotime($record["data_scadenza"]);
    if (WPsCRM_show_only_future_activity() && ( $record['status'] == 2 || $record['status'] == 3 )) {
      if ($date < time() - 86400) {
        unset($arr_results[$key]);
      } else {
        $arr_results[$key]['data_scadenza'] = $record["data_scadenza"];
      }
    } else {
      $arr_results[$key]['data_scadenza'] = $record["data_scadenza"];
    }
  }
  $arr_results = array_values($arr_results);

  header("Content-type: application/json");
  echo "{\"scheduler\":" . json_encode($arr_results) . "}";
  die();
}

add_action('wp_ajax_WPsCRM_get_client_scheduler', 'WPsCRM_get_client_scheduler');

//get main info for chosen client
function WPsCRM_get_client_info() {
  global $wpdb;
  $id_clienti = $_GET["id_clienti"];
  $table = WPsCRM_TABLE . "clienti";
  $ID_azienda = 1;
  $sql = "select indirizzo, cap, localita, provincia, cod_fis, p_iva, tipo_cliente from $table where ID_clienti=$id_clienti";
  //echo $sql;
  foreach ($wpdb->get_results($sql) as $record) {
    $arr[] = stripslashes_deep($record);
  }

  header("Content-type: application/json");
  echo "{\"info\":" . json_encode($arr) . "}";
  die();
}

add_action('wp_ajax_WPsCRM_get_client_info', 'WPsCRM_get_client_info');

//get main info for chosen product
/* function WPsCRM_get_product_info() {
  global $wpdb;
  $id=$_GET["id"];
  $sql="SELECT * FROM ".$wpdb->posts."  WHERE  post_type LIKE 'services'  AND ID=$id";
  if( $record=$wpdb->get_results( $sql )){
  $code=get_post_meta($id,'SOFT_service_code',true);
  $short_desc=get_post_meta($id,'SOFT_service_short_desc',true);
  $list_price_1=get_post_meta($id,'SOFT_service_price_1',true);
  $iva=get_post_meta($id,'SOFT_vat',true);
  $price_type=get_post_meta($id,'SOFT_service_price_per_month',true);
  $subscription=get_post_meta($id,'SOFT_service_subscription',true);
  if ($price_type=='month')
  {
  if($subscription !="" && $subscription !=0){
  $SQL="SELECT * FROM ".WPsCRM_TABLE."subscriptionrules WHERE ID=".$subscription;
  $months=$wpdb->get_row($SQL)->length;
  if($months !="")
  $price=$list_price_1 * $months;
  else
  $price=$list_price_1;
  }
  }
  else
  $price=$list_price_1;
  $sql="SELECT * FROM ".WPsCRM_TABLE."subscriptionrules WHERE s_specific=0";
  foreach( $wpdb->get_results( $sql ) as $record_s)
  {
  $id_r=$record_s->ID;
  $name_r=$record_s->name;
  $arr_r[]=array("ID"=>$id_r, "name"=>$name_r);
  }

  $arr[]=array("ID"=>$id, "codice"=>$code, "descrizione"=>$short_desc, "listino1"=>$price, "iva"=>$iva, "arr_rules"=>$arr_r, "rule"=>$subscription);

  }

  header("Content-type: application/json");
  echo "{\"info\":" .json_encode($arr ). "}";
  die();
  }
  add_action( 'wp_ajax_WPsCRM_get_product_info', 'WPsCRM_get_product_info' ); */

//get main info for manual product
function WPsCRM_get_product_manual_info() {
  global $wpdb;
  $options = get_option('CRM_documents_settings');
  $iva = $options['default_vat'];
  $arr_r = array();
  //get list of subscriptionrules
  $sql = "SELECT * FROM " . WPsCRM_TABLE . "subscriptionrules WHERE s_specific=0";
  foreach ($wpdb->get_results($sql) as $record_s) {
    $id_r = $record_s->ID;
    $name_r = $record_s->name;
    $arr_r[] = array("ID" => $id_r, "name" => $name_r);
  }

  $arr[] = array("iva" => $iva, "arr_rules" => $arr_r);

  header("Content-type: application/json");
  echo "{\"info\":" . json_encode($arr) . "}";
  die();
}

add_action('wp_ajax_WPsCRM_get_product_manual_info', 'WPsCRM_get_product_manual_info');

//get users for usage in subscription rules options and other sections related to kendo select
function WPsCRM_get_CRM_users() {
  $arr = array();
//    $current_user = wp_get_current_user();
//	if ( WPsCRM_is_agent() && !WPsCRM_agent_can())
//	    $arr[]= $current_user;
//    else
//    {
  $CRMusers = get_users(array('role__in' => array('CRM_agent', 'CRM_superagent', 'editor', 'administrator'), 'fields' => array('ID', 'display_name')));
  foreach ($CRMusers as $user) {
    $arr[] = $user;
  }
//    }
  header("Content-type: application/json");

  echo json_encode($arr);
  die();
}

add_action('wp_ajax_WPsCRM_get_CRM_users', 'WPsCRM_get_CRM_users', 10);

//get users for usage in subscription rules options and other sections related to kendo select
function WPsCRM_get_CRM_users_customer() {
  is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
  if (in_array('wp-smart-crm-agents/wp-smart-crm-agents.php', apply_filters('active_plugins', $filter))) {
    do_action('AGsCRM_get_users_customer_hook');
  } else {
    $arr = array();
    $CRMusers = get_users(array('role__in' => array('CRM_agent', 'CRM_superagent', 'editor', 'administrator'), 'fields' => array('ID', 'display_name')));
    foreach ($CRMusers as $user) {
      $arr[] = $user;
    }
    header("Content-type: application/json");

    echo json_encode($arr);
    die();
  }
}

add_action('wp_ajax_WPsCRM_get_CRM_users_customer', 'WPsCRM_get_CRM_users_customer', 10);

function WPsCRM_get_CRM_display_name() {
  $id = (int) $_POST['id'];
  $user = get_user_by('id', $id);

  header("Content-type: application/json");
  echo $user->display_name;
  die();
}

add_action('wp_ajax_WPsCRM_get_CRM_display_name', 'WPsCRM_get_CRM_display_name', 10);

//get roles  for usage in subscription rules options
function WPsCRM_get_registered_roles() {

  global $wp_roles;
  $arr = array();
  $index = 1;
  foreach ($wp_roles->roles as $key => $value) {
    if ($key != "CRM_client" && $key != "subscriber" && $key != "author" && $key != "contributor") {
      array_push($arr, array("role" => $key, "name" => $value['name'], "ID" => $index));
      $index++;
    }
  }
  header("Content-type: application/json");
  echo "{\"roles\":" . json_encode($arr) . ' } ';
  die();
}

add_action('wp_ajax_WPsCRM_get_registered_roles', 'WPsCRM_get_registered_roles');

function WPsCRM_add_rule() {
  global $wpdb;
  $table = WPsCRM_TABLE . "subscriptionrules";
  $rule = $_POST['rule'];
  print_r($rule);
  $wpdb->insert(
          $table, array(
      'name' => sanitize_text_field($rule['newRuleName']),
      'length' => $rule['newRuleLength']
          ), array(
      '%s',
      '%d'
          )
  );
  die();
}

add_action('wp_ajax_WPsCRM_add_rule', 'WPsCRM_add_rule');

function WPsCRM_edit_rule() {
  global $wpdb;
  $table = WPsCRM_TABLE . "subscriptionrules";
  $obj = $_POST['obj'];
  $wpdb->update(
          $table, array(
      'name' => sanitize_text_field($obj['editRuleName']),
      'length' => $obj['editRuleLength']
          ), array(
      'ID' => $obj['ID']
          ), array(
      '%s', // valore1
      '%d'
          )
  );
  die();
}

add_action('wp_ajax_WPsCRM_edit_rule', 'WPsCRM_edit_rule');

function WPsCRM_delete_rule() {
  global $wpdb;
  $table = WPsCRM_TABLE . "subscriptionrules";
  print_r($_POST);
  $rule = $_POST['rule'];
  $wpdb->delete($table, array('ID' => $rule));
  echo $wpdb->last_query;
  die();
}

add_action('wp_ajax_WPsCRM_delete_rule', 'WPsCRM_delete_rule');

function WPsCRM_edit_step() {
  global $wpdb;
  $table = WPsCRM_TABLE . "subscriptionrules";
  $steps = urldecode($_POST['steps']);
  $rule = $_POST['rule'];

  $wpdb->update(
          $table, array(
      'steps' => $steps,
          ), array(
      'ID' => $rule
          ), array(
      '%s',
          )
  );
  die();
}

add_action('wp_ajax_WPsCRM_edit_step', 'WPsCRM_edit_step');

function WPsCRM_save_todo() {
  global $wpdb;
  if (check_ajax_referer('update_scheduler', 'security', false) && current_user_can('manage_crm')) {
    $wpdb->show_errors();
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $a_table = WPsCRM_TABLE . "agenda";
    $s_table = WPsCRM_TABLE . "subscriptionrules";
    $steps = urldecode($_POST['steps']);

    $oggetto = $_POST['oggetto'];
    $priorita = $_POST['priorita'];
    $id_cliente = $_POST['id_cliente'];
    $start_date = $_POST['scadenza_inizio'];
    $end_date = $_POST['scadenza_fine'];
    $timestamp_date = date("Y-m-d", strtotime(substr($_REQUEST['scadenza_timestamp'], 0, strpos($_REQUEST['scadenza_timestamp'], "("))));
    $data_inserimento = date("Y-m-d H:i");
    $annotazioni = $_POST['annotazioni'];
    $tipo_agenda = $_POST['tipo_agenda'];
    $instantNotification = $_POST['instantNotification'];
    if ($tipo_agenda == 1)
      $n = "Todo";
    else
      $n = "Appuntamento";
    $wpdb->insert(
            $s_table, array(
        'steps' => $steps,
        'name' => $n,
        's_specific' => 1,
        's_type' => $tipo_agenda
            ), array('%s', '%s', '%d', '%d')
    );
    $id_sr = $wpdb->insert_id;
    $wpdb->insert(
            $a_table, array(
        'oggetto' => $oggetto,
        'fk_clienti' => $id_cliente,
        'annotazioni' => $annotazioni,
        'start_date' => WPsCRM_sanitize_date_format($start_date),
        'end_date' => WPsCRM_sanitize_date_format($end_date),
        'data_inserimento' => $data_inserimento,
        'fk_subscriptionrules' => $id_sr,
        'tipo_agenda' => $tipo_agenda,
        'priorita' => $priorita,
        'fatto' => 1,
        'fk_utenti_ins' => $user_id
            ), array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d')
    );

    $fk_scheduler = $wpdb->insert_id;
    $mail = new CRM_mail(array("ID_agenda" => $fk_scheduler, "sendNow" => $instantNotification));
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_save_todo', 'WPsCRM_save_todo');

function WPsCRM_save_annotation() {
  global $wpdb;
  $c_table = WPsCRM_TABLE . "clienti";
  var_dump($_POST);
  $id_cliente = $_POST['id_cliente'];
  $data_attivita = $_POST['data_attivita'];
  $current_user = wp_get_current_user();
  $user_firstname = $current_user->user_firstname;
  $user_lastname = $current_user->user_lastname;
  $data_attivita = date("Y-m-d H:i", strtotime(substr($_REQUEST['activityTimestamp'], 0, strpos($_REQUEST['activityTimestamp'], "("))));
  $annotazioni = $data_attivita . "~" . $user_firstname . " " . $user_lastname . "~" . $_POST['annotazioni'] . "|";
  $result = $wpdb->query(
          $wpdb->prepare(
                  "UPDATE $c_table SET annotazioni=concat(IFNULL(annotazioni,''),%s) WHERE ID_clienti=%d", $annotazioni, $id_cliente
          )
  );

  die();
}

add_action('wp_ajax_WPsCRM_save_annotation', 'WPsCRM_save_annotation');

function WPsCRM_delete_annotation() {
  global $wpdb;
  $c_table = WPsCRM_TABLE . "clienti";
  $id_cliente = $_REQUEST['id_cliente'];
  $index = $_REQUEST['index'];
  $SQL = "SELECT ID_clienti,annotazioni FROM $c_table WHERE ID_clienti=$id_cliente";

  $annotazioni = $wpdb->get_row($SQL);
  $array_annotazioni = explode('|', $annotazioni->annotazioni);
  //$array_annotazioni=array_reverse(array_filter($array_annotazioni) );

  unset($array_annotazioni[(int) $index]);
  $_annotazioni = implode('|', $array_annotazioni);
  $wpdb->update(
          $c_table, array(
      'annotazioni' => $_annotazioni
          ), array('ID_clienti' => $id_cliente), array('%s')
  );
  die();
}

add_action('wp_ajax_WPsCRM_delete_annotation', 'WPsCRM_delete_annotation');

function WPsCRM_reload_annotation() {
  global $wpdb;
  $table = WPsCRM_TABLE . "clienti";
  $sql = "select * from $table where ID_clienti=" . $_REQUEST['id_cliente'];
  $riga = $wpdb->get_row($sql);
  $field_separator = "~";
  $row_separator = "|";
  $timeline = explode($row_separator, $riga->annotazioni);
  $index = 0;
  $html = "<div id=\"_timeline\" style=\"display:none\">";
  foreach ($timeline as $activity) {
    if ($activity != "") {
      $act = explode($field_separator, $activity);
      $date = $act[0];

      $author = $act[1];
      $object = $act[2];

      $html .= '<div class="cd-timeline-block" data-index="' . $index . '" data-date="' . $date . '">
			<div class="cd-timeline-img cd-picture">
				<i class="glyphicon glyphicon-map-marker" style="font-size: 60px;color:darkgreen;position:relative;margin-left: -16px;margin-top: -8px;"></i>
			</div>
			<div class="cd-timeline-content">
				<h2>' . $author . '<i class="glyphicon glyphicon-remove" style="float:right;cursor:pointer;font-size:1.2em;margin-right:10px"></i></h2>
				<p>' . stripslashes($object) . '</p>
				<span class="cd-date">' . $date . '</span>
			</div>
		</div>';
      $index++;
    }
  }
  $html .= "</div>";
  echo $html;
  die();
}

add_action('wp_ajax_WPsCRM_reload_annotation', 'WPsCRM_reload_annotation');

//register invoices for register_invoices/form.php
function WPsCRM_register_invoices() {
  if (check_ajax_referer('update_document', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "documenti";
    $data_from = WPsCRM_sanitize_date_format($_POST["data_from"]);
    $data_to = WPsCRM_sanitize_date_format($_POST["data_to"]);
    $result = $wpdb->query(
            $wpdb->prepare(
                    "UPDATE $table SET registrato=1 WHERE tipo='2' and data>=%s and data<=%s", $data_from, $data_to
            )
    );

    echo $wpdb->last_query;
    die();
  }
}

add_action('wp_ajax_WPsCRM_register_invoices', 'WPsCRM_register_invoices');

//import customers
function WPsCRM_import_customers() {
  if (check_ajax_referer('import_customers', 'security', false) && current_user_can('manage_crm')) {
    //var_dump($_POST);
    //die();
    $newsletter_flag = $_POST["newsletter_flag"];
    is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
    if (in_array('newsletter/plugin.php', apply_filters('active_plugins', $filter)) && $newsletter_flag == 1)
      $newsletter = 1;
    else
      $newsletter = 0;
    global $wpdb;
    $table = WPsCRM_TABLE . "clienti";
    $ID_azienda = "1";
    $data_inserimento = date("Y-m-d");
    $data_modifica = date("Y-m-d");
    $file = $_FILES["file"]["tmp_name"];
    $override = $_POST["override"];
    if ($file) {
      if ($override) {
        $wpdb->query(
                $wpdb->prepare(
                        "
                     DELETE FROM $table
		             WHERE ID_clienti<>%d
		            ", 1
                )
        );
      }
      $handle = fopen($file, "r");
      $all_rows = array();
      $header = null;
      while (($data = fgetcsv($handle, 2000, ";")) !== FALSE) {
        if ($header === null) {
          $header = $data;
          continue;
        }
        $all_rows[] = array_combine($header, $data);
      }
      $row = 1;
      $arr_err = array();
      $string_err = "";

      foreach ($all_rows as $riga) {
        if (($riga["firstname"] && $riga["lastname"]) || $riga["company"]) {
          $arr_str_categories = explode(",", $riga["categories"]);
          $str_categories = "";
          if (!$riga["invoiceable"])
            $riga["invoiceable"] = 0;
          if (!$riga["type"])
            $riga["type"] = 0;
          foreach ($arr_str_categories as $cat) {
            if ($cat != "0" && $cat != "") {
              $categorybyname = get_term_by('name', $cat, 'WPsCRM_customersCat');
              if ($categorybyname != false) {
                $cat_id = $categorybyname->term_id;
              } else {
                $ret = wp_insert_term($cat, 'WPsCRM_customersCat');
                if (!is_wp_error($ret))
                  $cat_id = $ret["term_id"];
              }
              $str_categories = $str_categories . $cat_id . ",";
            }
          }
          $str_categories = rtrim($str_categories, ',');
          $arr_str_origins = explode(",", $riga["origins"]);
          $str_origins = "";
          foreach ($arr_str_origins as $ori) {
            if ($ori != "0" && $ori != "") {
              $categorybyname = get_term_by('name', $ori, 'WPsCRM_customersProv');
              if ($categorybyname != false) {
                $ori_id = $categorybyname->term_id;
              } else {
                $ret = wp_insert_term($ori, 'WPsCRM_customersProv');
                if (!is_wp_error($ret))
                  $ori_id = $ret["term_id"];
              }
              $str_origins = $str_origins . $ori_id . ",";
            }
          }
          $str_origins = rtrim($str_origins, ',');
          $arr_str_interests = explode(",", $riga["interests"]);
          $str_interests = "";
          foreach ($arr_str_interests as $int) {
            if ($int != "0" && $int != "") {
              $categorybyname = get_term_by('name', $int, 'WPsCRM_customersInt');
              if ($categorybyname != false) {
                $int_id = $categorybyname->term_id;
              } else {
                $ret = wp_insert_term($int, 'WPsCRM_customersInt');
                if (!is_wp_error($ret))
                  $int_id = $ret["term_id"];
              }
              $str_interests = $str_interests . $int_id . ",";
            }
          }
          $str_interests = rtrim($str_interests, ',');
          $current_user = wp_get_current_user();
          $user_firstname=$current_user->user_firstname;
          $user_lastname=$current_user->user_lastname;

          $annotazioni=date("d-m-Y")."~".$user_firstname." ".$user_lastname."~".addslashes(WPsCRM_string_convert($riga["notes"]))."|";
          $agente=(int)$riga["agent"];
          if (!$wpdb->insert(
                          $table, array('FK_aziende' => "$ID_azienda", 'nome' => addslashes(WPsCRM_string_convert($riga["firstname"])), 'cognome' => addslashes(WPsCRM_string_convert($riga["lastname"])), 'ragione_sociale' => addslashes(WPsCRM_string_convert($riga["company"])), 'indirizzo' => addslashes(WPsCRM_string_convert($riga["address"])), 'cap' => addslashes(WPsCRM_string_convert($riga["zip"])), 'localita' => addslashes(WPsCRM_string_convert($riga["city"])), 'provincia' => addslashes(WPsCRM_string_convert($riga["province"])), 'p_iva' => addslashes(WPsCRM_string_convert($riga["vat"])), 'cod_fis' => addslashes(WPsCRM_string_convert($riga["taxcode"])), 'telefono1' => addslashes(WPsCRM_string_convert($riga["phone1"])), 'telefono2' => addslashes(WPsCRM_string_convert($riga["mobile"])), 'fax' => addslashes(WPsCRM_string_convert($riga["fax"])), 'email' => addslashes(WPsCRM_string_convert($riga["email"])), 'sitoweb' => addslashes(WPsCRM_string_convert($riga["website"])), 'skype' => addslashes(WPsCRM_string_convert($riga["skype"])), 'data_modifica' => "$data_modifica", 'data_inserimento' => "$data_inserimento", 'data_nascita' => addslashes(WPsCRM_string_convert($riga["birthdate"])), 'luogo_nascita' => addslashes(WPsCRM_string_convert($riga["birthplace"])), 'nazione' => addslashes(WPsCRM_string_convert($riga["country"])), 'fatturabile' => $riga["invoiceable"], 'tipo_cliente' => $riga["type"], 'categoria' => $str_categories, 'provenienza' => $str_origins, 'interessi' => $str_interests, 'annotazioni' => $annotazioni, 'agente' => $agente), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d')
                  )) {
            //gestire errore mancato inserimento
            $row_err = __('Generic error', 'cpsmartcrm');
            $arr_err[] = array("riga" => $row, "errore" => $row_err);
            $string_err .= "#$row $row_err\n";
          }
          //echo $wpdb->last_query;
          if ($newsletter) {
            $arr_lists = explode(",", $_POST["lists"]);
            //var_dump($arr_lists);
            for ($i = 1; $i <= 20; $i++) {
              $nomelista = "list_" . $i;
              if (in_array($nomelista, $arr_lists))
                ${"list" . $i} = 1;
              else
                ${"list" . $i} = 0;
            }
            if ($riga["newsletter"] == 1) {
              $sql = "select id from " . $wpdb->prefix . 'newsletter' . " where email='" . $riga["email"] . "'";
              if ($rigan = $wpdb->get_row($sql)) {
                $insert_in_mailinglist = $wpdb->update(
                        $wpdb->prefix . 'newsletter', array("list_1" => $list1, "list_2" => $list2, "list_3" => $list3, "list_4" => $list4, "list_5" => $list5, "list_6" => $list6, "list_7" => $list7, "list_8" => $list8, "list_9" => $list9, "list_10" => $list10, "list_11" => $list11, "list_12" => $list12, "list_13" => $list13, "list_14" => $list14, "list_15" => $list15, "list_16" => $list16, "list_17" => $list17, "list_18" => $list18, "list_19" => $list19, "list_20" => $list20), array('ID' => $rigan->id), array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d')
                );
              } else {
                if ($riga["company"]) {
                  $name = $riga["company"];
                  $surname = "";
                } else {
                  $name = $riga["firstname"];
                  $surname = $riga["lastname"];
                }
                $token = substr(md5(rand()), 0, 10);
                $insert_in_mailinglist = $wpdb->insert(
                        $wpdb->prefix . 'newsletter', array('name' => $name, 'surname' => $surname, 'sex' => 'n', 'status' => 'C', 'created' => current_time('mysql', 1), 'email' => $riga["email"], 'token' => $token, "list_1" => $list1, "list_2" => $list2, "list_3" => $list3, "list_4" => $list4, "list_5" => $list5, "list_6" => $list6, "list_7" => $list7, "list_8" => $list8, "list_9" => $list9, "list_10" => $list10, "list_11" => $list11, "list_12" => $list12, "list_13" => $list13, "list_14" => $list14, "list_15" => $list15, "list_16" => $list16, "list_17" => $list17, "list_18" => $list18, "list_19" => $list19, "list_20" => $list20), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d')
                );
              }
            }
          }
        } else {
          //gestire errore dati obbligatori mancanti
          $row_err = "";
          if (!$riga["firstname"])
            $row_err .= sprintf(__('Fehlendes Feld: %s', 'cpsmartcrm'), "firstname") . "; ";
          if (!$riga["lastname"])
            $row_err .= sprintf(__('Fehlendes Feld: %s', 'cpsmartcrm'), "lastname") . "; ";
          if (!$riga["company"])
            $row_err .= sprintf(__('Fehlendes Feld: %s', 'cpsmartcrm'), "company");
          $arr_err[] = array("riga" => $row, "errore" => $row_err);
          $string_err .= "#$row $row_err\n";
        }
        $row++;
      }
    }
    else {
      $msg_err = __('Datei konnte nicht gelesen werden. Versuche es erneut.', 'cpsmartcrm');
      header("Content-type: application/json");
      echo json_encode(array("mess_id" => 1, "msg" => $msg_err));
    }
    if (count($arr_err)) {
      $nomefile = "error_import_" . date("YmdHi") . ".txt";
      $myFile = WPsCRM_DIR . "/logs/" . $nomefile;
      $fo = fopen($myFile, 'w');
      fwrite($fo, $string_err);
      fclose($fo);
      header("Content-type: application/json");
      //echo json_encode($arr_err);
      echo "{\"mess_id\": 2,\"errore\":" . json_encode($arr_err) . "}";
    } else {
      $msg_ok = __('Import erfolgreich abgeschlossen.', 'cpsmartcrm');
      header("Content-type: application/json");
      echo json_encode(array("mess_id" => 3, "msg" => $msg_ok));
    }
    die();
  }
}

add_action('wp_ajax_WPsCRM_import_customers', 'WPsCRM_import_customers');

function WPsCRM_is_user_registrable() {
  $email = $_POST['email'];
  $user = $_POST['_user'];
  if (!is_user_logged_in() && !email_exists($email) && !username_exists(sanitize_user($user)))
    echo $user;
  elseif (is_user_logged_in())
    echo $user;
  else
    echo "false";
  die();
}

add_action('wp_ajax_nopriv_WPsCRM_is_user_registrable', 'WPsCRM_is_user_registrable');
add_action('wp_ajax_WPsCRM_is_user_registrable', 'WPsCRM_is_user_registrable');

function WPsCRM_new_user($data) {
  //print_r($data);
  $user = $data['CRM_username'];
  $password = $data['CRM_password'];
  $password_strenght = $data['pwd_strenght'];

  $userdata = array(
      'user_login' => $user,
      //'user_url'    =>  $website,
      'user_email' => $data['CRM_email'],
      'user_pass' => $password,
      'first_name ' => sanitize_text_field($data['CRM_firstname']),
      'last_name' => sanitize_text_field($data['CRM_lastname'])
  );
  if (!username_exists($user)) {
    $user_id = wp_insert_user($userdata);
    $user_id_role = new WP_User($user_id);
    $user_id_role->set_role('CRM_client');
    wp_update_user(array(
        "ID" => $user_id,
        "first_name" => ucfirst(strtolower(sanitize_text_field($data['CRM_firstname']))),
        "last_name" => ucfirst(strtolower(sanitize_text_field($data['CRM_lastname']))),
        "display_name" => ucfirst(strtolower(sanitize_text_field($data['CRM_firstname']))) . " " . ucfirst(strtolower(sanitize_text_field($data['CRM_lastname']))),
        "nickname" => ucfirst(strtolower(sanitize_text_field($data['CRM_firstname']))),
        "user_email" => $data['CRM_email']
            )
    );

    WPsCRM_wp_new_user_notificattion($user_id, $password, $password_strenght);
  } else {
    $user_id = get_user_by('login', $user)->ID;
    wp_update_user(array(
        "ID" => $user_id,
        "first_name" => ucfirst(strtolower(sanitize_text_field($data['CRM_firstname']))),
        "last_name" => ucfirst(strtolower(sanitize_text_field($data['CRM_lastname']))),
        'user_email' => $data['CRM_email']
            )
    );
    //wp_update_user( array("ID"=>$user_id, array() );        
  }
  update_user_meta($user_id, "CRM_ID", $data['CRM_ID']);
  //update_user_meta( $user_id, "CRM_ID",  $data['CRM_ID'] ) ;
  return $user_id;
}

add_filter('WPsCRM_add_User', 'WPsCRM_new_user', 10, 1);

function WPsCRM_mail_to_customer() {
  if (check_ajax_referer('mailToCustomer', 'security', false) && current_user_can('manage_crm')) {

    $attachments = array();
    if ($_REQUEST['attachments'] != "") {

      $_attachments = json_decode(stripslashes($_REQUEST['attachments']));
      $upload_dir = wp_upload_dir();
      foreach ($_attachments as $_attachment) {

        if (!strstr($_attachment, "http")) {
          if (is_file(WPsCRM_UPLOADS . "/" . $_attachment))
            $attachments[] = WPsCRM_UPLOADS . "/" . $_attachment;
          elseif (is_file($upload_dir['basedir'] . '/' . $_attachment))
            $attachments[] = $upload_dir['basedir'] . '/' . $_attachment;
        }
        else {
          $attachments[] = $upload_dir['basedir'] . '/' . $_attachment;
        }
      }
    }

    $oCustomer = new CRM_customer;
    $oCustomer->set_customer($_REQUEST['id_cliente']);
    $customer = $oCustomer->get_customer();
    $newMail = new CRM_mail(array('customerID' => (int) $_REQUEST['id_cliente']));

    $mailfrom = $newMail->emailFrom;
    $mailto = $customer->email;

    $headers[] = 'From: ' . $newMail->emailFrom . PHP_EOL;
    if ($_REQUEST['mailNow'] == 1) {
      if ($_REQUEST['toCustomer'] == 1) {
        $newMail->sendMailToCustomer($mailfrom, $mailto, $_REQUEST['subject'], $_REQUEST['message'], date("Y-m-d H:i"), (int) $_REQUEST['id_cliente'], $headers, $attachments);
      }
      if ($_REQUEST['toUsers'] == 1) {
        foreach ($_REQUEST['Users'] as $user) {
          if ($user != "") {
            $userInfo = get_userdata((int) $user);
            $userEmail = $userInfo->user_email;
            $newMail->sendMailToUser($mailfrom, $userEmail, $_REQUEST['subject'], $_REQUEST['message'], date("Y-m-d H:i"), $userInfo->ID, $headers, $attachments);
          }
        }
        foreach ($_REQUEST['Groups'] as $group) {
          if ($group != "") {
            $_users = get_users(array('role' => $group));
            foreach ($_users as $mailuser) {
              if (!in_array($mailuser->ID, $_REQUEST['Users'])) {
                $userInfo = get_userdata($mailuser->ID);
                $userEmail = $userInfo->user_email;
                $mailtoUser = $userEmail->ID;
                $newMail->sendMailToUser($mailfrom, $userEmail, $_REQUEST['subject'], $_REQUEST['message'], date("Y-m-d H:i"), $userInfo->ID, $headers, $attachments);
              }
            }
          }
        }
      }
    } else {
      $time = time() + $_REQUEST['timediff'];
      $date = date("Y-m-d H:i", $time);
      echo "data=" . $date;
      //$date=WPsCRM_sanitize_date_format($_REQUEST['scheduled']);
      $newMail->setQueue($mailfrom, $mailto, $_REQUEST['subject'], $_REQUEST['message'], $date, 0, 0, 0, $_REQUEST['id_cliente'], $attachments);
    }

    die();
  } else
    die('security issue');
}

add_action('wp_ajax_WPsCRM_mail_to_customer', 'WPsCRM_mail_to_customer');

function WPsCRM_provenienza($selected) {
  $arr = array("SEGNALAZIONE");
  ?>
  <select name="provenienza" id="provenienza" class="form-control  k-dropdown _m">
      <option></option>
      <?php
      for ($i = 0; $i < count($arr); $i++) {
        ?>
        <option value="<?php echo $arr[$i] ?>" <?php echo $arr[$i] == $selected ? "selected" : "" ?>><?php echo $arr[$i] ?></option>
        <?php
      }
      ?>
  </select>
  <?php
}

function WPsCRM_priorita($selected = "Normale") {
  $arr = array(__('Low', 'cpsmartcrm'), __('Normal', 'cpsmartcrm'), __('High', 'cpsmartcrm'));
  ?>
  <select name="priorita" id="priorita" class="form-control k-dropdown _m _flat">
      <?php
      for ($i = 0; $i < count($arr); $i++) {
        ?>
        <option value="<?php echo $i + 1 ?>" <?php echo $i + 1 == $selected ? "selected" : "" ?>><?php echo $arr[$i] ?></option>
        <?php
      }
      ?>
  </select>
  <?php
}

function WPsCRM_sanitize($input) {

  $output = array();

  foreach ($input as $key => $value) {


    if (isset($input[$key])) {

      $output[$key] = sanitize_text_field($input[$key]);
    } // end if
  } // end foreach


  return apply_filters('WPsCRM_sanitize', $output, $input);
}

function WPsCRM_wp_new_user_notificattion($user_id, $plaintext_pass, $pass_strenght) {
  $user = new WP_User($user_id);

  $user_login = stripslashes($user->user_login);
  $display_name = $user->display_name;
  $user_email = stripslashes($user->user_email);

  $email_subject = "Willkommen bei " . htmlspecialchars_decode(strip_tags(get_bloginfo('name'))) . " " . $user_login . "!";

  ob_start();

  //include(__DIR__."/email/email_header.php");
  ?>
  <html>
      <head>

          <title><?php _e('Neue Benutzerregistrierung am', 'cpsmartcrm') ?> <?php bloginfo('name') ?></title>

      </head>
      <body>
          <p><?php _e('Ein ganz besonderes Willkommen an Dich', 'cpsmartcrm') ?>, <b><?php echo $display_name ?></b>. <?php _e('Danke, dass Du mitmachst', 'cpsmartcrm') ?> <i>"<?php bloginfo('name') ?>"</i>!</p>

          <p>
              <?php _e('Dein Passwort lautet', 'cpsmartcrm') ?> <strong style="color:orange"><?php echo $plaintext_pass ?></strong> <br>

          </p>
          <p>
              <?php
              switch ($pass_strenght) {
                case "undefined":
                  break;
                case 'veryweak':

                  $_message = __('Deine Passwortstärke wurde erkannt als', 'cpsmartcrm') . '<strong>' . __('sehr Schwach', 'cpsmartcrm') . '!!</strong>.' . __('Wir können keine Benutzer mit schwachen Passwörtern akzeptieren, da die Sicherheit der gesamten Webseite gefährdet sein könnte', 'cpsmartcrm') . '<br>' . __('Daher muss Dein Passwort mit Sonderzeichen, Großbuchstaben und normalen Buchstaben und Zahlen (mindestens 8) aenthalten. Klicke hier, um es zu aktualisieren', 'cpsmartcrm') . ': <a href="' . get_page_link($user_page) . '">' . __('Dein Profil', 'cpsmartcrm') . '</a>, ' . __('Danke', 'cpsmartcrm');
                  break;
                case 'weak':
                  $_message = __('Deine Passwortstärke wurde erkannt als', 'cpsmartcrm') . '<strong>' . __('Schwach', 'cpsmartcrm') . '!!</strong>.' . __('Wir können keine Benutzer mit schwachen Passwörtern akzeptieren, da die Sicherheit der gesamten Webseite gefährdet sein könnte', 'cpsmartcrm') . '<br>' . __('Daher muss Dein Passwort mit Sonderzeichen, Großbuchstaben und normalen Buchstaben und Zahlen (mindestens 8) aenthalten. Klicke hier, um es zu aktualisieren', 'cpsmartcrm') . ': <a href="' . get_page_link($user_page) . '">' . __('Dein Profil', 'cpsmartcrm') . '</a>, ' . __('Danke', 'cpsmartcrm');
                  break;
                case 'medium':
                  $_message = __('Deine Passwortstärke wurde erkannt als', 'cpsmartcrm') . '<strong>' . __('Mittel', 'cpsmartcrm') . '!!</strong>.' . __('Wir können keine Benutzer mit schwachen Passwörtern akzeptieren, da die Sicherheit der gesamten Webseite gefährdet sein könnte', 'cpsmartcrm') . '<br>' . __('Daher muss Dein Passwort mit Sonderzeichen, Großbuchstaben und normalen Buchstaben und Zahlen (mindestens 8) aenthalten. Klicke hier, um es zu aktualisieren', 'cpsmartcrm') . ': <a href="' . get_page_link($user_page) . '">' . __('Dein Profil', 'cpsmartcrm') . '</a>, ' . __('Danke', 'cpsmartcrm');
                  break;
                case 'strong':
                  break;
              }
              echo $_message;
              ?>
          </p>
          <p>
              <?php _e('Mit freundlichen Gruß', 'cpsmartcrm') ?>,<br>
              <?php bloginfo('name') ?> 
          </p>
      </body>
  </html>
  <?php
  $message = ob_get_contents();
  ob_end_clean();
  wp_mail($user_email, $email_subject, $message);
}

register_activation_hook(__FILE__, 'WPsCRM_wp_new_user_notificattion');
add_filter("WPsCRM_wp_mail_content_type", "WPsCRM_my_awesome_mail_content_type");

function WPsCRM_my_awesome_mail_content_type() {
  return "text/html";
}

add_filter("WPsCRM_p_mail_from", "WPsCRM_my_awesome_mail_from");

function WPsCRM_my_awesome_mail_from() {
  return "info@smart-cms.n3rds.work";
}

add_filter("WPsCRM_wp_mail_from_name", "WPsCRM_my_awesome_mail_from_name");

function WPsCRM_my_awesome_mail_from_name() {
  return "Softrade";
}

if (!wp_next_scheduled('WPsCRM_send_mail_hook')) {
  wp_schedule_event(time(), 'hourly', 'WPsCRM_send_mail_hook');
}

function WPsCRM_send_mail() {
  global $wpdb;
  $e_table = WPsCRM_TABLE . "emails";
  $a_table = WPsCRM_TABLE . "agenda";
  $sql = "SELECT $e_table.* FROM $e_table where e_sent=0 and e_unsent='' and e_date<=NOW()";
  foreach ($wpdb->get_results($sql) as $record) {
    $headers[] = 'From: ' . $record->e_from . PHP_EOL;
    $id = $record->id;
    $e_date = $record->e_date;
    $e_to = $record->e_to;
    $e_subject = $record->e_subject;
    $attachments = json_decode($record->attachments);
    $e_body = $record->e_body;
    if ($fk_agenda = $record->fk_agenda) {
      $sql = "SELECT $a_table.fatto FROM $a_table where id_agenda=$fk_agenda";
      $fatto = $wpdb->get_row($sql)->fatto;
      if ($fatto == 1) {
        if (wp_mail($e_to, $e_subject, $e_body, $headers, $attachments)) {
          $wpdb->update(
                  $e_table, array(
              'e_sent' => 1
                  ), array('id' => $id), array('%d')
          );
        }
      }
      if ($fatto == 2) {
        $wpdb->update(
                $e_table, array(
            'e_unsent' => 'Agenda espletata'
                ), array('id' => $id), array('%s')
        );
      }
      if ($fatto == 3) {
        $wpdb->update(
                $e_table, array(
            'e_unsent' => 'Agenda annullata'
                ), array('id' => $id), array('%s')
        );
      }
    } else {
      //nessuna agenda collegata, es. invio mail a cliente
      if (wp_mail($e_to, $e_subject, $e_body, $headers, $attachments)) {
        $wpdb->update(
                $e_table, array(
            'e_sent' => 1
                ), array('id' => $id), array('%d')
        );
      }
    }
  }
}

add_action('WPsCRM_send_mail_hook', 'WPsCRM_send_mail');

function WPsCRM_gen_random_code($length) {
  $characters = "abcdefghijklmnopqrstuvwxyzABCDERFGHIJKLMNOPQRSTUVWXYZ0123456789";
  $randomString = "";
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
  }
  return $randomString;
}

function WPsCRM_parse_annotation($string_to_parse, $field_separator = "~", $row_separator = "|") {
  $new_string = str_replace($row_separator, '<br>', stripslashes($string_to_parse));
  return $new_string;
}

function WPsCRM_timeline_annotation($string_to_parse, $print = true, $field_separator = "~", $row_separator = "|") {
  $timeline = explode($row_separator, $string_to_parse);
  //usort($timeline, "WPsCRM_compare_date");
  //var_dump($timeline);
  $index = 0;
  $html = "";
  $arr_act=array();
  foreach ($timeline as $activity) {
    if ($activity != "") {
      $act = explode($field_separator, $activity);
      $date = $act[0];
      $author = $act[1];
      $object = $act[2];
      $arr_act[]=array("data_scadenza"=>$date, "autore"=>$author, "oggetto"=>$object);
    }
  }
  //var_dump($arr_act);
  usort($arr_act, "WPsCRM_compare_date");
  //var_dump($arr_act);
  echo "<div id=\"_timeline\" style=\"display:none\">";
  foreach ($arr_act as $act) {
   // if ($activity != "") {
    //  $act = explode($field_separator, $activity);
      $date = WPsCRM_sanitize_date_format($act["data_scadenza"]);
      $author = $act["autore"];
      $object = $act["oggetto"];
      if ($print == true) {
        ?>
        <div class="cd-timeline-block" data-index="<?php echo $index ?>" data-date="<?php echo $date ?>">
            <div class="cd-timeline-img cd-picture">
                <i class="glyphicon glyphicon-map-marker" style="font-size: 60px;color:darkgreen;position:relative;margin-left: -16px;margin-top: -8px;"></i>
            </div>

            <div class="cd-timeline-content">
                <h2><?php echo $author ?><i class="glyphicon glyphicon-remove" style="float:right;cursor:pointer;font-size:1.2em;margin-right:10px"></i></h2>
                <p><?php echo stripslashes($object) ?></p>

                <span class="cd-date"><?php echo $date ?></span>
            </div>
        </div>
        <?php
      } else {
        $html .= '<div class="cd-timeline-block" data-index="' . $index . '">
			<div class="cd-timeline-img cd-picture">
				<i class="glyphicon glyphicon-map-marker" style="font-size: 60px;color:darkgreen;position:relative;margin-left: -16px;margin-top: -8px;"></i>
			</div>
			<div class="cd-timeline-content">
				<h2>' . $author . '<i class="glyphicon glyphicon-remove" style="float:right;cursor:pointer;font-size:1.2em;margin-right:10px"></i></h2>
				<p>' . stripslashes($object) . '</p>
				<span class="cd-date">' . $date . '</span>
			</div>
		</div>';
      }

      $index++;
  //  }
  }
  echo "</div>";
  return $html;
}

function WPsCRM_insert_notification($subscriptionrules, $document_id, $document_row_id, $scheduler_id) {
  global $wpdb;
  $SQL = "SELECT * FROM " . WPsCRM_TABLE . "subscriptionrules WHERE ID=" . $subscriptionrules;
  $months = $wpdb->get_row($SQL)->length;
  if ($document_id) {
    $res_d = $wpdb->get_row("select progressivo, fk_clienti, data from " . WPsCRM_TABLE . "documenti where id=$document_id");
    $prog = $res_d->progressivo;
    $fk_clienti = $res_d->fk_clienti;
    $data = $res_d->data;
    list ($anno, $mese, $giorno) = explode("-", $data);
    $data_scadenza = date("Y-m-d", mktime(0, 0, 0, $mese + $months, $giorno, $anno));
    if ($document_row_id) {
      $sql = "select descrizione from " . WPsCRM_TABLE . "documenti_dettaglio where id=$document_row_id";
      $qd = $wpdb->get_row($sql);
      $descrizione = $qd->descrizione;
    }
    if ($fk_clienti) {
      $sqlc = "SELECT nome, cognome, ragione_sociale, email FROM " . WPsCRM_TABLE . "clienti  where ID_clienti=$fk_clienti";
      $qc = $wpdb->get_row($sqlc);
      if ($qc->ragione_sociale)
        $cliente = $qc->ragione_sociale;
      else
        $cliente = $qc->nome . " " . $qc->cognome;
      $email = $qc->email;
    }
    $data_inserimento = date("Y-m-d H:i:s");
    $scheduler_subject = __("Auslaufender Dienst", 'cpsmartcrm');
    $scheduler_notes = __("Auslaufender Dienst", 'cpsmartcrm');
    $scheduler_notes .= " " . $descrizione . " ";
    $scheduler_notes .= __("Rechnung #", 'cpsmartcrm');
    $scheduler_notes .= " " . $prog . " ";
    $scheduler_notes .= __("zu", 'cpsmartcrm');
    $scheduler_notes .= " " . $cliente;
    $wpdb->insert(
            WPsCRM_TABLE . "agenda", array(
        'fk_clienti' => $fk_clienti,
        'start_date' => $data_scadenza,
        'end_date' => $data_scadenza,
        'fk_documenti' => $document_id,
        'oggetto' => $scheduler_subject,
        'annotazioni' => $scheduler_notes,
        'tipo_agenda' => 5,
        'data_inserimento' => $data_inserimento,
        'fk_subscriptionrules' => $subscriptionrules,
        'fk_documenti_dettaglio' => $document_row_id
            ), array('%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%d')
    );
    $fk_agenda = $wpdb->insert_id;
  }
}

//save client
function WPsCRM_save_client() {

  if (check_ajax_referer('update_customer', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "clienti";
    parse_str($_POST['fields'], $fields);
    $ID_azienda = "1";
    $taxObj = array();
    foreach ($fields as $field => $val) {
      if (strstr($field, 'ADVsCRM_custom_tax')) {
        $split = explode('-', $field);
        $tax = $split[1];
        $taxObj[] = array('taxonomy' => $tax, 'termid' => $val);
      }
    }

    $serialized_tax = maybe_serialize($taxObj);

    //$data_inserimento=date("Y-m-d");
    $data_modifica = date("Y-m-d");
    $data_nascita = WPsCRM_inverti_data($fields["data_nascita"]);
    //$cur_year=date("Y");
    $current_user = wp_get_current_user();
    if (WPsCRM_is_agent() && !WPsCRM_agent_can())
      $selectAgent = $current_user->ID;
    else
      $selectAgent = $fields["selectAgent"];

    is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
    if (in_array('wp-smart-crm-advanced/wp-smart-crm-advanced.php', apply_filters('active_plugins', $filter))) {
      //custom fields
      $f_table = WPsCRM_TABLE . "fields";
      //$v_table=WPsCRM_TABLE."values";
      $sql = "select id, field_name, field_type from $f_table where table_name='clienti'";
      $arr_custom_fields = array();
      foreach ($wpdb->get_results($sql) as $record) {
        $fk_fields = $record->id;
        $field_name = $record->field_name;
        $field_type = $record->field_type;
        //datetime field management for kendo grid in customers list
        if ($field_type == "datetime") {
          $date_field_name = "date_" . $field_name;
          //$date_field_name="date_".$field_name;
          $valore = $fields[$field_name];
          $date_valore = $fields[$date_field_name];
          $arr_custom_fields[] = array("id_fields" => $fk_fields, "value" => $valore, "datevalue" => $date_valore);
        } else {
          $valore = $fields[$field_name];
          $arr_custom_fields[] = array("id_fields" => $fk_fields, "value" => $valore);
        }
      }
      //print_r($arr_custom_fields);
      $serialized_custom_fields = maybe_serialize($arr_custom_fields);
    } else
      $serialized_custom_fields = "";
    if ($ID = $fields["ID"]) {
      $wpdb->update(
              $table, array('FK_aziende' => $ID_azienda, 'nome' => $fields["nome"], 'cognome' => $fields["cognome"], 'categoria' => $fields["customerCategory"], 'ragione_sociale' => $fields["ragione_sociale"], 'indirizzo' => $fields["indirizzo"], 'cap' => $fields["cap"], 'localita' => $fields["localita"], 'provincia' => $fields["provincia"], 'nazione' => $fields["nazione"], 'telefono1' => $fields["telefono1"], 'telefono2' => $fields["telefono2"], 'fax' => $fields["fax"], 'email' => $fields["email"], 'sitoweb' => $fields["sitoweb"], 'skype' => $fields["skype"], 'p_iva' => $fields["p_iva"], 'cod_fis' => $fields["cod_fis"], 'data_modifica' => $data_modifica, 'provenienza' => $fields["customerComesfrom"], 'agente' => $selectAgent, 'data_nascita' => $data_nascita, 'luogo_nascita' => $fields["luogo_nascita"], 'tipo_cliente' => $fields["tipo_cliente"], 'interessi' => $fields["customerInterests"], 'fatturabile' => $fields["fatturabile"], 'custom_fields' => $serialized_custom_fields, 'custom_tax' => $serialized_tax), array('ID_clienti' => $ID), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
      );
    } else {
      $wpdb->insert(
              $table, array('FK_aziende' => $ID_azienda, 'nome' => $fields["nome"], 'cognome' => $fields["cognome"], 'categoria' => $fields["customerCategory"], 'ragione_sociale' => $fields["ragione_sociale"], 'indirizzo' => $fields["indirizzo"], 'cap' => $fields["cap"], 'localita' => $fields["localita"], 'provincia' => $fields["provincia"], 'nazione' => $fields["nazione"], 'telefono1' => $fields["telefono1"], 'telefono2' => $fields["telefono2"], 'fax' => $fields["fax"], 'email' => $fields["email"], 'sitoweb' => $fields["sitoweb"], 'skype' => $fields["skype"], 'p_iva' => $fields["p_iva"], 'cod_fis' => $fields["cod_fis"], 'data_modifica' => $data_modifica, 'data_inserimento' => $fields["data_inserimento"], 'provenienza' => $fields["customerComesfrom"], 'agente' => $selectAgent, 'data_nascita' => $data_nascita, 'luogo_nascita' => $fields["luogo_nascita"], 'tipo_cliente' => $fields["tipo_cliente"], 'interessi' => $fields["customerInterests"], 'fatturabile' => $fields["fatturabile"], 'custom_fields' => $serialized_custom_fields), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%s')
      );
    }
    $ID_ret = $ID ? $ID : $wpdb->insert_id;

    //newsletter
    do_action('wp_ajax_WPsCRM_syncro_newsletter', $fields);
    echo "OK~" . $ID_ret;
    die;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_save_client', 'WPsCRM_save_client');

add_action('wp_ajax_WPsCRM_syncro_newsletter', 'WPsCRM_syncro_newsletter', 1);

function WPsCRM_syncro_newsletter($fields) {
  global $wpdb;
  if ($fields["newsletter_flag"] == 1) {
    for ($i = 1; $i <= 20; $i++) {
      $nomelista = "list_" . $i;
      if ($fields[$nomelista] == 1)
        ${"list" . $i} = 1;
      else
        ${"list" . $i} = 0;
    }
    $sql = "select id from " . $wpdb->prefix . 'newsletter' . " where email='" . $fields["email"] . "'";
    if ($riga = $wpdb->get_row($sql)) {
      $insert_in_mailinglist = $wpdb->update(
              $wpdb->prefix . 'newsletter', array("list_1" => $list1, "list_2" => $list2, "list_3" => $list3, "list_4" => $list4, "list_5" => $list5, "list_6" => $list6, "list_7" => $list7, "list_8" => $list8, "list_9" => $list9, "list_10" => $list10, "list_11" => $list11, "list_12" => $list12, "list_13" => $list13, "list_14" => $list14, "list_15" => $list15, "list_16" => $list16, "list_17" => $list17, "list_18" => $list18, "list_19" => $list19, "list_20" => $list20), array('ID' => $riga->id), array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d')
      );
    } else {
      if ($fields["ragione_sociale"]) {
        $name = $fields["ragione_sociale"];
        $surname = "";
      } else {
        $name = $fields["nome"];
        $surname = $fields["cognome"];
      }
      $token = substr(md5(rand()), 0, 10);
      $insert_in_mailinglist = $wpdb->insert(
              $wpdb->prefix . 'newsletter', array('name' => $name, 'surname' => $surname, 'sex' => 'n', 'status' => 'C', 'created' => current_time('mysql', 1), 'email' => $fields["email"], 'token' => $token, "list_1" => $list1, "list_2" => $list2, "list_3" => $list3, "list_4" => $list4, "list_5" => $list5, "list_6" => $list6, "list_7" => $list7, "list_8" => $list8, "list_9" => $list9, "list_10" => $list10, "list_11" => $list11, "list_12" => $list12, "list_13" => $list13, "list_14" => $list14, "list_15" => $list15, "list_16" => $list16, "list_17" => $list17, "list_18" => $list18, "list_19" => $list19, "list_20" => $list20), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d')
      );
    }
  }
  //echo "sono passato dall'hook wp_ajax_WPsCRM_syncro_newsletter";
  return;
}

//save crm User custom fields
function WPsCRM_save_crm_user_fields() {
  global $wpdb;
  $user = wp_get_current_user();
  $table = WPsCRM_TABLE . "clienti";
  parse_str($_POST['fields'], $fields);
  print_r($fields);
  $wpdb->update(
          $table, array('nome' => $fields["nome"], 'cognome' => $fields["cognome"], 'indirizzo' => $fields["indirizzo"], 'cap' => $fields["cap"], 'localita' => $fields["localita"], 'telefono1' => $fields["telefono1"], 'email' => $fields["email"], 'ragione_sociale' => $fields["ragione_sociale"], 'p_iva' => $fields["p_iva"], 'cod_fis' => $fields["cod_fis"]), array('ID_clienti' => $_POST["client_ID"]), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
  );
  echo $wpdb->last_query;
  wp_update_user(array(
      "ID" => $user->ID,
      "first_name" => sanitize_text_field($fields["nome"]),
      "last_name" => sanitize_text_field($fields["cognome"]),
      "user_email" => sanitize_text_field($fields["email"])
          )
  );
  /*
    foreach($fields as $meta_key=>$meta_value)
    if($meta_key !="CRM_firstname" && $meta_key !="CRM_lastname" && $meta_key !="CRM_email")
    update_user_meta( $user->ID, $meta_key, $meta_value);
    $user_info = get_user_meta($user->ID);
    var_dump($user_info);
   */
  die;
}

add_action('wp_ajax_WPsCRM_save_crm_user_fields', 'WPsCRM_save_crm_user_fields');

//add_action( 'wp_ajax_nopriv_save_crm_user_fields', 'save_crm_user_fields' );
//save client contacts for grid in clients/form.php
function WPsCRM_view_activity_modal() {
  global $wpdb;
  $ID = $_GET['id'];
  $report = $_REQUEST['report'];
  $a_table = WPsCRM_TABLE . "agenda";
  $c_table = WPsCRM_TABLE . "clienti";
  $s_table = WPsCRM_TABLE . "subscriptionrules";


  $sql = "select oggetto,tipo_agenda, annotazioni, start_date,end_date, data_agenda, fk_clienti, fk_contatti,fk_subscriptionrules, fatto, esito, priorita from $a_table where id_agenda=$ID";
  $riga = $wpdb->get_row($sql);

  switch ($riga->priorita) {
    case 1:
      $priorita = __("Niedrig", "cpsmartcrm");
      break;
    case 2:
      $priorita = __("Normal", "cpsmartcrm");
      break;
    case 3:
      $priorita = __("Hoch", "cpsmartcrm");
      break;
    default:
      $priorita = __("Normal", "cpsmartcrm");
      break;
  }
  if ($riga->fk_clienti) {
    $sql = "select ragione_sociale, nome, cognome from $c_table where ID_clienti=" . $riga->fk_clienti;
    $rigac = $wpdb->get_row($sql);
    $cliente = $rigac->ragione_sociale ? $rigac->ragione_sociale : $rigac->nome . " " . $rigac->cognome;
  }
  if ($riga->fk_contatti) {
    $sql = "select concat(nome,' ', cognome) as contatto from ana_contatti where ID_contatti=" . $riga->FK_contatti;
    $rigac = $wpdb->get_row($sql);
    $contatto = $rigac->contatto;
  }

  $oggetto = $riga->oggetto;
  $annotazioni = $riga->annotazioni;
  $data_scadenza = $riga->data_scadenza;

  if ($id_subscriptionrules = $riga->fk_subscriptionrules) {
    $sql = "SELECT * FROM $s_table WHERE ID=$id_subscriptionrules";

    $query = $wpdb->get_row($sql);
    $steps = $query->steps;
    $steps = json_decode($steps);
    $users = $steps[0]->selectedUsers;
    $groups = $steps[0]->selectedGroups;
    $days = $steps[0]->ruleStep;
    $remindToCustomer = $steps[0]->remindToCustomer == "on" ? __('Ja', 'cpsmartcrm') : __('Nein', 'cpsmartcrm');
    $mailToRecipients = $steps[0]->mailToRecipients == "on" ? "S&igrave;" : "No";
    $userDashboard = $steps[0]->userDashboard == "on" ? "S&igrave;" : "No";
    $groupDashboard = $steps[0]->groupDashboard == "on" ? "S&igrave;" : "No";
    $arr_users = explode(",", $users);
    $destinatari = "";

    foreach ($arr_users as $user) {
      $user_info = get_userdata($user);
      ($user_info->first_name != "" && $user_info->last_name != "") ? $u_name = $user_info->first_name . " " . $user_info->last_name : $u_name = $user_info->user_nicename;
      $destinatari .= $u_name . ", ";
    }
    $destinatari = substr($destinatari, 0, -2);
  }

  $where = "1";

  switch ($riga->tipo_agenda) {
    case 1:

      $icon = '<i class="glyphicon glyphicon-tag"></i> ' . __('TODO', 'cpsmartcrm');
      break;
    case 2:
      $icon = '<i class="glyphicon glyphicon-pushpin"></i> ' . __('Termin', 'cpsmartcrm');
      break;
    case 3:
      $icon = '<i class="glyphicon glyphicon-option-horizontal"></i> ' . __('Aktivität', 'cpsmartcrm');
      break;
    default:
      $tipo = "";
      break;
  }

  $html = "";
  $html .= '
    <div class="col-md-8 panel panel-primary _flat modal_inner" style="border:1px solid #666;text-align:left;background:#fff;padding-bottom:20px;margin: 46px auto;float: none;padding:0;position:relative">
    <div class="panel-heading" style="    padding: 3px 10px;">
        <h3 style="text-align:center;margin-top: 8px;">' . __('Aktivitätsdetails', 'cpsmartcrm') . ': <small style="margin-left:40px;color:#fff">' . $icon . '</small></h3>
    </div>
    <div class="panel-body" style="padding:0px 20px 10px 20px">
    
<form>
<table border="0" cellpadding="2" cellspacing="2" class="view table">
    <tr>
	    <td class="view">' . __('Kunde', 'cpsmartcrm') . ':</td>
	    <td class="view_text"><a href="' . admin_url('admin.php?page=smart-crm&p=clienti/form.php& ID=' . $riga->fk_clienti) . '">' . stripslashes($cliente) . '</a></td>
    </tr>
    <tr>
	    <td class="view">' . __('Kontakt', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $contatto . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Betreff', 'cpsmartcrm') . ':</td>
	    <td colspan="2" class="view_text">' . stripslashes($riga->oggetto) . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Beschreibung', 'cpsmartcrm') . ':</td>
	    <td colspan="2" class="view_text">' . stripslashes($riga->annotazioni) . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Benutzer', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . stripslashes($destinatari) . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Gruppen', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . stripslashes($groups) . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Tage im Voraus', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $days . '</td>
    </tr>';
  if ($riga->tipo_agenda == 2) {
    $html .= '<tr>
	    <td class="view">' . __('E-Mail an den Kunden senden', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $remindToCustomer . '</td>
    </tr>';
  };
  $html .= '
    <tr>
	    <td class="view">' . __('Sende E-Mails an Empfänger', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $mailToRecipients . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Im Konto-Dashboard veröffentlichen', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $userDashboard . '</td>
    </tr>
    <tr>
	    <td class="view">' . __('Im Gruppen-Dashboard veröffentlichen', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $groupDashboard . '</td>
    </tr>';
  if ($riga->tipo_agenda == 2) {
    $html .= '
    <tr>
	    <td class="view">' . __('Start', 'cpsmartcrm') . ': </td>
	    <td class="view_text"> ' . WPsCRM_culture_date_format($riga->start_date) . '</td>
    </tr>
    <tr>	
	    <td class="view">' . __('Ende', 'cpsmartcrm') . ':</td>
	    <td class="view_text"> ' . WPsCRM_culture_date_format($riga->end_date) . '</td>
    </tr>';
  } else {
    $html .= '
    <tr>
	    <td class="view">' . __('Ablauf', 'cpsmartcrm') . ': </td>
	    <td class="view_text"> ' . WPsCRM_culture_date_format($riga->start_date) . '</td>
    </tr>';
  };
  $html .= '
    <tr>	
	    <td class="view">' . __('Priorität', 'cpsmartcrm') . ':</td>
	    <td class="view_text">' . $priorita . '</td>
    </tr>
</table>

<fieldset>
    <legend>' . __('Ergebnis', 'cpsmartcrm') . '</legend>
    <table border="0" cellpadding="2" cellspacing="2" width="98%">
    <tr>
	    <td>' . __('Noch zu erledigen', 'cpsmartcrm') . ' <input type="radio" name="fatto" value="1" id="f_1" ';
  $riga->fatto == "1" ? $html .= "checked" : $html .= $report == "false" ? " disabled" : "";

  $html .= '></td><td>';
  $html .= '' . __('Erledigt', 'cpsmartcrm') . ' <input type="radio" name="fatto" value="2" id="f_2" ';
  $riga->fatto == "2" ? $html .= "checked" : $html .= $report == "false" ? " disabled" : "";
  $html .= '></td><td>';
  $html .= '' . __('Abgesagt', 'cpsmartcrm') . ' <input type="radio" name="fatto" value="3" id="f_3" ';
  $riga->fatto == "3" ? $html .= "checked" : $html .= $report == "false" ? " disabled" : "";
  $html .= '></td>
    </tr>
    <tr>
	    <td>' . __('Angebote', 'cpsmartcrm') . '</td>
	    <td colspan="2" style="padding-top:20px"><textarea rows="3" cols="50" name="esito" id="esito" ';
  $report == "false" ? $html .= " readonly" : $html .= "";
  $html .= '>' . stripslashes($riga->esito) . '</textarea></td>
    </tr>
    </table>
</fieldset>';
  if ($report != "false")
    $html .= '<span class="button button-primary _flat" id="save_activity_from_modal" data-id="' . $ID . '">' . __('Speichern', 'cpsmartcrm') . '</span>';

  $html .= '<span class="button button-secondary _flat _reset" id="configreset">' . __('Stornieren', 'cpsmartcrm') . '</span>
<div id="box_messaggio"></div>

</form>
<div class="modal_loader" style="background:#fff url(' . dirname(plugin_dir_url(plugin_basename(__FILE__))) . '/css/img/loading-image.gif);background-repeat:no-repeat;background-position:center center"></div>
</div>
</div>

';
  echo $html;
  die();
}

add_action('wp_ajax_WPsCRM_view_activity_modal', 'WPsCRM_view_activity_modal', 10);

function WPsCRM_update_options_modal() {
  if (check_ajax_referer('update_document', 'security', false) && current_user_can('manage_crm')) {
    $options = get_option($_POST['option_section']);
    $option = $_POST['option'];
    $options[$option] = $_POST['val'];

    update_option($_POST['option_section'], $options);

    if (gettype($options[$option]) != "array")
      echo ( $options[$option] );
  } else
    die('Security issue');

  die();
}

add_action('wp_ajax_WPsCRM_update_options_modal', 'WPsCRM_update_options_modal');

function WPsCRM_scheduler_update() {
  global $wpdb;

  if (check_ajax_referer('update_scheduler', 'security', false) && current_user_can('manage_crm')) {
    $a_table = WPsCRM_TABLE . "agenda";
    $id_agenda = (int) $_POST["ID"];
    $result = $wpdb->query(
            $wpdb->prepare(
                    "UPDATE $a_table SET fatto=%s, esito=%s WHERE id_agenda=%d", $_POST["fatto"], $_POST["esito"], $id_agenda
            )
    );
    if ($result > 0) {
      echo "Successfully Updated";
    } else {
      exit(var_dump($wpdb->last_query));
    }
    $wpdb->flush();
  } else
    die('Security issue');

  die();
}

add_action('wp_ajax_WPsCRM_scheduler_update', 'WPsCRM_scheduler_update');

function WPsCRM_dismiss_notice() {
  $notice = $_POST['notice'];
  $options = get_option('CRM_dismissed_notices');
  $options[$notice] = 1;
  update_option('CRM_dismissed_notices', $options);
  die();
}

add_action('wp_ajax_WPsCRM_dismiss_notice', 'WPsCRM_dismiss_notice');

function WPsCRM_save_client_partial() {
  if (check_ajax_referer('update_document', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "clienti";
    parse_str($_POST['values'], $arr);
    $wpdb->update(
            $table, array('indirizzo' => $arr["indirizzo"], 'cap' => $arr["cap"], 'localita' => $arr["localita"], 'provincia' => $arr["provincia"], 'p_iva' => $arr["p_iva"], 'cod_fis' => $arr["cod_fis"]), array('ID_clienti' => $arr['hidden_fk_clienti']), array('%s', '%s', '%s', '%s', '%s', '%s')
    );
  } else
    die('Security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_save_client_partial', 'WPsCRM_save_client_partial');

function WPsCRM_compare_date($a, $b) {
  // CONVERT $a AND $b to DATE AND TIME using strtotime() function
  $t1 = strtotime($a["data_scadenza"]);
  $t2 = strtotime($b["data_scadenza"]);
  if ($t1 == $t2) {
    return 0;
  }
  return ($t1 > $t2) ? -1 : 1;
}

function WPsCRM_show_only_future_activity() {//TEMP da implementare per mostrare gli eventi passati nelle grid
  $options = get_option('CRM_general_settings');
  if (isset($options['future_activities']) && $options['future_activities'] == "1")
    $return = true;
  else
    $return = false;
  return $return;
}

// gestisce la creazione dell'utente interno # 1 all'attivazione
function WPsCRM_check_client_one() {
  global $wpdb;
  $options = get_option('CRM_business_settings');
  $clients = WPsCRM_get_clients_array(1);
  $ID_azienda = "1";
  $data_inserimento = date("Y-m-d");
  $data_modifica = date("Y-m-d");
  $cur_year = date("Y");
  $table = WPsCRM_TABLE . "clienti";
  if (empty($clients)) {
      $wpdb->insert(
          $table, array(
              'FK_aziende' => "$ID_azienda",
              'nome' => $options['business_name'],
              'cognome' => '',
              'ragione_sociale' => $options['business_name'],
              'indirizzo' => $options['business_address'],
              'cap' => $options['business_zip'],
              'localita' => $options['business_town'],
              'provincia' => '',
              'telefono1' => $options['business_phone'],
              'telefono2' => '',
              'fax' => $options['business_fax'],
              'email' => $options['business_email'],
              'sitoweb' => '',
              'skype' => '',
              'p_iva' => isset($options['business_ustid']) ? $options['business_ustid'] : '', // Umsatzsteuer-ID
              'cod_fis' => isset($options['business_taxid']) ? $options['business_taxid'] : '', // Steuernummer
              'annotazioni' => '',
              'data_inserimento' => $data_inserimento,
              'provenienza' => '',
              'agente' => ''
          ),
          array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
      );
  } else {
      $wpdb->update(
          $table, array(
              'FK_aziende' => "$ID_azienda",
              'nome' => $options['business_name'],
              'cognome' => '',
              'ragione_sociale' => $options['business_name'],
              'indirizzo' => $options['business_address'],
              'cap' => $options['business_zip'],
              'localita' => $options['business_town'],
              'provincia' => '',
              'telefono1' => $options['business_phone'],
              'telefono2' => '',
              'fax' => $options['business_fax'],
              'email' => $options['business_email'],
              'sitoweb' => '',
              'skype' => '',
              'p_iva' => isset($options['business_ustid']) ? $options['business_ustid'] : '', // Umsatzsteuer-ID
              'cod_fis' => isset($options['business_taxid']) ? $options['business_taxid'] : '', // Steuernummer
              'annotazioni' => '',
              'data_modifica' => $data_modifica,
              'provenienza' => '',
              'agente' => ''
          ),
          array('ID_clienti' => 1),
          array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
      );
  }
  $client_one = $wpdb->insert_id;
  update_option('CRM_client_one', $client_one);
}

add_action('add_option_CRM_business_settings', 'WPsCRM_check_client_one', 99);
add_action('update_option_CRM_business_settings', 'WPsCRM_check_client_one', 99);

function WPsCRM_inverti_data($data, $sep = "-") {
  if ($data) {
    $arr_dataora = explode(" ", $data);
    $arr_data = explode($sep, $arr_dataora[0]);
    $nuova_data = $arr_data[2] . "-" . $arr_data[1] . "-" . $arr_data[0];
    if (isset($arr_dataora[1]))
      $nuova_data .= $arr_dataora[1] ? " " . substr($arr_dataora[1], 0, 5) : "";
    if ($nuova_data == "00-00-0000 00:00:00" || $nuova_data == "0000-00-00 00:00:00" || $nuova_data == "00-00-0000 00:00")
      $nuova_data = "";
    return $nuova_data;
  }
}

function WPsCRM_inverti_data_short($Data, $sep = "-") {
  if ($Data) {
    $MiaDataOra = explode(" ", $Data);
    $MiaData = explode($sep, $MiaDataOra[0]);
    $NuovaData = $MiaData[2] . "-" . $MiaData[1];
    $NuovaData .= $MiaDataOra[1] ? " " . substr($MiaDataOra[1], 0, 5) : "";
    if ($NuovaData == "00-00-0000" || $NuovaData == "0000-00-00")
      $NuovaData = "";
    return $NuovaData;
  }
}

//sanitize date  to be inserted in MYSQL date format
function WPsCRM_sanitize_date_format($_date, $sep = "-") {

  switch (WPsCRM_CULTURE) {
    case "it-IT":
      $date = WPsCRM_inverti_data($_date);
      break;
    case "en-US":
      $arr_dataora = explode(" ", $_date);
      //var_dump($arr_dataora);exit;
      $arr_data = explode($sep, $arr_dataora[0]);
      $nuova_data = $arr_data[2] . "-" . $arr_data[0] . "-" . $arr_data[1];
      $nuova_data .= $arr_dataora[1] ? " " . substr($arr_dataora[1], 0, 5) : "";
      if ($nuova_data == "00-00-0000 00:00:00" || $nuova_data == "0000-00-00 00:00:00" || $nuova_data == "00-00-0000 00:00")
        $nuova_data = "";
      $date = $nuova_data;

      break;
    case "en-GB":
      $date = WPsCRM_inverti_data($_date);
      break;
    case "de-DE":
      $date = WPsCRM_inverti_data($_date);
      break;
    case "fr-FR":
      $date = WPsCRM_inverti_data($_date);
      break;
    default: $date = WPsCRM_inverti_data($_date);
  }
  return $date;
}

//transoprts date from mysql to a format depending on culture
function WPsCRM_culture_date_format($_date, $sep = "-") {
  $date = $_date;
  switch (WPsCRM_CULTURE) {
    case "it-IT":
      $date = WPsCRM_inverti_data($_date);
      break;
    case "en-US":
      $arr_dataora = explode(" ", $_date);
      $arr_data = explode($sep, $arr_dataora[0]);
      $nuova_data = $arr_data[1] . "-" . $arr_data[2] . "-" . $arr_data[0];
      if (isset($arr_dataora[1]))
        $nuova_data .= $arr_dataora[1] ? " " . substr($arr_dataora[1], 0, 5) : "";
      if ($nuova_data == "00-00-0000 00:00:00" || $nuova_data == "0000-00-00 00:00:00" || $nuova_data == "00-00-0000 00:00")
        $nuova_data = "";
      $date = $nuova_data;

      break;
    case "en-GB":
      $date = WPsCRM_inverti_data($_date);
      break;
    case "de-DE":
      $date = WPsCRM_inverti_data($_date);
      break;
    case "fr-FR":
      $date = WPsCRM_inverti_data($_date);
      break;
    default: $date = WPsCRM_inverti_data($_date);
  }
  return $date;
}

function WPsCRM_get_countries($selected) {
  ob_start();
  ?>
  <option value="0"><?php _e('Select...', 'cpsmartcrm') ?></option>
  <option value="AF">Afghanistan</option>
  <option value="AX">Aland Islands</option>
  <option value="AL">Albania</option>
  <option value="DZ">Algeria</option>
  <option value="AS">American Samoa</option>
  <option value="AD">Andorra</option>
  <option value="AO">Angola</option>
  <option value="AI">Anguilla</option>
  <option value="AQ">Antarctica</option>
  <option value="AG">Antigua and Barbuda</option>
  <option value="AR">Argentina</option>
  <option value="AM">Armenia</option>
  <option value="AW">Aruba</option>
  <option value="AU">Australia</option>
  <option value="AT">Austria</option>
  <option value="AZ">Azerbaijan</option>
  <option value="BS">Bahamas</option>
  <option value="BH">Bahrain</option>
  <option value="BD">Bangladesh</option>
  <option value="BB">Barbados</option>
  <option value="BY">Belarus</option>
  <option value="BE">Belgium</option>
  <option value="BZ">Belize</option>
  <option value="BJ">Benin</option>
  <option value="BM">Bermuda</option>
  <option value="BT">Bhutan</option>
  <option value="BO">Bolivia, Plurinational State of</option>
  <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
  <option value="BA">Bosnia and Herzegovina</option>
  <option value="BW">Botswana</option>
  <option value="BV">Bouvet Island</option>
  <option value="BR">Brazil</option>
  <option value="IO">British Indian Ocean Territory</option>
  <option value="BN">Brunei Darussalam</option>
  <option value="BG">Bulgaria</option>
  <option value="BF">Burkina Faso</option>
  <option value="BI">Burundi</option>
  <option value="KH">Cambodia</option>
  <option value="CM">Cameroon</option>
  <option value="CA">Canada</option>
  <option value="CV">Cape Verde</option>
  <option value="KY">Cayman Islands</option>
  <option value="CF">Central African Republic</option>
  <option value="TD">Chad</option>
  <option value="CL">Chile</option>
  <option value="CN">China</option>
  <option value="CX">Christmas Island</option>
  <option value="CC">Cocos (Keeling) Islands</option>
  <option value="CO">Colombia</option>
  <option value="KM">Comoros</option>
  <option value="CG">Congo</option>
  <option value="CD">Congo, the Democratic Republic of the</option>
  <option value="CK">Cook Islands</option>
  <option value="CR">Costa Rica</option>
  <option value="CI">Côte d'Ivoire</option>
  <option value="HR">Croatia</option>
  <option value="CU">Cuba</option>
  <option value="CW">Curaçao</option>
  <option value="CY">Cyprus</option>
  <option value="CZ">Czech Republic</option>
  <option value="DK">Denmark</option>
  <option value="DJ">Djibouti</option>
  <option value="DM">Dominica</option>
  <option value="DO">Dominican Republic</option>
  <option value="EC">Ecuador</option>
  <option value="EG">Egypt</option>
  <option value="SV">El Salvador</option>
  <option value="GQ">Equatorial Guinea</option>
  <option value="ER">Eritrea</option>
  <option value="EE">Estonia</option>
  <option value="ET">Ethiopia</option>
  <option value="FK">Falkland Islands (Malvinas)</option>
  <option value="FO">Faroe Islands</option>
  <option value="FJ">Fiji</option>
  <option value="FI">Finland</option>
  <option value="FR">France</option>
  <option value="GF">French Guiana</option>
  <option value="PF">French Polynesia</option>
  <option value="TF">French Southern Territories</option>
  <option value="GA">Gabon</option>
  <option value="GM">Gambia</option>
  <option value="GE">Georgia</option>
  <option value="DE">Germany</option>
  <option value="GH">Ghana</option>
  <option value="GI">Gibraltar</option>
  <option value="GR">Greece</option>
  <option value="GL">Greenland</option>
  <option value="GD">Grenada</option>
  <option value="GP">Guadeloupe</option>
  <option value="GU">Guam</option>
  <option value="GT">Guatemala</option>
  <option value="GG">Guernsey</option>
  <option value="GN">Guinea</option>
  <option value="GW">Guinea-Bissau</option>
  <option value="GY">Guyana</option>
  <option value="HT">Haiti</option>
  <option value="HM">Heard Island and McDonald Islands</option>
  <option value="VA">Holy See (Vatican City State)</option>
  <option value="HN">Honduras</option>
  <option value="HK">Hong Kong</option>
  <option value="HU">Hungary</option>
  <option value="IS">Iceland</option>
  <option value="IN">India</option>
  <option value="ID">Indonesia</option>
  <option value="IR">Iran, Islamic Republic of</option>
  <option value="IQ">Iraq</option>
  <option value="IE">Ireland</option>
  <option value="IM">Isle of Man</option>
  <option value="IL">Israel</option>
  <option value="IT">Italy</option>
  <option value="JM">Jamaica</option>
  <option value="JP">Japan</option>
  <option value="JE">Jersey</option>
  <option value="JO">Jordan</option>
  <option value="KZ">Kazakhstan</option>
  <option value="KE">Kenya</option>
  <option value="KI">Kiribati</option>
  <option value="KP">Korea, Democratic People's Republic of</option>
  <option value="KR">Korea, Republic of</option>
  <option value="KW">Kuwait</option>
  <option value="KG">Kyrgyzstan</option>
  <option value="LA">Lao People's Democratic Republic</option>
  <option value="LV">Latvia</option>
  <option value="LB">Lebanon</option>
  <option value="LS">Lesotho</option>
  <option value="LR">Liberia</option>
  <option value="LY">Libya</option>
  <option value="LI">Liechtenstein</option>
  <option value="LT">Lithuania</option>
  <option value="LU">Luxembourg</option>
  <option value="MO">Macao</option>
  <option value="MK">Macedonia, the former Yugoslav Republic of</option>
  <option value="MG">Madagascar</option>
  <option value="MW">Malawi</option>
  <option value="MY">Malaysia</option>
  <option value="MV">Maldives</option>
  <option value="ML">Mali</option>
  <option value="MT">Malta</option>
  <option value="MH">Marshall Islands</option>
  <option value="MQ">Martinique</option>
  <option value="MR">Mauritania</option>
  <option value="MU">Mauritius</option>
  <option value="YT">Mayotte</option>
  <option value="MX">Mexico</option>
  <option value="FM">Micronesia, Federated States of</option>
  <option value="MD">Moldova, Republic of</option>
  <option value="MC">Monaco</option>
  <option value="MN">Mongolia</option>
  <option value="ME">Montenegro</option>
  <option value="MS">Montserrat</option>
  <option value="MA">Morocco</option>
  <option value="MZ">Mozambique</option>
  <option value="MM">Myanmar</option>
  <option value="NA">Namibia</option>
  <option value="NR">Nauru</option>
  <option value="NP">Nepal</option>
  <option value="NL">Netherlands</option>
  <option value="NC">New Caledonia</option>
  <option value="NZ">New Zealand</option>
  <option value="NI">Nicaragua</option>
  <option value="NE">Niger</option>
  <option value="NG">Nigeria</option>
  <option value="NU">Niue</option>
  <option value="NF">Norfolk Island</option>
  <option value="MP">Northern Mariana Islands</option>
  <option value="NO">Norway</option>
  <option value="OM">Oman</option>
  <option value="PK">Pakistan</option>
  <option value="PW">Palau</option>
  <option value="PS">Palestinian Territory, Occupied</option>
  <option value="PA">Panama</option>
  <option value="PG">Papua New Guinea</option>
  <option value="PY">Paraguay</option>
  <option value="PE">Peru</option>
  <option value="PH">Philippines</option>
  <option value="PN">Pitcairn</option>
  <option value="PL">Poland</option>
  <option value="PT">Portugal</option>
  <option value="PR">Puerto Rico</option>
  <option value="QA">Qatar</option>
  <option value="RE">Réunion</option>
  <option value="RO">Romania</option>
  <option value="RU">Russian Federation</option>
  <option value="RW">Rwanda</option>
  <option value="BL">Saint Barthélemy</option>
  <option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
  <option value="KN">Saint Kitts and Nevis</option>
  <option value="LC">Saint Lucia</option>
  <option value="MF">Saint Martin (French part)</option>
  <option value="PM">Saint Pierre and Miquelon</option>
  <option value="VC">Saint Vincent and the Grenadines</option>
  <option value="WS">Samoa</option>
  <option value="SM">San Marino</option>
  <option value="ST">Sao Tome and Principe</option>
  <option value="SA">Saudi Arabia</option>
  <option value="SN">Senegal</option>
  <option value="RS">Serbia</option>
  <option value="SC">Seychelles</option>
  <option value="SL">Sierra Leone</option>
  <option value="SG">Singapore</option>
  <option value="SX">Sint Maarten (Dutch part)</option>
  <option value="SK">Slovakia</option>
  <option value="SI">Slovenia</option>
  <option value="SB">Solomon Islands</option>
  <option value="SO">Somalia</option>
  <option value="ZA">South Africa</option>
  <option value="GS">South Georgia and the South Sandwich Islands</option>
  <option value="SS">South Sudan</option>
  <option value="ES">Spain</option>
  <option value="LK">Sri Lanka</option>
  <option value="SD">Sudan</option>
  <option value="SR">Suriname</option>
  <option value="SJ">Svalbard and Jan Mayen</option>
  <option value="SZ">Swaziland</option>
  <option value="SE">Sweden</option>
  <option value="CH">Switzerland</option>
  <option value="SY">Syrian Arab Republic</option>
  <option value="TW">Taiwan, Province of China</option>
  <option value="TJ">Tajikistan</option>
  <option value="TZ">Tanzania, United Republic of</option>
  <option value="TH">Thailand</option>
  <option value="TL">Timor-Leste</option>
  <option value="TG">Togo</option>
  <option value="TK">Tokelau</option>
  <option value="TO">Tonga</option>
  <option value="TT">Trinidad and Tobago</option>
  <option value="TN">Tunisia</option>
  <option value="TR">Turkey</option>
  <option value="TM">Turkmenistan</option>
  <option value="TC">Turks and Caicos Islands</option>
  <option value="TV">Tuvalu</option>
  <option value="UG">Uganda</option>
  <option value="UA">Ukraine</option>
  <option value="AE">United Arab Emirates</option>
  <option value="GB">United Kingdom</option>
  <option value="US">United States</option>
  <option value="UM">United States Minor Outlying Islands</option>
  <option value="UY">Uruguay</option>
  <option value="UZ">Uzbekistan</option>
  <option value="VU">Vanuatu</option>
  <option value="VE">Venezuela, Bolivarian Republic of</option>
  <option value="VN">Viet Nam</option>
  <option value="VG">Virgin Islands, British</option>
  <option value="VI">Virgin Islands, U.S.</option>
  <option value="WF">Wallis and Futuna</option>
  <option value="EH">Western Sahara</option>
  <option value="YE">Yemen</option>
  <option value="ZM">Zambia</option>
  <option value="ZW">Zimbabwe</option>
  <script>

    jQuery(document).ready(function ($) {

        var currentCountry = "<?php if (isset($selected)) echo $selected; ?>";

        $('#nazione option').each(function () {
            if ($(this).val() == currentCountry)
                $(this).attr('selected', 'selected');
        })
    })
  </script>
  <?php
  $output = ob_get_contents();
  ob_end_clean();
  return $output;
}

function WPsCRM_is_agent() {
  $current_user = wp_get_current_user();
  $is_agent = false;
  if (!current_user_can('manage_options') && current_user_can('manage_crm'))
    $is_agent = true;
  return $is_agent;
}

function WPsCRM_agent_can() {
  $current_user = wp_get_current_user();
  $options = get_option('CRM_general_settings');
  $user_can = false;
  if (current_user_can('manage_options') || (isset($options['crm_agent_can']) && $options['crm_agent_can'] == 1))
    $user_can = true;
  return $user_can;
}

/**
 * Toggle document status ( paid / non paid for invoices, accepted / non accepted in quotes)
 */
function WPsCRM_set_payment_status() {
  if (check_ajax_referer('update_document', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    $table = WPsCRM_TABLE . "documenti";
    $wpdb->update(
            $table, array(
        'pagato' => (int) $_REQUEST['value']
            ), array(
        'id' => (int) $_REQUEST['ID']
            ), array(
        '%d', // valore1
        '%d'
            )
    );
    echo $wpdb->last_query;
  } else
    die('Security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_set_payment_status', 'WPsCRM_set_payment_status');

function WPsCRM_get_currency() {
  $docOptions = get_option('CRM_documents_settings');
  if (isset($docOptions['crm_currency']))
    $crm_currency = $docOptions['crm_currency'];
  $currency = new stdClass;
  switch ($crm_currency) {
    case "EUR":
      $currency->name = "EUR";
      $currency->symbol = "&euro;";
      break;
    case "USD":
      $currency->name = "USD";
      $currency->symbol = "$";
      break;
    case "GBP":
      $currency->name = "GBP";
      $currency->symbol = "&pound;";
      break;
    case "CHF":
      $currency->name = "CHF";
      $currency->symbol = "Fr.";
      break;
    case "BRL":
      $currency->name = "BRL";
      $currency->symbol = "BRL";
      break;
    case "INR":
      $currency->name = "INR";
      $currency->symbol = "INR";
      break;
    case "JPY":
      $currency->name = "JPY";
      $currency->symbol = "¥";
      break;
    case "CNY":
      $currency->name = "CNY";
      $currency->symbol = "¥";
      break;
    default:
      $currency->name = WPsCRM_DEFAULT_CURRENCY;
      $currency->symbol = WPsCRM_DEFAULT_CURRENCY_SYMBOL;
      break;
  }
  return $currency;
}

function WPsCRM_reverse_invoice() {
  $accountability = $_REQUEST["accountability"];
  $amount = $_REQUEST["amount"];
  $refund = $_REQUEST["refund"];
  $tipo_cliente = $_REQUEST["tipo_cliente"]; //1=private, 2=company
  $documentOptions = get_option('CRM_documents_settings');
  $accOptions = get_option("CRM_acc_settings");
  $iva = $documentOptions['default_vat'] / 100;
  switch ($accountability) {
    case 0:
      if ($refund > 0)
        $amount = $amount - $refund;
      $imponibile = $amount / (1 + $iva);
      break;
    case 1:
      $cassa = $accOptions['cassa~1'] / 100;
      $rit_acconto = $accOptions['rit_acconto~1'] / 100;
      if ($refund > 0)
        $amount = $amount - $refund;
      if ($tipo_cliente == 1)
        $imponibile = $amount / (1 + $cassa + (1 + $cassa) * $iva);
      else
        $imponibile = $amount / (1 + $cassa + (1 + $cassa) * $iva - $rit_acconto);
      break;
    case 2:
      $cassa = $accOptions['cassa~2'] / 100;
      $rit_acconto = $accOptions['rit_acconto~2'] / 100;
      if ($refund > 0)
        $amount = $amount - $refund;
      if ($tipo_cliente == 1)
        $imponibile = $amount / (1 + $cassa + (1 + $cassa) * $iva);
      else
        $imponibile = $amount / (1 + $cassa + (1 + $cassa) * $iva - (1 + $cassa) * $rit_acconto);
      break;
    case 3:
      $cassa = $accOptions['cassa~3'] / 100;
      $rit_acconto = $accOptions['rit_acconto~3'] / 100;
      $enasarco = $accOptions['enasarco~3'];
      $aliquota_rit_acc = 100 - $enasarco;
      $perc_enasarco = $enasarco / 100;
      $perc_rit_acc = $aliquota_rit_acc / 100;
//				echo "$amount - $perc_enasarco - $cassa - $perc_rit_acc - $rit_acconto<br>";
      if ($refund > 0)
        $amount = $amount - $refund;
      if ($tipo_cliente == 1)
        $imponibile = $amount / (1 + $iva - $perc_enasarco * $cassa);
      else
        $imponibile = $amount / (1 + $iva - $perc_enasarco * $cassa - $perc_rit_acc * $rit_acconto);
      break;
    case 4:
      $cassa = $accOptions['cassa~4'] / 100;
      if ($refund > 0)
        $amount = $amount - $refund;
      $imponibile = $amount / (1 + $cassa);
      break;
  }
  echo round($imponibile, 2);
  die();
}

add_action('wp_ajax_WPsCRM_reverse_invoice', 'WPsCRM_reverse_invoice');

function WPsCRM_generate_document_HTML($ID) {
  global $wpdb;
  ob_start();
  $print_nonce = wp_create_nonce("print_document");

  global $document;
  $document_numbering = $document->numbering();
  $document_messages = $document->messages();
  $signature = $document->signature();
  $formatted_signature = html_entity_decode($document->formatted_signature());

  $c_table = WPsCRM_TABLE . "clienti";
  $d_table = WPsCRM_TABLE . "documenti";
  $dd_table = WPsCRM_TABLE . "documenti_dettaglio";

  $general_options = get_option('CRM_general_settings');
  $document_options = get_option('CRM_documents_settings');
  $def_iva = $document_options['default_vat'];
  $accOptions = get_option("CRM_acc_settings");

  //css
  $style = '<style type="text/Css">

	table.page_header {
		width: 100%; border: none; padding: 2mm
	}
	.WPsCRM_item td,.WPsCRM_table_total td{border-bottom:solid 1px #ccc;}
	.WPsCRM_grandTotal td{border-bottom:solid 1px whitesmoke; }
	.WPsCRM_cod{width: 12%}
	.WPsCRM_desc{width: 38%;}
	.WPsCRM_product_desc{font-size:13px}
	.WPsCRM_qty{width: 11%}
	.WPsCRM_price{width: 12%}
	.WPsCRM_discount{width: 12%}
	.WPsCRM_total{width:15%}
	.WPsCRM_table_total{
		width: 100%;
		float: right;
		background: whitesmoke;
		border:solid 1px #666;
		margin-top:30px
	}
	.WPsCRM_table_total td{
		width:25%;
	}
	.WPsCRM_companylogo {
		max-width: 200px;
		max-height: 200px;
		margin: 10px;
	}
	.WPsCRM_text-after,.WPsCRM_table-conditions{
	font-size:13px;color:#666
	}
	.WPsCRM_customer_data {
		float: right;
		background-color:#fafafa;
	}
	.WPsCRM_document-data {
	}
	.WPsCRM_document-body{padding:2%;position:relative;top:2%}

	.WPsCRM_document_subject {
		float: left;
		width: 100%;
	}
	.WPsCRM_text-before {
		width: 100%;
		float: left;
		margin-top: 2%;
	}
	.WPsCRM_text-free {
		width: 100%;
		float: left;
		margin-top: 2%;
	}
	.WPsCRM_text-after {
		width: 100%;
		float: left;
		margin-top: 2%;
	}

	.WPsCRM_items_header {
		background-color: #dddddd;
		padding:10px;border-bottom: solid 1px #393939;border-right: solid 1px #393939;min-width:10%
	}

	.WPsCRM_document-table {
		width: 100%;
		float: left;
		margin-top: 2%;
	}
	.WPsCRM_item {
	}
	.WPsCRM-total {
		width: 600px;
		float: right;
		background: whitesmoke;
	}

	.WPsCRM_paid {
		opacity: .5;
		transform: rotate(-7deg) scale(.7);
	}
	.WPsCRM_pdf-footer {
		position: absolute;
		bottom: .5in;
		height: .6in;
		left: .5in;
		right: .5in;
		padding-top: 10px;
		border-top: 1px solid #e5e5e5;
		text-align: left;
		color: #787878;
		font-size: 12px;
	}
	.WPsCRM_pdf-body {
			float:left
	}
	.WPsCRM_document-header{float:left}
	.size-a4 { width: 8.3in; height: 11.7in; }


	.WPsCRM_signatures {
		padding-top: .3in;
		float:right;
		width:300px;display:inline-block;
		clear:both;
	}
	.WPsCRM_formatted-signature {
            margin-top:15px;
	}
	.WPsCRM_headerlines {
		line-height: 1em;
		width: 100%;
		float: left;
		display: inline-block;
		clear: none;
	}' . $document_options['document_custom_css'] . '
	</style>
	';

  if ($general_options['print_logo'] == '1')
    $logo = '<img src="' . $general_options['company_logo'] . '" class="WPsCRM_companylogo" />';
  else
    $logo = "";

  //document header
  $headerLines = "";
  //$m=$document->master_data();
  //unset($m[0]);
  //$m[]=array('full_header'=>"<table align=\"right\"><tr><td><h2 class=\"WPsCRM_businessName\">".html_entity_decode($options['business_name'])."</h2>".html_entity_decode($options['business_address'])." - ".html_entity_decode($options['business_zip']).", ".html_entity_decode($options['business_town'])."</td></tr></table>",'show'=>1);	

  foreach ($document->master_data() as $data => $val) {
    $val1 = array_values($val);
    if ($val['show'] == 1) {
      if ($val['show_label'] == 1 && html_entity_decode($val1[0]) != "") {
        $headerLines .= '<small>' . key($val) . '</small>: ' . html_entity_decode($val1[0]) . '<br />';
      } else if ($val1[0] != "") {
        $headerLines .= html_entity_decode($val1[0]) . '<br />';
      }
    }
  }
  if ($document_options['header_alignment'] == "text,logo")
    $header = '<page_header>
				<table class="page_header" cellspacing="0" style="width: 100%; font-size: 15px">
					<tr>
						<td align="left" style="width: 66%;">' . $headerLines . '</td>
						<td style="width: 33%; text-align:right">' . $logo . '</td>
					</tr>
				</table>
			</page_header>';
  else
    $header = '<page_header>
				<table class="page_header" cellspacing="0" style="width: 100%; font-size: 15px">
					<tr>
						<td align="left" style="width: 33%;">' . $logo . '</td>
						<td style="width: 66%; text-align:right">' . $headerLines . '</td>
					</tr>
				</table>
			</page_header>';
  if ($ID) {
    $sql = "select * from $d_table where id=$ID";
    $riga = $wpdb->get_row($sql, ARRAY_A);
    switch ($tipo = $riga["tipo"]) {
      case 1:
        $progressivo = $riga["progressivo"];
        $document_name = __("Angebot", 'cpsmartcrm');
        $text_before = $document_messages['offers_before'];
        $text_after = $document_messages['offers_after'];
        $document_prefix = $document_numbering['offers_prefix'];
        $document_suffix = $document_numbering['offers_suffix'];
        $document_dear = $document_messages['offers_dear'];
        $edit_url = admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&ID=' . $ID);
        break;
      case 2:
        $progressivo = $riga["progressivo"];
        $document_name = __("Rechnung", 'cpsmartcrm');
        $text_before = $document_messages['invoices_before'];
        $text_after = $document_messages['invoices_after'];
        $document_prefix = $document_numbering['invoices_prefix'];
        $document_suffix = $document_numbering['invoices_suffix'];
        $document_dear = $document_messages['invoices_dear'];
        $edit_url = admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php&ID=' . $ID);
        break;
      case 3:
        $progressivo = $riga["id"];
        $document_name = __("Informelle Rechnung", 'cpsmartcrm');
        $text_before = $document_messages['invoices_before'];
        $text_after = $document_messages['invoices_after'];
        $document_prefix = $document_numbering['invoices_prefix'];
        $document_suffix = $document_numbering['invoices_suffix'];
        $document_dear = $document_messages['invoices_dear'];
        $edit_url = admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php&ID=' . $ID);

        break;
    }
    $riferimento = $riga["riferimento"];
    $oggetto = $tipo == 1 ? $riga["oggetto"] : "";
    if ($FK_clienti = $riga["fk_clienti"]) {
      $sql = "select ragione_sociale, nome, cognome, email, indirizzo, cap, localita, provincia, p_iva, cod_fis, tipo_cliente from $c_table where ID_clienti=" . $FK_clienti;
      //  	echo $sql;
      $rigac = $wpdb->get_row($sql, ARRAY_A);
      $cliente = $rigac["ragione_sociale"] ? $rigac["ragione_sociale"] : $rigac["nome"] . " " . $rigac["cognome"];
      $cliente = stripslashes($cliente);
      $indirizzo = $rigac["indirizzo"];
      $tipo_cliente = $rigac["tipo_cliente"];
      $cap = $rigac["cap"];
      $localita = $rigac["localita"];
      $provincia = $rigac["provincia"];
      $email = $rigac["email"];
      $p_iva = $rigac["p_iva"];
      $cod_fis = $rigac["cod_fis"];
    }
    if ($FK_contatti = $riga["FK_contatti"]) {
      $sql = "select concat(nome,' ', cognome) as contatto from ana_contatti where ID_contatti=" . $FK_contatti;
      $rigac = $wpdb->get_row($sql, ARRAY_A);
      $contatto = $rigac["contatto"];
    }
    $pagamento = $riga["modalita_pagamento"];
    $n_offerta = $document_prefix;
    $n_offerta .= $progressivo . "/" . date("Y", strtotime($riga["data"]));
    $n_offerta .= $document_suffix;
  }

  //document sub-header
  $subheader = '';
  //switch alignment ofcustomer data and document header reverse to logo/header
  if ($document_options['header_alignment'] == "text,logo") {
    $subheader .= '<table class="WPsCRM_customer_data" cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt;padding-bottom:20px">
			<tr>
				<td style="width:60%;vertical-align:top;color:#666">' . $document_name . '<b style="color:#393939"> # ' . $n_offerta . '</b> ' . __("ausgegeben am", 'cpsmartcrm') . ' ' . WPsCRM_culture_date_format($riga["data"]) . '</td>
				<td style="width:40%;text-align:right">' . $document_dear . ' <b>' . $cliente . '</b><br />
				' . $indirizzo . '<br />' . $cap . '  ' . $localita . '     ( ' . $provincia . ' )';
    if ($p_iva)
      $subheader .= '<br />' . __('Umsatzsteuer-ID', 'cpsmartcrm') . ': ' . $p_iva;
    if ($cod_fis)
      $subheader .= '<br />' . __('Steuernummer', 'cpsmartcrm') . ': ' . $cod_fis;
    if ($contatto)
      $subheader .= '<br />C.A. ' . $contatto;
    if ($riferimento)
      $subheader .= '<br />' . __('Referenz #', 'cpsmartcrm') . ': ' . $riferimento;
    $subheader .= '</td>';
  }
  else {
    $subheader .= '<table class="WPsCRM_customer_data" cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt;padding-bottom:20px">
			<tr>
				<td style="width:50%;text-align:left">' . $document_dear . ' <b>' . $cliente . '</b><br />
				' . $indirizzo . '<br />' . $cap . '  ' . $localita . '     ( ' . $provincia . ' )';
    if ($p_iva)
      $subheader .= '<br />' . __('Umsatzsteuer-ID', 'cpsmartcrm') . ': ' . $p_iva;
    if ($cod_fis)
      $subheader .= '<br />' . __('Steuernummer', 'cpsmartcrm') . ': ' . $cod_fis;
    if ($contatto)
      $subheader .= '<br />C.A. ' . $contatto;
    if ($riferimento)
      $subheader .= '<br />' . __('Referenz #', 'cpsmartcrm') . ': ' . $riferimento;
    $subheader .= '</td>';
    $subheader .= '<td style="width:50%;vertical-align:top;text-align:right;color:#666">' . $document_name . '<b style="color:#393939"> # ' . $n_offerta . '</b> ' . __("ausgegeben am", 'cpsmartcrm') . ' ' . WPsCRM_culture_date_format($riga["data"]) . '</td>';
  }
  $subheader .= '
			</tr>
		</table>';

  //document body
  $doc_body = "";
  if ($oggetto)
    $doc_body .= '<h4 class="WPsCRM_document_subject">' . __('Betreff', 'cpsmartcrm') . ': ' . $oggetto . '</h4>';
  if ($text_before)
    $doc_body .= '<p>' . stripslashes($text_before) . '</p>';
  if ($testo_libero = $riga["testo_libero"]) {
    $doc_body .= '<p>' . stripslashes($testo_libero) . '</p>';
  }
  //$doc_body .="</table>";
  $accontOptions = get_option("CRM_acc_settings");
  /**
   * //inclusione a seconda del tipo di contabilità
   */
  switch ($accontOptions['accountability']) {
    case 0:
      include (WPsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_0.php');
      break;
    case "1":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_1.php');
      break;
    case "2":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_2.php');
      break;
    case "3":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_3.php');
      break;
    case "4":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_4.php');
      break;
  }
  $tab_cond = "";
  if ($pagamento)
    $tab_cond .= "<tr style=\"background:transparent!important\"><td style=\"background:transparent!important\">" . __("Zahlung", 'cpsmartcrm') . ":</td><td>" . $pagamento . "</td></tr>";
  if ($riga["annotazioni"])
    $tab_cond .= "<tr style=\"background:transparent!important\"><td style=\"background:transparent!important\">" . __("Angebote", 'cpsmartcrm') . ":</td><td style='font-size:.9em;font-style:italic'>" . stripslashes($riga["annotazioni"]) . "ddd</td></tr>
	";
  $doc_body .= $t_articoli;
  //if ($tab_tot && $tipo==2)
  //if ($tab_tot)
  //{
  $doc_body .= '<nobreak><table class="WPsCRM_table_total" align="right">' . $tab_tot . '</table></nobreak>';
  //}
  if ($tab_cond) {
    $doc_body .= '
			<table class="table WPsCRM_table-conditions" style="width:100%;margin-top:10px" align="left">

				' . $tab_cond . '
			</table>
	';
  }
  //if ($riga["pagato"] && $tipo==2)
  //{
  //    $doc_body.='<div class="row"></div><div class="col-md-5 pull-left" style="padding:0"><table class="table _paid"><tr><td><img src="'.WPsCRM_URL.'css/img/paid_'.WPsCRM_CULTURE.'.png" class="WPsCRM_paid"/></td></tr></table></div>';
  //}
  //if ($tipo ==3)
  //{
  //    $doc_body.='<div class="row"></div><div class="col-md-5 pull-left" style="padding:0"><table class="table  _informal"><tr><td><img src="'.WPsCRM_URL.'css/img/informal_'.WPsCRM_CULTURE.'.png" class="WPsCRM_paid"/></td></tr></table></div>';
  //}
  if ($text_after)
    $doc_body .= '<p class="WPsCRM_text-after">' . stripslashes($text_after) . '</p>';
  //$doc_body.="<div class=\"WPsCRM_signatures\">";
  if ($tipo == 1 && $document->use_signature() == true)
    $doc_body .= '<div class="WPsCRM_signatures">' . $signature . '</div>';
  if ($tipo == 1 && $document->use_formatted_signature() == true)
    $doc_body .= '<table align="right" class="WPsCRM_formatted-signature"><tr><td>' . $formatted_signature . '</td></tr></table>';
  $content = $style . '<page backcolor="#FEFEFE" backtop="60mm" backbottom="30mm" footer="date;page" style="font-size: 12pt">' . $header . '' . $subheader . '' . $doc_body . '</page>';
  ob_end_clean();

  return $content;
}

function _WPsCRM_generate_document_HTML($ID) {
  global $wpdb;
  ob_start();
  $print_nonce = wp_create_nonce("print_document");
  //$ID=$_GET['id_invoice'];
  global $document;
  $document_numbering = $document->numbering();
  $document_messages = $document->messages();
  $signature = $document->signature();
  $formatted_signature = html_entity_decode($document->formatted_signature());

  $c_table = WPsCRM_TABLE . "clienti";
  $d_table = WPsCRM_TABLE . "documenti";
  $dd_table = WPsCRM_TABLE . "documenti_dettaglio";

  $general_options = get_option('CRM_general_settings');
  $document_options = get_option('CRM_documents_settings');
  $def_iva = $document_options['default_vat'];
  $accOptions = get_option("CRM_acc_settings");

  //css
  $style = '<style type="text/Css">
	table.page_header {
		width: 100%; border: none; padding: 2mm
	}
	.WPsCRM_th-code{width: 10%;border-bottom: solid 1px black;margin:40px}
	.WPsCRM_th-desc{width: 18%}
	.WPsCRM_companylogo {
		max-width: 200px;
		max-height: 200px;
		margin: 10px;
	}
	.WPsCRM_subheader {
	}
	.WPsCRM_customer_data {
		float: right;
		background-color:#fafafa;
	}
	.WPsCRM_document-data {
	}
	.WPsCRM_document-body{padding:2%;position:relative;top:2%}

	.WPsCRM_document_subject {
		float: left;
		width: 100%;
	}
	.WPsCRM_text-before {
		width: 100%;
		float: left;
		margin-top: 2%;
	}
	.WPsCRM_text-free {
		width: 100%;
		float: left;
		margin-top: 2%;
	}
	.WPsCRM_text-after {
		width: 100%;
		float: left;
		margin-top: 2%;
	}

	.WPsCRM_items_header {
		background-color: #dddddd;
	padding:10px;border-bottom: solid 1px #393939;border-right: solid 1px #393939;min-width:10%
	}
	.WPsCRM_desc{width:35%}
	.WPsCRM_price,.WPsCRM_tot{width:12%;margin-right:5mm}

	.WPsCRM_document-table {
		width: 100%;
		float: left;
		margin-top: 2%;
	}
	.WPsCRM_item {
	}
	.WPsCRM-total {
		width: 90%;
		float: right;
		background: whitesmoke;
	}
	.WPsCRM_table-conditions {
	}
	.WPsCRM_paid {
		opacity: .5;
		transform: rotate(-7deg) scale(.7);
	}
	.WPsCRM_pdf-footer {
		position: absolute;
		bottom: .5in;
		height: .6in;
		left: .5in;
		right: .5in;
		padding-top: 10px;
		border-top: 1px solid #e5e5e5;
		text-align: left;
		color: #787878;
		font-size: 12px;
	}
	.WPsCRM_pdf-body {
			float:left
	}
	.WPsCRM_document-header{float:left}
	.size-a4 { width: 8.3in; height: 11.7in; }


	.WPsCRM_signatures {
		padding-top: .3in;
		float:right;
		width:300px;display:inline-block;
		clear:both;
	}
	.WPsCRM_formatted-signature {
	}
	.WPsCRM_headerlines {
		line-height: 1em;
		width: 100%;
		float: left;
		display: inline-block;
		clear: none;
	}' . $document_options['document_custom_css'] . '
	</style>
	';

  if ($general_options['print_logo'] == '1')
    $logo = '<img src="' . $general_options['company_logo'] . '" class="WPsCRM_companylogo" />';
  else
    $logo = "";
  //$intestazione=implode("<br>", $document->company_data);
  //document header
  $headerLines = "";
  foreach ($document->master_data() as $data => $val) {
    $val1 = array_values($val);
    if ($val['show'] == 1) {
      if ($val['show_label'] == 1 && html_entity_decode($val1[0]) != "") {
        $headerLines .= '<small>' . key($val) . '</small>: ' . html_entity_decode($val1[0]) . '<br />';
        //if(key($val)=="full_header"){
        //    $headerLines.="sss<div style=\"height:2px;display:block;width:100%;background:#000\"></div>";
        //}
      } else if ($val1[0] != "") {
        $headerLines .= html_entity_decode($val1[0]) . '<br />';
      }
    }
  }
  $header = '<page_header>
				<table class="page_header" cellspacing="0" style="width: 100%; font-size: 15px">
					<tr>
						<td align="left" style="width: 66%;">' . $headerLines . '</td>
						<td style="width: 33%; text-align:right">' . $logo . '</td>


					</tr>
				</table>
			</page_header>';

  if ($ID) {
    $sql = "select * from $d_table where id=$ID";
    $riga = $wpdb->get_row($sql, ARRAY_A);
    switch ($tipo = $riga["tipo"]) {
      case 1:
        $progressivo = $riga["progressivo"];
        $document_name = __("Angebot", 'cpsmartcrm');
        $text_before = $document_messages['offers_before'];
        $text_after = $document_messages['offers_after'];
        $document_prefix = $document_numbering['offers_prefix'];
        $document_suffix = $document_numbering['offers_suffix'];
        $document_dear = $document_messages['offers_dear'];
        $edit_url = admin_url('admin.php?page=smart-crm&p=documenti/form_quotation.php&ID=' . $ID);
        break;
      case 2:
        $progressivo = $riga["progressivo"];
        $document_name = __("Rechnung", 'cpsmartcrm');
        $text_before = $document_messages['invoices_before'];
        $text_after = $document_messages['invoices_after'];
        $document_prefix = $document_numbering['invoices_prefix'];
        $document_suffix = $document_numbering['invoices_suffix'];
        $document_dear = $document_messages['invoices_dear'];
        $edit_url = admin_url('admin.php?page=smart-crm&p=documenti/form_invoice.php&ID=' . $ID);
        break;
      case 3:
        $progressivo = $riga["id"];
        $document_name = __("Informelle Rechnung", 'cpsmartcrm');
        $text_before = $document_messages['invoices_before'];
        $text_after = $document_messages['invoices_after'];
        $document_prefix = $document_numbering['invoices_prefix'];
        $document_suffix = $document_numbering['invoices_suffix'];
        $document_dear = $document_messages['invoices_dear'];
        $edit_url = admin_url('admin.php?page=smart-crm&p=documenti/form_invoice_informal.php&ID=' . $ID);

        break;
    }
    $riferimento = $riga["riferimento"];
    $oggetto = $tipo == 1 ? $riga["oggetto"] : "";
    if ($FK_clienti = $riga["fk_clienti"]) {
      $sql = "select ragione_sociale, nome, cognome, email, indirizzo, cap, localita, provincia, p_iva, cod_fis, tipo_cliente from $c_table where ID_clienti=" . $FK_clienti;
      //  	echo $sql;
      $rigac = $wpdb->get_row($sql, ARRAY_A);
      $cliente = $rigac["ragione_sociale"] ? $rigac["ragione_sociale"] : $rigac["nome"] . " " . $rigac["cognome"];
      $cliente = stripslashes($cliente);
      $indirizzo = $rigac["indirizzo"];
      $tipo_cliente = $rigac["tipo_cliente"];
      $cap = $rigac["cap"];
      $localita = $rigac["localita"];
      $provincia = $rigac["provincia"];
      $email = $rigac["email"];
      $p_iva = $rigac["p_iva"];
      $cod_fis = $rigac["cod_fis"];
    }
    if ($FK_contatti = $riga["FK_contatti"]) {
      $sql = "select concat(nome,' ', cognome) as contatto from ana_contatti where ID_contatti=" . $FK_contatti;
      $rigac = $wpdb->get_row($sql, ARRAY_A);
      $contatto = $rigac["contatto"];
    }
    $pagamento = $riga["modalita_pagamento"];
    $n_offerta = $document_prefix;
    $n_offerta .= $progressivo . "/" . date("Y", strtotime($riga["data"]));
    $n_offerta .= $document_suffix;
  }
  //document sub-header
  $subheader = '';
  $subheader .= '<table class="WPsCRM_customer_data" cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt;">
			<tr>
				<td style="width:60%;vertical-align:top"><b>' . $document_name . ' # ' . $n_offerta . ' ' . __("ausgestellt am", 'cpsmartcrm') . ' ' . WPsCRM_culture_date_format($riga["data"]) . '</b></td>
				<td style="width:40%; ">' . $document_dear . ' <b>' . $cliente . '</b><br />
				' . $indirizzo . '<br />' . $cap . '  ' . $localita . '     ( ' . $provincia . ' )';
  if ($p_iva)
    $subheader .= '<br />' . __('Umsatzsteuer-ID', 'cpsmartcrm') . ': ' . $p_iva;
  if ($cod_fis)
    $subheader .= '<br />' . __('Steuernummer', 'cpsmartcrm') . ': ' . $cod_fis;
  if ($contatto)
    $subheader .= '<br />C.A. ' . $contatto;
  if ($riferimento)
    $subheader .= '<br />' . __('Referenz #', 'cpsmartcrm') . ': ' . $riferimento;
  $subheader .= '</td>
			</tr>
		</table>';
  //$subheader .='<div class="col-md-6 WPsCRM_document-data"><b>'.$document_name.' # '.$n_offerta.' '.__("issued on",'cpsmartcrm').' '.WPsCRM_culture_date_format($riga["data"]).'</b></div>';
  //$subheader .='</section>';
  //document body
  if ($oggetto)
    $doc_body .= '<h4 class="WPsCRM_document_subject">' . __('Betreff', 'cpsmartcrm') . ': ' . $oggetto . '</h4>';
  if ($text_before)
    $doc_body .= '<table class="WPsCRM_text-before"><tr><td>' . stripslashes($text_before) . '</td></tr></table>';
  if ($testo_libero = $riga["testo_libero"]) {
    $doc_body .= '<table class="WPsCRM_text-free"><tr><td>' . stripslashes($testo_libero) . '</td></tr></table>';
  }
  $accontOptions = get_option("CRM_acc_settings");
  /**
   * //inclusione a seconda del tipo di contabilità
   */
  switch ($accontOptions['accountability']) {
    case 0:
      include (WPsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_0.php');
      break;
    case "1":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_1.php');
      break;
    case "2":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_2.php');
      break;
    case "3":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_3.php');
      break;
    case "4":
      include (ACCsCRM_DIR . '/inc/crm/documenti/accountabilities/accountability_print_4.php');
      break;
  }
  $tab_cond = "";
  if ($pagamento)
    $tab_cond .= "<tr style=\"background:transparent!important\"><td style=\"background:transparent!important\">" . __("Zahlung", 'cpsmartcrm') . ":</td><td>" . $pagamento . "</td></tr>";
  if ($riga["annotazioni"])
    $tab_cond .= "<tr style=\"background:transparent!important\"><td colspan='1' style=\"background:transparent!important\">" . __("Angebote", 'cpsmartcrm') . ":</td><td colspan='1' style='font-size:.9em;font-style:italic'>" . stripslashes($riga["annotazioni"]) . "</td></tr>
	";
  $doc_body .= $t_articoli;
  //if ($tab_tot && $tipo==2)
  //if ($tab_tot)
  //{
  $doc_body .= '<table class="table WPsCRM-total"><tbody>' . $tab_tot . '</tbody></table>';
  //}
  if ($tab_cond) {
    $doc_body .= '<div class="col-md-5 pull-left" style="padding:0">
			<table class="table WPsCRM_table-conditions">
			<thead>
					<th style="border:none">
						<h4>' . __("Bedingungen", 'cpsmartcrm') . ':</h4>
					</th>
				</thead>
			<tbody>' . $tab_cond . '</tbody></table></div>
	';
  }
  if ($riga["pagato"] && $tipo == 2) {
    $doc_body .= '<div class="row"></div><div class="col-md-5 pull-left" style="padding:0"><table class="table _paid"><tr><td><img src="' . WPsCRM_URL . 'css/img/paid_' . WPsCRM_CULTURE . '.png" class="WPsCRM_paid"/></td></tr></table></div>';
  }
  if ($tipo == 3) {
    $doc_body .= '<div class="row"></div><div class="col-md-5 pull-left" style="padding:0"><table class="table  _informal"><tr><td><img src="' . WPsCRM_URL . 'css/img/informal_' . WPsCRM_CULTURE . '.png" class="WPsCRM_paid"/></td></tr></table></div>';
  }
  if ($text_after)
    $doc_body .= '<table class="WPsCRM_text-after"><tr><td>' . stripslashes($text_after) . '</td></tr></table>';
  $doc_body .= "<div class=\"WPsCRM_signatures\">";
  if ($tipo == 1 && $document->use_signature() == true)
    $doc_body .= $signature;
  if ($tipo == 1 && $document->use_formatted_signature() == true)
    $doc_body .= '<table class="WPsCRM_formatted-signature"><tr><td>' . $formatted_signature . '</td></tr></table>';
  $doc_body .= "</div>";
  //$doc_body.='</section>';
  $content = $style . '<page backcolor="#FEFEFE" backtop="60mm" backbottom="30mm" footer="date;heure;page" style="font-size: 12pt">' . $header . '' . $subheader . '' . $doc_body . '</page>';
  ob_end_clean();
  ob_clean();
  return $content;
}

function WPsCRM_create_pdf_document($ID, $content, $attachment = 0, $old_file = null, $tipo = null, $WOOmail = null) {
  global $wpdb;

  $d_table = WPsCRM_TABLE . "documenti";
  $_customer = new CRM_customer();
  $_customer->set_customerbyID_doc($ID);
  $email = $_customer->email;
  $FK_clienti = $_customer->customerID;
  $html2pdf = new HTML2PDF('P', 'A4', 'it');
  //$html2pdf->setModeDebug();
  $html2pdf->pdf->SetDisplayMode('fullpage');

  $html2pdf->writeHTML($content, false);

  $save_to_path = WPsCRM_UPLOADS;
  if (!file_exists($save_to_path))
    wp_mkdir_p($save_to_path);
  if (file_exists($save_to_path . "/" . $old_file . ".pdf"))
    unlink($save_to_path . "/" . $old_file . ".pdf");
  $random_name = WPsCRM_gen_random_code(20);
  $document_name = $FK_clienti . "_" . $tipo . "_" . $ID . "_" . $random_name;
  $filename = $save_to_path . "/" . $document_name . ".pdf";
  //  echo $filename;
  $html2pdf->Output($filename, 'F');
  $wpdb->update(
          $d_table, array('filename' => "$document_name"), array('id' => $ID), array('%s')
  );

  if ($attachment == 1) {
    $user_page = get_option('CRM_user_page');
    //$html2pdf->WriteHTML($content,false);
    $content2 = $html2pdf->Output('', 'S');
    $content2 = chunk_split(base64_encode($content2));
    $mailto = $email;
    $mailto = "info@stefyonweb.com";
    $options = get_option('CRM_general_settings');
    $from_mail = ( isset($options['emailFrom']) && trim($options['emailFrom']) != "" ) ? $options['emailFrom'] : get_bloginfo('admin_email');
    $from_name = ( isset($options['nameFrom']) && trim($options['nameFrom']) != "" ) ? $options['nameFrom'] : "WP smart CRM - " . get_bloginfo('name');
    $e_date = date("Y-m-d");
    //$subject = 'Fattura del '.date("d-m-Y").' di '. $from_name; 
    $subject = sprintf(__('Rechnung ausgestellt am %1$s von %2$s', 'cpsmartcrm'), date("d-m-Y"), $from_name);
    //$message = "Gentile cliente, ti ringraziamo per il tuo acquisto. In allegato trovi la tua fattura.". PHP_EOL;
    $message = __('Sehr geehrter Kunde, wir danken Ihnen für Ihren Einkauf. Ihre Rechnung ist beigefügt.', 'cpsmartcrm');
    ;
    $message .= "\r\n";
    $message .= __('Mit freundlichen Grüßen', 'cpsmartcrm');
    $message .= PHP_EOL;
    //$message .= "Clicca il seguente link per accedere al tuo pannello di controllo:  <a href=\"".get_page_link($user_page)."\">".__("Profile page.",'cpsmartcrm')."</a>";
    //$attachname=$document_name.".pdf";
    $attachments[] = $filename;
    $headers[] = 'From: ' . $WOOmail->emailFrom . PHP_EOL;
    $WOOmail->sendMailToCustomer($from_mail, $mailto, $subject, $message, $e_date, $FK_clienti, $headers, $attachments);
  }
}

function WPsCRM_print_totalBox($tipo) {
  ob_start();
  ?>
  <?php
  if ($tipo == 1) {
    //return;
    ?>
    <div class="col-md-3">
        <label style="font-weight:normal;margin-top:10px;padding:8px 0;">
            <?php _e("Druck netto + Steuern", "cpsmartcrm") ?>
        </label>
        <input type="radio" name="printTotal" id="printTotal" value="all" checked />
    </div>
    <div class="col-md-3">
        <label style="font-weight:normal;margin-top:10px;padding:8px 0">
            <?php _e("Bruttobetrag drucken", "cpsmartcrm") ?>
        </label>
        <input type="radio" name="printTotal" id="printTotal" value="total" />
    </div>
    <div class="col-md-3">
        <label style="font-weight:normal;margin-top:10px;padding:8px 0">
            <?php _e("Tabelle der Gesamtsumme anzeigen", "cpsmartcrm") ?>
        </label>
        <input type="checkbox" name="printTotalTable" id="printTotalTable" checked />
    </div>
    <!--<div class="col-md-3">
        <label style="font-weight:normal;margin-top:10px;padding:8px 0">
    <?php _e("Do not print anyting", "cpsmartcrm") ?>
        </label>
        <input type="radio" name="printTotal" id="printTotal" value="none" />
    </div>-->
  <?php } ?>
  <?php if ($tipo == 2) { ?>
    <div class="col-md-6">
        <label style="font-weight:normal;margin-top:10px;padding:8px 0;display:flex"><?php _e("'PAID' drucken, wenn bezahlt", "cpsmartcrm") ?>

            <input type="checkbox" name="printPaid" id="printPaid" value="1" checked style="float:left"/>
        </label>
    </div>
  <?php } ?>
  <?php if ($tipo == 3) { ?>
    <div class="col-md-6">
        <label style="font-weight:normal;margin-top:10px;padding:8px 0">
            <?php _e("Drucken 'Nicht formell' ", "cpsmartcrm") ?>
        </label>
        <input type="checkbox" name="printPaid" id="printPaid" value="1" checked />
    </div>
  <?php } ?>
  <?php
  echo ob_get_clean();
}

;
add_action('WPsCRM_totalBox', 'WPsCRM_print_totalBox', 1);

add_action('show_user_profile', 'SOFT_user_CRM_ID');

// Hooks near the bottom of the profile page (if not current user)
add_action('edit_user_profile', 'SOFT_user_CRM_ID');

// @param WP_User $user
function SOFT_user_CRM_ID($user) {
  ?>
  <table class="form-table">
      <tr>
          <th>
              <label for="code">
                  <?php _e('ID CRM'); ?>
              </label>
          </th>
          <td>
              <input type="text" name="CRMcode" id="CRMcode" value="<?php echo esc_attr(get_the_author_meta('CRMcode', $user->ID)); ?>" class="regular-text" />
          </td>
      </tr>
  </table>
  <?php
}

// Hook is used to save custom fields that have been added to the WordPress profile page (if current user)
add_action('personal_options_update', 'update_extra_profile_fields');

// Hook is used to save custom fields that have been added to the WordPress profile page (if not current user)
add_action('edit_user_profile_update', 'update_extra_profile_fields');

function update_extra_profile_fields($user_id) {
  if (current_user_can('edit_user', $user_id))
    update_user_meta($user_id, 'CRMcode', $_POST['CRMcode']);
}

/**
 * Hook is used to display extra fields to import customer to newsletter plugin
 * @return void
 */
add_action('WPsCRM_add_rows_to_customer_form', 'WPsCRM_HTML_display_newsletter_row', 10, 3);

function WPsCRM_HTML_display_newsletter_row($custom_tax, $ID, $email) {
  is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
  if (in_array('newsletter/plugin.php', apply_filters('active_plugins', $filter))) {
    global $wpdb;
    if ($email) {
      //controllo se esiste gia un iscritto
      $sql = "select id, email, status, list_1, list_2, list_3, list_4, list_5, list_6, list_7, list_8, list_9, list_10, list_11, list_12, list_13, list_14, list_15, list_16, list_17, list_18, list_19, list_20 from " . $wpdb->prefix . 'newsletter' . " where email='" . $email . "'";
      $riga = $wpdb->get_row($sql);
      if ($riga) {
        $status = $riga->status;
        switch ($status) {
          case "C":
            $msg = __('Ein Datensatz mit dieser E-Mail existiert bereits im Status "Bestätigt". ', 'cpsmartcrm');
            break;
          case "S":
            $msg = __('Ein Datensatz mit dieser E-Mail befindet sich bereits im Status "Nicht bestätigt". ', 'cpsmartcrm');
            break;
          case "U":
            $msg = __('Ein Datensatz mit dieser E-Mail befindet sich bereits im Status "Abgemeldet". ', 'cpsmartcrm');
            break;
          case "B":
            $msg = __('Ein Datensatz mit dieser E-Mail ist bereits im Status "Zurückgewiesen" vorhanden. ', 'cpsmartcrm');
            break;
          default:
            $msg = __('Ein Datensatz mit dieser E-Mail ist bereits vorhanden ', 'cpsmartcrm');
            break;
        }
        ?>
        <div class="col-md-6 pull-right _customer_newsletter">
            <h3><?php _e('Newsletter-Management', 'cpsmartcrm') ?></h3>
            <div class="row form-group">
                <label class="control-label"><?php echo $msg ?> <small><a href="<?php echo admin_url('admin.php?page=newsletter_users_index') ?>" target="_blank"><?php _e('Abonnenten verwalten', 'cpsmartcrm') ?>&raquo;</a></small></label>
            </div>
            <input type="hidden" name="newsletter_flag" id="newsletter_flag" value="1">
            <?php
            $options_profile = get_option('newsletter_profile');
            echo '<div class="newsletter-preferences-group row form-group"><h6 style="border-top:1px solid #ccc;padding-top:6px">' . __('Füge einen Datensatz zu bestimmten Newsletter-Listen hinzu', 'cpsmartcrm') . ':</h6>';

            for ($i = 1; $i <= 20; $i++) {
              if (empty($options_profile['list_' . $i])) {
                continue;
              }
              $nomelista = "list_" . $i;
              if ($riga->$nomelista == 1)
                $checked = "checked";
              else
                $checked = "";
              ?>
              <div class="newsletter-preferences-item">
                  <label>
                      <input type="checkbox" id="list_<?php echo $i ?>" name="list_<?php echo $i ?>" value="1" <?php echo $checked ?>>
                      <?php echo esc_html($options_profile['list_' . $i]) ?>
                  </label>
              </div>
              <?php
            }
            echo '<div style="clear: both"></div></div>';
            ?></div><?php
      }
      else {
        ?>
        <div class="col-md-5 pull-right _customer_newsletter">

            <h3><?php _e('Newsletter-Management', 'cpsmartcrm') ?></h3>


            <div class="row form-group">
                <label class="col-sm-4 control-label"><?php _e('Im Newsletter einfügen', 'cpsmartcrm') ?>?</label>
                <div class="col-sm-1">
                    <input type="checkbox" name="newsletter_flag" id="newsletter_flag" value="1">
                </div>
            </div>
            <?php
            $options_profile = get_option('newsletter_profile');
            echo '<div class="newsletter-preferences-group">';

            for ($i = 1; $i <= 20; $i++) {
              if (empty($options_profile['list_' . $i])) {
                continue;
              }
              ?>
              <div class="newsletter-preferences-item">
                  <label>
                      <input type="checkbox" id="list_<?php echo $i ?>" name="list_<?php echo $i ?>" value="1">
                      <?php echo esc_html($options_profile['list_' . $i]) ?>
                  </label>
              </div>
              <?php
            }
            echo '<div style="clear: both"></div></div>';
            ?>
        </div>
        <?php
      }
    }
  }
}

function WPsCRM_advanced_print() {
  $advanced_print = false;
  $advSettings = get_option('CRM_adv_settings');
  $opt_adv_print = isset($advSettings["advanced_print"]) ? $advSettings["advanced_print"] : 0;
  is_multisite() ? $filter = get_blog_option(get_current_blog_id(), 'active_plugins') : $filter = get_option('active_plugins');
//	if ( in_array( 'wp-smart-crm-advanced/wp-smart-crm-advanced.php', apply_filters( 'active_plugins', $filter) ) && $opt_adv_print==1) 
  if (in_array('wp-smart-crm-advanced/wp-smart-crm-advanced.php', apply_filters('active_plugins', $filter)))
    $advanced_print = true;
  return $advanced_print;
}

//save document
function WPsCRM_save_document() {
  if (check_ajax_referer('update_document', 'security', false) && current_user_can('manage_crm')) {
    global $wpdb;
    parse_str($_POST['fields'], $fields);
    //var_dump($fields);
    $table = WPsCRM_TABLE . "documenti";
    $d_table = WPsCRM_TABLE . "documenti_dettaglio";
    $s_table = WPsCRM_TABLE . "subscriptionrules";
    $a_table = WPsCRM_TABLE . "agenda";
    $totale = WPsCRM_numfmt( $fields["totale"]);    
    if (!isset($fields["totale_netto"]) || !$fields["totale_netto"])
      $totale_netto = $totale;
    else
      $totale_netto = WPsCRM_numfmt( $fields["totale_netto"]);   
    if (isset($fields["modalita_pagamento"])) {
      $_modalita_pagamento = explode("~", $fields["modalita_pagamento"]);
      isset($_modalita_pagamento[1]) ? $modalita_pagamento = $_modalita_pagamento[0] : $modalita_pagamento = $fields["modalita_pagamento"];
    } else
      $modalita_pagamento = "";
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $type = $fields["type"];
    $document_options = get_option('CRM_documents_settings');
    if ($type == 1)
      $document_start = isset($document_options['offers_start']) ? $document_options['offers_start'] : "";
    elseif ($type == 2)
      $document_start = isset($document_options['invoices_start']) ? $document_options['invoices_start'] : "";
    if ($type == 3)
      $origine_proforma = 1;
    else
      $origine_proforma = 0;
    $data = WPsCRM_sanitize_date_format($fields["data"]);

    $data_scadenza = $fields["data_scadenza"] ? WPsCRM_sanitize_date_format($fields["data_scadenza"]) : "";
    $data_scadenza_timestamp = strtotime(WPsCRM_sanitize_date_format($fields["data_scadenza"]));
    $data_inserimento = date("Y-m-d");

    $data_timestamp = strtotime($data);
    $testo_libero = isset($fields["testo_libero"]) ? str_replace(array("\r\n", "\r"), "<br />", $fields["testo_libero"]) : "";
    $notify_payment = isset($fields["notify_payment"]) ? (int) $fields["notify_payment"] : 0;
    $progressivo = isset($fields["progressivo"]) ? (int) $fields["progressivo"] : 0;
    $notificationDays = isset($fields["notificationDays"]) ? (int) $fields["notificationDays"] : 0;
    $annotazioni = isset($fields["annotazioni"]) ? $fields["annotazioni"] : "";
    $commento = isset($fields["commento"]) ? $fields["commento"] : "";
    $oggetto = isset($fields["oggetto"]) ? $fields["oggetto"] : "";
    $riferimento = isset($fields["riferimento"]) ? $fields["riferimento"] : "";
    $totale_imponibile = isset($fields["totale_imponibile"]) ? WPsCRM_numfmt( $fields["totale_imponibile"]) : 0;
    $totale_imposta = isset($fields["totale_imposta"]) ? WPsCRM_numfmt( $fields["totale_imposta"]) : 0;
    $totale_cassa = isset($fields["totale_cassa"]) ? WPsCRM_numfmt( $fields["totale_cassa"]) : 0;
    $ritenuta_acconto = isset($fields["ritenuta_acconto"]) ? WPsCRM_numfmt( $fields["ritenuta_acconto"]) : 0;
    $pagato = isset($fields["pagato"]) ? $fields["pagato"] : 0;
    $perc_realizzo = isset($fields["perc_realizzo"]) ? $fields["perc_realizzo"] : 0;
    $quotation_value = isset($fields["quotation_value"]) ? WPsCRM_numfmt( $fields["quotation_value"]) : 0;
    $num_righe = $fields["num_righe"];
    $tipo_sconto = isset($fields["tipo_sconto"]) ? (int) $fields["tipo_sconto"] : 0;
    $fk_clienti = isset($fields["fk_clienti"]) ? $fields["fk_clienti"] : 0;
    $hidden_fk_clienti = isset($fields["hidden_fk_clienti"]) ? $fields["hidden_fk_clienti"] : 0;
    if ($ID = $fields["ID"]) {
      //delete scheduler, rules, emails associated to document
      $wpdb->delete(WPsCRM_TABLE . "emails", array('fk_documenti' => $ID));
      $result = $wpdb->query(
              $wpdb->prepare(
                      "delete $s_table.* from $s_table inner join $a_table on $s_table.ID= $a_table.fk_subscriptionrules where s_specific<>0 and $a_table.fk_documenti=%d", $ID
              )
      );
      $wpdb->delete(WPsCRM_TABLE . "agenda", array('fk_documenti' => $ID));

      $wpdb->update(
              $table, array('progressivo' => "$progressivo", 'data_inserimento' => "$data_inserimento", 'oggetto' => "$oggetto", 'riferimento' => "$riferimento", 'data' => "$data", 'modalita_pagamento' => "$modalita_pagamento", 'annotazioni' => "$annotazioni", 'totale_imponibile' => "$totale_imponibile", 'totale_imposta' => "$totale_imposta", 'totale' => "$totale", 'tot_cassa_inps' => "$totale_cassa", 'ritenuta_acconto' => "$ritenuta_acconto", 'totale_netto' => "$totale_netto", 'commento' => "$commento", 'testo_libero' => $testo_libero, 'giorni_pagamento' => $notificationDays, 'pagato' => $pagato, 'notifica_pagamento' => $notify_payment, 'perc_realizzo' => $perc_realizzo, 'valore_preventivo' => $quotation_value, 'data_scadenza' => $data_scadenza, 'data_scadenza_timestamp' => $data_scadenza_timestamp, 'tipo_sconto' => $tipo_sconto), array('id' => $ID), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%d', '%d', '%s', '%f', '%s', '%d', '%d')
      );
    } else {
      if ($type == 4)
        $where = "tipo=2";
      else
        $where = "tipo=$type";
      //  $cur_year = date("Y", $fields["data"]);
      $cur_year = date('Y');
      $anno_fattura = date('Y', $data_timestamp);
   //   echo "annocorrente=" . $cur_year;
    //  echo "annofattura=" . $anno_fattura;
      $sql = "select data_timestamp from $table where $where order by data_timestamp desc limit 0,1";
      $rigad = $wpdb->get_row($sql, ARRAY_A);
      $anno_ultima = date('Y', $rigad["data_timestamp"]);
      //echo "anno ultima=" . $anno_ultima;
      if ($anno_fattura == $cur_year) {
        if ($anno_fattura == $anno_ultima) {
          $sql = "select max(progressivo) as last_reg from $table where $where and year(data)='$anno_fattura'";
          $riga = $wpdb->get_row($sql, ARRAY_A);
          //     echo "ultima=". $riga["last_reg"];
          if ($document_start > $riga["last_reg"]) {
            $new_reg = $document_start + 1;
            $document_start = $new_reg;
          } else {
            $new_reg = $riga["last_reg"] + 1;
            $document_start = $new_reg;
          }
        } else {
          if (isset($document_start) && $document_start > 0) {
            $new_reg = $document_start + 1;
            $document_start = $new_reg;
          } else {
            $new_reg = 1;
            $document_start = $new_reg;
          }
        }
      }
      else{
        $sql = "select max(progressivo) as last_reg from $table where $where and year(data)='$anno_fattura'";
          $riga = $wpdb->get_row($sql, ARRAY_A);
          //     echo "ultima=". $riga["last_reg"];
          $new_reg = $riga["last_reg"] + 1;
        //  $document_start = $new_reg;
          
      }
      if ($type == 1)
        $document_options['offers_start'] = $document_start;
      elseif ($type == 2)
        $document_options['invoices_start'] = $document_start;
      update_option('CRM_documents_settings', $document_options);
      $wpdb->insert(
              $table, array('progressivo' => "$new_reg", 'tipo' => "$type", 'fk_clienti' => "$fk_clienti", 'data' => "$data", 'fk_utenti_ins' => "$user_id", 'oggetto' => "$oggetto", 'riferimento' => "$riferimento", 'modalita_pagamento' => "$modalita_pagamento", 'annotazioni' => "$annotazioni", 'totale_imponibile' => "$totale_imponibile", 'totale_imposta' => "$totale_imposta", 'totale' => "$totale", 'tot_cassa_inps' => "$totale_cassa", 'ritenuta_acconto' => "$ritenuta_acconto", 'totale_netto' => "$totale_netto", 'data_inserimento' => "$data_inserimento", 'commento' => "$commento", 'testo_libero' => $testo_libero, 'giorni_pagamento' => $notificationDays, 'perc_realizzo' => $perc_realizzo, 'notifica_pagamento' => $notify_payment, 'valore_preventivo' => $quotation_value, 'data_scadenza' => $data_scadenza, 'data_timestamp' => $data_timestamp, 'data_scadenza_timestamp' => $data_scadenza_timestamp, 'origine_proforma' => $origine_proforma, 'tipo_sconto' => $tipo_sconto), array('%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%d', '%f', '%s', '%d', '%d', '%d', '%d')
      );
    }
    //var_dump( $wpdb->last_query );
    $ID_ret = $ID ? $ID : $wpdb->insert_id;
    //articoli
    $wpdb->delete($d_table, array('fk_documenti' => $ID_ret, 'eliminato' => 1));
    $i = 1;
    $c_riga = 1;
    while (true) {
      $id_art = isset($fields["id_" . $i]) ? $fields["id_" . $i] : 0;
      $idd = isset($fields["idd_" . $i]) ? $fields["idd_" . $i] : 0;
      //	echo "riga n $i - idriga= $idd<br>";
      $tipo = $fields["tipo_" . $i];
      $codice = (string) $fields["codice_" . $i];
      $descrizione = (string) ("" . $fields["descrizione_" . $i]);
      $subscriptionrules = isset($fields["subscriptionrules_" . $i]) ? (int) $fields["subscriptionrules_" . $i] : 0;
      $prezzo =  $fields["prezzo_" . $i];
      $qta =  WPsCRM_numfmt($fields["qta_" . $i]);
      $sconto =  $fields["sconto_" . $i];
      $totale =  $fields["totale_" . $i];
      $iva = (int) $fields["iva_" . $i];
      $delete = isset($fields["delete_" . $i]) ? (int) $fields["delete_" . $i] : 0;
      //echo "id_art=".$id_art."<br>";
      if ($id_art) {
        //se nuovo lo aggiungo, altrimenti lo modifico
        if ($idd) {
          $wpdb->update(
                  $d_table, array('fk_documenti' => $ID_ret, 'fk_articoli' => $id_art, 'prezzo' => WPsCRM_numfmt( $prezzo), 'sconto' => WPsCRM_numfmt( $sconto), 'qta' => $qta, 'totale' => WPsCRM_numfmt( $totale), 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva), array('id' => $idd), array('%d', '%d', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%d')
          );
          $id_dd = $idd;
        } else {
          $wpdb->insert(
                  $d_table, array('fk_documenti' => $ID_ret, 'fk_articoli' => $id_art, 'prezzo' => WPsCRM_numfmt( $prezzo), 'sconto' => WPsCRM_numfmt( $sconto), 'qta' => $qta, 'totale' => WPsCRM_numfmt( $totale), 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva, 'tipo' => $tipo, 'fk_subscriptionrules' => $subscriptionrules), array('%d', '%d', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%d', '%d', '%d')
          );
//		var_dump( $wpdb->last_query );

          $id_dd = $wpdb->insert_id;
        }
        if ($subscriptionrules) {
          WPsCRM_insert_notification($subscriptionrules, $ID_ret, $id_dd, 0);
        }
        $c_riga++;
      } else {
        //se nuovo lo aggiungo, altrimenti lo modifico
        $sql = "select * from $d_table where id='$idd'";
        $res = $wpdb->get_results($sql, ARRAY_A);
        //  		$q=mysql_query($sql);
        if ($res) {
          $wpdb->update(
                  $d_table, array('fk_documenti' => $ID_ret, 'prezzo' => WPsCRM_numfmt( $prezzo), 'sconto' => WPsCRM_numfmt( $sconto), 'qta' => $qta, 'totale' => WPsCRM_numfmt( $totale), 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva), array('id' => $idd), array('%d', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%d')
          );
          $id_dd = $idd;
        } else {
          $wpdb->insert(
                  $d_table, array('fk_documenti' => $ID_ret, 'prezzo' => WPsCRM_numfmt( $prezzo), 'sconto' => WPsCRM_numfmt( $sconto), 'qta' => $qta, 'totale' => WPsCRM_numfmt( $totale), 'n_riga' => $i, 'codice' => $codice, 'descrizione' => $descrizione, 'iva' => $iva, 'tipo' => $tipo, 'fk_subscriptionrules' => $subscriptionrules), array('%d', '%f', '%f', '%f', '%f', '%d', '%s', '%s', '%d', '%d', '%d')
          );
//		var_dump( $wpdb->last_query );
          $id_dd = $wpdb->insert_id;
          //exit;
        }
        if ($subscriptionrules) {
          WPsCRM_insert_notification($subscriptionrules, $ID_ret, $id_dd, 0);
        }
        $c_riga++;
      }
      if ($c_riga > $num_righe)
        break;
      $i++;
    }
    //die;
    //echo "salvato<br>";
    //subscription rules
    $client_id = $fk_clienti ? $fk_clienti : $hidden_fk_clienti;
    if ($notify_payment && $type == 2) {
      $s_table = WPsCRM_TABLE . "subscriptionrules";
      $a_table = WPsCRM_TABLE . "agenda";
      $users = $fields["selectedUsers"];
      $groups = $fields["selectedGroups"];
      $days = -$notificationDays;
      $giorno = date("d", strtotime($data_scadenza));
      $mese = date("m", strtotime($data_scadenza));
      $anno = date("Y", strtotime($data_scadenza));
      $oggetto = "Fattura scaduta";
      $annotazioni = "Contattare il cliente";
      //	$data_scadenza=date("Y-m-d",mktime(0,0,0,$mese,$giorno-$days,$anno));
      //  $data_agenda=date("Y-m-d",mktime(0,0,0,$mese,$giorno-$days,$anno));
      $data_scadenza = date("Y-m-d", mktime(0, 0, 0, $mese, $giorno, $anno));
      $data_agenda = date("Y-m-d", mktime(0, 0, 0, $mese, $giorno, $anno));
      $s = "[";
      $s .= '{"ruleStep":"' . $days . '" ,"remindToCustomer":""';
      $s .= ',"selectedUsers":"' . $users . '"';
      $s .= ',"selectedGroups":"' . $groups . '"';
      $s .= ',"userDashboard":"on"';
      $s .= ',"groupDashboard":"on"';
      $s .= ',"mailToRecipients":"on"';
      $s .= '}';
      $s .= ']';

      if ($res = $wpdb->get_row("select * from $a_table where fk_documenti=$ID_ret AND fk_documenti_dettaglio = 0 ")) {
        //modify records in $a_table and $s_table
        $wpdb->update(
                $a_table, array(
            'start_date' => $data_scadenza,
            'end_date' => $data_scadenza,
            'data_agenda' => $data_agenda,
            'data_inserimento' => date("Y-m-d H:i")
                ), array('id_agenda' => $res->id_agenda), array('%s', '%s', '%s', '%s')
        );
        $wpdb->update(
                $s_table, array(
            'steps' => $s,
                ), array('ID' => $res->fk_subscriptionrules), array('%s')
        );
      } else {
        $wpdb->insert(
                $s_table, array(
            'steps' => $s,
            'name' => 'Todo',
            's_specific' => 1,
            's_type' => 1,
            's_email' => 0,
                ), array(
            '%s',
            '%s',
            '%d',
            '%d',
            '%d',
                )
        );
        $id_sr = $wpdb->insert_id;
        //  var_dump( $wpdb->last_query );

        $wpdb->insert(
                $a_table, array(
            'oggetto' => sanitize_text_field($oggetto),
            'fk_clienti' => $client_id,
            'annotazioni' => sanitize_text_field($annotazioni),
            'start_date' => $data_scadenza,
            'end_date' => $data_scadenza,
            'data_agenda' => $data_agenda,
            'priorita' => 3,
            'urgente' => 'Si',
            'importante' => 'Si',
            'data_inserimento' => date("Y-m-d H:i"),
            'fk_subscriptionrules' => $id_sr,
            'tipo_agenda' => 3,
            'fk_documenti' => $ID_ret,
            'fk_documenti_dettaglio' => 0
                ), array('%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d')
        );
      }
      //exit( var_dump( $wpdb->last_query ) );
    }
    $mail = new CRM_mail(array("ID_doc" => $ID_ret));


    if (WPsCRM_advanced_print()) {
      $sql = "select filename from $table where id=$ID_ret";
      $qf = $wpdb->get_row($sql);
      $old_file = $qf->filename;
      $WOOmail = new CRM_mail(array("ID_doc" => $ID_ret));
      $attachment = 0;
      ob_start();


      $content = WPsCRM_generate_document_HTML($ID_ret);
      //echo $content;exit;
      $options = get_option('CRM_business_settings');

      ob_end_clean();
      ob_clean();
      $html2pdf = new Html2Pdf('P', 'A4', 'it');
      //$html2pdf->setModeDebug();
      $html2pdf->pdf->SetDisplayMode('fullpage');

      $html2pdf->writeHTML($content, false);

      $save_to_path = WPsCRM_UPLOADS;
      if (!file_exists($save_to_path))
        wp_mkdir_p($save_to_path);
      if (file_exists($save_to_path . "/" . $old_file . ".pdf"))
        unlink($save_to_path . "/" . $old_file . ".pdf");
      $random_name = WPsCRM_gen_random_code(20);
      $document_name = $client_id . "_" . $type . "_" . $ID_ret . "_" . $random_name;
      $filename = $save_to_path . "/" . $document_name . ".pdf";
      //  echo $filename;
      $html2pdf->Output($filename, 'F');
      $wpdb->update(
              $table, array('filename' => "$document_name"), array('id' => $ID_ret), array('%s')
      );
//	$ret= WPsCRM_create_pdf_document($ID_ret, $content, $attachment, $old_file, $type, $WOOmail);
      //include($plugin_dir."/inc/crm/documenti/document_print2.php");
    }
    echo "OK~" . $ID_ret;
    die;
  } else
    die('security Issue');
  die();
}

add_action('wp_ajax_WPsCRM_save_document', 'WPsCRM_save_document');

function WPsCRM_string_convert($string) {
  $converted_string = htmlentities(mb_convert_encoding($string, 'UTF-8', 'ASCII'), ENT_SUBSTITUTE, "UTF-8");
  return $converted_string;
}
function WPsCRM_numfmt($num){
  $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
   
    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    } 

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

function WPsCRM_number_format_locale($number,$decimals=2) {
  setlocale(LC_NUMERIC, get_locale());
  $locale = localeconv();
  // var_dump($locale);
  return number_format($number,$decimals,
  $locale['decimal_point'],
  trim($locale['thousands_sep']));
  //return $number;
}
 
function cpsmartcrm_get_umsatzsteuer_info($options) {
    if (!empty($options['crm_kleinunternehmer'])) {
        return __('Kleinunternehmer nach §19 UStG (keine Umsatzsteuer-ID)', 'wp-smart-crm-invoices-pro');
    } else {
        return !empty($options['business_ustid']) ? esc_html($options['business_ustid']) : '<span style="color:red;">*</span>';
    }
}
/**
 * Section containing the pluggable functions of WP SmartCRM
 * 
 * @section customers (list,form,form_attivita,)
 * @section documents (list,form_invoice,form_quote)
 * @section scheduler (list,form todo, form appointments)
 * */
include_once(WPsCRM_DIR . '/inc/pluggable/clienti/list.php');
include_once(WPsCRM_DIR . '/inc/pluggable/clienti/form.php');
include_once(WPsCRM_DIR . '/inc/pluggable/documenti/list.php');
include_once(WPsCRM_DIR . '/inc/pluggable/scheduler/list.php');
