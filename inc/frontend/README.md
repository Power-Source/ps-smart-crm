# PS Smart CRM - Frontend System (Modular)

## 📁 Architektur

Das Frontend-System wurde von einem monolithischen zu einem modularen System refaktorisiert für bessere Wartbarkeit und Erweiterbarkeit.

```
inc/frontend/
├── init.php                          # Hauptinitialisierung & Helper-Funktionen
├── classes/
│   ├── Module_Base.php              # Basisklasse für alle Module
│   ├── User_Detector.php            # Zentrale User-Typ-Erkennung
│   ├── Frontend_Manager.php         # Orchest riert Module & Shortcodes
│   └── Frontend_Settings.php        # Settings Management
├── modules/
│   ├── agent-dashboard/
│   │   ├── Agent_Dashboard.php      # Hauptklasse
│   │   ├── handlers.php             # AJAX Handler
│   │   ├── views/
│   │   │   ├── index.php           # Main Layout
│   │   │   ├── timetracking.php    # Zeiterfassungs-Card
│   │   │   ├── tasks.php           # Aufgaben-Card
│   │   │   ├── inbox.php           # Postfach-Card (mit PM Integration)
│   │   │   └── profile.php         # Profil-Sektion
│   │   └── assets/
│   │       ├── style.css           # Modul-spezifisches CSS
│   │       └── script.js           # Modul-spezifisches JS
│   ├── customer-portal/
│   │   ├── Customer_Portal.php     # Hauptklasse
│   │   ├── handlers.php            # AJAX Handler
│   │   ├── views/
│   │   │   ├── index.php          # Main Layout mit Tabs
│   │   │   ├── invoices.php       # Rechnungen Tab
│   │   │   ├── quotations.php     # Angebote Tab
│   │   │   └── inbox.php          # Postfach Tab (mit PM Integration)
│   │   └── assets/
│   │       ├── style.css
│   │       └── script.js
│   └── guest-view/
│       ├── Guest_View.php          # Hauptklasse
│       ├── views/
│       │   └── index.php          # Login Screen
│       └── assets/
│           └── style.css
└── api/
    ├── agent-api.php               # Agent API Endpoints
    └── customer-api.php            # Customer API Endpoints
```

## 🚀 Wie es funktioniert

### 1. **Initialization (init.php)**
- Lädt alle Base Classes
- Lädt alle Module Classes
- Frontend Manager registriert sich hooks

### 2. **Frontend Manager (Singleton)**
- Lädt alle Module
- Registriert Shortcodes:
  - `[crm_agent_dashboard]` → Agent_Dashboard::render()
  - `[crm_customer_portal]` → Customer_Portal::render()
  - `[crm_guest_view]` → Guest_View::render()
- Queued Module Assets (CSS/JS)

### 3. **Module Base Class**
Jedes Modul erbt von `WPsCRM_Module_Base` und bekommt:
- `load_view()` - zum Laden von View-Dateien
- `enqueue_assets()` - zum Laden von CSS/JS
- `set_user_data() / get_user_data()` - für User Info
- `get_id() / get_name()` - für Modul Info

### 4. **User Detector**
Zentrale User-Typ-Erkennung mit Methoden:
- `get_type()` - Gibt `'agent'` oder `'customer'` oder `'guest'` zurück
- `is_agent()` - Prüft Agent-Status
- `is_customer()` - Prüft Kunden-Status
- `get_data()` - Gibt User-Daten (einschließlich Agent/Kunde spezifische)

### 5. **Each Module**
- Erbt von Module_Base
- Implementiert `render($atts)` Methode
- Lädt AJAX Handlers
- Rendert View mit User Data

## 🎯 Shortcodes

### Agent Dashboard
```
[crm_agent_dashboard]
```
Zeigt:
- **Für Agents**: Zeiterfassung, Aufgaben, Postfach, Profil
- **Für Kunden**: Info "bitte Kundenportal nutzen"
- **Für Gäste**: Login-Aufforderung

### Customer Portal
```
[crm_customer_portal]
```
Zeigt Tabs:
- **Invoices** (Rechnungen)
- **Quotations** (Angebote)
- **Inbox** (Postfach mit PM Integration)

Für Agents/Gäste werden entsprechende Hinweise gezeigt.

### Guest View
```
[crm_guest_view]
```
Einfache Login-Screen für nicht-angemeldete Benutzer.

## 📦 PM System Integration

Beide Dashboards (Agent & Customer) enthalten **echte PM Integration**:

```php
// In Views laden wir das echte PM System:
$pm_integration = WPsCRM_PM_Integration::get_instance();
if ($pm_integration->is_pm_active()) {
    echo do_shortcode('[message_inbox]');
}
```

## 🔧 Neue Module hinzufügen

```php
// 1. Erstelle folder: inc/frontend/modules/my-module/

// 2. Hauptklasse: inc/frontend/modules/my-module/My_Module.php
class WPsCRM_My_Module extends WPsCRM_Module_Base {
    protected $module_id = 'my-module';
    protected $module_name = 'My Module';
    
    public function render($atts = array()) {
        return $this->load_view('index', ['data' => 'value']);
    }
}

// 3. View: inc/frontend/modules/my-module/views/index.php
<?php echo $data; ?>

// 4. Registriere Klasse in Frontend_Manager::load_modules()
if (class_exists('WPsCRM_My_Module')) {
    $this->modules['my-module'] = new WPsCRM_My_Module();
}

// 5. Shortcode: [crm_my_module]
```

## 🎨 Assets Management

Jedes Modul laden seine eigenen Assets:
- CSS wird nur geladen wenn Modul rendert
- JS wird nur geladen wenn Modul rendert
- Verhindert Bloat und Performance-Probleme

```php
// In Modul:
public function enqueue_assets() {
    parent::enqueue_assets(); // Erledigt CSS/JS Queuing automatisch
}
```

## 🔐 Helper Funktionen (veraltete Kompatibilität)

Folgende Funktionen sind erhalten geblieben für Rückwärtskompatibilität:

```php
// User-Typ prüfen
wpscrm_is_user_agent($user_id);

// Kunde über Email finden
wpscrm_get_customer_by_email($email);

// Frontend Manager zugreifen
wpscrm_get_frontend_manager();
```

## ✅ Vorteile der neuen Struktur

| Aspekt | Alt | Neu |
|--------|-----|-----|
| **Datei-Größe** | 694 Zeilen | 40-150 Zeilen pro Datei |
| **Erweiterbarkeit** | Schwierig | Einfach - neue Module |
| **Wartbarkeit** | Kompliziert | Modular & sauber |
| **Generalisierung** | Keine | Module_Base |
| **Wiederverwendung** | Unmöglich | Views & Klassen |
| **Performance** | Monolith | Selective Loading |
| **Testing** | Schwierig | Module isoliert testen |

## 📝 Nächste Schritte

1. **Alte Dateien archivieren:**
   ```
   inc/frontend/modules/agent-dashboard.php → archiv/
   inc/frontend/modules/customer-portal.php → archiv/
   ```

2. **Testing durchführen:**
   - Agent Dashboard laden
   - Customer Portal laden
   - PM Integration testen
   - AJAX Handler testen

3. **Settings integrieren** (Frontend Tab in inc/options.php)

---

**Modullisierung abgeschlossen! 🎉**
