# B2B-Rechnungs-XML: Technik

## Ausgabeformat

Der Export erzeugt ein leichtgewichtiges, eigenes `B2BInvoice`-XML. Es enthält die vorhandene Rechnungsnummer, Daten, Verkäufer- und Käuferdaten, Positionen, Summen, Zahlungsinformationen und Referenzen.

Das Format ist absichtlich keine XRechnung-, UBL-, Peppol-, ZUGFeRD- oder Factur-X-Implementierung. Es ist für einfache B2B-Austauschprozesse gedacht, deren Empfänger dieses Format vereinbart haben.

## Erzeugung

Der Export verwendet ausschließlich bereits im CRM gespeicherte Daten:

1. Rechnung, Kundendaten und Positionen,
2. Verkäufer- und Zahlungsdaten aus den CRM-Einstellungen,
3. vorhandene Rechnungs- und Positionssummen,
4. optionale Bestell-, Vertrags- und Projektreferenzen.

## Hosting und Sicherheit

- Keine externen PHP-Pakete oder Validatoren erforderlich.
- Keine Java-Laufzeit erforderlich.
- Keine automatische Übertragung von Rechnungsdaten an Dritte.
- Download nur für Benutzer mit CRM-Berechtigung.
- Download mit WordPress-Nonce gegen unbeabsichtigte Aufrufe geschützt.
