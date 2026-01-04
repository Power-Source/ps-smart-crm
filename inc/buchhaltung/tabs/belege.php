<?php
/**
 * Belege/Dokumente Management Tab
 * Verwaltet Belege, Quittungen und andere Dokumente mit Verknüpfung zu Transaktionen
 */

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_crm')) {
    wp_die(__('Du hast keine Berechtigung, diese Seite zu sehen.', 'cpsmartcrm'));
}

global $wpdb;
$b_table = WPsCRM_TABLE . 'belege';
$d_table = WPsCRM_TABLE . 'documenti';
$i_table = WPsCRM_TABLE . 'incomes';
$e_table = WPsCRM_TABLE . 'expenses';

// Parse parameters
$action = sanitize_text_field($_GET['action'] ?? 'list');
$beleg_id = isset($_GET['beleg_id']) ? (int)$_GET['beleg_id'] : 0;
$page = max(1, (int) ($_GET['paged'] ?? 1));
$kategorie = sanitize_text_field($_GET['kategorie'] ?? '');
$beleg_typ = sanitize_text_field($_GET['beleg_typ'] ?? '');
$date_from = sanitize_text_field($_GET['date_from'] ?? '');
$date_to = sanitize_text_field($_GET['date_to'] ?? '');
$search = sanitize_text_field($_GET['search'] ?? '');
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Handle file download
if ($action === 'download' && $beleg_id > 0) {
    $beleg = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$b_table} WHERE id = %d", $beleg_id));
    if ($beleg && file_exists($beleg->file_path)) {
        header('Content-Type: ' . $beleg->file_mime);
        header('Content-Disposition: attachment; filename="' . basename($beleg->filename) . '"');
        header('Content-Length: ' . $beleg->file_size);
        readfile($beleg->file_path);
        exit;
    }
}

// Handle file delete
if ($action === 'delete' && $beleg_id > 0 && check_admin_referer('delete_beleg_' . $beleg_id)) {
    WPsCRM_delete_beleg($beleg_id);
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Beleg gelöscht.', 'cpsmartcrm') . '</p></div>';
}

