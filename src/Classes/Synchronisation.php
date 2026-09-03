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

use Contao\Config;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\File;
use Contao\FilesModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Überträgt die Nachrichten eines lokalen Contao-Archivs in die Tabelle
 * dsb_content einer zweiten, entfernten Datenbank (der DSOL-Website).
 *
 * Die Übertragung läuft nur in eine Richtung: von Contao zur DSOL-Website.
 * Verglichen wird über zwei Felder — tl_news.dsol_id verweist auf
 * dsb_content.id, und der Zeitstempel entscheidet, ob ein vorhandener
 * Datensatz aufgefrischt werden muss.
 *
 * Zur Vorgeschichte: Bis Fassung 2.0.1 lag diese Logik in einer frei
 * aufrufbaren Datei unter Resources/public/, die sich Contao über
 * system/initialize.php selbst hochgefahren hat. Diese Datei gibt es unter
 * Contao 5 nicht mehr, und der Aufruf war zudem ohne Anmeldung möglich.
 * Deshalb steckt die Arbeit jetzt in einem Dienst, den ausschließlich das
 * Backend-Modul aufruft.
 */
class Synchronisation
{
	/**
	 * Tabelle in der entfernten Datenbank, in die geschrieben wird.
	 */
	public const FERN_TABELLE = 'dsb_content';

	/**
	 * Domain, die beim Umschreiben der Links im Teaser vorangestellt wird.
	 *
	 * Die Teaser stammen aus Contao und enthalten dort seitenrelative Links.
	 * Auf der DSOL-Website laufen sie ins Leere, deshalb werden sie absolut
	 * auf die Herkunftsseite gesetzt.
	 */
	public const QUELL_DOMAIN = 'https://www.schachbund.de';

	/**
	 * Zielverzeichnis der Teaserbilder, relativ zum übergeordneten
	 * Verzeichnis der Contao-Installation.
	 *
	 * Die DSOL-Website liegt auf demselben Server neben der Contao-
	 * Installation; kopiert wird deshalb im Dateisystem statt über das Netz.
	 */
	public const BILD_VERZEICHNIS = 'deutsche-onlineliga/images';

	/**
	 * @var Connection Doctrine-Verbindung zur Contao-Datenbank
	 */
	private $verbindung;

	/**
	 * @var ImageFactoryInterface Erzeugt die Vorschaubilder der Teaser
	 */
	private $bildFabrik;

	/**
	 * @var InsertTagParser Ersetzt die Contao-Inserttags in den Teasern
	 */
	private $inserttags;

	/**
	 * @var string Absoluter Pfad zum Contao-Projektverzeichnis
	 */
	private $projektVerzeichnis;

	/**
	 * @var array<string> Gesammelte Protokollzeilen des laufenden Durchgangs
	 */
	private $protokoll = array();

	/**
	 * Nimmt die Abhängigkeiten entgegen.
	 *
	 * @param Connection            $verbindung         Verbindung zur Contao-Datenbank
	 * @param ImageFactoryInterface $bildFabrik         Dienst contao.image.factory, Ersatz für
	 *                                                  das unter Contao 5 entfallene Image::get()
	 * @param InsertTagParser       $inserttags         Dienst contao.insert_tag.parser, Ersatz für
	 *                                                  das unter Contao 5 entfallene
	 *                                                  Controller::replaceInsertTags()
	 * @param string                $projektVerzeichnis Absoluter Pfad zum Projektverzeichnis
	 */
	public function __construct(Connection $verbindung, ImageFactoryInterface $bildFabrik, InsertTagParser $inserttags, string $projektVerzeichnis)
	{
		$this->verbindung = $verbindung;
		$this->bildFabrik = $bildFabrik;
		$this->inserttags = $inserttags;
		$this->projektVerzeichnis = $projektVerzeichnis;
	}

