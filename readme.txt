=== PS Smart CRM ===
Author: PSOURCE
Tags: crm, invoices, todo
Requires at least: 4.2
Tested up to: 6.8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
PS Smart CRM erweitert Classic/ClassicPress um ein leistungsstarkes CRM mit vielen Funktionen. Verwalte Kunden, Rechnungen, TODO, Termine und Benachrichtigungen.

== Description ==


PS Smart CRM deckt eine Vielzahl von "Büroverwaltungsfunktionen" ab. 

CRM-Verwaltung: 

- Kundenarchiv-Raster
- Benutzerdefinierte Rolle "CRM-Agent
- Terminplaner für Aufgaben und Termine
- Zeitleiste für Notizen
- TODO / Termin Status Update
- Benachrichtigungssystem (E-Mail und Dashboard)
- Benachrichtigung an einzelne Benutzer(innen)
- Benachrichtigung an bestimmte WP-Rolle(n)
- Benutzerdefinierte Benachrichtigungsregeln
- Aufeinanderfolgende Benachrichtigungsschritte (für mittel-/langfristig auslaufende Dienstleistungen)
- Kunden CSV-Import 


Verwaltung von Rechnungen/Angeboten

- Dynamische Erstellung von Rechnungen/Angeboten mit mehrzeiligen Produkten
- Erstellung von Rechnungen/Angeboten im .pdf-Format
- PDF herunterladen und auf dem Server speichern
- Benutzerdefiniertes LOGO
- Benutzerdefinierte Ausrichtung der Kopfelemente in der PDF-Vorlage
- Konfigurierbare Fälligkeitsdaten für Zahlungen
- Benachrichtigung bei Ablauf der Zahlung
- Interne Kommentare und Angeboten
- Benutzerdefinierte Canvas-Signatur im Angebot (Touch-kompatibel)
- Registrierung von Rechnungen
- Benutzerdefinierter Startwert für die Nummerierung (Sie können zu jedem beliebigen Zeitpunkt des Jahres mit einer von 1 abweichenden Startnummer beginnen, in Übereinstimmung mit Ihrer Buchhaltung)


Alle Datensätze in den Rastern sind mit Filter-/Gruppierungs-/Sortierfunktionen für eine schnelle Nutzung ausgestattet.

Alle Informationen in den Rastern sind mit Symbolen/Farben visuell aufgewertet, um immer einen schnellen Überblick zu gewährleisten.

Wenn Sie uns Feedback schicken möchten, benutzen Sie das Support-Forum, wenn Sie an der Übersetzung in weitere Sprachen teilnehmen möchten, schreiben Sie uns eine Nachricht an info [at] smart-cms.smart-cms.n3rds.work/
Wichtig: Wenn Sie .mo/.po-Dateien im Plugin-Ordner "languages" ändern, können Ihre Änderungen beim nächsten Update verloren gehen. Um dies zu verhindern, kopieren Sie Ihre .mo/.po-Dateien in den Ordner "/wp-content/languages/plugins".


== Screenshots ==

1. Die Agenda-Ansicht mit Terminen und Aufgaben, die je nach Status unterschiedliche Stile haben (zu erledigen, erledigt, abgesagt)
2. Das Kundenformular mit allen Informationen und Quick-ToDo sowie einer Zusammenfassung der Aktivitäten und der zugehörigen Dokumente
3. Die Dokumentenliste gruppiert nach Typ (Rechnungen / Angebote)
4. Das PDF-Dokument, das heruntergeladen und auf dem Server gespeichert werden kann, um es später zu durchsuchen
5. Das "Benachrichtigungsregeln"-Screeen, mit dem Sie eine Reihe von Benachrichtigungsschritten erstellen können, die auf eine Regel anzuwenden sind
6. Das Popup-Fenster "Online-Hilfe", das Informationen zu jedem Bildschirm enthält, auf dem Sie sich gerade befinden


== Changelog ==

= 1.0.0 =

* Many fixes
* KendoUI Remove: 75%
* Angepasst auf Deutsches Steuerrecht

= 1.5.3 =

* Codefixes for PhP8.2
* Security-fixes
* New Docs

= 1.5.2 =
* Minor Bugfixes

= 1.5.16 =
* Bug fixed dbDelta at plugin activation + missing file
* Added discount type in invoices ( %  or value)

= 1.5.14 =
* Bug fixed php notices

= 1.5.13 =
* Bug fixed missing class upgrade from 1.5.09


= 1.5.11 =

* Added compatibility with  [thenewsletterplugin](https://www.thenewsletterplugin.com/ "Newsletter plugin for wordpress") with record bulk import
* Improved timeline log view for customer

= 1.5.09 =

* Bug fixed db upgrade from 1.5.08

= 1.5.08 =

* Added support for BRL, CNY, JPY, INR currencies and date format
* Bug fixed document custom numbering
* Added  categories , interests and origins in CSV import + all missing fields


= 1.5.07 =

* Bug fixed saving customer with some general options selected

= 1.5.06 =

* Bug fixed customer form php open tags 

= 1.5.05 =

* Bug fixed error on activation for PHP < 5.3


= 1.5.04 =

* CSS improvement in document printable version with some options added
* Usability improved in customer edit/insert
* Bug fixed in TODO status update


= 1.5.03 =

* Bug fixed in category update at setup 


= 1.5.02 =

* Bug fixed product description in invoices and quotes not saving correctly 

= 1.5.01 =

* Minor bug fix unespected output in send mail via cron



= 1.5.0 =

* UI improvement in grids: create TODO, appointments and activities directly from customers grid
* Implemented better taxonomies and terms management for customers
* Implemented split Invoices / Quotes in separate grids


= 1.4.9 =

* Implemented: automatic reset for progressive numbering of invoices and quotes at new year change



= 1.4.8 =

* Bug fixed utf8 encoding in customer address field
* Featured added: Greek culture support for js widgets ( calendar, datepicker)



= 1.4.7 =

* Bug fixed in todo and appointments creations for GERMAN language
* Featured added: send instant notification at todo / appointments creation


= 1.4.6 =

* Minor Bug fixed in todo and appointments notification scheduled time


= 1.4.5 =

* Feature added: Quick toggle invoices payment status (paid/unpaid) from document grid 
* Bug fixed at setup for some mysql configuration
* Bug fixed: PDF documents  export not working



= 1.4.4 =

* Feature added: Date range selection improved in todo and document grids 


= 1.4.3 =

* Fixed: Customer selection in appointment/todo creation


= 1.4.2 =

* Fixed: import csv not working properly
* Fixed: missing csv example file


= 1.4.1 =

* Fixed: CRM-Agents can only manage their customers and their customers' documents
* Optionally possible to choose whether to print totals in quotes or not
* Minor bug fixes


= 1.4 =

* Feature added: csv customers import 
* Added link to CRM important sections in the main WP admin menu 


= 1.2 =

* bug fixed for currencies and date format


= 1.1 =

* ENGLISH TRANSLATION


= 1.0 =

* First version


== Upgrade Notices ==