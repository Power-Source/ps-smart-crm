---
layout: psource-theme
title: "Dev-Referenz: Integrationen, Hooks & Actions"
---

<h2 align="center" style="color:#38c2bb;">📚 PS Smart Business</h2>

<div class="menu">
	<a href="index.html" style="color:#38c2bb;">🏠 Übersicht</a>
	<a href="dokumentation.html" style="color:#38c2bb;">📝 Dokumentation</a>
	<a href="https://github.com/cp-psource/ps-smart-crm/discussions" style="color:#38c2bb;">💬 Forum</a>
	<a href="https://github.com/Power-Source/ps-smart-crm/releases" style="color:#38c2bb;">⬇️ Releases</a>
</div>

## Dokumentations-Navigation

**Priorisiert (verwandte Kapitel):**

- [Übersicht](index.html)
- [Allgemeine Dokumentation](dokumentation.html)
- [Einrichtung & Berechtigungen](einrichtung-berechtigungen.html)
- [CRM & Prozessmanagement](crm-prozessmanagement.html)
- [Dokumente, Angebote & Rechnungen](dokumente-rechnungen.html)
- [Buchhaltung & Abrechnung](buchhaltung-abrechnung.html)
- [Integrationen, API & Erweiterbarkeit](integrationen-api-erweiterbarkeit.html)
- [Dev-Referenz: Integrationen, Hooks & Actions](dev-hooks-actions.html)
- [PWA & Frontend-Module](pwa-frontend-module.html)
- [WebApp-Funktion (PWA/App-Modus)](webapp-funktion.html)
- [PS-Bloghosting Integrations-README](README.html)
- [Über das Projekt](about.html)

# Dev-Referenz: Integrationen, Hooks & Actions

Diese Seite ist die technische Referenz für Entwickler zur Integration von PS Smart Business mit anderen PSOURCE-Plugins.

## Scope & Stand

Dieser Katalog basiert auf dem aktuellen Code-Stand im Plugin und ist nach Hook-Typ gruppiert:

- **Custom Extension Hooks** (vom Plugin angeboten)
- **Incoming Hooks** (WordPress / PSOURCE / Drittplugins, auf die das Plugin hört)
- **AJAX Action Catalog** (alle registrierten `wp_ajax_*` Endpunkte)

Stand: März 2026

## 1) Custom Extension Hooks (vom Plugin angeboten)

### Action-Hooks (`do_action`)

