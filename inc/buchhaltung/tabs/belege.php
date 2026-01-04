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

// Lade Kategorien aus Einstellungen
$acc_options = get_option('CRM_accounting_settings', array());
$income_categories_raw = (isset($acc_options['income_categories']) && is_array($acc_options['income_categories'])) ? $acc_options['income_categories'] : array();
$expense_categories_raw = (isset($acc_options['expense_categories']) && is_array($acc_options['expense_categories'])) ? $acc_options['expense_categories'] : array();

// Prüfe ob Kleinunternehmer-Regelung aktiv ist
$doc_options = get_option('CRM_documents_settings', array());
$default_vat = isset($doc_options['default_vat']) ? (int)$doc_options['default_vat'] : 19;

// Bereinige und dedupliziere Kategorien
$income_categories = array();
foreach ($income_categories_raw as $cat) {
    if (!empty($cat)) {
        $income_categories[] = sanitize_text_field($cat);
    }
}
$income_categories = array_values(array_unique($income_categories));

$expense_categories = array();
foreach ($expense_categories_raw as $cat) {
    if (!empty($cat)) {
        $expense_categories[] = sanitize_text_field($cat);
    }
}
$expense_categories = array_values(array_unique($expense_categories));

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
    .belege-upload-container {
        background: #fff;
        padding: 30px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-bottom: 30px;
    }
    
    .belege-section-header {
        margin: 0 0 25px 0;
        padding-bottom: 15px;
        border-bottom: 2px solid #0073aa;
        font-size: 18px;
        color: #333;
        font-weight: 600;
    }
    
    .belege-form-section {
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 25px;
        border-left: 5px solid #0073aa;
    }
    
    .belege-form-section.basic {
        background: #f9f9f9;
    }
    
    .belege-form-section.transaction {
        background: #f0f8ff;
        border-left-color: #2196f3;
    }
    
    .belege-section-title {
        margin: 0 0 20px 0;
        font-size: 15px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #333;
    }
    
    .belege-form-section.transaction .belege-section-title {
        color: #1976d2;
    }
    
    .belege-form-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .belege-form-row.full {
        grid-template-columns: 1fr;
    }
    
    .belege-form-group {
        display: flex;
        flex-direction: column;
    }
    
    .belege-form-group label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .belege-form-group .required {
        color: #d32f2f;
    }
    
    .belege-form-group input,
    .belege-form-group select,
    .belege-form-group textarea {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    
    .belege-form-group input:focus,
    .belege-form-group select:focus,
    .belege-form-group textarea:focus {
        outline: none;
        border-color: #0073aa;
        box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
    }
    
    .belege-form-group textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .belege-form-group small {
        display: block;
        margin-top: 6px;
        color: #666;
        font-size: 13px;
    }
    
    .belege-conditional-fields {
        display: none;
        animation: fadeIn 0.3s ease-in;
    }
    
    .belege-conditional-fields.show {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .belege-submit-section {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }
    
    .belege-submit-btn {
        padding: 12px 30px;
        font-size: 15px;
        font-weight: 600;
        background: #0073aa;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .belege-submit-btn:hover {
        background: #005a87;
    }
</style>

<div class="belege-upload-container">
    <h3 class="belege-section-header"><?php _e('Neuen Beleg hochladen', 'cpsmartcrm'); ?></h3>
    
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('belege_upload'); ?>
        <input type="hidden" name="upload_beleg" value="1">
        
        <!-- SCHRITT 1: BELEGINFORMATIONEN -->
        <div class="belege-form-section basic">
            <h4 class="belege-section-title"><?php _e('Schritt 1: Beleginformationen', 'cpsmartcrm'); ?></h4>
            
            <!-- Datei-Upload -->
            <div class="belege-form-group" style="margin-bottom: 20px;">
                <label for="beleg_datei">
                    <?php _e('Datei', 'cpsmartcrm'); ?> <span class="required">*</span>
                </label>
                <input type="file" id="beleg_datei" name="beleg_datei" required 
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.zip">
                <small><?php _e('Max. 10MB | Akzeptierte Formate: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, ZIP', 'cpsmartcrm'); ?></small>
            </div>
            
            <!-- Belegtyp, Datum, Kunde -->
            <div class="belege-form-row">
                <div class="belege-form-group">
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
                
                <div class="belege-form-group">
                    <label for="beleg_datum"><?php _e('Belegdatum', 'cpsmartcrm'); ?></label>
                    <input type="date" id="beleg_datum" name="beleg_datum" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="belege-form-group">
                    <label for="fk_kunde"><?php _e('Kunde (optional)', 'cpsmartcrm'); ?></label>
                    <select id="fk_kunde" name="fk_kunde">
                        <option value=""><?php _e('-- Keine Zuordnung --', 'cpsmartcrm'); ?></option>
                        <?php
                        $kunden = $wpdb->get_results("SELECT ID_kunde, name, nachname FROM " . WPsCRM_TABLE . "kunde WHERE eliminato = 0 ORDER BY name LIMIT 100");
                        foreach ($kunden as $kunde) {
                            echo '<option value="' . $kunde->ID_kunde . '">' . esc_html($kunde->name . ' ' . $kunde->nachname) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <!-- Beschreibung -->
            <div class="belege-form-row full">
                <div class="belege-form-group">
                    <label for="beschreibung"><?php _e('Beschreibung', 'cpsmartcrm'); ?></label>
                    <textarea id="beschreibung" name="beschreibung" 
                              placeholder="<?php _e('z.B. Rechnungsbeschreibung, Notizen zum Beleg...', 'cpsmartcrm'); ?>"></textarea>
                </div>
            </div>
        </div>
        
        <!-- SCHRITT 2: OPTIONAL TRANSAKTION -->
        <div class="belege-form-section transaction">
            <h4 class="belege-section-title"><?php _e('Schritt 2 (Optional): Transaktion zuordnen', 'cpsmartcrm'); ?></h4>
            
            <div class="belege-form-group" style="margin-bottom: 20px;">
                <label for="transaction_type">
                    <?php _e('Soll diesem Beleg eine Ausgabe oder Einnahme zugeordnet werden?', 'cpsmartcrm'); ?>
                </label>
                <select id="transaction_type" name="transaction_type" onchange="toggleTransactionFields()">
                    <option value="none">— <?php _e('Nein, keine Zuordnung', 'cpsmartcrm'); ?> —</option>
                    <option value="expense"><?php _e('Ja, als Ausgabe', 'cpsmartcrm'); ?></option>
                    <option value="income"><?php _e('Ja, als Einnahme', 'cpsmartcrm'); ?></option>
                </select>
            </div>
            
            <!-- Bedingte Felder -->
            <div id="trans_conditional_fields" class="belege-conditional-fields">
                <!-- Kategorie, Betrag, USt-Satz -->
                <div class="belege-form-row">
                    <div class="belege-form-group">
                        <label for="trans_kategorie">
                            <?php _e('Kategorie', 'cpsmartcrm'); ?> <span class="required">*</span>
                        </label>
                        <select id="trans_kategorie" name="trans_kategorie">
                            <option value="">— <?php _e('Wählen', 'cpsmartcrm'); ?> —</option>
                            <?php if (!empty($expense_categories)): ?>
                                <optgroup label="<?php _e('Ausgaben', 'cpsmartcrm'); ?>">
                                    <?php foreach ($expense_categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($income_categories)): ?>
                                <optgroup label="<?php _e('Einnahmen', 'cpsmartcrm'); ?>">
                                    <?php foreach ($income_categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="belege-form-group">
                        <label for="trans_amount">
                            <?php _e('Betrag (netto)', 'cpsmartcrm'); ?> <span class="required">*</span>
                        </label>
                        <input type="number" id="trans_amount" name="trans_amount" step="0.01" min="0" placeholder="0,00">
                    </div>
                    
                    <div class="belege-form-group">
                        <label for="trans_tax_rate"><?php _e('USt.-Satz', 'cpsmartcrm'); ?></label>
                        <select id="trans_tax_rate" name="trans_tax_rate">
                            <option value="0" <?php selected($default_vat, 0); ?>>0 % (<?php _e('befreit', 'cpsmartcrm'); ?>)</option>
                            <option value="7" <?php selected($default_vat, 7); ?>>7 % (<?php _e('Ermäßigt', 'cpsmartcrm'); ?>)</option>
                            <option value="19" <?php selected($default_vat, 19); ?>>19 % (<?php _e('Regulär', 'cpsmartcrm'); ?>)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Beschreibung / Referenz -->
                <div class="belege-form-row full">
                    <div class="belege-form-group">
                        <label for="trans_description"><?php _e('Beschreibung / Referenz', 'cpsmartcrm'); ?></label>
                        <input type="text" id="trans_description" name="trans_description" 
                               placeholder="<?php _e('z.B. Rechnungsnummer, Beleg-ID...', 'cpsmartcrm'); ?>">
                    </div>
                </div>
                
                <!-- Notizen & Tags -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="belege-form-group">
                        <label for="notizen"><?php _e('Notizen', 'cpsmartcrm'); ?></label>
                        <textarea id="notizen" name="notizen" 
                                  placeholder="<?php _e('Interne Notizen...', 'cpsmartcrm'); ?>"></textarea>
                    </div>
                    
                    <div class="belege-form-group">
                        <label for="tags"><?php _e('Tags (komma-getrennt)', 'cpsmartcrm'); ?></label>
                        <textarea id="tags" name="tags" 
                                  placeholder="<?php _e('z.B. Steuer, Abzugsfähig, Prüfung', 'cpsmartcrm'); ?>"></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SUBMIT BUTTON -->
        <div class="belege-submit-section">
            <button type="submit" class="belege-submit-btn">
                💾 <?php _e('Beleg speichern', 'cpsmartcrm'); ?>
            </button>
        </div>
    </form>
</div>

<script>
function toggleTransactionFields() {
    const transType = document.getElementById('transaction_type').value;
    const condFields = document.getElementById('trans_conditional_fields');
    const kategoriSelect = document.getElementById('trans_kategorie');
    
    if (transType !== 'none') {
        condFields.classList.add('show');
        
        // Filter kategorie options based on transaction type
        const options = kategoriSelect.querySelectorAll('option, optgroup');
        options.forEach(el => {
            if (el.tagName === 'OPTGROUP') {
                if (transType === 'expense') {
                    el.style.display = el.label.includes('Ausgab') ? 'block' : 'none';
                } else if (transType === 'income') {
                    el.style.display = el.label.includes('Einnahm') ? 'block' : 'none';
                }
            }
        });
        
        kategoriSelect.value = '';
    } else {
        condFields.classList.remove('show');
    }
}
</script>

<?php
// Statistiken abrufen
$stats = WPsCRM_get_belege_statistics($date_from ?: null, $date_to ?: null);
$total_belege = $wpdb->get_var("SELECT COUNT(*) FROM {$b_table} WHERE deleted = 0");
$total_size = $wpdb->get_var("SELECT SUM(file_size) FROM {$b_table} WHERE deleted = 0");
?>

<!-- STATISTIKEN -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0;">
    <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; border-left: 4px solid #0073aa;">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 10px;">
            <?php _e('Belege insgesamt', 'cpsmartcrm'); ?>
        </div>
        <div style="font-size: 28px; font-weight: 600; color: #0073aa;">
            <?php echo $total_belege; ?>
        </div>
    </div>
    <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; border-left: 4px solid #2196f3;">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 10px;">
            <?php _e('Kategorien', 'cpsmartcrm'); ?>
        </div>
        <div style="font-size: 28px; font-weight: 600; color: #2196f3;">
            <?php echo count($stats); ?>
        </div>
    </div>
    <div style="background: #f5f5f5; padding: 20px; border-radius: 5px; border-left: 4px solid #4caf50;">
        <div style="font-size: 12px; color: #666; text-transform: uppercase; font-weight: 600; margin-bottom: 10px;">
            <?php _e('Speicherplatz', 'cpsmartcrm'); ?>
        </div>
        <div style="font-size: 28px; font-weight: 600; color: #4caf50;">
            <?php echo size_format($total_size); ?>
        </div>
    </div>
</div>

<!-- FILTER SECTION -->
<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
    <form method="get" style="display: contents;">
        <input type="hidden" name="page" value="smart-crm">
        <input type="hidden" name="p" value="buchhaltung/index.php">
        <input type="hidden" name="accounting_tab" value="belege">
        
        <input type="search" name="search" placeholder="<?php _e('Belegnummer, Beschreibung...', 'cpsmartcrm'); ?>" 
               value="<?php echo esc_attr($search); ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px;">
        
        <select name="beleg_typ" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px;">
            <option value=""><?php _e('Alle Belegtypen', 'cpsmartcrm'); ?></option>
            <option value="Rechnung" <?php selected($beleg_typ, 'Rechnung'); ?>><?php _e('Rechnung', 'cpsmartcrm'); ?></option>
            <option value="Quittung" <?php selected($beleg_typ, 'Quittung'); ?>><?php _e('Quittung', 'cpsmartcrm'); ?></option>
            <option value="Beleg" <?php selected($beleg_typ, 'Beleg'); ?>><?php _e('Beleg', 'cpsmartcrm'); ?></option>
            <option value="Zahlungsnachweis" <?php selected($beleg_typ, 'Zahlungsnachweis'); ?>><?php _e('Zahlungsnachweis', 'cpsmartcrm'); ?></option>
            <option value="Rechnung Lieferant" <?php selected($beleg_typ, 'Rechnung Lieferant'); ?>><?php _e('Lieferantenrechnung', 'cpsmartcrm'); ?></option>
        </select>
        
        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" 
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px;">
        
        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" 
               style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px;">
        
        <button type="submit" style="padding: 8px 16px; background: #0073aa; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 600;">
            🔍 <?php _e('Filtern', 'cpsmartcrm'); ?>
        </button>
        <a href="?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege" 
           style="padding: 8px 16px; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 3px; display: inline-block; font-weight: 600;">
            <?php _e('Zurücksetzen', 'cpsmartcrm'); ?>
        </a>
    </form>
</div>

<!-- BELEGE LIST -->
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
<table style="width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #ddd; border-radius: 5px; overflow: hidden;">
    <thead style="background: #f5f5f5;">
        <tr>
            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px;"><?php _e('Belegnummer', 'cpsmartcrm'); ?></th>
            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px;"><?php _e('Datum', 'cpsmartcrm'); ?></th>
            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px;"><?php _e('Typ', 'cpsmartcrm'); ?></th>
            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px;"><?php _e('Beschreibung', 'cpsmartcrm'); ?></th>
            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px;"><?php _e('Datei', 'cpsmartcrm'); ?></th>
            <th style="padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 13px;"><?php _e('Aktionen', 'cpsmartcrm'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($belege as $beleg): ?>
        <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 12px; font-size: 13px;"><strong><?php echo esc_html($beleg->belegnummer); ?></strong></td>
            <td style="padding: 12px; font-size: 13px;"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($beleg->beleg_datum))); ?></td>
            <td style="padding: 12px; font-size: 13px;">
                <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; background: #e3f2fd; color: #0d47a1; font-weight: 600; font-size: 11px;">
                    <?php echo esc_html($beleg->beleg_typ); ?>
                </span>
            </td>
            <td style="padding: 12px; font-size: 13px; max-width: 300px;">
                <?php echo esc_html(substr($beleg->beschreibung, 0, 50)); ?><?php echo strlen($beleg->beschreibung) > 50 ? '...' : ''; ?>
            </td>
            <td style="padding: 12px; font-size: 13px;"><small><?php echo esc_html($beleg->filename); ?></small></td>
            <td style="padding: 12px; font-size: 13px;">
                <a href="?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege&action=download&beleg_id=<?php echo $beleg->id; ?>" 
                   style="padding: 4px 8px; background: #f0f0f0; color: #0073aa; text-decoration: none; border-radius: 3px; font-size: 12px; margin-right: 5px; display: inline-block;">
                    ⬇️ <?php _e('Download', 'cpsmartcrm'); ?>
                </a>
                <a href="<?php echo wp_nonce_url('?page=smart-crm&p=buchhaltung/index.php&accounting_tab=belege&action=delete&beleg_id=' . $beleg->id, 'delete_beleg_' . $beleg->id); ?>" 
                   onclick="return confirm('<?php _e('Beleg wirklich löschen?', 'cpsmartcrm'); ?>')" 
                   style="padding: 4px 8px; background: #f8d7da; color: #dc3545; text-decoration: none; border-radius: 3px; font-size: 12px; display: inline-block;">
                    🗑️ <?php _e('Löschen', 'cpsmartcrm'); ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($total_pages > 1): ?>
<div style="margin-top: 30px; text-align: center;">
    <?php
    $base = add_query_arg(array(
        'paged' => '%#%',
        'search' => $search,
        'beleg_typ' => $beleg_typ,
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
<div style="text-align: center; padding: 40px 20px; color: #999;">
    <p style="font-size: 16px;">📋 <?php _e('Keine Belege gefunden.', 'cpsmartcrm'); ?></p>
</div>
<?php
}
?>
