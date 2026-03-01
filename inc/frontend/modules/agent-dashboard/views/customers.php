<?php
/**
 * Kundenverwaltung - 1:1 CRM-Schnittstelle im Frontend-Dashboard
 * 
 * Tabs:
 * 1. Meine Kunden - Suche & Liste (nur eigene Kunden WHERE agente = user_id)
 * 2. Neuer Kunde / Bearbeiten - VOLLSTÄNDIGES Formular mit ALLEN CRM-Feldern
 * 3. Dokumente - Angebote, Rechnungen, Proforma je Kunde
 * 4. Kontakte - Ansprechpartner je Kunde
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$current_user_id = get_current_user_id();
$nonce_customer = wp_create_nonce( 'crm_customer_management' );

// Lade Taxonom-Werte für Select-Felder
$categories = WPsCRM_get_customer_field_values('categoria');
$interessen = WPsCRM_get_customer_field_values('interessi');
$provenienz = WPsCRM_get_customer_field_values('provenienza');
?>

<div class="crm-dashboard-card crm-customers-module">
    <div class="crm-card-header">
        <h3>
            <i class="dashicons dashicons-businessperson"></i>
            <?php _e('Kundenverwaltung', 'cpsmartcrm'); ?>
        </h3>
    </div>
    <div class="crm-card-body">
        <!-- Tab Navigation -->
        <div class="crm-customers-tabs">
            <div class="crm-customers-tabs-nav">
                <button class="crm-customers-tab-btn active" data-tab="customers-list">
                    <i class="dashicons dashicons-groups"></i>
                    <?php _e('Meine Kunden', 'cpsmartcrm'); ?>
                </button>
                <button class="crm-customers-tab-btn" data-tab="customers-form" style="display:none;">
                    <i class="dashicons dashicons-edit"></i>
                    <span id="customer-form-title"><?php _e('Neuer Kunde', 'cpsmartcrm'); ?></span>
                </button>
                <button class="crm-customers-tab-btn" data-tab="customers-docs" style="display:none;">
                    <i class="dashicons dashicons-media-document"></i>
                    <?php _e('Dokumente', 'cpsmartcrm'); ?>
                    <span class="crm-badge" id="customer-docs-badge"></span>
                </button>
                <button class="crm-customers-tab-btn" data-tab="customers-contacts" style="display:none;">
                    <i class="dashicons dashicons-id"></i>
                    <?php _e('Kontakte', 'cpsmartcrm'); ?>
                    <span class="crm-badge" id="customer-contacts-badge"></span>
                </button>
            </div>

            <!-- ======================= TAB 1: KUNDENLISTE ======================= -->
            <div class="crm-customers-tab-content active" id="customers-list">
                <div class="crm-search-box">
                    <input type="text" id="customer-search-input" class="crm-input" placeholder="<?php _e('Kunde suchen (Name, Firma, Email, Telefon)...', 'cpsmartcrm'); ?>">
                    <button type="button" class="crm-btn crm-btn-primary" id="btn-search-customers">
                        <i class="dashicons dashicons-search"></i>
                        <?php _e('Suchen', 'cpsmartcrm'); ?>
                    </button>
                    <button type="button" class="crm-btn crm-btn-success" id="btn-add-new-customer">
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('Neuer Kunde', 'cpsmartcrm'); ?>
                    </button>
                </div>
                
                <div id="customers-list-container">
                    <div class="crm-loader">
                        <i class="dashicons dashicons-update-alt spin"></i>
                        <?php _e('Lade Kundenliste...', 'cpsmartcrm'); ?>
                    </div>
                </div>
            </div>

            <!-- ======================= TAB 2: KUNDENFORMULAR (1:1 Backend CRM) ======================= -->
            <div class="crm-customers-tab-content" id="customers-form">
                <form id="form-customer-save" class="crm-customer-form">
                    <input type="hidden" name="ID" id="customer-id-hidden" value="0">
                    <input type="hidden" name="action" value="crm_frontend_save_customer">
                    <input type="hidden" name="security" value="<?php echo $nonce_customer; ?>">
                    
                    <!-- SEKTION 1: Grunddaten -->
                    <div class="crm-form-section">
                        <h4><?php _e('Grunddaten', 'cpsmartcrm'); ?></h4>
                        
                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Datum', 'cpsmartcrm'); ?></label>
                                <input type="text" name="einstiegsdatum" id="einstiegsdatum" class="crm-input crm-datepicker" value="<?php echo date('d.m.Y'); ?>">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Land', 'cpsmartcrm'); ?> <span class="required">*</span></label>
                                <select name="nation" id="nation" class="crm-select" required>
                                    <?php echo WPsCRM_get_countries('DE'); ?>
                                </select>
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Kundentyp', 'cpsmartcrm'); ?></label>
                                <div class="crm-radio-group">
                                    <label><input type="radio" name="tipo_cliente" value="1" checked> <?php _e('Privat', 'cpsmartcrm'); ?></label>
                                    <label><input type="radio" name="tipo_cliente" value="2"> <?php _e('Business', 'cpsmartcrm'); ?></label>
                                </div>
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Rechnungspflichtig?', 'cpsmartcrm'); ?></label>
                                <div class="crm-radio-group">
                                    <label><input type="radio" name="abrechnungsmodus" value="1"> <?php _e('Ja', 'cpsmartcrm'); ?></label>
                                    <label><input type="radio" name="abrechnungsmodus" value="0" checked> <?php _e('Nein', 'cpsmartcrm'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKTION 2: Firmen-/Personendaten -->
                    <div class="crm-form-section">
                        <h4><?php _e('Firmen- / Personendaten', 'cpsmartcrm'); ?></h4>
                        
                        <div class="crm-form-row">
                            <div class="crm-form-group crm-form-group-full">
                                <label><?php _e('Firmenname', 'cpsmartcrm'); ?></label>
                                <input type="text" name="firmenname" id="firmenname" class="crm-input" maxlength="250" placeholder="z.B. Müller GmbH">
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Vorname', 'cpsmartcrm'); ?></label>
                                <input type="text" name="name" id="name" class="crm-input" maxlength="100">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Nachname', 'cpsmartcrm'); ?></label>
                                <input type="text" name="nachname" id="nachname" class="crm-input" maxlength="100">
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Steuer-Code', 'cpsmartcrm'); ?></label>
                                <input type="text" name="cod_fis" id="cod_fis" class="crm-input" maxlength="30">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Umsatzsteuer-ID', 'cpsmartcrm'); ?></label>
                                <input type="text" name="p_iva" id="p_iva" class="crm-input" maxlength="30">
                            </div>
                        </div>
                    </div>

                    <!-- SEKTION 3: Kontaktdaten -->
                    <div class="crm-form-section">
                        <h4><?php _e('Kontaktdaten', 'cpsmartcrm'); ?></h4>
                        
                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Email', 'cpsmartcrm'); ?> <span class="required">*</span></label>
                                <input type="email" name="email" id="email" class="crm-input" maxlength="50" required>
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Telefon', 'cpsmartcrm'); ?></label>
                                <input type="text" name="telefono1" id="telefono1" class="crm-input" maxlength="50" placeholder="+49 123 456789">
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Mobil', 'cpsmartcrm'); ?></label>
                                <input type="text" name="telefono2" id="telefono2" class="crm-input" maxlength="50">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Fax', 'cpsmartcrm'); ?></label>
                                <input type="text" name="fax" id="fax" class="crm-input" maxlength="50">
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Webseite', 'cpsmartcrm'); ?></label>
                                <input type="text" name="sitoweb" id="sitoweb" class="crm-input" maxlength="100" placeholder="https://www.beispiel.de">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Skype', 'cpsmartcrm'); ?></label>
                                <input type="text" name="skype" id="skype" class="crm-input" maxlength="100">
                            </div>
                        </div>
                    </div>

                    <!-- SEKTION 4: Adresse -->
                    <div class="crm-form-section">
                        <h4><?php _e('Adresse', 'cpsmartcrm'); ?></h4>
                        
                        <div class="crm-form-row">
                            <div class="crm-form-group crm-form-group-full">
                                <label><?php _e('Straße & Hausnummer', 'cpsmartcrm'); ?></label>
                                <input type="text" name="adresse" id="adresse" class="crm-input" maxlength="200" placeholder="Musterstraße 123">
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('PLZ', 'cpsmartcrm'); ?></label>
                                <input type="text" name="cap" id="cap" class="crm-input" maxlength="10" placeholder="12345">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Stadt', 'cpsmartcrm'); ?></label>
                                <input type="text" name="standort" id="standort" class="crm-input" maxlength="55" placeholder="Berlin">
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Staat/Provinz', 'cpsmartcrm'); ?></label>
                                <input type="text" name="provinz" id="provinz" class="crm-input" maxlength="100">
                            </div>
                        </div>
                    </div>

                    <!-- SEKTION 5: Zusatzinformationen -->
                    <div class="crm-form-section">
                        <h4><?php _e('Zusatzinformationen', 'cpsmartcrm'); ?></h4>
                        
                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Geburtsort', 'cpsmartcrm'); ?></label>
                                <input type="text" name="luogo_nascita" id="luogo_nascita" class="crm-input" maxlength="200">
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Geburtsdatum', 'cpsmartcrm'); ?></label>
                                <input type="text" name="geburtsdatum" id="geburtsdatum" class="crm-input crm-datepicker" placeholder="TT.MM.JJJJ">
                            </div>
                        </div>
                    </div>

                    <!-- SEKTION 6: CRM-Kategorisierung -->
                    <div class="crm-form-section">
                        <h4><?php _e('CRM-Kategorisierung', 'cpsmartcrm'); ?></h4>
                        
                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Kategorie', 'cpsmartcrm'); ?></label>
                                <select name="customerCategory" id="customerCategory" class="crm-select-multi" multiple>
                                    <?php if (!empty($categories)): foreach($categories as $cat): ?>
                                        <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; else: ?>
                                        <option value="" disabled><?php _e('Keine Kategorien verfügbar', 'cpsmartcrm'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Interessen', 'cpsmartcrm'); ?></label>
                                <select name="customerInterests" id="customerInterests" class="crm-select-multi" multiple>
                                    <?php if (!empty($interessen)): foreach($interessen as $int): ?>
                                        <option value="<?php echo esc_attr($int->term_id); ?>"><?php echo esc_html($int->name); ?></option>
                                    <?php endforeach; else: ?>
                                        <option value="" disabled><?php _e('Keine Interessen verfügbar', 'cpsmartcrm'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="crm-form-row">
                            <div class="crm-form-group">
                                <label><?php _e('Herkunft/Quelle', 'cpsmartcrm'); ?></label>
                                <select name="customerComesfrom" id="customerComesfrom" class="crm-select-multi" multiple>
                                    <?php if (!empty($provenienz)): foreach($provenienz as $prov): ?>
                                        <option value="<?php echo esc_attr($prov->term_id); ?>"><?php echo esc_html($prov->name); ?></option>
                                    <?php endforeach; else: ?>
                                        <option value="" disabled><?php _e('Keine Quellen verfügbar', 'cpsmartcrm'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="crm-form-group">
                                <label><?php _e('Zugewiesener Agent', 'cpsmartcrm'); ?></label>
                                <select name="selectAgent" id="selectAgent" class="crm-select">
                                    <option value="<?php echo $current_user_id; ?>"><?php _e('Mir zuweisen', 'cpsmartcrm'); ?> (<?php echo wp_get_current_user()->display_name; ?>)</option>
                                </select>
                                <small style="color:#666;font-size:11px;"><?php _e('Agent wird automatisch auf aktuellen Nutzer gesetzt', 'cpsmartcrm'); ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="crm-form-actions">
                        <button type="submit" class="crm-btn crm-btn-success">
                            <i class="dashicons dashicons-saved"></i>
                            <?php _e('Kund speichern', 'cpsmartcrm'); ?>
                        </button>
                        <button type="button" class="crm-btn crm-btn-secondary" id="btn-cancel-customer-form">
                            <i class="dashicons dashicons-no-alt"></i>
                            <?php _e('Abbrechen', 'cpsmartcrm'); ?>
                        </button>
                        <button type="button" class="crm-btn crm-btn-warning" id="btn-reset-customer-form" style="margin-left:auto;">
                            <i class="dashicons dashicons-undo"></i>
                            <?php _e('Zurücksetzen', 'cpsmartcrm'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ======================= TAB 3: DOKUMENTE ======================= -->
            <div class="crm-customers-tab-content" id="customers-docs">
                <input type="hidden" id="selected-customer-id-docs" value="0">
                
                <button type="button" class="crm-btn crm-btn-secondary btn-back-to-list">
                    <i class="dashicons dashicons-arrow-left-alt"></i>
                    <?php _e('Zurück zur Liste', 'cpsmartcrm'); ?>
                </button>

                <div class="crm-docs-container" style="margin-top:20px;">
                    <h4><?php _e('Angebote', 'cpsmartcrm'); ?> (Preventivi)</h4>
                    <div id="docs-quotes-list" class="crm-docs-list"></div>

                    <h4 style="margin-top:30px;"><?php _e('Rechnungen', 'cpsmartcrm'); ?> (Fatture)</h4>
                    <div id="docs-invoices-list" class="crm-docs-list"></div>

                    <h4 style="margin-top:30px;"><?php _e('Proforma', 'cpsmartcrm'); ?></h4>
                    <div id="docs-proforma-list" class="crm-docs-list"></div>
                </div>
            </div>

            <!-- ======================= TAB 4: KONTAKTE ======================= -->
            <div class="crm-customers-tab-content" id="customers-contacts">
                <input type="hidden" id="selected-customer-id-contacts" value="0">
                
                <div style="display:flex;gap:10px;margin-bottom:20px;">
                    <button type="button" class="crm-btn crm-btn-secondary btn-back-to-list">
                        <i class="dashicons dashicons-arrow-left-alt"></i>
                        <?php _e('Zurück zur Liste', 'cpsmartcrm'); ?>
                    </button>
                    <button type="button" class="crm-btn crm-btn-primary" id="btn-add-contact">
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('Neuer Kontakt', 'cpsmartcrm'); ?>
                    </button>
                </div>
                
                <div id="contacts-list-container" class="crm-contacts-list"></div>

                <!-- Modal: Kontakt hinzufügen/bearbeiten -->
                <div id="contact-modal" class="crm-modal" style="display:none;">
                    <div class="crm-modal-content">
                        <span class="crm-modal-close">&times;</span>
                        <h3><?php _e('Kontakt hinzufügen', 'cpsmartcrm'); ?></h3>
                        
                        <form id="form-contact-save">
                            <input type="hidden" name="fk_kunde" id="contact-fk-kunde" value="0">
                            <input type="hidden" name="contact_id" id="contact-id" value="0">
                            <input type="hidden" name="action" value="crm_frontend_save_contact">
                            <input type="hidden" name="security" value="<?php echo $nonce_customer; ?>">
                            
                            <div class="crm-form-group">
                                <label><?php _e('Vorname', 'cpsmartcrm'); ?> <span class="required">*</span></label>
                                <input type="text" name="contact_name" id="contact-name" class="crm-input" required>
                            </div>
                            
                            <div class="crm-form-group">
                                <label><?php _e('Nachname', 'cpsmartcrm'); ?> <span class="required">*</span></label>
                                <input type="text" name="contact_nachname" id="contact-nachname" class="crm-input" required>
                            </div>
                            
                            <div class="crm-form-group">
                                <label><?php _e('Email', 'cpsmartcrm'); ?> <span class="required">*</span></label>
                                <input type="email" name="contact_email" id="contact-email" class="crm-input" required>
                            </div>
                            
                            <div class="crm-form-group">
                                <label><?php _e('Telefon', 'cpsmartcrm'); ?></label>
                                <input type="text" name="contact_telefono" id="contact-telefono" class="crm-input">
                            </div>
                            
                            <div class="crm-form-group">
                                <label><?php _e('Position/Funktion', 'cpsmartcrm'); ?></label>
                                <input type="text" name="contact_qualifica" id="contact-qualifica" class="crm-input" placeholder="z.B. Geschäftsführer, Einkaufsleiter">
                            </div>
                            
                            <div class="crm-form-actions">
                                <button type="submit" class="crm-btn crm-btn-success">
                                    <i class="dashicons dashicons-saved"></i>
                                    <?php _e('Speichern', 'cpsmartcrm'); ?>
                                </button>
                                <button type="button" class="crm-btn crm-btn-secondary crm-modal-close">
                                    <?php _e('Abbrechen', 'cpsmartcrm'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================= STYLES ======================= -->
<style>
.crm-customers-module .crm-customers-tabs-nav {
    display: flex;
    gap: 5px;
    border-bottom: 2px solid #ddd;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.crm-customers-module .crm-customers-tab-btn {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-bottom: none;
    padding: 10px 16px;
    cursor: pointer;
    border-radius: 5px 5px 0 0;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 6px;
}

.crm-customers-module .crm-customers-tab-btn.active {
    background: #fff;
    border-bottom: 2px solid #fff;
    margin-bottom: -2px;
    font-weight: 700;
    color: #4caf50;
}

.crm-customers-module .crm-customers-tab-btn:hover:not(.active) {
    background: #e8f5e9;
}

.crm-customers-module .crm-customers-tab-content {
    display: none;
}

.crm-customers-module .crm-customers-tab-content.active {
    display: block;
}

.crm-customers-module .crm-search-box {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.crm-customers-module .crm-search-box input {
    flex: 1;
}

.crm-customers-module .crm-customer-form .crm-form-section {
    background: #fafafa;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 6px;
    border-left: 4px solid #4caf50;
}

.crm-customers-module .crm-customer-form .crm-form-section h4 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
    color: #333;
    font-size: 16px;
}

.crm-customers-module .crm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.crm-customers-module .crm-form-group {
    display: flex;
    flex-direction: column;
}

.crm-customers-module .crm-form-group-full {
    grid-column: span 2;
}

.crm-customers-module .crm-form-group label {
    font-weight: 600;
    margin-bottom: 6px;
    color: #555;
    font-size: 13px;
}

.crm-customers-module .crm-form-group label .required {
    color: #f44336;
    margin-left: 3px;
}

.crm-customers-module .crm-radio-group {
    display: flex;
    gap: 20px;
    align-items: center;
}

.crm-customers-module .crm-radio-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 400;
}

.crm-customers-module .crm-input,
.crm-customers-module .crm-select,
.crm-customers-module .crm-select-multi {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border 0.3s;
}

.crm-customers-module .crm-input:focus,
.crm-customers-module .crm-select:focus,
.crm-customers-module .crm-select-multi:focus {
    border-color: #4caf50;
    outline: none;
    box-shadow: 0 0 0 2px rgba(76,175,80,0.1);
}

.crm-customers-module .crm-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 2px solid #e0e0e0;
}

.crm-customers-module .crm-badge {
    background: #ff9800;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 5px;
    font-weight: 700;
}

.crm-customers-module .crm-customer-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.crm-customers-module .crm-customer-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #4caf50;
    transform: translateY(-2px);
}

.crm-customers-module .crm-customer-card h4 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 16px;
}

.crm-customers-module .crm-customer-card p {
    margin: 4px 0;
    color: #666;
    font-size: 13px;
}

.crm-customers-module .crm-loader {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.crm-customers-module .spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.crm-customers-module .crm-modal {
    position: fixed;
    z-index: 99999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
}

.crm-customers-module .crm-modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.crm-customers-module .crm-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 32px;
    font-weight: 700;
    color: #999;
    cursor: pointer;
    line-height: 1;
}

.crm-customers-module .crm-modal-close:hover {
    color: #333;
}

.crm-customers-module .crm-doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.crm-customers-module .crm-doc-table th {
    background: #f5f5f5;
    padding: 12px;
    text-align: left;
    border-bottom: 2px solid #ddd;
    font-weight: 600;
}

.crm-customers-module .crm-doc-table td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
}

.crm-customers-module .crm-doc-table tr:hover {
    background: #f9f9f9;
}

.crm-customers-module .crm-contact-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
}

.crm-customers-module .crm-contact-card h5 {
    margin: 0 0 10px 0;
    color: #333;
}

.crm-customers-module .btn-back-to-list {
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .crm-customers-module .crm-form-row {
        grid-template-columns: 1fr;
    }
    
    .crm-customers-module .crm-form-group-full {
        grid-column: auto;
    }
    
    .crm-customers-module .crm-search-box {
        flex-direction: column;
    }
}
</style>

<!-- ======================= JAVASCRIPT ======================= -->
<script>
jQuery(document).ready(function($) {
    var selectedCustomerId = 0;
    var selectedCustomerName = '';
    
    // =========================
    // TAB-WECHSEL
    // =========================
    $('.crm-customers-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        
        $('.crm-customers-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.crm-customers-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // =========================
    // KUNDENLISTE LADEN
    // =========================
    function loadCustomersList(searchTerm) {
        $('#customers-list-container').html('<div class="crm-loader"><i class="dashicons dashicons-update-alt spin"></i> <?php _e('Lade Kunden...', 'cpsmartcrm'); ?></div>');
        
        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_get_agent_customers',
                security: '<?php echo $nonce_customer; ?>',
                search: searchTerm || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    var customers = response.data.customers || response.data;
                    renderCustomersList(customers);
                } else {
                    $('#customers-list-container').html('<div class="crm-alert" style="background:#ffebee;padding:15px;border-left:4px solid #f44336;color:#c62828;"><?php _e('Fehler beim Laden der Kunden.', 'cpsmartcrm'); ?></div>');
                }
            },
            error: function() {
                $('#customers-list-container').html('<div class="crm-alert" style="background:#ffebee;padding:15px;border-left:4px solid #f44336;color:#c62828;"><?php _e('Verbindungsfehler.', 'cpsmartcrm'); ?></div>');
            }
        });
    }
    
    // =========================
    // KUNDENLISTE RENDERN
    // =========================
    function renderCustomersList(customers) {
        var html = '';
        
        if (customers && customers.length > 0) {
            customers.forEach(function(customer) {
                var displayName = customer.firmenname || (customer.name + ' ' + customer.nachname).trim();
                if (!displayName) displayName = '<?php _e('Unbenannter Kunde', 'cpsmartcrm'); ?>';
                
                html += '<div class="crm-customer-card" data-id="' + customer.ID_kunde + '">';
                html += '  <div>';
                html += '    <h4>' + displayName + '</h4>';
                if (customer.email) html += '<p><i class="dashicons dashicons-email-alt"></i> ' + customer.email + '</p>';
                if (customer.telefono1) html += '<p><i class="dashicons dashicons-phone"></i> ' + customer.telefono1 + '</p>';
                if (customer.standort) html += '<p><i class="dashicons dashicons-location"></i> ' + (customer.adresse ? customer.adresse + ', ' : '') + customer.standort + '</p>';
                html += '  </div>';
                html += '  <div style="display:flex;flex-direction:column;gap:5px;">';
                html += '    <button class="crm-btn crm-btn-sm crm-btn-primary btn-edit-customer" data-id="' + customer.ID_kunde + '"><i class="dashicons dashicons-edit"></i> <?php _e('Bearbeiten', 'cpsmartcrm'); ?></button>';
                html += '    <button class="crm-btn crm-btn-sm crm-btn-info btn-view-docs" data-id="' + customer.ID_kunde + '" data-name="' + displayName + '"><i class="dashicons dashicons-media-document"></i> <?php _e('Dokumente', 'cpsmartcrm'); ?></button>';
                html += '    <button class="crm-btn crm-btn-sm crm-btn-warning btn-view-contacts" data-id="' + customer.ID_kunde + '" data-name="' + displayName + '"><i class="dashicons dashicons-id"></i> <?php _e('Kontakte', 'cpsmartcrm'); ?></button>';
                html += '  </div>';
                html += '</div>';
            });
        } else {
            html = '<div class="crm-alert" style="background:#e3f2fd;padding:20px;border-left:4px solid #2196F3;color:#1976D2;text-align:center;">';
            html += '<i class="dashicons dashicons-info" style="font-size:24px;"></i><br>';
            html += '<?php _e('Keine Kunden gefunden. Legen Sie Ihren ersten Kunden an!', 'cpsmartcrm'); ?></div>';
        }
        
        $('#customers-list-container').html(html);
    }
    
    // =========================
    // INITIALER LOAD
    // =========================
    loadCustomersList();
    
    // =========================
    // SUCHE
    // =========================
    $('#btn-search-customers').on('click', function() {
        var searchTerm = $('#customer-search-input').val();
        loadCustomersList(searchTerm);
    });
    
    $('#customer-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#btn-search-customers').click();
        }
    });
    
    // =========================
    // NEUER KUNDE
    // =========================
    $('#btn-add-new-customer').on('click', function() {
        resetCustomerForm();
        $('#customer-form-title').text('<?php _e('Neuer Kunde', 'cpsmartcrm'); ?>');
        $('.crm-customers-tab-btn[data-tab="customers-form"]').show().click();
    });
    
    // =========================
    // KUNDE BEARBEITEN
    // =========================
    $(document).on('click', '.btn-edit-customer', function(e) {
        e.stopPropagation();
        var customerId = $(this).data('id');
        loadCustomerData(customerId);
    });
    
    // =========================
    // KUNDE LADEN
    // =========================
    function loadCustomerData(customerId) {
        $('#customer-form-title').text('<?php _e('Kunde bearbeiten', 'cpsmartcrm'); ?>');
        $('.crm-customers-tab-btn[data-tab="customers-form"]').show().click();
        
        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_get_customer_data',
                security: '<?php echo $nonce_customer; ?>',
                customer_id: customerId
            },
            success: function(response) {
                if (response.success && response.data) {
                    var c = response.data;
                    
                    $('#customer-id-hidden').val(c.ID_kunde || 0);
                    $('#firmenname').val(c.firmenname || '');
                    $('#name').val(c.name || '');
                    $('#nachname').val(c.nachname || '');
                    $('#email').val(c.email || '');
                    $('#telefono1').val(c.telefono1 || '');
                    $('#telefono2').val(c.telefono2 || '');
                    $('#fax').val(c.fax || '');
                    $('#adresse').val(c.adresse || '');
                    $('#cap').val(c.cap || '');
                    $('#standort').val(c.standort || '');
                    $('#provinz').val(c.provinz || '');
                    $('#nation').val(c.nation || 'DE');
                    $('#cod_fis').val(c.cod_fis || '');
                    $('#p_iva').val(c.p_iva || '');
                    $('#sitoweb').val(c.sitoweb || '');
                    $('#skype').val(c.skype || '');
                    $('#luogo_nascita').val(c.luogo_nascita || '');
                    $('#einstiegsdatum').val(c.einstiegsdatum || '');
                    $('#geburtsdatum').val(c.geburtsdatum || '');
                    
                    if (c.tipo_cliente) {
                        $('input[name="tipo_cliente"][value="' + c.tipo_cliente + '"]').prop('checked', true);
                    }
                    
                    if (c.abrechnungsmodus !== undefined) {
                        $('input[name="abrechnungsmodus"][value="' + c.abrechnungsmodus + '"]').prop('checked', true);
                    }
                    
                    // Multi-Selects
                    if (c.categoria) {
                        var cats = c.categoria.split(',');
                        $('#customerCategory').val(cats).trigger('change');
                    }
                    
                    if(c.interessi) {
                        var ints = c.interessi.split(',');
                        $('#customerInterests').val(ints).trigger('change');
                    }
                    
                    if (c.provenienza) {
                        var provs = c.provenienza.split(',');
                        $('#customerComesfrom').val(provs).trigger('change');
                    }
                    
                    if (c.agente) {
                        $('#selectAgent').val(c.agente);
                    }
                }
            }
        });
    }
    
    // =========================
    // FORMULAR SPEICHERN
    // =========================
    $('#form-customer-save').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serializeArray();
        
        // SELECT2/Multi-Select-Werte hinzufügen
        var catVals = $('#customerCategory').val();
        var intVals = $('#customerInterests').val();
        var provVals = $('#customerComesfrom').val();
        
        formData.push({ name: 'customerCategory', value: Array.isArray(catVals) ? catVals.join(',') : (catVals || '') });
        formData.push({ name: 'customerInterests', value: Array.isArray(intVals) ? intVals.join(',') : (intVals || '') });
        formData.push({ name: 'customerComesfrom', value: Array.isArray(provVals) ? provVals.join(',') : (provVals || '') });
        formData.push({ name: 'agente', value: <?php echo $current_user_id; ?> }); // Automatisch auf aktuellen User setzen
        
        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: $.param(formData),
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Kunde erfolgreich gespeichert!', 'cpsmartcrm'); ?>');
                    resetCustomerForm();
                    loadCustomersList();
                    $('.crm-customers-tab-btn[data-tab="customers-list"]').click();
                    $('.crm-customers-tab-btn[data-tab="customers-form"]').hide();
                } else {
                    alert('<?php _e('Fehler beim Speichern: ', 'cpsmartcrm'); ?>' + (response.data.message || ''));
                }
            }
        });
    });
    
    // =========================
    // FORMULAR ZURÜCKSETZEN
    // =========================
    function resetCustomerForm() {
        $('#form-customer-save')[0].reset();
        $('#customer-id-hidden').val('0');
        $('#customerCategory, #customerInterests, #customerComesfrom').val(null).trigger('change');
        $('#einstiegsdatum').val('<?php echo date('d.m.Y'); ?>');
        $('input[name="tipo_cliente"][value="1"]').prop('checked', true);
        $('input[name="abrechnungsmodus"][value="0"]').prop('checked', true);
    }
    
    $('#btn-reset-customer-form').on('click', resetCustomerForm);
    
    $('#btn-cancel-customer-form').on('click', function() {
        $('.crm-customers-tab-btn[data-tab="customers-list"]').click();
        $('.crm-customers-tab-btn[data-tab="customers-form"]').hide();
    });
    
    // =========================
    // DOKUMENTE ANZEIGEN
    // =========================
    $(document).on('click', '.btn-view-docs', function(e) {
        e.stopPropagation();
        selectedCustomerId = $(this).data('id');
        selectedCustomerName = $(this).data('name');
        
        $('#selected-customer-id-docs').val(selectedCustomerId);
        $('#customer-docs-badge').text(selectedCustomerName);
        $('.crm-customers-tab-btn[data-tab="customers-docs"]').show().click();
        
        loadCustomerDocuments(selectedCustomerId);
    });
    
    function loadCustomerDocuments(customerId) {
        $('#docs-quotes-list, #docs-invoices-list, #docs-proforma-list').html('<p style="color:#999;padding:10px;"><?php _e('Lade...', 'cpsmartcrm'); ?></p>');
        
        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_get_customer_documents',
                security: '<?php echo $nonce_customer; ?>',
                customer_id: customerId
            },
            success: function(response) {
                if (response.success && response.data) {
                    var docs = response.data;
                    
                    var quotes = docs.filter(function(d){ return d.tipo == 1; });
                    var invoices = docs.filter(function(d){ return d.tipo == 2; });
                    var proforma = docs.filter(function(d){ return d.tipo == 3; });
                    
                    renderDocuments(quotes, '#docs-quotes-list');
                    renderDocuments(invoices, '#docs-invoices-list');
                    renderDocuments(proforma, '#docs-proforma-list');
                }
            }
        });
    }
    
    function renderDocuments(docs, targetSelector) {
        var html = '';
        
        if (docs.length > 0) {
            html += '<table class="crm-doc-table">';
            html += '<thead><tr>';
            html += '<th><?php _e('Nr.', 'cpsmartcrm'); ?></th>';
            html += '<th><?php _e('Datum', 'cpsmartcrm'); ?></th>';
            html += '<th><?php _e('Betreff', 'cpsmartcrm'); ?></th>';
            html += '<th><?php _e('Betrag', 'cpsmartcrm'); ?></th>';
            html += '<th><?php _e('Status', 'cpsmartcrm'); ?></th>';
            html += '</tr></thead><tbody>';
            
            docs.forEach(function(doc) {
                var status = doc.pagato == 1 ? '<span style="color:green;font-weight:600;">✓ <?php _e('Bezahlt', 'cpsmartcrm'); ?></span>' : '<span style="color:orange;font-weight:600;">⏱ <?php _e('Offen', 'cpsmartcrm'); ?></span>';
                html += '<tr>';
                html += '<td><strong>' + (doc.progressivo || doc.id) + '</strong></td>';
                html += '<td>' + (doc.einstiegsdatum || doc.data || '—') + '</td>';
                html += '<td>' + (doc.oggetto || '—') + '</td>';
                html += '<td><strong>' + (doc.totale || '0.00') + ' €</strong></td>';
                html += '<td>' + status + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        } else {
            html = '<div class="crm-alert" style="background:#e3f2fd;padding:15px;border-left:4px solid #2196F3;color:#1976D2;"><?php _e('Keine Dokumente vorhanden', 'cpsmartcrm'); ?></div>';
        }
        
        $(targetSelector).html(html);
    }
    
    // =========================
    // KONTAKTE ANZEIGEN
    // =========================
    $(document).on('click', '.btn-view-contacts', function(e) {
        e.stopPropagation();
        selectedCustomerId = $(this).data('id');
        selectedCustomerName = $(this).data('name');
        
        $('#selected-customer-id-contacts').val(selectedCustomerId);
        $('#contact-fk-kunde').val(selectedCustomerId);
        $('#customer-contacts-badge').text(selectedCustomerName);
        $('.crm-customers-tab-btn[data-tab="customers-contacts"]').show().click();
        
        loadCustomerContacts(selectedCustomerId);
    });
    
    function loadCustomerContacts(customerId) {
        $('#contacts-list-container').html('<div class="crm-loader"><i class="dashicons dashicons-update-alt spin"></i> <?php _e('Lade Kontakte...', 'cpsmartcrm'); ?></div>');
        
        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_get_customer_contacts',
                security: '<?php echo $nonce_customer; ?>',
                customer_id: customerId
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderContacts(response.data);
                }
            }
        });
    }
    
    function renderContacts(contacts) {
        var html = '';
        
        if (contacts.length > 0) {
            contacts.forEach(function(contact) {
                html += '<div class="crm-contact-card">';
                html += '  <h5>' + contact.name + ' ' + contact.nachname + '</h5>';
                if (contact.qualifica) html += '<p><strong><?php _e('Position', 'cpsmartcrm'); ?>:</strong> ' + contact.qualifica + '</p>';
                if (contact.email) html += '<p><i class="dashicons dashicons-email-alt"></i> ' + contact.email + '</p>';
                if (contact.telefono) html += '<p><i class="dashicons dashicons-phone"></i> ' + contact.telefono + '</p>';
                html += '</div>';
            });
        } else {
            html = '<div class="crm-alert" style="background:#e3f2fd;padding:15px;border-left:4px solid #2196F3;color:#1976D2;"><?php _e('Keine Kontakte vorhanden', 'cpsmartcrm'); ?></div>';
        }
        
        $('#contacts-list-container').html(html);
    }
    
    // =========================
    // KONTAKT HINZUFÜGEN
    // =========================
    $('#btn-add-contact').on('click', function() {
        $('#form-contact-save')[0].reset();
        $('#contact-id').val('0');
        $('#contact-fk-kunde').val(selectedCustomerId);
        $('#contact-modal').fadeIn();
    });
    
    $('#form-contact-save').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Kontakt erfolgreich gespeichert!', 'cpsmartcrm'); ?>');
                    $('#contact-modal').fadeOut();
                    loadCustomerContacts(selectedCustomerId);
                } else {
                    alert('<?php _e('Fehler beim Speichern.', 'cpsmartcrm'); ?>');
                }
            }
        });
    });
    
    // =========================
    // MODAL SCHLIESSEN
    // =========================
    $('.crm-modal-close').on('click', function() {
        $(this).closest('.crm-modal').fadeOut();
    });
    
    // =========================
    // ZURÜCK ZUR LISTE
    // =========================
    $('.btn-back-to-list').on('click', function() {
        $('.crm-customers-tab-btn[data-tab="customers-list"]').click();
        $('.crm-customers-tab-btn[data-tab="customers-docs"]').hide();
        $('.crm-customers-tab-btn[data-tab="customers-contacts"]').hide();
        $('.crm-customers-tab-btn[data-tab="customers-form"]').hide();
    });
    
    // =========================
    // DATEPICKER (jQuery UI)
    // =========================
    if (typeof $.fn.datepicker !== 'undefined') {
        $('.crm-datepicker').datepicker({
            dateFormat: 'dd.mm.yy',
            changeMonth: true,
            changeYear: true,
            yearRange: '1900:+10'
        });
    }
    
    // =========================
    // SELECT2 (wenn verfügbar)
    // =========================
    if (typeof $.fn.select2 !== 'undefined') {
        $('#customerCategory, #customerInterests, #customerComesfrom').select2({
            width: '100%',
            multiple: true,
            tags: true,
            tokenSeparators: [','],
            placeholder: '<?php _e('Auswählen...', 'cpsmartcrm'); ?>'
        });
        
        $('#selectAgent, #nation').select2({
            width: '100%'
        });
    }
});
</script>
