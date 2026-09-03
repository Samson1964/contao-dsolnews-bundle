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
 * Palette
 *
 * tl_settings ist ein DC_File-Container: Die Werte landen nicht in einer
 * Tabelle, sondern in der localconfig.php und werden über Config::get()
 * gelesen. Ein 'sql'-Schlüssel an den Feldern wäre deshalb wirkungslos und
 * steht hier bewusst nicht.
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{dsolnews_legend:hide},dsolnews_host,dsolnews_db,dsolnews_user,dsolnews_pass,dsolnews_archiv';

/*
 * Felder
 */

// Host der DSOL-Datenbank
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_host'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_host'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Name der DSOL-Datenbank
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_db'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_db'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Benutzer der DSOL-Datenbank
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_user'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_user'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Passwort der DSOL-Datenbank
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_pass'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_pass'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Lokales Nachrichten-Archiv, dessen Inhalt übertragen wird
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_archiv'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_archiv'],
	'inputType'               => 'select',
	'foreignKey'              => 'tl_news_archive.title',
	'eval'                    => array
	(
		'includeBlankOption'  => true,
		'tl_class'            => 'w50'
	)
);