| Hook | Zweck | Quelle |
|---|---|---|
| `wpscrm_role_assigned` | Nach Zuweisung einer Agent-Rolle | `inc/user-profile-integration.php` |
| `WPsCRM_accounting_after` | Erweiterungspunkt nach Buchhaltungs-Rendering | `inc/buchhaltung/index.php` |
| `WPsCRM_register_additional_general_options` | Zusätzliche General-Optionen registrieren | `inc/options.php` |
| `WPsCRM_register_additional_clients_options` | Zusätzliche Client-Optionen registrieren | `inc/options.php` |
| `WPsCRM_add_woo_settings_fields` | Zusätzliche Woo-Settings-Felder | `inc/options.php` |
| `WPsCRM_add_acc_settings_fields` | Zusätzliche Accounting-Settings-Felder | `inc/options.php` |
| `WPsCRM_add_adv_settings_fields` | Zusätzliche Advanced-Settings-Felder | `inc/options.php` |
| `WPsCRM_add_ag_settings_fields` | Zusätzliche Agents-Settings-Felder | `inc/options.php` |
| `business_extra_field` | Extra Felder in Business-Settings | `inc/options.php` |
| `WPsCRM_add_tabs_to_document_settings` | Dokument-Settings Tabs erweitern | `inc/options.php` |
| `WPsCRM_add_documents_inner_divs` | Zusätzliche Dokument-Settings-Inhalte | `inc/options.php` |
| `WPsCRM_stats_grid_toolbar` | Toolbar in Stats-Grid erweitern | `inc/crm/dokumente/stats.php` |
| `WPsCRM_add_tabs_to_stats_list` | Stats-Tabs erweitern | `inc/crm/dokumente/stats.php` |
| `WPsCRM_statsDatasource` | Stats-Datenquelle einspeisen | `inc/crm/dokumente/stats.php` |
| `WPsCRM_statsGrid` | Stats-Grid ausgeben/erweitern | `inc/crm/dokumente/stats.php` |
| `WPsCRM_add_divs_to_stats_list` | Zusätzliche Stats-Divs rendern | `inc/crm/dokumente/stats.php` |
| `WpsCRM_advanced_document_buttons` | Zusätzliche Buttons in Dokument-Formen | `inc/crm/dokumente/form_*.php` |
| `WPsCRM_show_WOO_products` | Produkt-Auswahl (Woo/Shop) einhängen | `inc/crm/dokumente/form_*.php` |
| `WPsCRM_advanced_rows` | Zusätzliche Zeilen im Accountability-Bereich | `inc/crm/dokumente/accountabilities/accountability_0.php` |
| `WPsCRM_Einvoice` | E-Invoice Erweiterungspunkt | `inc/crm/dokumente/accountabilities/accountability_0.php` |
| `WPsCRM_users_import` | Importprozess erweitern | `inc/crm/import/form.php` |
| `WPsCRM_add_submenu_documents` | Submenü Dokumente erweitern | `inc/crm/c_menu.php` |
| `WPsCRM_add_options_in_menu` | CRM-Menüoptionen erweitern | `inc/crm/c_menu.php` |
| `add_menu_items_b` | Externer Menü-Hook | `inc/crm/c_menu.php` |
| `AGsCRM_get_clients_hook` | Agents-Addon: Kunden-Hook | `inc/functions.php` |
| `AGsCRM_get_documents_hook` | Agents-Addon: Dokumente-Hook | `inc/functions.php` |
| `AGsCRM_get_scheduler_hook` | Agents-Addon: Scheduler-Hook | `inc/functions.php` |
| `AGsCRM_get_users_customer_hook` | Agents-Addon: User/Kunden-Hook | `inc/functions.php` |
| `WPsCRM_send_mail_hook` | Mailversand-Trigger | `inc/functions.php` |
| `WPsCRM_totalBox` | Total-Box Ausgabe erweitern | `inc/functions.php` |
| `WPsCRM_add_rows_to_customer_form` | Kundenformular-Felder ergänzen | `inc/functions.php` |
| `wpscrm_newsletter_subscriber_added` | Nach Newsletter-Sync-Event | `inc/newsletter-integration.php` |
| `wpscrm_customer_added` | Kunde hinzugefügt (Brücken-Event) | `inc/newsletter-integration.php` |
| `wpscrm_customer_updated` | Kunde aktualisiert (Brücken-Event) | `inc/newsletter-integration.php` |
| `wpscrm_customer_deleted` | Kunde gelöscht (Brücken-Event) | `inc/newsletter-integration.php` |

### Filter-Hooks (`apply_filters`)

| Hook | Zweck | Quelle |
|---|---|---|
| `WPsCRM_accounting_tabs` | Buchhaltungs-Tabs erweitern | `inc/buchhaltung/index.php` |
| `WPsCRM_accounting_integrations` | Integrations-Registry für Buchhaltung | `inc/buchhaltung/tabs/integrations.php` |
| `wpscrm_settings_tabs` | Settings-Tabs erweitern | `inc/options.php` |
| `WPsCRM_add_User` | User-Erstellung abfangen/überschreiben | `inc/crm/kunde/insert.php`, `inc/functions.php` |
| `WPsCRM_sanitize` | Sanitizing zentral erweitern | `inc/functions.php` |
| `WPsCRM_wp_mail_content_type` | Content-Type für CRM-Mails | `inc/functions.php` |
| `WPsCRM_p_mail_from` | Mail-Absenderadresse filtern | `inc/functions.php` |
| `WPsCRM_wp_mail_from_name` | Mail-Absendername filtern | `inc/functions.php` |
| `wpscrm_pwa_manifest` | PWA Manifest erweitern | `inc/pwa/class-pwa-manager.php` |
| `wpscrm_pwa_shortcuts` | PWA Shortcuts erweitern | `inc/pwa/class-pwa-manager.php` |
| `wpscrm_pwa_menu_location` | Menüposition im App-Template | `inc/pwa/class-app-template.php` |
| `wpscrm_agent_role_slugs` | Agent-Rollenliste für App-Mode | `inc/pwa/class-app-template.php` |
| `wpscrm_scheduler_sources` | Support als Scheduler-Quelle ergänzen | `inc/support-integration.php` |
| `wpscrm_scheduler_data` | Scheduler-Daten erweitern | `inc/support-integration.php` |

