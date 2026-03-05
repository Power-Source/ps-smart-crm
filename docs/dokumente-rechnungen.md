---
layout: psource-theme
title: "Dokumente, Angebote & Rechnungen"
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

# Dokumente, Angebote & Rechnungen

Dieses Modul bildet euren Dokumentenfluss von Angebot bis Rechnung inklusive PDF-Ausgabe ab.

## Was hier dokumentiert wird

- Angebots- und Rechnungserstellung
- Nummerierung, Präfix/Suffix, Startwerte
- Zahlungsziele und Fälligkeiten
- Interne Kommentare und Signatur-Prozesse

## Empfehlungs-Workflow

1. Angebot aus Kundenkontext erstellen
2. Positionen und Steuersätze prüfen
3. PDF erzeugen und intern freigeben
4. Angebot versenden, Rückmeldung dokumentieren
5. In Rechnung überführen oder neu ausstellen

## Dokumentstandards

- Einheitliche Nummernlogik pro Geschäftsjahr
- Klarer Textbaustein vor/nach Dokumentinhalt
- Prüfroutine vor Versand: Betrag, Frist, Empfänger

## Zahlung & Mahnlogik

- Fälligkeitsdatum pro Dokument setzen
- Erinnerungstage global definieren
- Eskalation über Rollenbenachrichtigungen planen

## Qualitäts-Checkliste

- Kopfbereich korrekt ausgerichtet (Logo/Adresse)
- Zahlungsart korrekt hinterlegt
- PDF gespeichert oder archiviert
- Interner Kommentar vorhanden (bei Sonderfällen)

## Admin-Checkliste

- Dokumentvorlagen und Textbausteine abgestimmt
- Zahlungsarten inklusive Tagewert gepflegt
- Mahnfristen global definiert
- Freigabeprozess für Angebots- und Rechnungsversand festgelegt

## Praxisbeispiel

Ein Angebot für einen Bestandskunden wird mit drei Positionen erstellt und als PDF intern freigegeben. Nach Kundenzusage wird das Angebot in eine Rechnung überführt, inklusive Zahlungsziel und Erinnerungstagen. Ergebnis: durchgängiger Dokumentenfluss ohne Medienbruch.

---

## Seiten-Navigation

<p><a href="crm-prozessmanagement.html">⬅️ Zurück</a> · <a href="index.html">🏠 Übersicht</a> · <a href="buchhaltung-abrechnung.html">Weiter ➡️</a></p>
