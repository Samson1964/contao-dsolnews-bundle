<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Legende
 */
$GLOBALS['TL_LANG']['tl_settings']['dsolnews_legend'] = 'Datenbank DSOL-Nachrichten';

/*
 * Felder
 */
$GLOBALS['TL_LANG']['tl_settings']['dsolnews_host'] = array('Host', 'Host-Adresse der DSOL-Datenbank');
$GLOBALS['TL_LANG']['tl_settings']['dsolnews_db'] = array('Datenbank', 'Name der DSOL-Datenbank');
$GLOBALS['TL_LANG']['tl_settings']['dsolnews_user'] = array('Benutzer', 'Benutzer der DSOL-Datenbank');
$GLOBALS['TL_LANG']['tl_settings']['dsolnews_pass'] = array('Passwort', 'Passwort der DSOL-Datenbank');
$GLOBALS['TL_LANG']['tl_settings']['dsolnews_archiv'] = array('Nachrichten-Archiv', 'Lokales Nachrichten-Archiv auswählen, in dem die DSOL-Nachrichten verwaltet werden.');
