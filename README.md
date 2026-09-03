# DSOL-Nachrichten verwalten

Contao-Erweiterung, die die Nachrichten eines Contao-Nachrichtenarchivs auf die
Website der Deutschen Schach-Online-Liga (DSOL) überträgt.

Läuft unter **Contao 4.13** und **Contao 5** mit PHP 7.4 bis 8.4.

## Was die Erweiterung tut

Die DSOL-Website liegt in einer eigenen Datenbank auf demselben Server. Diese
Erweiterung schreibt die Nachrichten eines ausgewählten Contao-Archivs in deren
Tabelle `dsb_content`:

* Neue Nachrichten werden dort angelegt; die vergebene ID kommt als `dsol_id`
  in `tl_news` zurück.
* Bereits übertragene Nachrichten werden aufgefrischt, sobald sich ihr
  Zeitstempel geändert hat.
* Teaserbild und Vorschaubild werden ins Bildverzeichnis der DSOL-Website
  kopiert.
* Inserttags im Teaser werden aufgelöst, seitenrelative Links auf
  `https://www.schachbund.de` umgeschrieben.

Übertragen wird ausschließlich von Contao zur DSOL-Website. Auf der
DSOL-Website wird nichts gelöscht.

## Einrichtung

1. Erweiterung installieren und die Datenbank aktualisieren (es kommt die
   Spalte `tl_news.dsol_id` hinzu).
2. Unter **System → Einstellungen → Datenbank DSOL-Nachrichten** Host, Datenbank,
   Benutzer und Passwort der DSOL-Datenbank eintragen und das lokale
   Nachrichtenarchiv auswählen.
3. Das Zielverzeichnis der Bilder muss existieren. Es liegt neben der
   Contao-Installation unter `deutsche-onlineliga/images`.

## Bedienung

Im ausgewählten Nachrichtenarchiv erscheint beim Bearbeiten die Legende
**DSOL-Nachrichten** mit der Schaltfläche *Nachrichten synchronisieren*. Sie
führt auf eine Seite im Backend, auf der die Übertragung gestartet wird; das
Protokoll erscheint anschließend auf derselben Seite.

Beim ersten Lauf legt die Erweiterung die Tabelle `dsb_content` an, falls sie in
der DSOL-Datenbank noch fehlt.

## Hinweis zur Fassung 3.0.0

Bis Fassung 2.0.1 lief die Übertragung über die Datei
`src/Resources/public/Synchronisation.php`, die Contao über
`system/initialize.php` selbst hochgefahren hat. Diesen Weg gibt es unter
Contao 5 nicht mehr, und die Datei war zudem ohne Anmeldung aufrufbar. Die
Übertragung steckt jetzt im Backend-Modul und ist damit nur noch angemeldeten
Benutzern mit Zugriff auf das Nachrichten-Modul zugänglich.

Beim Aktualisieren einer bestehenden Installation kann eine verwaiste Kopie der
alten Datei unter `public/bundles/contaodsolnews/` liegen bleiben — Contao
kopiert Bundle-Dateien dorthin, wenn keine Verknüpfungen möglich sind. Dieses
Verzeichnis gehört von Hand entfernt.

## Lizenz

LGPL-3.0-or-later, siehe [LICENSE](LICENSE).
