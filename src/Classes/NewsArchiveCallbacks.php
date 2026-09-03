<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoDsolnewsBundle\Classes;

use Contao\Input;
use Contao\StringUtil;
use Contao\System;

/**
 * Rückrufe für den Data Container tl_news_archive.
 *
 * Bis Fassung 2.0.1 stand diese Klasse als globale Klasse
 * tl_news_archive_dsolnews unmittelbar in der DCA-Datei. Unter Contao 5 hat
 * das nicht mehr funktioniert, weil dort der Typhinweis \DataContainer ohne
 * Namensraum nicht mehr aufgelöst wird; deshalb liegt sie jetzt hier.
 */
class NewsArchiveCallbacks
{
	/**
	 * Erzeugt die Schaltfläche, die zur Synchronisationsseite führt.
	 *
	 * Das Feld dsolnews_synchro hat keine eigene Spalte in der Datenbank, es
	 * dient allein der Anzeige dieser Schaltfläche in der Palette des
	 * Nachrichten-Archivs.
	 *
	 * @param object $dc     Der DataContainer des Archivs; wird nicht ausgewertet,
	 *                       weil die ID ohnehin aus der Adresse stammt
	 * @param string $xlabel Zusätzliche Bedienelemente, die Contao anbietet
	 *                       (Assistenten); hier nicht verwendet
	 *
	 * @return string Das fertige HTML des Widgets
	 */
	public function getButton($dc, string $xlabel = ''): string
	{
		$container = System::getContainer();

		$url = $container->get('router')->generate('contao_backend', array
		(
			'do'  => 'news',
			'key' => 'dsolnews_synchro',
			'id'  => (int) Input::get('id'),
			'rt'  => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
		));

		return '
		<div class="w50 widget">
		<div class="selector_container">
		<p>
			<a href="'.StringUtil::specialchars($url).'" class="tl_submit">'.$GLOBALS['TL_LANG']['tl_news_archive']['dsolnews_synchro'][0].'</a>
		</p>
		</div>
		<p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_news_archive']['dsolnews_synchro'][1].'</p>
		</div>
		';
	}
}
