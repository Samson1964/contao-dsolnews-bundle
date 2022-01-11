<?php

namespace Schachbulle\ContaoDsolnewsBundle\Classes;

/*
 * Ersetzt den Tag {{adresse::ID}} bzw. {{adresse::ID::Funktion}}
 * durch die entsprechende Adresse aus tl_adressen
 */

class DSOLNews extends \Backend
{
	public function Synchronisation(\DataContainer $dc)
	{
		if(\Input::get('key') != 'dsolnews_synchro')
		{
			// Beenden, wenn der Parameter nicht übereinstimmt
			return '';
		}

		// Objekt BackendUser importieren
		$this->import('BackendUser','User');

		// Formular wurde abgeschickt, CSS-Datei importieren
		if(\Input::get('FORM_SUBMIT') == 'tl_dsolnews_synchro')
		{

			$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaodsolnews/js/jquery-3.5.1.min.js';

			// Zurück-URL erstellen
			$backurl = \Environment::get('url').'/contao?do=news&act=edit&id='.\Input::get('id').'&rt='.REQUEST_TOKEN;

			$content .= '<div id="tl_buttons">';
			$content .= '<a href="'.$backurl.'" class="header_back" title="'. specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']) .'" accesskey="b">'. $GLOBALS['TL_LANG']['MSC']['backBT'] .'</a>';
			$content .= '</div>';
			$content .= '<div id="dsolnews_import" style="margin:10px;"></div>';
			$content .= '<div id="dsolnews_import_status" style="margin:10px;"><img src="bundles/contaodsolnews/images/ajax-loader.gif"></div>';

			$content .= '<script>'."\n";
			$content .= '$.ajax({'."\n";
			$content .= '  url: "bundles/contaodsolnews/Synchronisation.php",'."\n";
			$content .= '  cache: false,'."\n";
			$content .= '  success: function(response) {'."\n";
			$content .= '    $("#dsolnews_import").append(response);'."\n";
			$content .= '    $("#dsolnews_import_status").html("");'."\n";
			$content .= '  }'."\n";
			$content .= '});'."\n";
			$content .= '</script>'."\n";

			return $content;
			// Cookie setzen und zurückkehren (key=dsolnews_synchro aus URL entfernen)
			\System::setCookie('BE_PAGE_OFFSET', 0, 0);
			//$this->redirect(str_replace('&key=dsolnews_synchro', '', \Environment::get('request')));
		}
		else
		{
			$template = new \BackendTemplate('be_dsolnews_synchro');
			return $template->parse();
		}
	}
}