// Handle file upload
if (isset($_POST['upload_beleg']) && check_admin_referer('belege_upload')) {
    if (!empty($_FILES['beleg_datei'])) {
        // Bestimme ob eine neue Ausgabe/Einnahme erstellt werden soll
        $new_transaction_id = null;
        $transaction_type = sanitize_text_field($_POST['transaction_type'] ?? 'none');
        
        if ($transaction_type === 'expense' || $transaction_type === 'income') {
            // Neue Transaktion erstellen
            $trans_date = sanitize_text_field($_POST['beleg_datum'] ?? date('Y-m-d'));
            $trans_kategorie = sanitize_text_field($_POST['trans_kategorie'] ?? '');
            $trans_amount = floatval($_POST['trans_amount'] ?? 0);
            $trans_tax_rate = floatval($_POST['trans_tax_rate'] ?? 0);
            $trans_description = sanitize_textarea_field($_POST['trans_description'] ?? '');
            
            if (!empty($trans_kategorie) && $trans_amount > 0) {
                // Berechne Steuern
                $trans_tax = $trans_amount * ($trans_tax_rate / 100);
                $trans_gross = $trans_amount + $trans_tax;
                
                // Speichere in richtige Tabelle
                if ($transaction_type === 'expense') {
                    $result = $wpdb->insert(
                        $e_table,
                        array(
                            'data' => $trans_date,
                            'kategoria' => $trans_kategorie,
                            'descrizione' => $trans_description,
                            'imponibile' => $trans_amount,
                            'aliquota' => $trans_tax_rate,
                            'imposta' => $trans_tax,
                            'totale' => $trans_gross,
                            'created_at' => current_time('mysql'),
                        ),
                        array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s')
                    );
                    if ($result) {
                        $new_transaction_id = $wpdb->insert_id;
                    }
                } else {
                    $result = $wpdb->insert(
                        $i_table,
                        array(
                            'data' => $trans_date,
                            'kategoria' => $trans_kategorie,
                            'descrizione' => $trans_description,
                            'imponibile' => $trans_amount,
                            'aliquota' => $trans_tax_rate,
                            'imposta' => $trans_tax,
                            'totale' => $trans_gross,
                            'created_at' => current_time('mysql'),
                        ),
                        array('%s', '%s', '%s', '%f', '%f', '%f', '%f', '%s')
                    );
                    if ($result) {
                        $new_transaction_id = $wpdb->insert_id;
                    }
                }
            }
        }
        
        // Upload Beleg
        $upload_result = WPsCRM_upload_beleg($_FILES['beleg_datei'], array(
            'beleg_typ'    => sanitize_text_field($_POST['beleg_typ'] ?? ''),
            'beleg_datum'  => sanitize_text_field($_POST['beleg_datum'] ?? date('Y-m-d')),
            'kategorie'    => sanitize_text_field($_POST['kategorie'] ?? ''),
            'beschreibung' => sanitize_textarea_field($_POST['beschreibung'] ?? ''),
            'notizen'      => sanitize_textarea_field($_POST['notizen'] ?? ''),
            'tags'         => sanitize_text_field($_POST['tags'] ?? ''),
            'fk_documenti' => !empty($_POST['fk_documenti']) ? (int)$_POST['fk_documenti'] : null,
            'fk_incomes'   => ($transaction_type === 'income' && $new_transaction_id) ? $new_transaction_id : (!empty($_POST['fk_incomes']) ? (int)$_POST['fk_incomes'] : null),
            'fk_expenses'  => ($transaction_type === 'expense' && $new_transaction_id) ? $new_transaction_id : (!empty($_POST['fk_expenses']) ? (int)$_POST['fk_expenses'] : null),
            'fk_kunde'     => !empty($_POST['fk_kunde']) ? (int)$_POST['fk_kunde'] : null,
        ));
        
        if (is_wp_error($upload_result)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . $upload_result->get_error_message() . '</p></div>';
        } else {
            $msg = $upload_result['message'];
            if ($new_transaction_id) {
                $msg .= ' | ' . ($transaction_type === 'expense' ? __('Ausgabe', 'cpsmartcrm') : __('Einnahme', 'cpsmartcrm')) . ' ' . __('erstellt', 'cpsmartcrm');
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . $msg . ' (' . $upload_result['belegnummer'] . ')</p></div>';
        }
    }
}

?>
<style>
    .crm-belege-container {
        background: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .crm-belege-filters {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
    }
    
    .crm-belege-filters input,
    .crm-belege-filters select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 13px;
    }
    
    .crm-beleg-list {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    
    .crm-beleg-list thead {
        background: #f5f5f5;
    }
    
    .crm-beleg-list th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        border-bottom: 2px solid #ddd;
    }
    
    .crm-beleg-list td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    
    .crm-beleg-list tbody tr:hover {
        background: #fafafa;
    }
    
    .crm-beleg-actions {
        white-space: nowrap;
    }
    
    .crm-beleg-actions a,
    .crm-beleg-actions button {
        padding: 4px 8px;
        margin-right: 5px;
        font-size: 12px;
        text-decoration: none;
        border-radius: 3px;
        cursor: pointer;
        background: #f0f0f0;
        border: none;
        color: #0073aa;
    }
    
    .crm-beleg-actions a:hover,
    .crm-beleg-actions button:hover {
        background: #e0e0e0;
    }
    
    .crm-beleg-delete {
        color: #dc3545 !important;
    }
    
    .crm-beleg-delete:hover {
        background: #f8d7da !important;
    }
    
    .crm-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .crm-badge-typ {
        background: #e3f2fd;
        color: #0d47a1;
    }
    
    .crm-badge-kategorie {
        background: #f3e5f5;
        color: #4a148c;
    }
    
    .crm-upload-section {
        background: #f9f9f9;
        padding: 20px;
        border: 2px dashed #ddd;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .crm-upload-section h3 {
        margin-top: 0;
    }
    
    .crm-upload-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .crm-form-group {
        display: flex;
        flex-direction: column;
    }
    
    .crm-form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 13px;
    }
    
    .crm-form-group input,
    .crm-form-group select,
    .crm-form-group textarea {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-size: 13px;
        font-family: inherit;
    }
    
    .crm-form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .crm-form-group-full {
        grid-column: 1 / -1;
    }
    
    .crm-submit-section {
        grid-column: 1 / -1;
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .crm-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
    }
    
    .crm-btn-primary {
        background: #0073aa;
        color: white;
    }
    
    .crm-btn-primary:hover {
        background: #005a87;
    }
    
    .crm-btn-secondary {
        background: #f0f0f0;
        color: #333;
    }
    
    .crm-btn-secondary:hover {
        background: #e0e0e0;
    }
    
    .crm-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .crm-stat-box {
        background: #f9f9f9;
        padding: 15px;
        border-left: 4px solid #0073aa;
        border-radius: 5px;
    }
    
    .crm-stat-box-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .crm-stat-box-value {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin-top: 5px;
    }
    
    .crm-no-belege {
        text-align: center;
        padding: 40px 20px;
        color: #999;
    }
    
    .crm-no-belege svg {
        width: 60px;
        height: 60px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
    
    #trans_kategorie_group,
    #trans_amount_group,
    #trans_tax_rate_group,
    #trans_description_group,
    #trans_notizen_tags_group {
        transition: opacity 0.3s ease;
    }