	/**
	 * Führt einen vollständigen Abgleich aus.
	 *
	 * Es wird nicht abgebrochen, wenn ein einzelnes Bild fehlt; solche Fälle
	 * landen als Fehlerzeile im Protokoll, der Rest läuft weiter. Ein Abbruch
	 * erfolgt nur bei fehlenden Einstellungen oder wenn die entfernte
	 * Datenbank nicht erreichbar ist.
	 *
	 * Seiteneffekte: schreibt in die entfernte Tabelle dsb_content, trägt in
	 * tl_news.dsol_id die vergebene Fremd-ID nach und kopiert Bilddateien in
	 * ein Verzeichnis außerhalb der Contao-Installation.
	 *
	 * @return array<string> Die Protokollzeilen des Durchgangs; sie enthalten
	 *                       bereits HTML (Fettdruck) und werden im Backend
	 *                       unverändert ausgegeben
	 */
	public function ausfuehren(): array
	{
		$this->protokoll = array();

		$archiv = (int) Config::get('dsolnews_archiv');

		if ($archiv < 1)
		{
			$this->fehler('In den Einstellungen ist kein Nachrichten-Archiv ausgewählt.');

			return $this->protokoll;
		}

		try
		{
			$fern = $this->fernVerbindung();
		}
		catch (\Throwable $e)
		{
			$this->fehler('Die Datenbank der DSOL-Website ist nicht erreichbar: '.$e->getMessage());

			return $this->protokoll;
		}

		try
		{
			// Die Zieltabelle liegt in einer fremden Datenbank und wird von
			// Contao nicht mitgepflegt, also legen wir sie beim ersten Lauf
			// selbst an.
			if (!$fern->createSchemaManager()->tablesExist(array(self::FERN_TABELLE)))
			{
				$fern->executeStatement($this->tabellenDefinition());
				$this->zeile('Die Tabelle <b>'.self::FERN_TABELLE.'</b> war noch nicht vorhanden und wurde angelegt.');
			}

			$this->abgleichen($fern, $archiv);
		}
		catch (\Throwable $e)
		{
			$this->fehler('Abbruch: '.$e->getMessage());
		}

		return $this->protokoll;
	}

	/**
	 * Baut die Verbindung zur Datenbank der DSOL-Website auf.
	 *
	 * Bis Fassung 2.0.1 lief das über Database::getInstance() mit eigenen
	 * Zugangsdaten. Diesen Weg gibt es unter Contao 5 nicht mehr — dort nimmt
	 * getInstance() keine Argumente mehr entgegen und liefert immer die
	 * Contao-Datenbank. Deshalb wird die zweite Verbindung direkt über
	 * Doctrine aufgebaut.
	 *
	 * @return Connection Die offene Verbindung zur entfernten Datenbank
	 *
	 * @throws \RuntimeException          Wenn Zugangsdaten in den Einstellungen fehlen
	 * @throws \Doctrine\DBAL\Exception   Wenn Doctrine die Verbindung nicht herstellen kann
	 */
	private function fernVerbindung(): Connection
	{
		$host = (string) Config::get('dsolnews_host');
		$datenbank = (string) Config::get('dsolnews_db');
		$benutzer = (string) Config::get('dsolnews_user');
		$passwort = (string) Config::get('dsolnews_pass');

		if ('' === $host || '' === $datenbank || '' === $benutzer)
		{
			throw new \RuntimeException('Host, Datenbank oder Benutzer fehlen in den Einstellungen.');
		}

		$verbindung = DriverManager::getConnection(array
		(
			'driver'   => 'pdo_mysql',
			'host'     => $host,
			'dbname'   => $datenbank,
			'user'     => $benutzer,
			'password' => $passwort,
			'charset'  => 'utf8mb4',
		));

		// Doctrine baut die Verbindung erst beim ersten Zugriff auf. Ein
		// belangloser Aufruf sorgt dafür, dass ein falsches Passwort sofort
		// auffällt und nicht erst mitten im Abgleich.
		$verbindung->fetchOne('SELECT 1');

		return $verbindung;
	}

