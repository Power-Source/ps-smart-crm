---
layout: psource-theme
title: "Einrichtung & Berechtigungen"
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

# Einrichtung & Berechtigungen

Diese Seite führt durch die Basis-Einrichtung von PS Smart Business als Business Suite.

## Zielbild

Nach der Einrichtung steht ein stabiles Grundsystem mit klaren Rollen, sauberen Stammdaten und nachvollziehbaren Benachrichtigungen.

## 1) Aktivierung & Erstkonfiguration

- Plugin aktivieren
- Grundkonfiguration im Admin-Bereich öffnen
- Pflichtwerte für Dokumente, Benachrichtigungen und Darstellung setzen
- Testkontakt anlegen

## 2) Rollenmodell definieren

Empfohlenes Minimum:

- **Administrator**: Systemkonfiguration, Freigaben, globale Sicht
- **CRM-Agent**: Operative Arbeit in Kunden-, Termin- und Dokumentenprozessen

Praxisregel: Rechte so eng wie möglich vergeben, nur bei Bedarf erweitern.

## 3) Pflicht-Checks vor Go-Live

- Absendername und Absender-E-Mail für Benachrichtigungen gesetzt
- Dokument-Nummerierung und Startwert geprüft
- Standard-Steuersatz geprüft
- Grid-Höhen und Listenansichten auf Team-Bedarf angepasst

## 4) Quick-Win Setup (15 Minuten)

1. Einen Kunden mit vollständigen Stammdaten anlegen
2. Einen Termin und eine Aufgabe erstellen
3. Ein Test-Angebot als PDF erzeugen
4. Benachrichtigung an eine Rolle auslösen

Wenn alle vier Schritte funktionieren, ist die Grundinstallation produktionsnah.

## Häufige Stolpersteine

- Nummerierung nach bereits erstellten Dokumenten ändern
- Zu breite Rollenrechte für Agenten
- Fehlende E-Mail-Absenderkonfiguration

## Admin-Checkliste

- Rollen und Rechte für Administrator und CRM-Agent geprüft
- Absendername und Benachrichtigungs-E-Mail gesetzt
- Dokument-Nummerierung inkl. Startwert festgelegt
- Testkontakt inkl. Testaufgabe und Testtermin angelegt

## Praxisbeispiel

Ein kleines Team startet mit zwei CRM-Agenten. Der Admin legt Rollen fest, setzt Standard-Steuersatz und Nummerierung und erstellt einen Testkunden. Danach wird ein Termin mit Reminder und ein Test-Angebot erzeugt. Ergebnis: Das Team kann am selben Tag produktiv starten und weiß, wer welche Verantwortung trägt.

---

## Seiten-Navigation

<p><a href="index.html">⬅️ Zurück</a> · <a href="index.html">🏠 Übersicht</a> · <a href="crm-prozessmanagement.html">Weiter ➡️</a></p>
