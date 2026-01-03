-- Migration von italienischen zu deutschen Tabellennamen
-- WICHTIG: Backup erstellen vor Ausführung!
-- Diese Queries müssen mit dem WordPress-Präfix angepasst werden (z.B. wp_ oder wps_)

-- Beispiel: Wenn dein Präfix "wps_" ist, ersetze {prefix} mit wps_

-- Tabellen umbenennen
RENAME TABLE {prefix}kunde TO {prefix}kunde;
RENAME TABLE {prefix}documenti TO {prefix}dokumente;
RENAME TABLE {prefix}articoli TO {prefix}artikel;
RENAME TABLE {prefix}documenti_dettaglio TO {prefix}dokumente_dettaglio;

-- Foreign Key Spalten umbenennen in kunde-Tabelle
ALTER TABLE {prefix}kunde CHANGE COLUMN ID_kunde ID_kunde INT(11) NOT NULL AUTO_INCREMENT;

-- Foreign Key Spalten in dokumente-Tabelle
ALTER TABLE {prefix}dokumente CHANGE COLUMN ID_documenti ID_dokumente INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE {prefix}dokumente CHANGE COLUMN fk_kunde fk_kunde INT(11);

-- Foreign Key Spalten in artikel-Tabelle  
ALTER TABLE {prefix}artikel CHANGE COLUMN ID_articoli ID_artikel INT(11) NOT NULL AUTO_INCREMENT;

-- Foreign Key Spalten in dokumente_dettaglio-Tabelle
ALTER TABLE {prefix}dokumente_dettaglio CHANGE COLUMN fk_documenti fk_dokumente INT(11);
ALTER TABLE {prefix}dokumente_dettaglio CHANGE COLUMN fk_articoli fk_artikel INT(11);

-- Scheduler/Activity Tabelle
ALTER TABLE {prefix}scheduler CHANGE COLUMN fk_kunde fk_kunde INT(11);

-- Eventuell weitere Tabellen mit Referenzen prüfen und hier ergänzen
