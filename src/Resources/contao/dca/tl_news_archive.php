<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['dsolnews_synchro'] = array
(
	'input_field_callback'    => array('tl_news_archive_dsolnews', 'getButton'),
);

// Palette ändern, wenn das Nachrichten-Archiv für DSOL-Nachrichten verwendet werden soll
if(\Input::get('id') == $GLOBALS['TL_CONFIG']['dsolnews_archiv'])
{
	PaletteManipulator::create()
		// Neue Legende "dsolnews_legend" vor "title_legend" einfügen
		->addLegend('dsolnews_legend', 'title_legend', PaletteManipulator::POSITION_BEFORE)
		// Neues Feld "dsolnews_synchro" an Legende "dsolnews_legend" anhängen
		->addField('dsolnews_synchro', 'dsolnews_legend', PaletteManipulator::POSITION_APPEND)
		// Palette ändern
		->applyToPalette('default', 'tl_news_archive');
}

class tl_news_archive_dsolnews
{
	public function getButton(\DataContainer $dc)
	{
		return '
		<div class="w50 widget">
		<div class="selector_container">
		<p>
			<a href="contao?do=news&key=dsolnews_synchro&id='.\Input::get('id').'&rt='.REQUEST_TOKEN.'" class="tl_submit">'.$GLOBALS['TL_LANG']['tl_news_archive']['dsolnews_synchro'][0].'</a>
		</p>
		</div>
		<p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_news_archive']['dsolnews_synchro'][1].'</p>
		</div>
		';

	}
}
