<?php

if (!defined('ABSPATH')) {
  exit;
}
$sql = "select fk_articoli, n_riga, prezzo, totale, qta, codice, descrizione, sconto, tipo, iva from $dd_table where fk_documenti=$ID order by $dd_table.n_riga";
$qd = $wpdb->get_results($sql, ARRAY_A);
if ($qd) {
  $_header = "";
  //check if records with code/discount exists: if not, I will hide the columns
  $count_c = $wpdb->get_var("SELECT COUNT(*) FROM $dd_table  where fk_documenti=$ID and codice<>''");
  $count_d = $wpdb->get_var("SELECT COUNT(*) FROM $dd_table  where fk_documenti=$ID and sconto<>0");
  if ($count_c != 0 && $count_d != 0)
    $desc_width = "38%";
  elseif ($count_c == 0 && $count_d == 0)
    $desc_width = "50%";
  elseif ($count_c == 0 || $count_d == 0)
    $desc_width = "40%";
  if ($count_c != 0)
    $_header .= "<th class=\"WPsCRM_items_header WPsCRM_cod\">" . __('Code', 'cpsmartcrm') . "</th>";
  $_header .= "<th class=\"WPsCRM_items_header WPsCRM_desc\" style='width:" . $desc_width . "'>" . __('Beschreibung', 'cpsmartcrm') . "</th>";
  $_header .= "<th class=\"WPsCRM_items_header WPsCRM_qty\">" . __('Menge', 'cpsmartcrm') . "</th>
	<th class=\"WPsCRM_items_header WPsCRM_price\">" . __('Einzelpreis', 'cpsmartcrm') . "</th>";
  if ($count_d != 0)
    $_header .= "<th class=\"WPsCRM_items_header WPsCRM_discount\">" . __('Rabatt', 'cpsmartcrm') . "</th>";
  $_header .= "<th class=\"WPsCRM_items_header WPsCRM_total\">" . __('Gesamt', 'cpsmartcrm') . "</th>";
  $t_articoli = '
		<table class="table table-bordered WPsCRM_document-table"><thead>
		<tr class="WPsCRM_header-row">' . $_header . '
		</tr>
		</thead><tbody>';
  $body = "";
  $totale_imposta = 0;
  $totale_rimborso = 0;
  $totale_righe = 0;
  $index_riga = 0;
  foreach ($qd as $rigaa) {
    $tipo_riga = $rigaa["tipo"];
    $code = "";
    $art_id = $rigaa["fk_articoli"];

    $code = $rigaa["codice"];
    $descrizione = stripslashes($rigaa["descrizione"]);
    $descrizione_length = strlen($descrizione);
    $prezzo = $rigaa["prezzo"];
    $iva = $rigaa["iva"];
    $lordo = $rigaa["totale"];
    $sconto = $rigaa["sconto"];
    if ($tipo_sconto == 0) {
      $pre_sc = $prezzo - ($prezzo * $sconto / 100);
      $sconto = WPsCRM_number_format_locale($sconto) . "%";
    } else {
      $pre_sc = $prezzo - $sconto;
      $sconto = WPsCRM_get_currency()->symbol . " " . WPsCRM_number_format_locale($sconto);
    }
    $tot_riga = $pre_sc * $rigaa["qta"];

    if ($tipo_riga == 4) {
      $totale_rimborso += $tot_riga;
    } else {
      $imposta = $tot_riga * $iva / 100;
      $totale_imposta += $imposta;
      $totale_righe += $tot_riga;
    }
    if ($tipo_riga == 3) {
      $body .= '<tr class="_item"><td colspan="6">' . $descrizione . '</td>';
    } elseif ($tipo_riga != 4) {
      $prezzo_lordo=$prezzo+$prezzo*$iva/100;
      $prezzo = WPsCRM_number_format_locale($prezzo);
      $tot_riga_lordo = $lordo * $rigaa["qta"];
      $totale_lordo = WPsCRM_number_format_locale($lordo);
      $qta=$rigaa["qta"];
      if ($qta==(int)$qta)
        $qta=(int)$qta;
      else
        $qta=WPsCRM_number_format_locale($qta);

      $body .= '<tr id="riga-' . $index_riga . '" class="WPsCRM_item" data-net="' . $prezzo . '" data-desc-lenght="' . $descrizione_length . '" data-gros="' . WPsCRM_number_format_locale($prezzo_lordo) . '" data-totalgros="' . $totale_lordo . '" data-totalnet="' . WPsCRM_number_format_locale($tot_riga) . '">';
      if ($count_c != 0)
        $body .= '<td class="WPsCRM_cod">' . $code . '</td>';
      $body .= '<td class="WPsCRM_desc">' . $descrizione . '</td>
                <td class="WPsCRM_qty" align="right">' . $qta . '</td>
                <td class="WPsCRM_price" align="right">' . WPsCRM_get_currency()->symbol . ' <span class="row_amount">' . $prezzo . '</span></td>';
      if ($count_d != 0)
        $body .= '<td class="WPsCRM_discount" align="right">' . $sconto . '</td>';
      $body .= '<td class="WPsCRM_total" align="right">' . WPsCRM_get_currency()->symbol . ' <span class="tot_riga">' . WPsCRM_number_format_locale($tot_riga) . '</span></td>
				</tr>';
    }
    if ($index_riga > 3 && $index_riga % 3 == int) {
      //$body.='<td class="page-break"></td>';
    }

    $index_riga ++;
  }
  $t_articoli .= $body . '</tbody></table>';
}



if ($totale_righe) {
  $totale_righe = sprintf("%01.2f", $totale_righe);
  $totale_imponibile = $riga["totale_imponibile"];
  $totale_imposta = $riga["totale_imposta"];
  $totale_netto = $riga["totale_netto"];
  $tab_tot = "
  	<tr class='total_net'><td>" . __("Betrag", 'cpsmartcrm') . "</td><td align='right'>" . WPsCRM_get_currency()->symbol . " " . WPsCRM_number_format_locale($totale_imponibile) . "</td></tr>";
  //$tab_tot.="<tr class='total_gros' style='display:none'><td>".__("Price",'cpsmartcrm')."</td><td align='right'>".WPsCRM_get_currency()->symbol." ".number_format($totale_netto, 2, ',', '.')."</td></tr>";
  $tab_tot .= "<tr class=\"print_tax\"><td >" . __("Steuer", 'cpsmartcrm') . "</td><td align='right'>" . WPsCRM_get_currency()->symbol . " " . WPsCRM_number_format_locale($totale_imposta) . "</td></tr>";
  if ($totale_rimborso != 0)
    $tab_tot .= "<tr class=\"WPsCRM_rowRefund\"><td>" . __("Erstattung", 'cpsmartcrm') . "</td><td align='right'>" . WPsCRM_get_currency()->symbol . " " . WPsCRM_number_format_locale($totale_rimborso) . "</td></tr>
  	";
  $tab_tot .= "<tr class=\"WPsCRM_grandTotal\"><td><h4>" . __("Gesamtsumme", 'cpsmartcrm') . "</h4></td><td align='right'><h4>" . WPsCRM_get_currency()->symbol . " " . WPsCRM_number_format_locale($totale_netto) . "</h4></td></tr>
  ";
}