## 2) Incoming Hooks (auf die das Plugin hört)

### PSOURCE / Drittplugin Hooks

| Hook | Verwendung | Datei |
|---|---|---|
| `prosites_transaction_record` | PS-Bloghosting Transaktionen als Einnahmen buchen | `inc/integrations/ps-bloghosting.php` |
| `mp_order_paid` | MarketPress Paid Orders als Einnahmen buchen | `inc/integrations/marketpress.php` |
| `support_ticket_created` | Support-Event Sync | `inc/support-integration.php` |
| `support_ticket_assigned` | Support-Event Sync | `inc/support-integration.php` |
| `support_ticket_status_changed` | Support-Event Sync | `inc/support-integration.php` |
| `support_ticket_updated` | Support-Event Sync | `inc/support-integration.php` |
| `support_ticket_reply_added` | Support-Event Sync | `inc/support-integration.php` |
| `enewsletter_crm_subscriber_added` | Newsletter-zu-CRM Sync | `inc/newsletter-integration.php` |
| `mm_suggest_users_args` | PM-User-Vorschläge filtern | `inc/classes/CRMpmIntegration.class.php` |
| `mm_before_send_message` | PM-Nachrichtenkontext anreichern | `inc/classes/CRMpmIntegration.class.php` |

### Integrationsbeispiele (Dokureferenz)

In `inc/integrations/ps-bloghosting-examples.php` zusätzlich gezeigt:

- `psts_extend`
- `psts_upgrade`
- `psts_downgrade`
- `psts_activated`

## 3) AJAX Action Catalog

### Legacy CRM AJAX (`wp_ajax_WPsCRM_*`)

`WPsCRM_get_clients2`, `WPsCRM_get_clients3`, `WPsCRM_get_client_contacts`, `WPsCRM_get_client_schedule`, `WPsCRM_get_agenda_item`, `WPsCRM_delete_agenda_item`, `WPsCRM_save_client_contact`, `WPsCRM_delete_client_contact`, `WPsCRM_delete_activity`, `WPsCRM_get_products`, `WPsCRM_get_products_document`, `WPsCRM_get_documents`, `WPsCRM_get_documents_for_customer`, `WPsCRM_delete_document_row`, `WPsCRM_save_pdf_document`, `WPsCRM_make_invoice`, `WPsCRM_get_scheduler`, `WPsCRM_get_client_scheduler`, `WPsCRM_get_client_info`, `WPsCRM_save_activity`, `WPsCRM_get_product_manual_info`, `WPsCRM_get_CRM_users`, `WPsCRM_get_CRM_users_customer`, `WPsCRM_get_CRM_display_name`, `WPsCRM_get_registered_roles`, `WPsCRM_add_rule`, `WPsCRM_edit_rule`, `WPsCRM_delete_rule`, `WPsCRM_edit_step`, `WPsCRM_save_todo`, `WPsCRM_save_appuntamento`, `WPsCRM_save_annotation`, `WPsCRM_delete_annotation`, `WPsCRM_reload_annotation`, `WPsCRM_register_invoices`, `WPsCRM_import_customers`, `WPsCRM_is_user_registrable`, `WPsCRM_mail_to_customer`, `WPsCRM_save_client`, `WPsCRM_syncro_newsletter`, `WPsCRM_save_crm_user_fields`, `WPsCRM_view_activity_modal`, `WPsCRM_update_options_modal`, `WPsCRM_scheduler_update`, `WPsCRM_dismiss_notice`, `WPsCRM_save_client_partial`, `WPsCRM_set_payment_status`, `WPsCRM_reverse_invoice`.

Nopriv:

- `wp_ajax_nopriv_WPsCRM_is_user_registrable`

