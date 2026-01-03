# PS-Bloghosting & PS-Smart-CRM Integration - Dokumentations-Index

Willkommen zur umfassenden Dokumentation für die Integration von **PS-Bloghosting Zahlungssystem** mit dem **PS-Smart-CRM Plugin**.

---

## 📚 DOKUMENTATIONEN

### 1. **ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md** ⭐ HAUPTDOKUMENTATION
Umfassende technische Anleitung zur PS-Bloghosting Zahlungs-Integration mit detaillierten Informationen zu:
- Alle Zahlungs-Hooks und deren Parameter
- Datenbank-Tabellenstrukturen
- Wie man an Zahlungsdaten kommt
- Transaction-Objekt Struktur
- Gateway-spezifische Implementierungen
- SQL-Queries für Zahlungsdaten

**📍 Datei:** `ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md`

---

### 2. **ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md** ⚡ QUICK-REFERENCE
Schnelle Nachschlage-Checkliste mit:
- Haupthooks Zusammenfassung
- Datenbank-Tabellen Übersicht
- Zahlungsdaten Auslesen (Code-Beispiele)
- Gateway-spezifische Hooks
- Integration in Smart-CRM (Template-Code)
- Zahlungs-Struktur Übersicht
- Debugging-Tipps

**📍 Datei:** `ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md`

---

### 3. **ps-bloghosting-examples.php** 💻 CODE-BEISPIELE
Praktische PHP-Code-Beispiele zur sofortigen Verwendung:

#### Beispiele im Datei
1. Zahlungs-Hook Implementierung
2. Abonnement-Verlängerungs-Hook
3. Level-Upgrade/Downgrade Tracking
4. Zahlungsdaten auslesen (10+ Funktionen)
5. Zahlungs-Daten transformieren
6. Hilfsfunktionen für CRM-Verarbeitung
7. Testing & Debugging
8. CRM-Tabellen erstellen
9. Weitere Beispiele

**📍 Datei:** `inc/integrations/ps-bloghosting-examples.php`

---

### 4. **ZAHLUNGS_SQL_REFERENZ.sql** 🗄️ DATABASE-QUERIES
Umfangreiche SQL-Query-Sammlung zur Datenbank-Analyse mit Beispielen für:
- Zahlungs-Transaktionen Abfragen
- Abonnements & Blogs
- Einnahmen-Analyse (täglich, wöchentlich, monatlich)
- Benutzer & Kontakt-Informationen
- Statistiken & Reports
- Performance-Optimierungen
- Export-Queries

**📍 Datei:** `ZAHLUNGS_SQL_REFERENZ.sql`

---

## 🎯 EINSTIEGSPUNKTE NACH ANWENDUNGSFALL

### 🔌 Ich möchte einen Hook registrieren
→ **ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md** (Abschnitt: Integration in Smart-CRM)
→ **ps-bloghosting-examples.php** (Beispiele 1-4)

### 📊 Ich muss Zahlungsdaten abrufen
→ **ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md** (Abschnitt: Zahlungsdaten Auslesen)
→ **ps-bloghosting-examples.php** (Beispiele 5-10)
→ **ZAHLUNGS_SQL_REFERENZ.sql** (Zahlungs-Transaktionen & Abonnements)

### 💾 Ich brauche SQL-Queries
→ **ZAHLUNGS_SQL_REFERENZ.sql** (alle Abschnitte)

### 🛠️ Ich debugge Probleme
→ **ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md** (Debugging-Tipps)
→ **ps-bloghosting-examples.php** (Beispiel 14-15)

### 📈 Ich analysiere Einnahmen
→ **ZAHLUNGS_SQL_REFERENZ.sql** (Einnahmen-Analyse & Reports)

### 🏗️ Ich baue neue CRM-Features
→ **ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md** (gesamtes Dokument)
→ **ps-bloghosting-examples.php** (Beispiele 11-12)

---

## 🔑 WICHTIGSTE HOOKS

| Hook | Auslöser | Nutzen |
|------|----------|--------|
| `prosites_transaction_record` | Zahlungsaufzeichnung | Transaction-Objekt empfangen |
| `psts_extend` | Abonnement-Verlängerung | Blog-ID, neues Ablaufdatum, Level |
| `psts_upgrade` | Level-Upgrade | Alte/neue Level-Info |
| `psts_downgrade` | Level-Downgrade | Alte/neue Level-Info |
| `wp_ajax_nopriv_psts_stripe_webhook` | Stripe Webhook | Stripe-Zahlungs-Events |
| `wp_ajax_nopriv_psts_pypl_ipn` | PayPal IPN | PayPal-Zahlungs-Events |

---

## 💾 WICHTIGSTE TABELLEN

| Tabelle | Inhalt | Primary Key |
|---------|--------|-------------|
| `wp_pro_sites_transactions` | Alle Zahlungen | `id` |
| `wp_pro_sites` | Abonnement-Status | `blog_ID` |
| `wp_pro_sites_signup_stats` | Aktions-Statistiken | `action_ID` |

---

## 🚀 SCHNELL-START SETUP

### 1. Hook registrieren
```php
add_action( 'prosites_transaction_record', function( $transaction ) {
    // Zahlung verarbeiten
    error_log( 'Zahlung: ' . $transaction->invoice_number );
} );
```

### 2. Zahlungsdaten abrufen
```php
$subscription = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->base_prefix}pro_sites WHERE blog_ID = %d",
    $blog_id
) );
echo $subscription->total; // Zahlungsbetrag
```