</style>

<script>
function toggleTransactionFields() {
    const transType = document.getElementById('transaction_type').value;
    const condFields = document.getElementById('trans_conditional_fields');
    
    if (transType !== 'none') {
        condFields.style.display = 'block';
    } else {
        condFields.style.display = 'none';
    }
}
</script>

<div class="crm-belege-container">
    <h2><?php _e('📎 Belege & Dokumente', 'cpsmartcrm'); ?></h2>
    
    <?php
    // Statistiken abrufen
    $stats = WPsCRM_get_belege_statistics($date_from ?: null, $date_to ?: null);
    $total_belege = $wpdb->get_var("SELECT COUNT(*) FROM {$b_table} WHERE deleted = 0");
    $total_size = $wpdb->get_var("SELECT SUM(file_size) FROM {$b_table} WHERE deleted = 0");
    ?>
    
    <div class="crm-stats">
        <div class="crm-stat-box">
            <div class="crm-stat-box-label"><?php _e('Belege insgesamt', 'cpsmartcrm'); ?></div>
            <div class="crm-stat-box-value"><?php echo $total_belege; ?></div>
        </div>
        <div class="crm-stat-box">
            <div class="crm-stat-box-label"><?php _e('Kategorien', 'cpsmartcrm'); ?></div>
            <div class="crm-stat-box-value"><?php echo count($stats); ?></div>
        </div>
        <div class="crm-stat-box">
            <div class="crm-stat-box-label"><?php _e('Speicherplatz', 'cpsmartcrm'); ?></div>
            <div class="crm-stat-box-value"><?php echo size_format($total_size); ?></div>
        </div>
    </div>
    
    <!-- Upload Section -->
    <div class="crm-upload-section">
        <h3><?php _e('📤 Neuen Beleg hochladen', 'cpsmartcrm'); ?></h3>
        <form method="post" enctype="multipart/form-data" class="crm-upload-form">
            <?php wp_nonce_field('belege_upload'); ?>
            
            <div class="crm-form-group">
                <label for="beleg_datei"><?php _e('Datei', 'cpsmartcrm'); ?> *</label>
                <input type="file" id="beleg_datei" name="beleg_datei" required 
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.zip">
                <small><?php _e('Max. 10MB | PDF, JPG, PNG, DOC, XLS, ZIP', 'cpsmartcrm'); ?></small>
            </div>
            
            <div class="crm-form-group">
                <label for="beleg_typ"><?php _e('Belegtyp', 'cpsmartcrm'); ?></label>
                <select id="beleg_typ" name="beleg_typ">
                    <option value="Rechnung"><?php _e('Rechnung', 'cpsmartcrm'); ?></option>
                    <option value="Quittung"><?php _e('Quittung', 'cpsmartcrm'); ?></option>
                    <option value="Beleg"><?php _e('Beleg', 'cpsmartcrm'); ?></option>
                    <option value="Zahlungsnachweis"><?php _e('Zahlungsnachweis', 'cpsmartcrm'); ?></option>
                    <option value="Rechnung Lieferant"><?php _e('Lieferantenrechnung', 'cpsmartcrm'); ?></option>
                    <option value="Kostenvoranschlag"><?php _e('Kostenvoranschlag', 'cpsmartcrm'); ?></option>
                </select>
            </div>
            
            <div class="crm-form-group">
                <label for="beleg_datum"><?php _e('Belegdatum', 'cpsmartcrm'); ?></label>
                <input type="date" id="beleg_datum" name="beleg_datum" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="crm-form-group">
                <label for="kategorie"><?php _e('Kategorie', 'cpsmartcrm'); ?> *</label>
                <select id="kategorie" name="kategorie" required>
                    <option value=""><?php _e('-- Wählen --', 'cpsmartcrm'); ?></option>
                    <option value="Betriebskosten"><?php _e('Betriebskosten', 'cpsmartcrm'); ?></option>
                    <option value="Material"><?php _e('Material', 'cpsmartcrm'); ?></option>
                    <option value="Dienste"><?php _e('Dienste', 'cpsmartcrm'); ?></option>
                    <option value="Reiskosten"><?php _e('Reiskosten', 'cpsmartcrm'); ?></option>
                    <option value="Versicherung"><?php _e('Versicherung', 'cpsmartcrm'); ?></option>
                    <option value="Strom/Gas/Wasser"><?php _e('Strom/Gas/Wasser', 'cpsmartcrm'); ?></option>
                    <option value="Telefon/Internet"><?php _e('Telefon/Internet', 'cpsmartcrm'); ?></option>
                    <option value="Miete"><?php _e('Miete', 'cpsmartcrm'); ?></option>
                    <option value="Sonstiges"><?php _e('Sonstiges', 'cpsmartcrm'); ?></option>
                </select>
            </div>
            
            <div class="crm-form-group">
                <label for="fk_kunde"><?php _e('Kunde (optional)', 'cpsmartcrm'); ?></label>
                <select id="fk_kunde" name="fk_kunde">
                    <option value=""><?php _e('-- Keine Zuordnung --', 'cpsmartcrm'); ?></option>
                    <?php
                    $kunden = $wpdb->get_results("SELECT id, Nome, Cognome FROM " . WPsCRM_TABLE . "kunde ORDER BY Nome LIMIT 100");
                    foreach ($kunden as $kunde) {
                        echo '<option value="' . $kunde->id . '">' . esc_html($kunde->Nome . ' ' . $kunde->Cognome) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="crm-form-group crm-form-group-full">
                <label for="beschreibung"><?php _e('Beschreibung', 'cpsmartcrm'); ?></label>
                <textarea id="beschreibung" name="beschreibung" placeholder="<?php _e('Kurzbeschreibung des Belegs...', 'cpsmartcrm'); ?>"></textarea>
            </div>
        </form>
    </div>
    
    <!-- TRANSACTION SECTION - Separate Box -->
    <div style="background: #f5f9fc; border: 1px solid #b3d9ef; border-radius: 5px; padding: 20px; margin-bottom: 30px;">
        <h3 style="margin: 0 0 5px 0; color: #0073aa; font-size: 16px;">
            <?php _e('Ausgabe / Einnahme zuordnen', 'cpsmartcrm'); ?>
        </h3>
        <p style="font-size: 12px; color: #666; margin: 0 0 20px 0;">
            <?php _e('Optional: Erstelle oder verknüpfe eine Ausgabe oder Einnahme zu diesem Beleg', 'cpsmartcrm'); ?>
        </p>
        
        <form method="post" enctype="multipart/form-data" class="crm-upload-form">
            <?php wp_nonce_field('belege_upload'); ?>
            
            <!-- Hidden fields for beleg info -->
            <input type="hidden" name="upload_beleg" value="1">
            <input type="hidden" id="hidden_beleg_datei" name="beleg_datei">
            <input type="hidden" name="beleg_typ" value="Beleg">
            <input type="hidden" id="hidden_beleg_datum" name="beleg_datum" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="kategorie" value="Sonstiges">
            
            <!-- Transaction Type - ALWAYS VISIBLE -->
            <div style="margin-bottom: 20px;">
                <div class="crm-form-group">
                    <label for="transaction_type" style="font-weight: 600; color: #333;">
                        <?php _e('Transaktionstyp *', 'cpsmartcrm'); ?>
                    </label>
                    <select id="transaction_type" name="transaction_type" onchange="toggleTransactionFields()" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; width: 100%;">
                        <option value="none">— <?php _e('Keine Zuordnung', 'cpsmartcrm'); ?> —</option>
                        <option value="expense"><?php _e('Ausgabe', 'cpsmartcrm'); ?></option>
                        <option value="income"><?php _e('Einnahme', 'cpsmartcrm'); ?></option>
                    </select>
                </div>
            </div>
            
            <!-- CONDITIONAL FIELDS - Hidden by default -->
            <div id="trans_conditional_fields" style="display: none;">
                <!-- Row 1: Kategorie, Betrag, USt-Satz -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="crm-form-group">
                        <label for="trans_kategorie" style="font-weight: 600; color: #333;">
                            <?php _e('Kategorie *', 'cpsmartcrm'); ?>
                        </label>
                        <select id="trans_kategorie" name="trans_kategorie" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="">— <?php _e('Wählen', 'cpsmartcrm'); ?> —</option>
                            <optgroup label="<?php _e('Ausgaben', 'cpsmartcrm'); ?>">
                                <option value="Material"><?php _e('Material', 'cpsmartcrm'); ?></option>
                                <option value="Software"><?php _e('Software', 'cpsmartcrm'); ?></option>
                                <option value="Reiskosten"><?php _e('Reiskosten', 'cpsmartcrm'); ?></option>
                                <option value="Büromaterial"><?php _e('Büromaterial', 'cpsmartcrm'); ?></option>
                                <option value="Marketing"><?php _e('Marketing', 'cpsmartcrm'); ?></option>
                                <option value="Sonstiges"><?php _e('Sonstiges', 'cpsmartcrm'); ?></option>
                            </optgroup>
                            <optgroup label="<?php _e('Einnahmen', 'cpsmartcrm'); ?>">
                                <option value="Dienstleistung"><?php _e('Dienstleistung', 'cpsmartcrm'); ?></option>
                                <option value="Produktverkauf"><?php _e('Produktverkauf', 'cpsmartcrm'); ?></option>
                                <option value="Wartung"><?php _e('Wartung', 'cpsmartcrm'); ?></option>
                                <option value="Abo"><?php _e('Abo', 'cpsmartcrm'); ?></option>
                                <option value="Sonstiges"><?php _e('Sonstiges', 'cpsmartcrm'); ?></option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="crm-form-group">
                        <label for="trans_amount" style="font-weight: 600; color: #333;">
                            <?php _e('Betrag (netto) *', 'cpsmartcrm'); ?>
                        </label>
                        <input type="number" id="trans_amount" name="trans_amount" step="0.01" min="0" placeholder="0,00" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="crm-form-group">
                        <label for="trans_tax_rate" style="font-weight: 600; color: #333;">
                            <?php _e('USt.-Satz', 'cpsmartcrm'); ?>
                        </label>
                        <select id="trans_tax_rate" name="trans_tax_rate" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="0">0 % (<?php _e('befreit', 'cpsmartcrm'); ?>)</option>
                            <option value="7">7 % (<?php _e('Ermäßigt', 'cpsmartcrm'); ?>)</option>
                            <option value="19" selected>19 % (<?php _e('Regulär', 'cpsmartcrm'); ?>)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Row 2: Beschreibung -->
                <div style="margin-bottom: 20px;">
                    <div class="crm-form-group">
                        <label for="trans_description" style="font-weight: 600; color: #333;">
                            <?php _e('Beschreibung / Referenz', 'cpsmartcrm'); ?>
                        </label>
                        <input type="text" id="trans_description" name="trans_description" placeholder="<?php _e('z.B. Rechnungsnummer, Beleg-ID...', 'cpsmartcrm'); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; width: 100%;">
                    </div>
                </div>
                
                <!-- Row 3: Notizen und Tags -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="crm-form-group">
                        <label for="notizen" style="font-weight: 600; color: #333;">
                            <?php _e('Notizen', 'cpsmartcrm'); ?>
                        </label>
                        <textarea id="notizen" name="notizen" placeholder="<?php _e('Interne Notizen...', 'cpsmartcrm'); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; min-height: 60px;"></textarea>
                    </div>
                    
                    <div class="crm-form-group">
                        <label for="tags" style="font-weight: 600; color: #333;">
                            <?php _e('Tags (komma-getrennt)', 'cpsmartcrm'); ?>
                        </label>
                        <input type="text" id="tags" name="tags" placeholder="<?php _e('z.B. Steuer, Abzugsfähig', 'cpsmartcrm'); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Original Upload Form - Redesigned -->
    <div class="crm-upload-section">
        <h3><?php _e('📤 Neuen Beleg hochladen', 'cpsmartcrm'); ?></h3>
        <form method="post" enctype="multipart/form-data" class="crm-upload-form">
            <?php wp_nonce_field('belege_upload'); ?>
            
            <div class="crm-form-group">
                <label for="beleg_datei"><?php _e('Datei', 'cpsmartcrm'); ?> *</label>
                <input type="file" id="beleg_datei" name="beleg_datei" required 
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.zip">
                <small><?php _e('Max. 10MB | PDF, JPG, PNG, DOC, XLS, ZIP', 'cpsmartcrm'); ?></small>
            </div>
            
            <div class="crm-form-group">
                <label for="beleg_typ"><?php _e('Belegtyp', 'cpsmartcrm'); ?></label>
                <select id="beleg_typ" name="beleg_typ">
                    <option value="Rechnung"><?php _e('Rechnung', 'cpsmartcrm'); ?></option>
                    <option value="Quittung"><?php _e('Quittung', 'cpsmartcrm'); ?></option>
                    <option value="Beleg"><?php _e('Beleg', 'cpsmartcrm'); ?></option>
                    <option value="Zahlungsnachweis"><?php _e('Zahlungsnachweis', 'cpsmartcrm'); ?></option>
                    <option value="Rechnung Lieferant"><?php _e('Lieferantenrechnung', 'cpsmartcrm'); ?></option>
                    <option value="Kostenvoranschlag"><?php _e('Kostenvoranschlag', 'cpsmartcrm'); ?></option>
                </select>
            </div>
            
            <div class="crm-form-group">
                <label for="beleg_datum"><?php _e('Belegdatum', 'cpsmartcrm'); ?></label>
                <input type="date" id="beleg_datum" name="beleg_datum" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="crm-form-group">
                <label for="kategorie"><?php _e('Kategorie', 'cpsmartcrm'); ?> *</label>
                <select id="kategorie" name="kategorie" required>
                    <option value=""><?php _e('-- Wählen --', 'cpsmartcrm'); ?></option>
                    <option value="Betriebskosten"><?php _e('Betriebskosten', 'cpsmartcrm'); ?></option>
                    <option value="Material"><?php _e('Material', 'cpsmartcrm'); ?></option>
                    <option value="Dienste"><?php _e('Dienste', 'cpsmartcrm'); ?></option>
                    <option value="Reiskosten"><?php _e('Reiskosten', 'cpsmartcrm'); ?></option>
                    <option value="Versicherung"><?php _e('Versicherung', 'cpsmartcrm'); ?></option>
                    <option value="Strom/Gas/Wasser"><?php _e('Strom/Gas/Wasser', 'cpsmartcrm'); ?></option>
                    <option value="Telefon/Internet"><?php _e('Telefon/Internet', 'cpsmartcrm'); ?></option>
                    <option value="Miete"><?php _e('Miete', 'cpsmartcrm'); ?></option>
                    <option value="Sonstiges"><?php _e('Sonstiges', 'cpsmartcrm'); ?></option>
                </select>
            </div>
            
            <div class="crm-form-group">
                <label for="fk_kunde"><?php _e('Kunde (optional)', 'cpsmartcrm'); ?></label>
                <select id="fk_kunde" name="fk_kunde">
                    <option value=""><?php _e('-- Keine Zuordnung --', 'cpsmartcrm'); ?></option>
                    <?php
                    $kunden = $wpdb->get_results("SELECT id, Nome, Cognome FROM " . WPsCRM_TABLE . "kunde ORDER BY Nome LIMIT 100");
                    foreach ($kunden as $kunde) {
                        echo '<option value="' . $kunde->id . '">' . esc_html($kunde->Nome . ' ' . $kunde->Cognome) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="crm-form-group crm-form-group-full">
                <label for="beschreibung"><?php _e('Beschreibung', 'cpsmartcrm'); ?></label>
                <textarea id="beschreibung" name="beschreibung" placeholder="<?php _e('Kurzbeschreibung des Belegs...', 'cpsmartcrm'); ?>"></textarea>
            </div>
            
            <div class="crm-form-group crm-form-group-full">
                <label for="notizen"><?php _e('Notizen', 'cpsmartcrm'); ?></label>
                <textarea id="notizen" name="notizen" placeholder="<?php _e('Interne Notizen...', 'cpsmartcrm'); ?>"></textarea>
            </div>
            
            <div class="crm-form-group crm-form-group-full">
                <label for="tags"><?php _e('Tags (komma-getrennt)', 'cpsmartcrm'); ?></label>
                <input type="text" id="tags" name="tags" placeholder="<?php _e('z.B. Steuer, Abzugsfähig, Prüfung ausstehend', 'cpsmartcrm'); ?>">
            </div>
            
            <div class="crm-submit-section">
                <button type="submit" name="upload_beleg" class="crm-btn crm-btn-primary">
                    <?php _e('✓ Beleg speichern', 'cpsmartcrm'); ?>
                </button>
            </div>
        </form>
    </div>
    <div class="crm-belege-filters">
        <form method="get" style="display: contents;">
            <input type="hidden" name="page" value="smart-crm">
            <input type="hidden" name="p" value="buchhaltung/index.php">
            <input type="hidden" name="accounting_tab" value="belege">
            
            <input type="search" name="search" placeholder="<?php _e('Belegnummer, Beschreibung...', 'cpsmartcrm'); ?>" 
                   value="<?php echo esc_attr($search); ?>">
            
            <select name="beleg_typ">
                <option value=""><?php _e('Alle Belegtypen', 'cpsmartcrm'); ?></option>
                <option value="Rechnung" <?php selected($beleg_typ, 'Rechnung'); ?>><?php _e('Rechnung', 'cpsmartcrm'); ?></option>
                <option value="Quittung" <?php selected($beleg_typ, 'Quittung'); ?>><?php _e('Quittung', 'cpsmartcrm'); ?></option>
                <option value="Beleg" <?php selected($beleg_typ, 'Beleg'); ?>><?php _e('Beleg', 'cpsmartcrm'); ?></option>
                <option value="Zahlungsnachweis" <?php selected($beleg_typ, 'Zahlungsnachweis'); ?>><?php _e('Zahlungsnachweis', 'cpsmartcrm'); ?></option>
                <option value="Rechnung Lieferant" <?php selected($beleg_typ, 'Rechnung Lieferant'); ?>><?php _e('Lieferantenrechnung', 'cpsmartcrm'); ?></option>
            </select>
            
            <select name="kategorie">
                <option value=""><?php _e('Alle Kategorien', 'cpsmartcrm'); ?></option>
                <option value="Betriebskosten" <?php selected($kategorie, 'Betriebskosten'); ?>><?php _e('Betriebskosten', 'cpsmartcrm'); ?></option>
                <option value="Material" <?php selected($kategorie, 'Material'); ?>><?php _e('Material', 'cpsmartcrm'); ?></option>
                <option value="Dienste" <?php selected($kategorie, 'Dienste'); ?>><?php _e('Dienste', 'cpsmartcrm'); ?></option>
                <option value="Reiskosten" <?php selected($kategorie, 'Reiskosten'); ?>><?php _e('Reiskosten', 'cpsmartcrm'); ?></option>
                <option value="Versicherung" <?php selected($kategorie, 'Versicherung'); ?>><?php _e('Versicherung', 'cpsmartcrm'); ?></option>
            </select>
            
            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" 
                   placeholder="<?php _e('Von Datum', 'cpsmartcrm'); ?>">
            
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" 
                   placeholder="<?php _e('Bis Datum', 'cpsmartcrm'); ?>">
            
            <button type="submit" class="crm-btn crm-btn-primary"><?php _e('🔍 Filtern', 'cpsmartcrm'); ?></button>
            <a href="?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege" class="crm-btn crm-btn-secondary">
                <?php _e('Zurücksetzen', 'cpsmartcrm'); ?>
            </a>
        </form>
    </div>
    
    <!-- Belege List -->
    <?php
    $where = "deleted = 0";
    $where_args = array();
    
    if (!empty($search)) {
        $where .= " AND (belegnummer LIKE %s OR beschreibung LIKE %s)";
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $where_args[] = $search_term;
        $where_args[] = $search_term;
    }
    
    if (!empty($beleg_typ)) {
        $where .= " AND beleg_typ = %s";
        $where_args[] = $beleg_typ;
    }
    
    if (!empty($kategorie)) {
        $where .= " AND kategorie = %s";
        $where_args[] = $kategorie;
    }
    
    if (!empty($date_from)) {
        $where .= " AND beleg_datum >= %s";
        $where_args[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where .= " AND beleg_datum <= %s";
        $where_args[] = $date_to;
    }
    
    // Get total count
    $sql = "SELECT COUNT(*) FROM {$b_table} WHERE {$where}";
    if (!empty($where_args)) {
        $sql = $wpdb->prepare($sql, ...$where_args);
    }
    $total_items = (int)$wpdb->get_var($sql);
    $total_pages = ceil($total_items / $items_per_page);
    
    // Get belege
    $sql = "SELECT * FROM {$b_table} WHERE {$where} ORDER BY beleg_datum DESC LIMIT %d OFFSET %d";
    $sql_args = array_merge($where_args, array($items_per_page, $offset));
    $sql = $wpdb->prepare($sql, ...$sql_args);
    $belege = $wpdb->get_results($sql);
    
    if (!empty($belege)) {
    ?>
        <table class="crm-beleg-list">
            <thead>
                <tr>
                    <th><?php _e('Belegnummer', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Datum', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Typ', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Kategorie', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Beschreibung', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Datei', 'cpsmartcrm'); ?></th>
                    <th><?php _e('Aktionen', 'cpsmartcrm'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($belege as $beleg): ?>
                <tr>
                    <td><strong><?php echo esc_html($beleg->belegnummer); ?></strong></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($beleg->beleg_datum))); ?></td>
                    <td><span class="crm-badge crm-badge-typ"><?php echo esc_html($beleg->beleg_typ); ?></span></td>
                    <td><span class="crm-badge crm-badge-kategorie"><?php echo esc_html($beleg->kategorie); ?></span></td>
                    <td><?php echo esc_html(substr($beleg->beschreibung, 0, 50)); ?><?php echo strlen($beleg->beschreibung) > 50 ? '...' : ''; ?></td>
                    <td><small><?php echo esc_html($beleg->filename); ?></small></td>
                    <td class="crm-beleg-actions">
                        <a href="?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege&action=download&beleg_id=<?php echo $beleg->id; ?>" 
                           class="crm-btn" title="<?php _e('Herunterladen', 'cpsmartcrm'); ?>">
                            ⬇️
                        </a>
                        <a href="?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege&action=delete&beleg_id=<?php echo $beleg->id; ?>&<?php echo wp_create_nonce('delete_beleg_' . $beleg->id); ?>" 
                           class="crm-btn crm-beleg-delete" onclick="return confirm('<?php _e('Beleg wirklich löschen?', 'cpsmartcrm'); ?>')" 
                           title="<?php _e('Löschen', 'cpsmartcrm'); ?>">
                            🗑️
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php
            $base = add_query_arg(array(
                'paged' => '%#%',
                'search' => $search,
                'beleg_typ' => $beleg_typ,
                'kategorie' => $kategorie,
                'date_from' => $date_from,
                'date_to' => $date_to,
            ), '?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege');
            
            $args = array(
                'base' => $base,
                'format' => '&paged=%#%',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page,
            );
            
            echo paginate_links($args);
            ?>
        </div>
        <?php endif; ?>
    <?php
    } else {
    ?>
        <div class="crm-no-belege">
            <p><?php _e('Keine Belege gefunden.', 'cpsmartcrm'); ?></p>
        </div>
    <?php
    }
    ?>
</div>
