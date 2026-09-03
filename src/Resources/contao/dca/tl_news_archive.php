<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Config;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Input;
use Schachbulle\ContaoDsolnewsBundle\Classes\NewsArchiveCallbacks;

/*
 * Anzeigefeld mit der Schaltfläche "Nachrichten synchronisieren".
 *
 * Das Feld hat bewusst weder 'sql' noch 'exclude': Es gibt keine Spalte dazu,
 * und Contao 4.13 blendet ein Feld mit 'exclude' in der Bearbeitungsansicht
 * ganz aus, während Contao 5 stattdessen die Feldberechtigung prüft. Ohne
 * 'exclude' erscheint es in beiden Fassungen.
 */
$GLOBALS['TL_DCA']['tl_news_archive']['fields']['dsolnews_synchro'] = array
(
	'input_field_callback'    => array(NewsArchiveCallbacks::class, 'getButton'),
);

/*
 * Die Schaltfläche gehört nur an das eine Archiv, das in den Einstellungen für
 * die DSOL-Nachrichten ausgewählt wurde.
 */
$dsolnewsArchiv = (int) Config::get('dsolnews_archiv');

if ($dsolnewsArchiv > 0 && (int) Input::get('id') === $dsolnewsArchiv)
{
	PaletteManipulator::create()
		// Neue Legende "dsolnews_legend" vor "title_legend" einfügen
		->addLegend('dsolnews_legend', 'title_legend', PaletteManipulator::POSITION_BEFORE)
		// Neues Feld "dsolnews_synchro" an Legende "dsolnews_legend" anhängen
		->addField('dsolnews_synchro', 'dsolnews_legend', PaletteManipulator::POSITION_APPEND)
		// Palette ändern
		->applyToPalette('default', 'tl_news_archive');
}
