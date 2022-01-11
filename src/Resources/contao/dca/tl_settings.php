<?php

/**
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{dsolnews_legend:hide},dsolnews_host,dsolnews_db,dsolnews_user,dsolnews_pass,dsolnews_archiv';

/**
 * fields
 */

// Alte Elobase-Datenbank Host
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_host'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_host'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank Datenbank
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_db'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_db'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank Benutzer
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_user'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_user'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank Passwort
$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_pass'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_pass'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dsolnews_archiv'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dsolnews_archiv'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'              => 'tl_news_archive.title',
	'eval'                    => array
	(
		'includeBlankOption'  => true,
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) NOT NULL default ''"
);
