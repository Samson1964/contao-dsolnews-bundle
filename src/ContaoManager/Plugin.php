<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoDsolnewsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use Schachbulle\ContaoDsolnewsBundle\ContaoDsolnewsBundle;

/**
 * Registriert das Bundle im Contao Manager.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Meldet das Bundle beim Contao Manager an.
	 *
	 * Geladen wird nach dem Kern und nach dem Nachrichten-Bundle, weil dieses
	 * Bundle die Data Container tl_news und tl_news_archive erweitert und sich
	 * in das Backend-Modul "news" einhängt. Beides muss vorher dagewesen sein.
	 *
	 * @param ParserInterface $parser Wird vom Contao Manager übergeben und hier
	 *                                nicht benötigt, da keine fremden Bundle-
	 *                                Konfigurationen eingelesen werden
	 *
	 * @return array<BundleConfig> Die Bundle-Konfiguration dieses Pakets
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return array
		(
			BundleConfig::create(ContaoDsolnewsBundle::class)
				->setLoadAfter(array(ContaoCoreBundle::class, ContaoNewsBundle::class)),
		);
	}
}
