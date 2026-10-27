# E-Rechnung: Datenmapping

## Datenquelle

Der Export liest ausschließlich vorhandene CRM-Daten. Er berechnet keine unabhängige zweite Rechnung.

## Rechnung

| E-Rechnungsfeld | CRM-Quelle |
|---|---|
| Rechnungsnummer | Präfix + `dokumente.progressivo` + Suffix |
| Rechnungsdatum | `dokumente.data` |
| Fälligkeitsdatum | `dokumente.data_scadenza` |
| Zahlungsbedingung | `dokumente.modalita_pagamento` |
| Währung | EUR |
| Buyer Reference | Rechnungsangabe, sonst Kundenstamm |
| Leistungszeitraum | optionale Rechnungsangabe |
| Bestell-/Vertrags-/Projektreferenz | optionale Rechnungsangabe |

## Positionen und Steuern

| E-Rechnungsfeld | CRM-Quelle |
|---|---|
| Beschreibung | `dokumente_dettaglio.descrizione` |
| Menge | `dokumente_dettaglio.qta` |
| Einzelpreis | `dokumente_dettaglio.prezzo` |
| Rabatt | `dokumente_dettaglio.sconto` und vorhandener Rabattmodus |
| Umsatzsteuersatz | `dokumente_dettaglio.iva` |
| Einheit | `dokumente_dettaglio.unit_code` |
| Steuerkategorie | `dokumente_dettaglio.tax_category` oder Ableitung aus vorhandener Steuerregel |

Unterstützte Steuerkategorien:

- `S`: reguläre Umsatzsteuer
- `Z`: Nullsteuersatz
- `E`: steuerbefreit, einschließlich § 19 UStG
- `AE`: Reverse Charge nach § 13b UStG
- `K`: innergemeinschaftliche Lieferung

Die vorhandenen CRM-Gesamtsummen müssen mit den aus den Positionen ermittelten Beträgen übereinstimmen. Abweichungen stoppen den Export.

## Kunde

Die bestehende Kundentyp-Auswahl steuert den Standard:

- Privatkunde: E-Rechnung aus
- Unternehmen: E-Rechnung an
- Behörde: E-Rechnung an

Ergänzende Empfängerdaten:

- Buyer Reference / Leitweg-ID
- elektronische Adresse
- Adress-Schema
- Ansprechpartner

## Speicherung

`smartcrm_einvoice_document_data` speichert nur die Auswahl und ergänzende Angaben:

- `enabled`
- `buyer_reference`
- `service_start_date`
- `service_end_date`
- `order_reference`
- `contract_reference`
- `project_reference`

Die XML-Datei wird beim Download aus der aktuellen gespeicherten Rechnung erzeugt und nicht als paralleles Dokument verwaltet.