	/**
	 * Vergleicht die lokalen Nachrichten mit der entfernten Tabelle und
	 * schreibt die Unterschiede fort.
	 *
	 * @param Connection $fern   Verbindung zur Datenbank der DSOL-Website
	 * @param int        $archiv ID des lokalen Nachrichten-Archivs (tl_news.pid)
	 *
	 * @return void
	 */
	private function abgleichen(Connection $fern, int $archiv): void
	{
		$nachrichten = $this->verbindung->fetchAllAssociative('SELECT * FROM tl_news WHERE pid = ?', array($archiv));

		$this->zeile('<b>'.\count($nachrichten).' Nachrichten</b> im lokalen Archiv');
		$this->zeile('');
		$this->zeile('Prüfung der Nachrichten im lokalen Archiv …');

		$neu = 0;
		$geaendert = 0;

		foreach ($nachrichten as $nachricht)
		{
			$fremdId = (int) ($nachricht['dsol_id'] ?? 0);

			$vorhanden = $fremdId > 0
				? $fern->fetchAssociative('SELECT id, tstamp FROM '.self::FERN_TABELLE.' WHERE id = ?', array($fremdId))
				: false;

			if (\is_array($vorhanden))
			{
				// Nur auffrischen, wenn sich der Zeitstempel unterscheidet.
				if ((int) $vorhanden['tstamp'] === (int) $nachricht['tstamp'])
				{
					continue;
				}

				$this->zeile('… … Update: '.StringUtil::specialchars((string) $nachricht['headline']));
				++$geaendert;

				$fern->update(self::FERN_TABELLE, $this->datensatz($nachricht), array('id' => $fremdId));

				continue;
			}

			$this->zeile('… … Neu: '.StringUtil::specialchars((string) $nachricht['headline']));
			++$neu;

			$fern->insert(self::FERN_TABELLE, $this->datensatz($nachricht));

			// Die vergebene Fremd-ID muss zurück nach Contao, sonst legt der
			// nächste Durchgang denselben Datensatz noch einmal an.
			$this->verbindung->update('tl_news', array('dsol_id' => (int) $fern->lastInsertId()), array('id' => (int) $nachricht['id']));
		}

		$this->zeile('');
		$this->zeile('… <b>'.$neu.'</b> neue Nachrichten zur DSOL-Website übertragen');
		$this->zeile('… <b>'.$geaendert.'</b> Nachrichten auf der DSOL-Website geändert');
		$this->zeile('');
		$this->zeile('<b>Fertig</b>');
	}

	/**
	 * Stellt aus einer Contao-Nachricht den Datensatz für die entfernte
	 * Tabelle zusammen.
	 *
	 * Die Teaserbilder werden dabei mitkopiert, weil ihre Dateinamen im
	 * Datensatz stehen müssen.
	 *
	 * @param array $nachricht Ein vollständiger Datensatz aus tl_news
	 *
	 * @return array<string,mixed> Die Spalten der Tabelle dsb_content
	 */
	private function datensatz(array $nachricht): array
	{
		$bild = $this->bilderKopieren($nachricht['singleSRC'] ?? null, (int) $nachricht['id'], $nachricht['size'] ?? null);

		return array
		(
			'tstamp'      => (int) $nachricht['tstamp'],
			'headline'    => (string) $nachricht['headline'],
			'date'        => (int) $nachricht['date'],
			'subheadline' => (string) ($nachricht['subheadline'] ?? ''),
			'teaser'      => $this->teaser((string) ($nachricht['teaser'] ?? '')),
			'author'      => $this->autor((int) ($nachricht['author'] ?? 0)),
			'image'       => $bild['image'],
			'thumbnail'   => $bild['thumb'],
			'alt'         => (string) ($nachricht['alt'] ?? ''),
			'imageTitle'  => (string) ($nachricht['imageTitle'] ?? ''),
			'size'        => (string) ($nachricht['size'] ?? ''),
			'caption'     => (string) ($nachricht['caption'] ?? ''),
			'floating'    => (string) ($nachricht['floating'] ?? 'above'),
			'published'   => (string) ($nachricht['published'] ?? ''),
		);
	}