### Settings/Buchhaltung AJAX

- `wp_ajax_wpscrm_get_terms`
- `wp_ajax_wpscrm_add_term`
- `wp_ajax_wpscrm_delete_term`
- `wp_ajax_wpscrm_add_expense`
- `wp_ajax_wpscrm_add_income`
- `wp_ajax_wpscrm_delete_transaction`

### Timetracking AJAX

- `wp_ajax_wpscrm_start_timer`
- `wp_ajax_wpscrm_stop_timer`
- `wp_ajax_wpscrm_pause_timer`
- `wp_ajax_wpscrm_get_active_timer`
- `wp_ajax_wpscrm_update_timer_entry`
- `wp_ajax_wpscrm_delete_timer_entry`
- `wp_ajax_crm_add_manual_time`

### Frontend/API AJAX (`wp_ajax_crm_*`)

- `crm_create_frontend_page`
- `crm_customer_get_documents`
- `crm_customer_download_pdf`
- `crm_customer_get_summary`
- `crm_customer_get_invoices` (+ `nopriv`)
- `crm_create_todo`
- `crm_create_appointment`
- `crm_create_quotation`
- `crm_create_invoice`
- `crm_create_proforma`
- `crm_agent_timetracking_toggle`
- `crm_agent_get_stats`
- `crm_agent_get_active_tracking`
- `crm_toggle_task`
- `crm_get_agent_customers`
- `crm_get_customer_data`
- `crm_frontend_save_customer`
- `crm_get_customer_documents`
- `crm_get_customer_contacts`
- `crm_frontend_save_contact`

Hinweis: Einige `crm_*` Hooks sind mehrfach registriert (API + Modulklasse) und sollten bei Refactoring auf Kollisionen geprüft werden.

### PWA/WebApp AJAX

- `wp_ajax_wpscrm_subscribe_push`
- `wp_ajax_wpscrm_unsubscribe_push`
- `wp_ajax_wpscrm_save_push_prefs`
- `wp_ajax_wpscrm_create_ticket`
- `wp_ajax_wpscrm_add_ticket_reply`
- `wp_ajax_wpscrm_vote_faq` (+ `wp_ajax_nopriv_wpscrm_vote_faq`)

## 4) WordPress Lifecycle Hooks (Core Integration)

Hauptsächlich verwendet: `plugins_loaded`, `init`, `admin_init`, `admin_menu`, `admin_head`, `admin_footer`, `template_redirect`, `wp_head`, `wp_footer`, `wp_enqueue_scripts`, `widgets_init`, `login_redirect`, `template_include`, `show_user_profile`, `edit_user_profile`, `personal_options_update`, `edit_user_profile_update`.

## 5) Dev-Checkliste für neue Integration

1. Integration über `WPsCRM_accounting_integrations` registrieren
2. Externen Payment-/Order-Hook anbinden
3. Idempotenz und Duplikat-Guard umsetzen
4. Datenmapping (Netto/Steuer/Brutto, Datum, Referenzen) definieren
5. AJAX-Endpoints mit Nonce und Capability absichern
6. Eigene Extension-Hooks (`do_action`/`apply_filters`) dokumentieren

## 6) Beispiel: Eigene Integration andocken

```php
add_filter('WPsCRM_accounting_integrations', function($integrations) {
  $integrations['my-plugin'] = array(
    'name' => 'My Plugin',
    'plugin' => 'my-plugin/my-plugin.php',
    'status' => 'available',
    'fields' => array(
      'sync_enabled' => array(
        'type' => 'checkbox',
        'label' => __('Automatische Synchronisation aktivieren', 'cpsmartcrm'),
      ),
    ),
  );

  return $integrations;
});

add_action('my_plugin_order_paid', 'my_sync_into_wpscrm');

function my_sync_into_wpscrm($order) {
  if (empty($order)) {
    return;
  }

  // Mapping + Insert in smartcrm_incomes
}
```

---

## Seiten-Navigation

<p><a href="integrationen-api-erweiterbarkeit.html">⬅️ Zurück</a> · <a href="index.html">🏠 Übersicht</a> · <a href="pwa-frontend-module.html">Weiter ➡️</a></p>
