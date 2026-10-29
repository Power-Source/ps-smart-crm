# E-Rechnung: schlanke Roadmap

## Ziel

PS Smart Business ergänzt bestehende Rechnungen um ein strukturiertes XML-Format. Es entsteht kein zweites Rechnungs-, Steuer- oder Buchhaltungssystem.

## Grundsätze

- Die vorhandene CRM-Rechnung ist die einzige Datenquelle.
- Vorhandene Positionen, Summen, Steuern und Rechnungsnummern bleiben maßgeblich.
- PDF und XML sind zwei Ausgaben derselben Rechnung.
- B2B aktiviert die XML-Ausgabe standardmäßig; die Auswahl kann je Rechnung geändert werden.
- Der WordPress-Server benötigt kein Java.
- Keine Rechnungsdaten werden ohne ausdrückliche Konfiguration an externe Dienste übertragen.

## Stand

### CRM-Ausgangsrechnungen

- [x] B2B/B2C/Behörde am Kunden speichern.
- [x] E-Rechnung im normalen Rechnungsformular aktivieren.
- [x] Vorhandene CRM-Rechnung als schlankes B2B-Rechnungs-XML ausgeben.
- [x] Vorhandene Steuerregeln übernehmen.
- [x] XML über einen berechtigungs- und noncegeschützten Download erzeugen.
- [ ] Vollständigen Ablauf mit realen B2B-Testrechnungen im Browser prüfen.

### Eingangsrechnungen

- [ ] XML-Datei in der CRM-Buchhaltung hochladen.
- [ ] Absender, Rechnungsnummer, Datum, Positionen und Summen auslesen.
- [ ] Importvorschau anzeigen.
- [ ] Nach Bestätigung in die vorhandene Buchhaltung übernehmen.
- [ ] Doppelte Importe anhand stabiler Rechnungsmerkmale verhindern.

### MarketPress-B2B-Erweiterung

- [ ] Erweiterung optional aktivierbar machen.
- [ ] Benötigte B2B-Felder in Kundenkonto und Checkout ergänzen.
- [ ] MarketPress-Rechnung als maßgebliche Rechnung verwenden.
- [ ] Bei B2B zusätzlich XML erzeugen.
- [ ] Fertige MarketPress-Rechnung einmalig in die CRM-Buchhaltung synchronisieren.
- [ ] MarketPress-Rechnungsnummer und Steuerberechnung unverändert übernehmen.

## Nicht Bestandteil

- Java- oder KoSIT-Laufzeit auf dem WordPress-Server
- XRechnung-, UBL-, Peppol-, ZUGFeRD- oder Factur-X-Kompatibilität
- parallele Steuerberechnung
- parallele Rechnungsverwaltung
- eigener Ausstellungsstatus für XML
- persistente XML-Snapshots oder Validierungsberichte
- ungefragte Übertragung an externe Validatoren