	/**
	 * Bereitet einen Teasertext für die DSOL-Website auf.
	 *
	 * Zuerst werden die Contao-Inserttags aufgelöst, danach die beiden
	 * seitenrelativen Linkformen auf die Herkunftsdomain umgeschrieben. Die
	 * Reihenfolge ist wichtig: Ein Inserttag kann selbst einen Link erzeugen,
	 * der anschließend noch mitgenommen werden soll.
	 *
	 * @param string $text Der Teaser aus tl_news, darf leer sein
	 *
	 * @return string Der aufbereitete Teaser
	 */
	private function teaser(string $text): string
	{
		if ('' === $text)
		{
			return '';
		}

		$text = $this->inserttags->replace($text);
		$text = str_replace('<a href="files/', '<a href="'.self::QUELL_DOMAIN.'/files/', $text);
		$text = str_replace('<a href="index.php/', '<a href="'.self::QUELL_DOMAIN.'/', $text);

		return $text;
	}

	/**
	 * Löst eine Benutzer-ID in den Klarnamen des Autors auf.
	 *
	 * @param int $id ID des Backend-Benutzers aus tl_news.author
	 *
	 * @return string Der Name, oder ein leerer String, wenn der Benutzer
	 *                zwischenzeitlich gelöscht wurde
	 */
	private function autor(int $id): string
	{
		if ($id < 1)
		{
			return '';
		}

		$name = $this->verbindung->fetchOne('SELECT name FROM tl_user WHERE id = ?', array($id));

		return false === $name ? '' : (string) $name;
	}

	/**
	 * Kopiert das Teaserbild einer Nachricht samt Vorschaubild zur
	 * DSOL-Website.
	 *
	 * Beide Dateien bekommen einen aus der Nachrichten-ID abgeleiteten Namen,
	 * damit ein späterer Durchgang dieselbe Datei überschreibt statt eine
	 * zweite anzulegen.
	 *
	 * Fehler beim Kopieren beenden den Abgleich nicht; sie werden protokolliert
	 * und der Datensatz erhält dann leere Bildnamen.
	 *
	 * @param string|null $uuid    Binäre UUID aus tl_news.singleSRC, oder null
	 *                             bei einer Nachricht ohne Teaserbild
	 * @param int         $newsId  ID der Nachricht, dient als Namensbestandteil
	 * @param string|null $groesse Serialisierte Bildgröße aus tl_news.size
	 *
	 * @return array{image:string,thumb:string} Die Dateinamen im Zielverzeichnis,
	 *                                          beide leer, wenn kein Bild vorliegt
	 *                                          oder das Kopieren scheiterte
	 */
	private function bilderKopieren(?string $uuid, int $newsId, ?string $groesse): array
	{
		$leer = array('image' => '', 'thumb' => '');

		if (empty($uuid))
		{
			return $leer;
		}

		$objBild = FilesModel::findByUuid($uuid);

		if (null === $objBild)
		{
			$this->fehler('… Teaserbild der Nachricht '.$newsId.' ist nicht mehr im Dateiverwalter vorhanden.');

			return $leer;
		}

		$zielVerzeichnis = \dirname($this->projektVerzeichnis).'/'.self::BILD_VERZEICHNIS;

		if (!is_dir($zielVerzeichnis))
		{
			$this->fehler('… Zielverzeichnis '.$zielVerzeichnis.' existiert nicht, Bilder werden übersprungen.');

			return $leer;
		}

		$quellpfad = $this->projektVerzeichnis.'/'.$objBild->path;

		if (!is_file($quellpfad))
		{
			$this->fehler('… Quelldatei '.$quellpfad.' fehlt.');

			return $leer;
		}

		$zieldatei = $newsId.'_teaser.'.$objBild->extension;
		$this->kopieren($quellpfad, $zielVerzeichnis.'/'.$zieldatei, 'Original');

		// Vorschaubild erzeugen lassen. Image::get() gibt es unter Contao 5
		// nicht mehr; die Bildfabrik liefert stattdessen ein Objekt mit dem
		// absoluten Pfad der Datei.
		$masse = StringUtil::deserialize($groesse, true);
		$quellpfadThumb = $this->bildFabrik->create($quellpfad, $masse ?: null)->getPath();

		// Contao erzeugt Vorschaubilder seit 4.9 aufgeschoben: Der Pfad steht
		// zwar fest, die Datei entsteht aber erst beim ersten Abruf über den
		// Browser. Vor dem Kopieren muss sie deshalb angefordert werden.
		// File::createIfDeferred() erledigt das in beiden Contao-Fassungen mit
		// dem jeweils richtigen Resizer, ohne dass wir dessen Dienst-ID kennen
		// müssen — die heißt in 4.13 anders als in 5.
		$this->erzeugeAufgeschoben($quellpfadThumb);

		$zieldateiThumb = $newsId.'_teaser_thumb.'.$objBild->extension;
		$this->kopieren($quellpfadThumb, $zielVerzeichnis.'/'.$zieldateiThumb, 'Vorschaubild');

		return array('image' => $zieldatei, 'thumb' => $zieldateiThumb);
	}

