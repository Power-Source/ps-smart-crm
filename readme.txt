=== PS SmartBusiness ===
Author: PSOURCE
Tags: crm, invoices, todo
Requires at least: 4.2
Tested up to: 6.8.1
ClassicPress: 2.6.0
Stable tag: 1.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
PS Smart Business ist die smarte Business Suite für Classic/ClassicPress: Kunden, Dokumente, Buchhaltung, Prozesse und Team-Workflows in einem System.

== Description ==


PS Smart Business deckt eine Vielzahl moderner Büro- und Geschäftsprozesse ab und wächst vom klassischen CRM zur modularen Business Suite.

CRM-Verwaltung:

- Kundenarchiv-Raster
- Benutzerdefinierte Rolle "CRM-Agent"
- Terminplaner für Aufgaben und Termine
- Zeitleiste für Notizen
- TODO / Termin Status Update
- Benachrichtigungssystem (E-Mail und Dashboard)
- Benachrichtigung an einzelne Benutzer(innen)
- Benachrichtigung an bestimmte WP-Rolle(n)
- Benutzerdefinierte Benachrichtigungsregeln
- Aufeinanderfolgende Benachrichtigungsschritte (für mittel-/langfristig auslaufende Dienstleistungen)
- Kunden CSV-Import
- Dashboard- und Grid-basierte Arbeitsoberfläche für schnelle Team-Prozesse


Verwaltung von Rechnungen/Angeboten

- Dynamische Erstellung von Rechnungen/Angebote mit mehrzeiligen Produkten
- Erstellung von Rechnungen/Angebote im .pdf-Format
- PDF herunterladen und auf dem Server speichern
- Benutzerdefiniertes LOGO
- Benutzerdefinierte Ausrichtung der Kopfelemente in der PDF-Vorlage
- Konfigurierbare Fälligkeitsdaten für Zahlungen
- Benachrichtigung bei Ablauf der Zahlung
- Interne Kommentare und Angebote
- Benutzerdefinierte Canvas-Signatur im Angebot (Touch-kompatibel)
- Registrierung von Rechnungen
- Benutzerdefinierter Startwert für die Nummerierung (Sie können zu jedem beliebigen Zeitpunkt des Jahres mit einer von 1 abweichenden Startnummer beginnen, in Übereinstimmung mit Ihrer Buchhaltung)
- Buchhaltung - Verbinde andere Plugins mit dem CRM und erfasse alle Umsätze in deiner Buchhaltung

Suite-Erweiterungen:

- Erweiterbare Integrationen und API-Module
- PWA-Bausteine für App-ähnliche Nutzung
- Frontend-Module für kundennahe Prozesse
- Pluggable Architektur für projektspezifische Erweiterungen

Alle Datensätze in den Rastern sind mit Filter-/Gruppierungs-/Sortierfunktionen für eine schnelle Nutzung ausgestattet.

Alle Informationen in den Rastern sind mit Symbolen/Farben visuell aufgewertet, um immer einen schnellen Überblick zu gewährleisten.

Wenn Sie uns Feedback schicken möchten, benutzen Sie das Support-Forum, wenn Sie an der Übersetzung in weitere Sprachen teilnehmen möchten, schreiben Sie uns eine Nachricht an info [at] smart-cms.smart-cms.n3rds.work/
Wichtig: Wenn Sie .mo/.po-Dateien im Plugin-Ordner "languages" ändern, können Ihre Änderungen beim nächsten Update verloren gehen. Um dies zu verhindern, kopieren Sie Ihre .mo/.po-Dateien in den Ordner "/wp-content/languages/plugins".


== Changelog ==

= 1.1.2 =

* Multisite-Reparatur: Bestehende Tabellen `smartcrm_agent_roles` und `smartcrm_agents` werden bei Bedarf auf InnoDB umgestellt
* Fehlerbehebung: Foreign Key `fk_agent_role` wird für bestehende Agenten-Tabellen automatisch nachgezogen, wenn die Daten konsistent sind
* Stabilität: Reparatur läuft jetzt auch im DB-Check für bereits aktivierte Subsites und verhindert wiederkehrende FK-Fehler im Log
* Admin-Fix: DataTables wird auf der CRM-Startseite wieder korrekt geladen, sodass Dashboard-Tabellen ohne JavaScript-Fehler initialisieren
* Rollen-Fix: Bestehende Standardrollen erhalten fehlende Rechte-Capabilities automatisch nachträglich, ohne individuelle Einstellungen zu überschreiben

= 1.1.1 =

* Multisite-Fix: Tabellenanlage verwendet jetzt konsistent den Site-Prefix (`$wpdb->prefix`) statt des globalen Präfixes
* Fehlerbehebung: Fehlende Tabelle `smartcrm_agent_roles` wird bei DB-Check erkannt und Setup erneut ausgeführt
* Stabilität: Schutz vor wiederholten DB-Fehlern, wenn Agentenrollen-Tabelle auf einer Site noch nicht existiert

= 1.1.0 =

* Positionierung als Business Suite statt reinem CRM geschärft
* Dokumentationsstruktur gestartet und Übersicht ausgebaut
* Fokus auf modulare Bereiche: CRM, Dokumente, Buchhaltung, PWA und Integrationen
* Basis für weiteren Ausbau der Anwender- und Admin-Dokumentation gelegt

= 1.0.1 =

* Quittungsverwaltung hinzugefügt

= 1.0.0 =

* Release