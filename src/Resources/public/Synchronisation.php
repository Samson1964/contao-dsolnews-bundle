<?php

// Contao einbinden
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodsolnews/Synchronisation.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

/**
 * Run in a custom namespace, so the class can be replaced
 */
use Contao\Controller;

/**
 * Klasse Synchronisation
 * ==================================================================================
 * Synchronisiert die Nachrichten der DSOL-Website mit dem lokalen Nachrichten-Archiv
 */
class Synchronisation
{
	public function __construct()
	{
	}

	public function run()
	{

		try
		{
			// Tabelle content der DSOL-Datenbank auslesen
			$objDSOLDB = \Database::getInstance(array
			(
				'dbHost'     => $GLOBALS['TL_CONFIG']['dsolnews_host'],
				'dbUser'     => $GLOBALS['TL_CONFIG']['dsolnews_user'],
				'dbPass'     => $GLOBALS['TL_CONFIG']['dsolnews_pass'],
				'dbDatabase' => $GLOBALS['TL_CONFIG']['dsolnews_db']
			));

			// Nachrichten der DSOL-Website auslesen
			$objDSOL = $objDSOLDB->prepare("SELECT * FROM dsb_content")
			                     ->execute();

			// Nachrichten aus dem lokalen Archiv auslesen
			$objNews = \Database::getInstance()->prepare("SELECT * FROM tl_news WHERE pid=?")
			                                   ->execute($GLOBALS['TL_CONFIG']['dsolnews_archiv']);
			echo '<b>'.$objNews->numRows . ' Nachrichten</b> im lokalen Archiv<br><br>';

			// Nachrichten vom lokalen Archiv zur DSOL-Website hinzufügen (Neu/Update)
			echo 'Prüfung der Nachrichten im lokalen Archiv...<br>';
			$news_new = 0;
			$news_update = 0;
			$text_new = '';
			$text_update = '';
			if($objNews->numRows)
			{
				while($objNews->next())
				{
					// Nachricht im entfernten Archiv vorhanden?
					$objRecord = $objDSOLDB->prepare("SELECT * FROM dsb_content WHERE id=?")
					                       ->limit(1)
					                       ->execute($objNews->dsol_id);
					if($objRecord->numRows == 1)
					{
						// Nachricht bereits vorhanden, Update prüfen
						if($objRecord->tstamp == $objNews->tstamp)
						{
							//$text_update .= '... ... Update: '.$objNews->headline.' (DSOL: '.$objRecord->tstamp.' / Contao: '.$objNews->tstamp.')<br>';
							$text_update .= '... ... Update: '.$objNews->headline.'<br>';
							$news_update++;
							$bild = self::getImages($objNews->singleSRC, $objNews->id, $objNews->size);
							$set = array
							(
								'tstamp'      => $objNews->tstamp,
								'headline'    => $objNews->headline,
								'date'        => $objNews->date,
								'subheadline' => $objNews->subheadline,
								'teaser'      => self::Parser($objNews->teaser),
								'author'      => self::Autor($objNews->author),
								'image'       => $bild['image'],
								'thumbnail'   => $bild['thumb'],
								'alt'         => $objNews->alt,
								'imageTitle'  => $objNews->imageTitle,
								'size'        => $objNews->size,
								'caption'     => $objNews->caption,
								'floating'    => $objNews->floating,
								'published'   => $objNews->published,
							);
							$objInsert = $objDSOLDB->prepare("UPDATE dsb_content %s WHERE id=?")
							                       ->set($set)
							                       ->execute($objNews->dsol_id);
						}
					}
					else
					{
						// Nachricht nicht vorhanden, jetzt eintragen
						$text_new .= '... ... Neu: '.$objNews->headline.'<br>';
						$news_new++;
						$bild = self::getImages($objNews->singleSRC, $objNews->id, $objNews->size);
						$set = array
						(
							'tstamp'      => $objNews->tstamp,
							'headline'    => $objNews->headline,
							'date'        => $objNews->date,
							'subheadline' => $objNews->subheadline,
							'teaser'      => self::Parser($objNews->teaser),
							'author'      => self::Autor($objNews->author),
							'image'       => $bild['image'],
							'thumbnail'   => $bild['thumb'],
							'alt'         => $objNews->alt,
							'imageTitle'  => $objNews->imageTitle,
							'size'        => $objNews->size,
							'caption'     => $objNews->caption,
							'floating'    => $objNews->floating,
							'published'   => $objNews->published,
						);
						$objInsert = $objDSOLDB->prepare("INSERT INTO dsb_content %s")
						                       ->set($set)
						                       ->execute();
						// dsol_id in Contao eintragen
						$objUpdate = \Database::getInstance()->prepare("UPDATE tl_news SET dsol_id=? WHERE id=?")
						                                     ->execute($objInsert->insertId, $objNews->id);
					}
				}
			}
			echo '... <b>'.$news_new.'</b> neue Nachrichten nach DSOL-Website exportiert<br>';
			echo $text_new;
			echo '... <b>'.$news_update.'</b> Nachrichten auf DSOL-Website geändert<br>';
			echo $text_update;

			echo '<br>Fertig';
		}


		catch(\Doctrine\DBAL\Exception\TableNotFoundException $ex)
		{
			// Tabelle dsb_content existiert nicht, jetzt anlegen
			//echo "Tabelle dsb_content nicht gefunden<br>";
			$sql =
			"CREATE TABLE `dsb_content` (
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

			$objDSOL = $objDSOLDB->prepare($sql)
			                     ->execute();
			//echo "Tabelle wurde angelegt";
			return;
		}

		catch(\Exception $ex)
		{
			print_r($ex);
		}

	}

