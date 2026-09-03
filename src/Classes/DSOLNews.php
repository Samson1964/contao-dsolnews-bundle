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

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Backend-Einstieg der Nachrichten-Synchronisation.
 *
 * Die Klasse hängt als eigener Schlüssel im Backend-Modul "news"
 * (siehe config.php) und wird über die Adresse
 * contao?do=news&key=dsolnews_synchro aufgerufen. Damit greifen der
 * Backend-Login und die Rechteprüfung des Moduls; bis Fassung 2.0.1 lief
 * die Übertragung dagegen über eine frei aufrufbare Datei im
 * public-Verzeichnis.
 *
 * Der Abgleich selbst läuft als ein einzelner AJAX-Aufruf auf dieselbe
 * Adresse, damit der Browser nicht mit einem scheinbar hängenden Seitenaufbau
 * dasteht. Das Ergebnis kommt als JSON zurück und wird im Template angezeigt.
 */
class DSOLNews
{
	/**
	 * Name des Formularfelds, über das der AJAX-Schritt angefordert wird.
	 *
	 * Bewusst nicht "action": Contao fängt POST-Anfragen mit diesem Feldnamen
	 * in Backend::getBackendModule() selbst ab, bevor der Modul-Schlüssel
	 * überhaupt an die Reihe kommt.
	 */
	public const AKTION = 'dsolnewsAktion';

	/**
	 * @var Synchronisation Führt die eigentliche Übertragung aus
	 */
	private $synchronisation;

	/**
	 * @var Connection Doctrine-Verbindung zur Contao-Datenbank
	 */
	private $verbindung;

	/**
	 * Nimmt die Abhängigkeiten entgegen.
	 *
	 * Die Klasse wird nicht selbst instanziiert, sondern von Contao über
	 * System::importStatic() aus dem Service-Container geholt. Deshalb ist sie
	 * in der services.yaml unter ihrem Klassennamen als öffentlicher Dienst
	 * eingetragen.
	 *
	 * @param Synchronisation $synchronisation Der Dienst, der den Abgleich durchführt
	 * @param Connection      $verbindung      Verbindung zur Contao-Datenbank; wird nur
	 *                                         für die Anzeige des Archivtitels gebraucht
	 */
	public function __construct(Synchronisation $synchronisation, Connection $verbindung)
	{
		$this->synchronisation = $synchronisation;
		$this->verbindung = $verbindung;
	}

	/**
	 * Einstiegspunkt des Modul-Schlüssels "dsolnews_synchro".
	 *
	 * Beantwortet zuerst den AJAX-Schritt — dabei wird eine ResponseException
	 * geworfen, die Methode kehrt in diesem Fall also nicht zurück — und
	 * liefert sonst die Übersichtsseite mit der Startschaltfläche aus.
	 *
	 * @param object|null $dc Der DataContainer des Nachrichten-Moduls; wird nicht
	 *                        ausgewertet, weil das zu bearbeitende Archiv aus den
	 *                        Einstellungen stammt und nicht aus der Auswahl
	 *
	 * @return string Das gerenderte Backend-Template
	 */
	public function Synchronisation($dc = null): string
	{
		if ('dsolnews_synchro' !== Input::get('key'))
		{
			return '';
		}

		if ('start' === Input::post(self::AKTION))
		{
			$this->ajaxSynchronisation();
		}

		$archiv = (int) Config::get('dsolnews_archiv');

		$objTemplate = new BackendTemplate('be_dsolnews_synchro');
		$objTemplate->zurueck = StringUtil::ampersand(str_replace('&key=dsolnews_synchro', '', (string) Environment::get('request')));
		$objTemplate->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
		$objTemplate->archivFehlt = $archiv < 1;
		$objTemplate->archivTitel = $archiv > 0 ? $this->archivTitel($archiv) : '';

		return $objTemplate->parse();
	}

	/**
	 * Führt den Abgleich aus und beendet die Anfrage mit einer JSON-Antwort.
	 *
	 * Die Antwort enthält immer den Schlüssel "protokoll" mit den Zeilen des
	 * Durchgangs; tritt eine unerwartete Ausnahme auf, kommt zusätzlich
	 * "fehler" mit der Meldung zurück, damit das Skript im Browser nicht
	 * stumm stehenbleibt.
	 *
	 * @return void Kehrt nie zurück
	 *
	 * @throws ResponseException Immer, mit der fertigen JSON-Antwort
	 */
	private function ajaxSynchronisation(): void
	{
		try
		{
			$protokoll = $this->synchronisation->ausfuehren();
			$antwort = array('protokoll' => $protokoll);
		}
		catch (\Throwable $e)
		{
			$antwort = array('protokoll' => array(), 'fehler' => $e->getMessage());
		}

		throw new ResponseException(new JsonResponse($antwort));
	}

	/**
	 * Liest den Titel des eingestellten Nachrichten-Archivs.
	 *
	 * Dient nur der Anzeige, damit im Backend erkennbar ist, welches Archiv
	 * übertragen wird.
	 *
	 * @param int $archiv ID des Datensatzes in tl_news_archive
	 *
	 * @return string Der Titel, oder ein leerer String, wenn das Archiv
	 *                inzwischen gelöscht wurde
	 */
	private function archivTitel(int $archiv): string
	{
		$titel = $this->verbindung->fetchOne('SELECT title FROM tl_news_archive WHERE id = ?', array($archiv));

		return false === $titel ? '' : (string) $titel;
	}
}
