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
                                <label><?php _e('Datum Erstkontakt', 'cpsmartcrm'); ?></label>
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
                
                <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
                    <button type="button" class="crm-btn crm-btn-secondary btn-back-to-list">
                        <i class="dashicons dashicons-arrow-left-alt"></i>
                        <?php _e('Zurück zur Liste', 'cpsmartcrm'); ?>
                    </button>
                    <button type="button" class="crm-btn crm-btn-success" id="btn-add-quotation">
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('Neues Angebot', 'cpsmartcrm'); ?>
                    </button>
                    <button type="button" class="crm-btn crm-btn-success" id="btn-add-invoice">
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('Neue Rechnung', 'cpsmartcrm'); ?>
                    </button>
                    <button type="button" class="crm-btn crm-btn-success" id="btn-add-proforma">
                        <i class="dashicons dashicons-plus"></i>
                        <?php _e('Neue Proforma', 'cpsmartcrm'); ?>
                    </button>
                </div>

                <!-- Quotation Form (Hidden) -->
                <form id="form-add-quotation" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4><?php _e('Neues Angebot', 'cpsmartcrm'); ?></h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Datum</label>
                            <input type="date" name="quotation-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Gültig bis</label>
                            <input type="date" name="quotation-due-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Betreff/Beschreibung</label>
                        <input type="text" name="quotation-subject" placeholder="z.B. Angebot für Projektarbeit..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;font-weight:600;">Positionszeilen</label>
                        <div class="crm-doc-line-wrap">
                            <div class="crm-discount-mode" data-form-type="quotation">
                                <span>Rabattart:</span>
                                <label><input type="radio" name="quotation-discount-mode" value="percent" checked> %</label>
                                <label><input type="radio" name="quotation-discount-mode" value="fixed"> €</label>
                            </div>
                            <table class="crm-doc-line-editor" id="quotation-lines-table">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Beschreibung</th>
                                        <th>Menge</th>
                                        <th>Einzelpreis</th>
                                        <th>Rabatt</th>
                                        <th>MwSt. %</th>
                                        <th>Gesamt</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="quotation-lines-body"></tbody>
                            </table>
                            <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" id="btn-add-quotation-line" style="margin-top:8px;">+ Zeile hinzufügen</button>
                            <div class="crm-doc-totals" id="quotation-totals">
                                <span>Netto: <strong id="quotation-total-net">0.00 €</strong></span>
                                <span>MwSt.: <strong id="quotation-total-tax">0.00 €</strong></span>
                                <span>Brutto: <strong id="quotation-total-gross">0.00 €</strong></span>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Notizen</label>
                        <textarea name="quotation-notes" placeholder="Zusätzliche Informationen..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; min-height: 60px; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="crm-btn crm-btn-sm crm-btn-success" style="cursor: pointer;">✅ Speichern</button>
                        <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary btn-cancel-doc-form" style="cursor: pointer;">Abbrechen</button>
                    </div>
                    <input type="hidden" name="nonce" value="<?php echo $nonce_customer; ?>">
                </form>

                <!-- Invoice Form (Hidden) -->
                <form id="form-add-invoice" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4><?php _e('Neue Rechnung', 'cpsmartcrm'); ?></h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Rechnungsdatum</label>
                            <input type="date" name="invoice-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Fälligkeitsdatum</label>
                            <input type="date" name="invoice-due-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Betreff</label>
                        <input type="text" name="invoice-subject" placeholder="z.B. Rechnung für Leistungen im September..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display:block;font-size:12px;color:#666;margin-bottom:6px;font-weight:600;">Rechnungszeilen</label>
                        <div class="crm-doc-line-wrap">
                            <div class="crm-discount-mode" data-form-type="invoice">
                                <span>Rabattart:</span>
                                <label><input type="radio" name="invoice-discount-mode" value="percent" checked> %</label>
                                <label><input type="radio" name="invoice-discount-mode" value="fixed"> €</label>
                            </div>
                            <table class="crm-doc-line-editor" id="invoice-lines-table">
                                <thead>
                                    <tr>
                                        <th>Typ</th>
                                        <th>Beschreibung</th>
                                        <th>Menge</th>
                                        <th>Einzelpreis</th>
                                        <th>Rabatt</th>
                                        <th>MwSt. %</th>
                                        <th>Gesamt</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-lines-body"></tbody>
                            </table>
                            <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" id="btn-add-invoice-line" style="margin-top:8px;">+ Zeile hinzufügen</button>
                            <div class="crm-doc-totals" id="invoice-totals">
                                <span>Netto: <strong id="invoice-total-net">0.00 €</strong></span>
                                <span>MwSt.: <strong id="invoice-total-tax">0.00 €</strong></span>
                                <span>Brutto: <strong id="invoice-total-gross">0.00 €</strong></span>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Zahlungsart</label>
                        <select name="invoice-payment-method" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                            <option value="">-- Bitte wählen --</option>
                            <option value="Banküberweisung">Banküberweisung</option>
                            <option value="Kreditkarte">Kreditkarte</option>
                            <option value="Lastschrift">Lastschrift</option>
                            <option value="Barzahlung">Barzahlung</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Notizen</label>
                        <textarea name="invoice-notes" placeholder="Zusätzliche Informationen, Zahlungsbedingungen..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; min-height: 60px; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="crm-btn crm-btn-sm crm-btn-success" style="cursor: pointer;">✅ Speichern</button>
                        <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary btn-cancel-doc-form" style="cursor: pointer;">Abbrechen</button>
                    </div>
                    <input type="hidden" name="nonce" value="<?php echo $nonce_customer; ?>">
                </form>

                <!-- Proforma Form (Hidden) -->
                <form id="form-add-proforma" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4><?php _e('Neue Proforma', 'cpsmartcrm'); ?></h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Datum</label>
                            <input type="date" name="proforma-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Fälligkeitsdatum</label>
                            <input type="date" name="proforma-due-date" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Betreff</label>
                        <input type="text" name="proforma-subject" placeholder="z.B. Proforma für Vorauszahlung..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Gesamtbetrag</label>
                        <input type="number" name="proforma-amount" step="0.01" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 600;">Notizen</label>
                        <textarea name="proforma-notes" placeholder="Zusätzliche Informationen..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; min-height: 60px; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="crm-btn crm-btn-sm crm-btn-success" style="cursor: pointer;">✅ Speichern</button>
                        <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary btn-cancel-doc-form" style="cursor: pointer;">Abbrechen</button>
                    </div>
                    <input type="hidden" name="nonce" value="<?php echo $nonce_customer; ?>">
                </form>

                <div class="crm-docs-container" style="margin-top:20px;">
                    <h4><?php _e('Angebote', 'cpsmartcrm'); ?></h4>
                    <div id="docs-quotes-list" class="crm-docs-list"></div>

                    <h4 style="margin-top:30px;"><?php _e('Rechnungen', 'cpsmartcrm'); ?></h4>
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
    color: #2f455c;
}

