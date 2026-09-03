<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoDsolnewsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Hauptklasse des Bundles.
 *
 * Sie bleibt leer, weil das Bundle keine eigenen Compiler-Pässe und keine
 * abweichende Extension-Auflösung benötigt; Symfony findet die Extension
 * anhand des Namenskonvention (ContaoDsolnewsExtension im Unterverzeichnis
 * DependencyInjection).
 */
class ContaoDsolnewsBundle extends Bundle
{
}