	/**
	 * Fordert ein aufgeschoben erzeugtes Bild an, damit die Datei danach
	 * tatsächlich auf der Festplatte liegt.
	 *
	 * Liegt die Datei bereits vor oder ist sie gar nicht aufgeschoben, tut die
	 * Methode nichts. Schlägt die Erzeugung fehl, wird das hier nicht gemeldet
	 * — der anschließende Kopiervorgang meldet es ohnehin.
	 *
	 * @param string $absoluterPfad Absoluter Pfad der Bilddatei
	 *
	 * @return void
	 */
	private function erzeugeAufgeschoben(string $absoluterPfad): void
	{
		if (is_file($absoluterPfad))
		{
			return;
		}

		// Contao\File erwartet einen Pfad relativ zum Projektverzeichnis.
		$praefix = $this->projektVerzeichnis.\DIRECTORY_SEPARATOR;
		$normiert = str_replace('/', \DIRECTORY_SEPARATOR, $absoluterPfad);

		if (0 !== strpos($normiert, $praefix))
		{
			return;
		}

		$relativerPfad = str_replace('\\', '/', substr($normiert, \strlen($praefix)));

		try
		{
			(new File($relativerPfad))->createIfDeferred();
		}
		catch (\Throwable $e)
		{
			// Der Kopiervorgang meldet den Fehlschlag gleich selbst.
		}
	}

	/**
	 * Kopiert eine Datei und schreibt das Ergebnis ins Protokoll.
	 *
	 * @param string $quelle    Absoluter Pfad der Quelldatei
	 * @param string $ziel      Absoluter Pfad der Zieldatei
	 * @param string $bezeichnung Wort für die Protokollzeile, etwa "Original"
	 *
	 * @return void
	 */
	private function kopieren(string $quelle, string $ziel, string $bezeichnung): void
	{
		if (@copy($quelle, $ziel))
		{
			$this->zeile('… '.$bezeichnung.' kopiert nach '.$ziel);

			return;
		}

		$this->fehler('… '.$bezeichnung.' konnte nicht nach '.$ziel.' kopiert werden.');
	}

	/**
	 * Hängt eine Protokollzeile an.
	 *
	 * @param string $text Die Zeile, darf bereits HTML enthalten
	 *
	 * @return void
	 */
	private function zeile(string $text): void
	{
		$this->protokoll[] = $text;
	}

	/**
	 * Hängt eine als Fehler gekennzeichnete Protokollzeile an.
	 *
	 * Der Text wird maskiert, weil hier auch Dateipfade und Meldungen aus
	 * Ausnahmen landen, die im Browser als HTML gedeutet würden.
	 *
	 * @param string $text Die Fehlermeldung
	 *
	 * @return void
	 */
	private function fehler(string $text): void
	{
		$this->protokoll[] = '<span class="tl_red">'.StringUtil::specialchars($text).'</span>';
	}

	/**
	 * Liefert die CREATE-TABLE-Anweisung für die entfernte Tabelle.
	 *
	 * Die Tabelle wird von Contao nicht verwaltet, deshalb steht ihre
	 * Definition hier als fester Text statt in einer DCA-Datei.
	 *
	 * @return string Die vollständige SQL-Anweisung
	 */
	private function tabellenDefinition(): string
	{
		return "CREATE TABLE `".self::FERN_TABELLE."` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`tstamp` int(10) unsigned NOT NULL DEFAULT 0,
			`headline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`date` int(10) unsigned NOT NULL DEFAULT 0,
			`subheadline` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`teaser` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
			`author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`imageTitle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`size` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`caption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			`floating` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'above',
			`published` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
	}
}