.crm-customers-module .crm-customers-tab-btn:hover:not(.active) {
    background: #eef2f6;
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
    border-left: 4px solid #7c8b99;
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
    border-color: #7c8b99;
    outline: none;
    box-shadow: 0 0 0 2px rgba(124,139,153,0.14);
}

.crm-customers-module .crm-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 25px;
    border-top: 2px solid #e0e0e0;
}

.crm-customers-module .crm-badge {
    background: #7a8793;
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
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    border-color: #8a97a3;
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

.crm-customers-module .crm-doc-line-wrap {
    background: #fff;
    border: 1px solid #d9e0e6;
    border-radius: 6px;
    padding: 10px;
}

.crm-customers-module .crm-discount-mode {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 12px;
    color: #526477;
}

.crm-customers-module .crm-discount-mode label {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.crm-customers-module .crm-doc-line-editor {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.crm-customers-module .crm-doc-line-editor th,
.crm-customers-module .crm-doc-line-editor td {
    border-bottom: 1px solid #e7edf2;
    padding: 8px 6px;
    text-align: left;
}

.crm-customers-module .crm-doc-line-editor th {
    background: #f5f8fb;
    color: #405468;
    font-weight: 600;
}

.crm-customers-module .crm-doc-line-editor input,
.crm-customers-module .crm-doc-line-editor textarea,
.crm-customers-module .crm-doc-line-editor select {
    width: 100%;
    border: 1px solid #d4dde5;
    border-radius: 4px;
    padding: 6px;
    box-sizing: border-box;
    font-size: 12px;
}

.crm-customers-module .crm-doc-line-editor .line-total-cell {
    min-width: 90px;
    font-weight: 600;
    color: #2f455c;
}

.crm-customers-module .crm-doc-line-editor .is-disabled {
    opacity: 0.55;
    background: #f1f4f7;
}

.crm-customers-module .crm-doc-totals {
    margin-top: 10px;
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    flex-wrap: wrap;
    font-size: 13px;
    color: #55697d;
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

.crm-customers-module .crm-empty-state {
    background: #f7f9fc;
    border: 1px solid #d7e0e8;
    border-radius: 8px;
    padding: 34px 24px;
    text-align: center;
    margin: 20px 0;
}

.crm-customers-module .crm-empty-state-icon {
    color: #8a97a3;
    font-size: 42px;
    display: block;
    margin-bottom: 12px;
}

.crm-customers-module .crm-empty-state h4 {
    margin: 0 0 10px 0;
    color: #2f3f4f;
    font-size: 22px;
    font-weight: 600;
}

.crm-customers-module .crm-empty-state p {
    margin: 0 0 18px 0;
    color: #667585;
    font-size: 14px;
}

.crm-customers-module .crm-empty-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
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
    function activateCustomerTab(tabId) {
        var $tabButton = $('.crm-customers-tab-btn[data-tab="' + tabId + '"]');

        $('.crm-customers-tab-btn').removeClass('active');
        $('.crm-customers-tab-content').removeClass('active').hide();

        $tabButton.show().addClass('active');
        $('#' + tabId).addClass('active').show();
    }

    $('.crm-customers-tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        activateCustomerTab(tabId);
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
            if ($('#btn-search-customers').length) {
                $('#btn-search-customers').click();
            }
        }
    });
    
    // =========================
    // NEUER KUNDE
    // =========================
    $('#btn-add-new-customer').on('click', function() {
        resetCustomerForm();
        $('#customer-form-title').text('<?php _e('Neuer Kunde', 'cpsmartcrm'); ?>');
        activateCustomerTab('customers-form');
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
        activateCustomerTab('customers-form');
        
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
                        if ($('#customerCategory').length) $('#customerCategory').val(cats).trigger('change');
                    }
                    
                    if(c.interessi) {
                        var ints = c.interessi.split(',');
                        if ($('#customerInterests').length) $('#customerInterests').val(ints).trigger('change');
                    }
                    
                    if (c.provenienza) {
                        var provs = c.provenienza.split(',');
                        if ($('#customerComesfrom').length) $('#customerComesfrom').val(provs).trigger('change');
                    }
                    
                    if (c.agente) {
                        if ($('#selectAgent').length) $('#selectAgent').val(c.agente);
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
                    if ($('.crm-customers-tab-btn[data-tab="customers-list"]').length) {
                        $('.crm-customers-tab-btn[data-tab="customers-list"]').click();
                    }
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
        if ($('#form-customer-save').length) $('#form-customer-save')[0].reset();
        if ($('#customer-id-hidden').length) $('#customer-id-hidden').val('0');
        if ($('#customerCategory, #customerInterests, #customerComesfrom').length) {
            $('#customerCategory, #customerInterests, #customerComesfrom').val(null).trigger('change');
        }
        if ($('#einstiegsdatum').length) $('#einstiegsdatum').val('<?php echo date('d.m.Y'); ?>');
        if ($('input[name="tipo_cliente"][value="1"]').length) $('input[name="tipo_cliente"][value="1"]').prop('checked', true);
        if ($('input[name="abrechnungsmodus"][value="0"]').length) $('input[name="abrechnungsmodus"][value="0"]').prop('checked', true);
    }
    
    $('#btn-reset-customer-form').on('click', resetCustomerForm);
    
    $('#btn-cancel-customer-form').on('click', function() {
        activateCustomerTab('customers-list');
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
        activateCustomerTab('customers-docs');
        
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
                    
                    renderDocuments(docs.quotations || [], '#docs-quotes-list', 'quotation');
                    renderDocuments(docs.invoices || [], '#docs-invoices-list', 'invoice');
                    renderDocuments(docs.proforma || [], '#docs-proforma-list', 'proforma');
                } else {
                    $('#docs-quotes-list, #docs-invoices-list, #docs-proforma-list').html('<div style="color:red;padding:10px;">Fehler: ' + (response.data?.message || 'Unbekannt') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#docs-quotes-list, #docs-invoices-list, #docs-proforma-list').html('<div style="color:red;padding:10px;">AJAX-Fehler: ' + error + '</div>');
            }
        });
    }
    
    function renderDocuments(docs, targetSelector, docType) {
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
                var status = doc.pagato == 1 ? '<span style="color:green;font-weight:600;"><?php _e('Bezahlt', 'cpsmartcrm'); ?></span>' : '<span style="color:orange;font-weight:600;"><?php _e('Offen', 'cpsmartcrm'); ?></span>';
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
            var iconClass = 'dashicons-media-document';
            var title = '<?php _e('Noch keine Dokumente für diesen Kunden', 'cpsmartcrm'); ?>';
            var text = '<?php _e('Erstelle jetzt dein erstes Dokument.', 'cpsmartcrm'); ?>';
            var buttonId = '';
            var buttonLabel = '';

            if (docType === 'quotation') {
                title = '<?php _e('Noch keine Angebote für diesen Kunden', 'cpsmartcrm'); ?>';
                text = '<?php _e('Erstelle jetzt dein erstes Angebot!', 'cpsmartcrm'); ?>';
                buttonId = 'btn-add-quotation-from-empty';
                buttonLabel = '<?php _e('Angebot erstellen', 'cpsmartcrm'); ?>';
            } else if (docType === 'invoice') {
                title = '<?php _e('Noch keine Rechnungen für diesen Kunden', 'cpsmartcrm'); ?>';
                text = '<?php _e('Erstelle jetzt deine erste Rechnung!', 'cpsmartcrm'); ?>';
                buttonId = 'btn-add-invoice-from-empty';
                buttonLabel = '<?php _e('Rechnung erstellen', 'cpsmartcrm'); ?>';
            } else if (docType === 'proforma') {
                title = '<?php _e('Noch keine Proforma für diesen Kunden', 'cpsmartcrm'); ?>';
                text = '<?php _e('Erstelle jetzt deine erste Proforma!', 'cpsmartcrm'); ?>';
                buttonId = 'btn-add-proforma-from-empty';
                buttonLabel = '<?php _e('Proforma erstellen', 'cpsmartcrm'); ?>';
            }

            html += '<div class="crm-empty-state">';
            html += '<i class="dashicons ' + iconClass + ' crm-empty-state-icon"></i>';
            html += '<h4>' + title + '</h4>';
            html += '<p>' + text + '</p>';
            html += '<div class="crm-empty-actions">';
            html += '<button type="button" class="crm-btn crm-btn-secondary" id="' + buttonId + '">' + buttonLabel + '</button>';
            html += '</div>';
            html += '</div>';
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
        activateCustomerTab('customers-contacts');
        
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
                } else {
                    $('#contacts-list-container').html('<div style="color:red;padding:10px;">Fehler: ' + (response.data?.message || 'Unbekannt') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#contacts-list-container').html('<div style="color:red;padding:10px;">AJAX-Fehler: ' + error + '</div>');
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
            html = '<div class="crm-empty-state">';
            html += '<i class="dashicons dashicons-id crm-empty-state-icon"></i>';
            html += '<h4><?php _e('Noch keine Kontakte für diesen Kunden', 'cpsmartcrm'); ?></h4>';
            html += '<p><?php _e('Füge Ansprechpartner hinzu, um Kontaktdaten zu verwalten!', 'cpsmartcrm'); ?></p>';
            html += '<div class="crm-empty-actions">';
            html += '<button type="button" class="crm-btn crm-btn-secondary" id="btn-add-contact-from-empty"><?php _e('Kontakt hinzufügen', 'cpsmartcrm'); ?></button>';
            html += '</div>';
            html += '</div>';
        }
        
        $('#contacts-list-container').html(html);
    }
    
    // =========================
    // KONTAKT HINZUFÜGEN
    // =========================
    $(document).on('click', '#btn-add-contact, #btn-add-contact-from-empty', function() {
        if ($('#form-contact-save').length) $('#form-contact-save')[0].reset();
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
        if ($('.crm-customers-tab-btn[data-tab="customers-list"]').length) {
            $('.crm-customers-tab-btn[data-tab="customers-list"]').click();
        }
        $('.crm-customers-tab-btn[data-tab="customers-docs"]').hide();
        $('.crm-customers-tab-btn[data-tab="customers-contacts"]').hide();
        $('.crm-customers-tab-btn[data-tab="customers-form"]').hide();
    });
    
    // =========================
    // DOKUMENT FORMULARE
    // =========================

    function formatMoney(value) {
        return (parseFloat(value || 0).toFixed(2)) + ' €';
    }

    function getDiscountMode(formType) {
        return $('input[name="' + formType + '-discount-mode"]:checked').val() || 'percent';
    }

    function applyLineTypeState($row) {
        const lineType = parseInt($row.find('.line-type').val() || '2', 10);
        const $qty = $row.find('.line-qty');
        const $price = $row.find('.line-price');
        const $discount = $row.find('.line-discount');
        const $vat = $row.find('.line-vat');

        const setDisabled = function($input, disabled) {
            $input.prop('disabled', disabled);
            $input.toggleClass('is-disabled', disabled);
        };

        if (lineType === 3) {
            setDisabled($qty, true);
            setDisabled($price, true);
            setDisabled($discount, true);
            setDisabled($vat, true);
            $qty.val('0');
            $price.val('0');
            $discount.val('0');
            $vat.val('0');
        } else if (lineType === 4) {
            setDisabled($qty, false);
            setDisabled($price, false);
            setDisabled($discount, true);
            setDisabled($vat, true);
            if (!$qty.val() || parseFloat($qty.val()) <= 0) {
                $qty.val('1');
            }
            $discount.val('0');
            $vat.val('0');
        } else {
            setDisabled($qty, false);
            setDisabled($price, false);
            setDisabled($discount, false);
            setDisabled($vat, false);
            if (!$qty.val() || parseFloat($qty.val()) <= 0) {
                $qty.val('1');
            }
        }
    }

    function buildLineRow(formType, vatDefault) {
        return '<tr>' +
            '<td><select class="doc-line-input line-type"><option value="2">Standard</option><option value="3">Beschreibung</option><option value="4">Rückerstattung</option></select></td>' +
            '<td><textarea class="doc-line-input line-description" rows="1" placeholder="Leistungsbeschreibung"></textarea></td>' +
            '<td><input type="number" class="doc-line-input line-qty" step="0.01" min="0" value="1"></td>' +
            '<td><input type="number" class="doc-line-input line-price" step="0.01" min="0" value="0.00"></td>' +
            '<td><input type="number" class="doc-line-input line-discount" step="0.01" min="0" value="0"></td>' +
            '<td><input type="number" class="doc-line-input line-vat" step="0.01" min="0" value="' + vatDefault + '"></td>' +
            '<td class="line-total-cell">0.00 €</td>' +
            '<td><button type="button" class="crm-btn crm-btn-secondary crm-btn-sm btn-remove-doc-line">×</button></td>' +
            '</tr>';
    }

    function addDocumentLine(formType, vatDefault) {
        const $row = $(buildLineRow(formType, vatDefault));
        $('#' + formType + '-lines-body').append($row);
        applyLineTypeState($row);
        recalculateDocument(formType);
    }

    function recalculateDocument(formType) {
        let net = 0;
        let tax = 0;

        $('#' + formType + '-lines-body tr').each(function() {
            const lineType = parseInt($(this).find('.line-type').val() || '2', 10);
            const qty = parseFloat($(this).find('.line-qty').val()) || 0;
            const price = parseFloat($(this).find('.line-price').val()) || 0;
            const discount = parseFloat($(this).find('.line-discount').val()) || 0;
            const vat = parseFloat($(this).find('.line-vat').val()) || 0;

            let lineNet = 0;
            let lineTax = 0;
            let lineGross = 0;

            if (lineType !== 3) {
                const lineBase = qty * price;
                const discountMode = getDiscountMode(formType);
                let discountValue = 0;

                if (discountMode === 'fixed') {
                    discountValue = Math.min(lineBase, Math.max(0, discount));
                } else {
                    discountValue = lineBase * Math.max(0, discount) / 100;
                }

                lineNet = Math.max(0, lineBase - discountValue);
                lineTax = (lineType === 4) ? 0 : lineNet * (vat / 100);
                lineGross = lineNet + lineTax;

                if (lineType === 4) {
                    lineNet = -lineNet;
                    lineTax = 0;
                    lineGross = -lineGross;
                }
            }

            net += lineNet;
            tax += lineTax;

            $(this).find('.line-total-cell').text(formatMoney(lineGross));
        });

        const gross = net + tax;
        $('#' + formType + '-total-net').text(formatMoney(net));
        $('#' + formType + '-total-tax').text(formatMoney(tax));
        $('#' + formType + '-total-gross').text(formatMoney(gross));
    }

    function collectDocumentLines(formType) {
        const lines = [];
        $('#' + formType + '-lines-body tr').each(function() {
            const lineType = parseInt($(this).find('.line-type').val() || '2', 10);
            const description = ($(this).find('.line-description').val() || '').trim();
            const qty = parseFloat($(this).find('.line-qty').val()) || 0;
            const price = parseFloat($(this).find('.line-price').val()) || 0;
            const discount = parseFloat($(this).find('.line-discount').val()) || 0;
            const vat = parseFloat($(this).find('.line-vat').val()) || 0;

            if (!description) {
                return;
            }

            lines.push({
                line_type: lineType,
                description: description,
                quantity: qty,
                unit_price: price,
                discount_value: discount,
                vat_percent: vat
            });
        });
        return lines;
    }

    $(document).on('click', '#btn-add-quotation-line', function() { addDocumentLine('quotation', 19); });
    $(document).on('click', '#btn-add-invoice-line', function() { addDocumentLine('invoice', 19); });
    $(document).on('change', '#quotation-lines-body .line-type', function() { applyLineTypeState($(this).closest('tr')); recalculateDocument('quotation'); });
    $(document).on('change', '#invoice-lines-body .line-type', function() { applyLineTypeState($(this).closest('tr')); recalculateDocument('invoice'); });
    $(document).on('change', 'input[name="quotation-discount-mode"]', function() { recalculateDocument('quotation'); });
    $(document).on('change', 'input[name="invoice-discount-mode"]', function() { recalculateDocument('invoice'); });
    $(document).on('input', '#quotation-lines-body .doc-line-input', function() { recalculateDocument('quotation'); });
    $(document).on('input', '#invoice-lines-body .doc-line-input', function() { recalculateDocument('invoice'); });
    $(document).on('click', '#quotation-lines-body .btn-remove-doc-line', function() {
        $(this).closest('tr').remove();
        if (!$('#quotation-lines-body tr').length) addDocumentLine('quotation', 19);
        recalculateDocument('quotation');
    });
    $(document).on('click', '#invoice-lines-body .btn-remove-doc-line', function() {
        $(this).closest('tr').remove();
        if (!$('#invoice-lines-body tr').length) addDocumentLine('invoice', 19);
        recalculateDocument('invoice');
    });
    
    // Toggle Quotation Form 
    $(document).on('click', '#btn-add-quotation, #btn-add-quotation-from-empty', function() {
        const $form = $('#form-add-quotation');
        if ($form.is(':visible')) {
            $form.slideUp(200);
        } else {
            $('#form-add-invoice').slideUp(200);
            $('#form-add-proforma').slideUp(200);
            $form.slideDown(200);
            if (!$('#quotation-lines-body tr').length) addDocumentLine('quotation', 19);
            $form.find('input[name="quotation-date"]').focus();
        }
    });
    
    // Toggle Invoice Form
    $(document).on('click', '#btn-add-invoice, #btn-add-invoice-from-empty', function() {
        const $form = $('#form-add-invoice');
        if ($form.is(':visible')) {
            $form.slideUp(200);
        } else {
            $('#form-add-quotation').slideUp(200);
            $('#form-add-proforma').slideUp(200);
            $form.slideDown(200);
            if (!$('#invoice-lines-body tr').length) addDocumentLine('invoice', 19);
            $form.find('input[name="invoice-date"]').focus();
        }
    });

    // Toggle Proforma Form
    $(document).on('click', '#btn-add-proforma, #btn-add-proforma-from-empty', function() {
        const $form = $('#form-add-proforma');
        if ($form.is(':visible')) {
            $form.slideUp(200);
        } else {
            $('#form-add-quotation').slideUp(200);
            $('#form-add-invoice').slideUp(200);
            $form.slideDown(200);
            $form.find('input[name="proforma-date"]').focus();
        }
    });
    
    // Cancel Document Form
    $(document).on('click', '.btn-cancel-doc-form', function() {
        $(this).closest('form').slideUp(200);
    });
    
    // Submit Quotation Form
    $('#form-add-quotation').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#selected-customer-id-docs').val();
        if (!customerId) {
            alert('Fehler: Kein Kunde ausgewählt');
            return;
        }
        
        const lines = collectDocumentLines('quotation');
        if (!lines.length) {
            alert('Bitte mindestens eine Positionszeile hinzufügen.');
            return;
        }

        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_create_quotation',
                nonce: $('#form-add-quotation input[name="nonce"]').val(),
                customer_id: customerId,
                date: $('input[name="quotation-date"]').val(),
                due_date: $('input[name="quotation-due-date"]').val(),
                subject: $('input[name="quotation-subject"]').val(),
                notes: $('textarea[name="quotation-notes"]').val(),
                discount_mode: getDiscountMode('quotation'),
                line_items: JSON.stringify(lines)
            },
            success: function(response) {
                if (response.success) {
                    alert('Angebot erstellt.');
                    $('#form-add-quotation')[0].reset();
                    $('#quotation-lines-body').empty();
                    addDocumentLine('quotation', 19);
                    $('#form-add-quotation').slideUp(200);
                    loadCustomerDocuments(customerId);
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            }
        });
    });
    
    // Submit Invoice Form
    $('#form-add-invoice').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#selected-customer-id-docs').val();
        if (!customerId) {
            alert('Fehler: Kein Kunde ausgewählt');
            return;
        }
        
        const lines = collectDocumentLines('invoice');
        if (!lines.length) {
            alert('Bitte mindestens eine Rechnungszeile hinzufügen.');
            return;
        }

        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_create_invoice',
                nonce: $('#form-add-invoice input[name="nonce"]').val(),
                customer_id: customerId,
                date: $('input[name="invoice-date"]').val(),
                due_date: $('input[name="invoice-due-date"]').val(),
                subject: $('input[name="invoice-subject"]').val(),
                payment_method: $('select[name="invoice-payment-method"]').val(),
                notes: $('textarea[name="invoice-notes"]').val(),
                discount_mode: getDiscountMode('invoice'),
                line_items: JSON.stringify(lines)
            },
            success: function(response) {
                if (response.success) {
                    alert('Rechnung erstellt.');
                    $('#form-add-invoice')[0].reset();
                    $('#invoice-lines-body').empty();
                    addDocumentLine('invoice', 19);
                    $('#form-add-invoice').slideUp(200);
                    loadCustomerDocuments(customerId);
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            }
        });
    });

    // Submit Proforma Form
    $('#form-add-proforma').on('submit', function(e) {
        e.preventDefault();

        const customerId = $('#selected-customer-id-docs').val();
        if (!customerId) {
            alert('Fehler: Kein Kunde ausgewählt');
            return;
        }

        $.ajax({
            url: crmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'crm_create_proforma',
                nonce: $('#form-add-proforma input[name="nonce"]').val(),
                customer_id: customerId,
                date: $('input[name="proforma-date"]').val(),
                due_date: $('input[name="proforma-due-date"]').val(),
                subject: $('input[name="proforma-subject"]').val(),
                amount: $('input[name="proforma-amount"]').val(),
                notes: $('textarea[name="proforma-notes"]').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('Proforma erstellt.');
                    $('#form-add-proforma')[0].reset();
                    $('#form-add-proforma').slideUp(200);
                    loadCustomerDocuments(customerId);
                } else {
                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                }
            }
        });
    });
    
    // =========================
    // DATEPICKER (jQuery UI)
    // =========================
    function initDatepickers() {
        if (typeof $.fn.datepicker !== 'undefined') {
            $('.crm-datepicker').not('.hasDatepicker').datepicker({
                dateFormat: 'dd.mm.yy',
                changeMonth: true,
                changeYear: true,
                yearRange: '1900:+10'
            });
        }
    }
    initDatepickers();
    
    // Re-init datepickers wenn Tabs angezeigt werden
    $(document).on('click', '.crm-customers-tab-btn', function() {
        setTimeout(function() {
            initDatepickers();
        }, 100);
    });
    
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