<?php

declare(strict_types=1);

/*
 * Dieses Bundle synchronisiert Nachrichten eines Contao-Archivs mit der
 * Website der Deutschen Schach-Online-Liga; es läuft unter Contao 4.13
 * und Contao 5.
 *
 * @license LGPL-3.0-or-later
 */

use Schachbulle\ContaoDsolnewsBundle\Classes\DSOLNews;

/*
 * Zusätzlicher Schlüssel im Backend-Modul "news".
 *
 * Aufgerufen wird er über contao?do=news&key=dsolnews_synchro. Contao holt die
 * Klasse in Backend::getBackendModule() über System::importStatic() aus dem
 * Service-Container, weil sie in der services.yaml unter ihrem Klassennamen
 * als öffentlicher Dienst registriert ist. Der Klassenname muss deshalb
 * vollständig ausgeschrieben sein — Contao 5 kennt keine globalen
 * Klassenaliasse mehr.
 */
$GLOBALS['BE_MOD']['content']['news']['dsolnews_synchro'] = array(DSOLNews::class, 'Synchronisation');
