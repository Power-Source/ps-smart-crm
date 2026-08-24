=== PS Smart Business ===
Contributors: PSOURCE
Tags: crm, invoices, accounting, business, customers, quotes
Requires at least: 4.2
Tested up to: 7.1.0
Requires PHP: 7.4
ClassicPress: 2.7.1
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

PS Smart Business ist eine modulare Business-Suite für WordPress und ClassicPress. Verwalte Kunden, Aufgaben, Angebote, Rechnungen und Buchhaltung zentral in deinem eigenen System.

== Description ==

PS Smart Business verbindet CRM, Dokumentenverwaltung und betriebliche Prozesse in einer übersichtlichen Oberfläche.

Statt verschiedene Systeme für Kundenverwaltung, Termine, Rechnungen und interne Abläufe zu verwenden, kannst du deine wichtigsten Geschäftsprozesse direkt in WordPress oder ClassicPress verwalten.

== Funktionen ==

= CRM =

* Kundenverwaltung mit übersichtlichem Raster
* Eigene CRM-Agent-Rolle für Teammitglieder
* Aufgaben- und Terminverwaltung
* Kundenbezogene Zeitleiste für Notizen und Aktivitäten
* TODO- und Terminstatus
* E-Mail- und Dashboard-Benachrichtigungen
* Benachrichtigungen für einzelne Benutzer oder WordPress-Rollen
* Individuelle Benachrichtigungsregeln
* Mehrstufige Benachrichtigungen für langfristige Vorgänge
* Kundenimport per CSV
* Filter-, Sortier- und Gruppierungsfunktionen

= Angebote & Rechnungen =

* Angebote und Rechnungen mit beliebig vielen Positionen
* PDF-Erstellung
* PDF-Download und Speicherung auf dem Server
* Eigenes Logo und anpassbare PDF-Kopfbereiche
* Konfigurierbare Zahlungsfristen
* Benachrichtigungen bei fälligen Zahlungen
* Interne Kommentare
* Touch-fähige Unterschrift für Angebote
* Registrierung von Rechnungen
* Individueller Startwert für die Rechnungsnummerierung
* Buchhaltungsfunktionen und Erweiterungsmöglichkeiten

= Buchhaltung =

PS Smart Business bietet grundlegende Funktionen für die Erfassung und Auswertung geschäftlicher Vorgänge.

Die Buchhaltung kann über Erweiterungen und Integrationen mit anderen Plugins verbunden werden, sodass Umsätze und weitere Geschäftsdaten zentral weiterverarbeitet werden können.

= Erweiterbar =

PS Smart Business ist modular aufgebaut und lässt sich an individuelle Anforderungen anpassen.

* Erweiterbare Integrationen
* API- und Erweiterungsmodule
* Frontend-Module
* PWA-Komponenten
* Projektspezifische Erweiterungen
* Pluggable Architektur

== Installation ==

1. Lade PS Smart Business herunter.
2. Installiere das Plugin über den WordPress-/ClassicPress-Adminbereich oder kopiere es nach `/wp-content/plugins/`.
3. Aktiviere das Plugin.
4. Öffne den neuen Bereich von PS Smart Business im Adminbereich.
5. Passe die Einstellungen an deine Anforderungen an.

== Unterstützung ==

Du hast einen Fehler gefunden, eine Idee für eine Verbesserung oder möchtest bei der Weiterentwicklung helfen?

Dann schau im PSOURCE-Projekt vorbei oder eröffne ein Issue im Repository.

== Übersetzungen ==

PS Smart Business ist für Übersetzungen vorbereitet.

Eigene Übersetzungen solltest du nicht direkt im Plugin-Verzeichnis bearbeiten. Änderungen dort können bei einem Plugin-Update überschrieben werden.

Lege deine Übersetzungsdateien stattdessen unter:

`/wp-content/languages/plugins/`

ab.

== Changelog ==

= 1.1.4 =
* Parsley Fehler auf Einstellungsseite behoben
* Dokumentationslinks auf neues PSOURCE Wiki umgelegt
* Fix: Vorsteueranzeige bei Kleinunternehmern korrigiert
* Buchhaltungs UI angepasst

= 1.1.3 =
* Fix: Fehlende Abhängigkeiten ergänzt

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