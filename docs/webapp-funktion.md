---
layout: psource-theme
title: "WebApp-Funktion (PWA/App-Modus)"
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

# WebApp-Funktion (PWA/App-Modus)

Diese Seite beschreibt die WebApp-Funktion von PS Smart Business: installierbare App, App-Modus ohne Theme, Offline-Verhalten und Push-Benachrichtigungen.

## Architektur im Überblick

Die WebApp besteht aus drei Kernbausteinen:

- **PWA Manager**: registriert Manifest- und Service-Worker-Endpunkte, Meta-Tags und Script-Registrierung
- **App Template**: rendert bei `?app=1` ein cleanes Standalone-Layout ohne WordPress-Theme
- **PWA Frontend JS**: steuert Install-Prompt, Push-Subscription und Offline-Status

## Endpunkte & URLs

Wichtige Endpunkte:

- `crm-manifest.json` → Web App Manifest
- `crm-service-worker.js` → Service Worker Script
- `/?app=1` → App-Modus (Standalone-Ansicht)

Typische Test-URLs:

- `https://deine-domain.tld/?app=1`
- `https://deine-domain.tld/crm-manifest.json`
- `https://deine-domain.tld/crm-service-worker.js`

## App-Modus (`?app=1`)

Wenn `?app=1` gesetzt ist, wird das normale Theme umgangen und ein spezielles App-Template geladen.

Verhalten im App-Modus:

- Login-Ansicht bei nicht eingeloggten Usern
- Rollen-Erkennung für Agent/Kunde
- Routing auf passende Dashboard-Ansicht
- Interne Links werden automatisch mit `app=1` ergänzt
- Admin-Bar wird ausgeblendet

## Manifest-Funktion

Das Manifest wird dynamisch erzeugt und enthält u. a.:

- App-Name, Kurzname, Beschreibung
- Start-URL und Scope
- Theme- und Background-Color
- Icons (uploadbar oder Fallback-Icons)
- optionale Shortcuts (z. B. Ticket-Quick-Action)

## Service Worker Funktion

Der Service Worker übernimmt:

- Installation & Aktivierung inkl. Cache-Versionierung
- Löschen alter Caches bei Versionswechsel
- Fetch-Strategie: **Network First**, Cache-Fallback
- Ausschluss sensibler Routen (`/wp-admin/`, Login, AJAX/API)
- Push-Notification Anzeige + Click-Handling
- Background Sync für offline gespeicherte Ticket-Antworten

## Push-Benachrichtigungen

Push wird über Browser-APIs + AJAX-Endpoints gesteuert.

Ablauf:

1. Permission anfragen
2. Push-Subscription im Browser erstellen
3. Subscription am Server speichern
4. Benachrichtigungen über Service Worker ausspielen

Hinweise:

- Push benötigt HTTPS (oder localhost)
- Für produktive Nutzung müssen VAPID-Keys korrekt gesetzt sein

## Admin-Einstellungen

Die WebApp/PWA-Einstellungen sind im CRM-Settings-Bereich konfigurierbar:

- App-Name, Kurzname, Beschreibung
- Theme-/Background-Farbe
- Icons (512x512, 192x192)
- Test-Links für App-Modus und Manifest

Beim Speichern der PWA-Settings werden Rewrite Rules aktualisiert.

## AJAX-Endpunkte (WebApp-relevant)

Für den App-Betrieb sind u. a. diese Endpunkte aktiv:

- Ticket erstellen
- Ticket-Antwort hinzufügen
- FAQ-Voting

Sicherheitsaspekte:

- Nonce-Checks (`check_ajax_referer`)
- Login- und Rollenprüfung
- Input-Sanitizing vor Verarbeitung

## Admin-Checkliste

- `?app=1` lädt korrektes Standalone-Template
- `crm-manifest.json` ist erreichbar und valide
- `crm-service-worker.js` wird registriert
- Install-Prompt erscheint auf unterstützten Browsern
- Push-Permission und Subscription funktionieren über HTTPS

## Troubleshooting

**Manifest lädt nicht**

- Permalinks neu speichern
- Rewrite Rules prüfen
- URL exakt auf `crm-manifest.json` testen

**Service Worker registriert nicht**

- Browser-Konsole auf Fehler prüfen
- HTTPS/Scope prüfen
- Alte Service Worker im Browser löschen und neu laden

**Push funktioniert nicht**

- HTTPS aktiv?
- Notification Permission auf „Erlauben“?
- VAPID-Key-Konfiguration geprüft?

---

## Seiten-Navigation

<p><a href="pwa-frontend-module.html">⬅️ Zurück</a> · <a href="index.html">🏠 Übersicht</a> · <a href="dokumentation.html">Weiter ➡️</a></p>