### 3. SQL ausführen
```sql
SELECT * FROM wp_pro_sites_transactions
WHERE transaction_date = '2024-01-01'
ORDER BY transaction_date DESC;
```

---

## 📖 DOKUMENTATIONS-STRUKTUR

```
ps-smart-crm/docs/
├── ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md    ← Hauptdokumentation
├── ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md     ← Quick Reference
├── ZAHLUNGS_SQL_REFERENZ.sql             ← SQL-Queries
└── README.md                              ← Diese Datei

ps-smart-crm/inc/integrations/
└── ps-bloghosting-examples.php           ← Code-Beispiele
```

---

## 🔗 RELEVANTE PLUGIN-DATEIEN

### PS-Bloghosting Hauptdateien

| Datei | Beschreibung |
|-------|-----------|
| `/pro-sites.php` | Hauptplugin, Hook-Definitionen |
| `/pro-sites-files/lib/ProSites/Helper/Transaction.php` | Transaction-Verarbeitung |
| `/pro-sites-files/gateways/gateway-stripe.php` | Stripe Gateway |
| `/pro-sites-files/gateways/gateway-paypal-express-pro.php` | PayPal Gateway |
| `/pro-sites-files/gateways/gateway-2checkout.php` | 2Checkout Gateway |

### Smart-CRM Integrations-Dateien

| Datei | Beschreibung |
|-------|-----------|
| `/inc/integrations/ps-bloghosting-examples.php` | Code-Beispiele & Helper-Funktionen |
| `/docs/ZAHLUNGS_*.md` | Alle Dokumentations-Dateien |

---

## 📋 HÄUFIG VERWENDETE FUNKTIONEN

### Aus ps-bloghosting-examples.php

```php
// Abonnement abrufen
pscrm_get_blog_subscription( $blog_id )

// Zahlungshistorie abrufen
pscrm_get_blog_payments( $blog_id, $limit )

// Statistiken abrufen
pscrm_get_payment_stats( $start_date, $end_date )

// Zahlungen nach Gateway
pscrm_get_payments_by_gateway( $start_date, $end_date )

// Ablauf-Benachrichtigungen
pscrm_get_expiring_soon_subscriptions( $days )

// Test-Hook ausführen
pscrm_test_payment_hook()

// Debug-Logs anzeigen
pscrm_debug_payment_logs( $blog_id )
```

---

## 🎓 LEARNING PATH

### Anfänger
1. **ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md** (5 Min)
2. **ps-bloghosting-examples.php** - Beispiele 1-4 (10 Min)
3. Einen Hook im eigenen Code registrieren (15 Min)

### Fortgeschrittene
1. **ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md** (20 Min)
2. **ps-bloghosting-examples.php** - Beispiele 5-12 (20 Min)
3. Eigene Integration bauen (30+ Min)

### Experte
1. PS-Bloghosting Source Code durchlesen
2. Stripe/PayPal API-Dokumentation
3. Custom Gateways implementieren

---

## ❓ FAQ

### F: Welcher Hook für jede Zahlung?
**A:** `prosites_transaction_record` - siehe ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md

### F: Wo speichert PS-Bloghosting Zahlungen?
**A:** `wp_pro_sites_transactions` Tabelle - siehe ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md

### F: Wie bekomme ich Zahlungs-Daten?
**A:** SQL-Query oder Hook - siehe ps-bloghosting-examples.php (Beispiele 5-10)

### F: Wie aktiviere ich Stripe Webhooks?
**A:** Automatisch - siehe ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md (Abschnitt 6)

### F: Wo finde ich Code-Beispiele?
**A:** `/inc/integrations/ps-bloghosting-examples.php`

---

## 🐛 FEHLERBEHANDLUNG

Wenn du auf Probleme stößt:

1. **Hook wird nicht ausgelöst?**
   - Siehe `pscrm_test_payment_hook()` in ps-bloghosting-examples.php
   - Error-Log prüfen: `error_log( print_r( $data, true ) );`

2. **Daten fehlen?**
   - SQL-Query prüfen (ZAHLUNGS_SQL_REFERENZ.sql)
   - Tabelle existiert? (`SHOW TABLES LIKE '%transactions%'`)
   - Blog-ID gültig?

3. **Gateway funktioniert nicht?**
   - Siehe Gateway-spezifische Sektion in ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md
   - Webhook-Handler aktiviert?

---

## 📞 SUPPORT & QUELLEN

- **PS-Bloghosting Doku:** Siehe `/pro-sites-files/docs/`
- **Smart-CRM Doku:** Siehe `/docs/`
- **Stripe API:** https://stripe.com/docs
- **PayPal API:** https://developer.paypal.com

---

## 📝 LIZENZ & HINWEISE

Diese Dokumentation ist für das PS-Bloghosting und PS-Smart-CRM Plugin erstellt worden.

**Stand:** Januar 2026
**Version:** 1.0

---

## 🚀 NÄCHSTE SCHRITTE

1. ✅ Diese README lesen (fertig!)
2. 📖 ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md durchlesen
3. 💻 Code-Beispiele aus ps-bloghosting-examples.php verwenden
4. 🔧 Hooks in eigenem Code registrieren
5. 🧪 Mit Test-Zahlungen testen
6. 📊 SQL-Queries verwenden für Analyse

---

**Viel Erfolg bei der Integration! 🎉**

---

**Fragen zur Dokumentation? Siehe ZAHLUNGS_INTEGRATIONS_ANLEITUNG.md oder Debug-Tipps in ZAHLUNGS_HOOKS_SCHNELLREFERENZ.md**
