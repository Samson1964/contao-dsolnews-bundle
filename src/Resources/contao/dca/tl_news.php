<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

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

if(\Input::get('table') == 'tl_news' && \Input::get('act') == 'edit')
{
	// Editiermodus für eine Nachricht, zuerst nach Archiv suchen
	$objNews = \Database::getInstance()->prepare("SELECT pid FROM tl_news WHERE id=?")
	                                   ->execute(\Input::get('id'));
	// Palette ändern, wenn Archiv paßt
	if($objNews->pid == $GLOBALS['TL_CONFIG']['dsolnews_archiv'])
	{
		PaletteManipulator::create()
			// Neue Legende "dsolnews_legend" vor "title_legend" einfügen
			->addLegend('dsolnews_legend', 'title_legend', PaletteManipulator::POSITION_BEFORE)
			// Neues Feld "dsolnews_synchro" an Legende "dsolnews_legend" anhängen
			->addField('dsol_id', 'dsolnews_legend', PaletteManipulator::POSITION_APPEND)
			// Palette ändern
			->applyToPalette('default', 'tl_news');
	}
}
