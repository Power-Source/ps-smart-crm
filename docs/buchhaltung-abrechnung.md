---
layout: psource-theme
title: "Buchhaltung & Abrechnung"
---

<h2 align="center" style="color:#38c2bb;">📚 PS Smart Business</h2>

<div class="menu">
	<a href="index.html" style="color:#38c2bb;">🏠 Übersicht</a>
	<a href="dokumentation.html" style="color:#38c2bb;">📝 Dokumentation</a>
	<a href="https://github.com/cp-psource/ps-smart-crm/discussions" style="color:#38c2bb;">💬 Forum</a>
	<a href="https://github.com/Power-Source/ps-smart-crm/releases" style="color:#38c2bb;">⬇️ Releases</a>
</div>

# Buchhaltung & Abrechnung

Dieser Bereich beschreibt, wie Umsatzdaten strukturiert erfasst und für Buchhaltungsprozesse vorbereitet werden.

## Ziel

Ein konsistenter Datenfluss zwischen CRM-Dokumenten, registrierten Rechnungen und angebundenen Systemen.

## Basisbausteine

- Registrierung von Rechnungen
- Fortlaufende Nummerierung mit validem Startwert
- Zuordnung von Zahlungen und Status
- Erweiterung über Buchhaltungs-Module

## Monatsabschluss-Check (MVP)

1. Alle offenen Rechnungen auf Status prüfen
2. Überfällige Zahlungen markieren
3. Fehlende Rechnungsregistrierungen ergänzen
4. Summen plausibilisieren (Brutto/Netto/Steuer)
5. Übergabe in externes Reporting vorbereiten

## Datenqualität

- Eindeutige Rechnungsnummer ohne Doppelungen
- Vollständige Kundenzuordnung
- Klarer Zahlungsstatus
- Änderungs- und Korrekturpfad dokumentiert

## Integrationsgedanke

Die Suite kann externe Umsätze anbinden. Dokumentiere pro Integration:

- Datenquelle
- Mapping-Regeln
- Fehlerfälle
- Verantwortliche Person

## Admin-Checkliste

- Monatsabschluss-Routine im Team terminiert
- Offene und überfällige Rechnungen sichtbar gemacht
- Nummernlogik und Dublettenprüfung dokumentiert
- Verantwortlichkeit für Korrekturen festgelegt

## Praxisbeispiel

Zum Monatsende prüft das Team alle offenen Rechnungen, markiert überfällige Fälle und ergänzt fehlende Registrierungen. Danach werden Netto-, Steuer- und Bruttosummen plausibilisiert. Ergebnis: saubere Übergabe an Steuerberatung oder externes Controlling.

---

## Seiten-Navigation

<p><a href="dokumente-rechnungen.html">⬅️ Zurück</a> · <a href="index.html">🏠 Übersicht</a> · <a href="integrationen-api-erweiterbarkeit.html">Weiter ➡️</a></p>
