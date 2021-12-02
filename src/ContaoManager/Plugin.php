<?php

namespace Schachbulle\ContaoDsolnewsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoDsolnewsBundle\ContaoDsolnewsBundle;

class Plugin implements BundlePluginInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getBundles(ParserInterface $parser)
	{
		return [
			BundleConfig::create(ContaoDsolnewsBundle::class)
				->setLoadAfter([ContaoCoreBundle::class, \Contao\NewsBundle\ContaoNewsBundle::class]),
		];
	}
}
