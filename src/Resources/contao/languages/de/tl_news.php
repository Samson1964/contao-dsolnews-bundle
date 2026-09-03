<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_LANG']['tl_news']['dsolnews_legend'] = 'Zuordnung DSOL-Website';
$GLOBALS['TL_LANG']['tl_news']['dsol_id'] = array('DSOL-ID', 'Bitte nicht ändern! Die ID verbindet diese Nachricht mit der entsprechenden Nachricht auf der DSOL-Website. Wenn Sie hier 0 eintragen, wird auf der DSOL-Website eine neue Nachricht erzeugt. Bei anderen Werten, die nicht vom System eingetragen wurden, kann die Synchronisierung durcheinanderkommen.');
