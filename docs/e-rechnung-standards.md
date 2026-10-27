# E-Rechnung: Standards und Technik

## Ausgabeformat

Der erste unterstützte Export ist:

- XRechnung 3.0
- UBL 2.1
- Rechnungsart `380`

## Prüfung

Vor dem Download erfolgen:

1. Prüfung der benötigten Rechnungs-, Verkäufer- und Käuferdaten,
2. Prüfung von Positionen, Steuern und Summen,
3. Erzeugung der UBL-XML,
4. XSD-Prüfung der erzeugten XML,
5. erneuter Vergleich der serialisierten Summen.

Ein Exportfehler erzeugt keine scheinbar erfolgreiche XML-Datei.

## Hosting und Sicherheit

- Keine Java-Laufzeit erforderlich.
- Keine dynamisch heruntergeladenen Validator-Programme.
- Keine automatische Übertragung von Rechnungsdaten an Dritte.
- Download nur für Benutzer mit CRM-Berechtigung.
- Download mit WordPress-Nonce gegen unbeabsichtigte Aufrufe geschützt.
- XML wird mit `LIBXML_NONET` ohne externe Netzwerkzugriffe verarbeitet.

## Abgrenzung

Die lokale Prüfung stellt sicher, dass das Plugin intern konsistente und XSD-gültige XML erzeugt. Sie wird nicht als Nachweis einer externen Zertifizierung bezeichnet.

