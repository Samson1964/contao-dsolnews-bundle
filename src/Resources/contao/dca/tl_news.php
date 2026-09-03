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
use Contao\Database;
use Contao\Input;

/*
 * Verweis auf den zugehörigen Datensatz der DSOL-Website.
 *
 * Der Wert wird von der Synchronisation gesetzt und sollte von Hand nur
 * angefasst werden, wenn eine Nachricht bewusst neu übertragen werden soll.
 */
$GLOBALS['TL_DCA']['tl_news']['fields']['dsol_id'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_news']['dsol_id'],
	'exclude'                 => true,
	'search'                  => true,
	'sorting'                 => true,
	'inputType'               => 'text',
	'eval'                    => array
	(
		'mandatory'           => false,
		'maxlength'           => 10,
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) unsigned NOT NULL default 0"
);

/*
 * Das Feld gehört nur in die Palette, wenn die bearbeitete Nachricht im
 * eingestellten DSOL-Archiv liegt.
 *
 * Dafür ist eine eigene Abfrage nötig, weil in der DCA-Datei noch kein
 * Datensatz geladen ist. Die Abfrage läuft deshalb ausdrücklich nur beim
 * Bearbeiten einer einzelnen Nachricht und nicht bei jedem Laden der DCA.
 */
$dsolnewsArchiv = (int) Config::get('dsolnews_archiv');

if ($dsolnewsArchiv > 0 && 'tl_news' === Input::get('table') && 'edit' === Input::get('act') && Input::get('id'))
{
	$dsolnewsNachricht = Database::getInstance()
		->prepare("SELECT pid FROM tl_news WHERE id=?")
		->limit(1)
		->execute(Input::get('id'));

	if ($dsolnewsNachricht->numRows && (int) $dsolnewsNachricht->pid === $dsolnewsArchiv)
	{
		PaletteManipulator::create()
			// Neue Legende "dsolnews_legend" vor "title_legend" einfügen
			->addLegend('dsolnews_legend', 'title_legend', PaletteManipulator::POSITION_BEFORE)
			// Neues Feld "dsol_id" an Legende "dsolnews_legend" anhängen
			->addField('dsol_id', 'dsolnews_legend', PaletteManipulator::POSITION_APPEND)
			// Palette ändern
			->applyToPalette('default', 'tl_news');
	}
}
