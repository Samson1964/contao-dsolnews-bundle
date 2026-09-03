<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoDsolnewsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Lädt die Dienste des Bundles in den Symfony-Container.
 */
class ContaoDsolnewsExtension extends Extension
{
	/**
	 * Liest die services.yaml des Bundles ein.
	 *
	 * Die Basisklasse aus dem Namensraum DependencyInjection\Extension gibt es
	 * bereits in Symfony 5.4 (Contao 4.13) wie auch in Symfony 7 (Contao 5),
	 * die Klasse ist also für beide Zielfassungen richtig.
	 *
	 * @param array            $mergedConfig Die zusammengeführte Konfiguration; dieses
	 *                                       Bundle besitzt keine eigene Konfiguration
	 *                                       und wertet den Wert deshalb nicht aus
	 * @param ContainerBuilder $container    Der Container, in den die Dienste kommen
	 *
	 * @return void
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);

		$loader->load('services.yaml');
	}
}