	/*
	 * Funktion Parser
	 * Ersetzt in einem Text alle Contao-Variablen und modifiziert Links
	 */
	protected function Parser($string)
	{
		$string = \Controller::replaceInsertTags($string);
		$string = str_replace('<a href="files/', '<a href="https://www.schachbund.de/files/', $string);
		$string = str_replace('<a href="index.php/', '<a href="https://www.schachbund.de/', $string);
		return $string;
	}

	/*
	 * Funktion Autor
	 * Liefert den Namen des Autors zu einer ID
	 */
	protected function Autor($id)
	{
		$objUser = \Database::getInstance()->prepare("SELECT name FROM tl_user WHERE id=?")
		                                   ->execute($id);
		return (string)$objUser->name.'';
	}

	/*
	 * Funktion getImages
	 * Kopiert das Teaserbild in das Zielsystem
	 */
	protected function getImages($uuid, $newsId, $imageSize)
	{
		// Bild abrufen
		if($uuid)
		{
			$groesse = unserialize($imageSize);
			$objBild = \FilesModel::findByUuid($uuid);
			$quellpfad = $_SERVER['DOCUMENT_ROOT'].'/../'.$objBild->path;
			$zieldatei = $newsId.'_teaser.'.$objBild->extension;
			$zielpfad = $_SERVER['DOCUMENT_ROOT'].'/../../deutsche-onlineliga/images/'.$zieldatei;
			echo '... Kopiere Original '.$quellpfad.' nach ';
			echo $zielpfad.' ';
			$status = copy($quellpfad, $zielpfad);
			if($status) echo 'OK<br>';
			else echo 'ERROR<br>';

			$thumbnail = \Image::get($objBild->path, $groesse[0], $groesse[1], $groesse[2]);

			if(method_exists(\File::class, 'createIfDeferred') && is_callable(\File::class, 'createIfDeferred'))
			{
				(new \File($thumbnail))->createIfDeferred(); // Bild erstellen lassen, ab Contao 4.8.2
			}

			$quellpfad_thumb = $_SERVER['DOCUMENT_ROOT'].'/../'.$thumbnail;
			$zieldatei_thumb = $newsId.'_teaser_thumb.'.$objBild->extension;
			$zielpfad_thumb = $_SERVER['DOCUMENT_ROOT'].'/../../deutsche-onlineliga/images/'.$zieldatei_thumb;
			echo '... Kopiere Thumbnail '.$quellpfad_thumb.' nach ';
			echo $zielpfad_thumb.' ';
			$status = copy($quellpfad_thumb, $zielpfad_thumb);
			if($status) echo 'OK<br>';
			else echo 'ERROR<br>';

			return array('image' => $zieldatei, 'thumb' => $zieldatei_thumb);
		}
		else
		{
			return array('image' => '', 'thumb' => '');
		}
	}
}

/**
 * Instantiate controller
 */
$objClick = new Synchronisation();
$objClick->run();
