# DSOL-Nachrichten Changelog

## Version 3.0.0 (2026-09-03)

* Change: Lauffähig unter Contao 4.13 und Contao 5.7 mit PHP 7.4 bis 8.4; Anforderungen in der composer.json entsprechend gesetzt
* Change: Die Übertragung läuft nicht mehr über die frei aufrufbare Datei `src/Resources/public/Synchronisation.php`, sondern im Backend-Modul. Damit greifen Anmeldung und Rechteprüfung; die Datei ist entfallen, weil `system/initialize.php` unter Contao 5 nicht mehr existiert
* Change: Die Verbindung zur DSOL-Datenbank wird über Doctrine aufgebaut, da `Database::getInstance()` unter Contao 5 keine eigenen Zugangsdaten mehr entgegennimmt
* Change: Ersatz der unter Contao 5 entfallenen Aufrufe — `Controller::replaceInsertTags()` durch den Dienst `contao.insert_tag.parser`, `Image::get()` durch `contao.image.factory`, `specialchars()` und `ampersand()` durch `StringUtil`, `REQUEST_TOKEN` durch den CSRF-Dienst
* Change: Alle Klassennamen vollständig ausgeschrieben, da Contao 5 keine globalen Klassenaliasse mehr registriert; die Rückrufklasse des Nachrichtenarchivs liegt jetzt als `Classes\NewsArchiveCallbacks` vor
* Change: Fortschrittsanzeige ohne jQuery; die mitgelieferte Fassung 3.5.1 und die nicht mehr verwendeten Symbolbilder sind entfallen
* Fix: Die Tabelle `dsb_content` wird beim ersten Lauf angelegt und der Abgleich läuft anschließend durch, statt einen zweiten Aufruf zu verlangen
* Fix: Fehlende Einstellungen, ein gelöschtes Teaserbild oder ein fehlendes Zielverzeichnis brechen den Lauf nicht mehr mit einer PHP-Meldung ab, sondern erscheinen als Fehlerzeile im Protokoll
* Fix: `dsolnews_archiv` hatte in `tl_settings` eine SQL-Definition mit unzulässigem Vorgabewert; `tl_settings` schreibt in die `localconfig.php` und braucht gar keine
* Fix: Doppelt vergebener Sprachschlüssel `tl_news_archive.dsolnews_synchro` und ein überzähliges Apostroph am Request-Token im Backend-Template
* Add: README mit Einrichtung und Bedienung

## Version 2.0.1 (2026-07-30)

* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 2.0.0 (2025-09-09)

* Add: Abhängigkeit PHP 8

## Version 1.0.2 (2022-01-11)

* Fix: Debug-Code entfernt, damit Update-Prüfung wieder funktioniert

## Version 1.0.1 (2022-01-11)

* Add: Hinweis für Änderungen der Spalte tl_news.dsol_id
* Fix: Ersetzung der Inserttags im Teaser vor der Synchronisation
* Add: Hinzufügen der Domain schachbund.de in Links des Teasers

## Version 1.0.0 (2022-01-11)

* Aus- und Umbau des Bundles: Nicht in externe Tabelle content schreiben, sondern in eigene Tabelle dsb_content

## Version 0.0.2 (2021-12-02)

* Fix: Fehler in composer.json

## Version 0.0.1 (2021-12-02)

* Initiale Version für Contao 4
