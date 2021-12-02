<?php

namespace Schachbulle\ContaoSchiedsrichterverteilerBundle\Classes;

/*
 * Ersetzt den Tag {{adresse::ID}} bzw. {{adresse::ID::Funktion}}
 * durch die entsprechende Adresse aus tl_adressen
 */

class Standardverteiler extends \Backend
{

	public function setDefault(\DataContainer $dc)
	{
		if(\Input::get('key') != 'setDefault')
		{
			// Beenden, wenn der Parameter nicht übereinstimmt
			return '';
		}

		// Objekt BackendUser importieren
		$this->import('BackendUser','User');

		// Formular wurde abgeschickt, CSS-Datei importieren
		if(\Input::post('FORM_SUBMIT') == 'tl_schiedsrichterverteiler_default')
		{
			// Aktive Verteiler suchen
			$result = \Database::getInstance()->prepare("SELECT * FROM tl_schiedsrichterverteiler WHERE standard = ?")
			                                  ->execute(1);
			if($result->numRows)
			{
				// Standard in allen Verteilern deaktivieren
				while($result->next())
				{
					$set = array
					(
						'tstamp'   => time(),
						'standard' => ''
					);
					\Database::getInstance()->prepare("UPDATE tl_schiedsrichterverteiler %s WHERE id = ?")
					                        ->set($set)
					                        ->execute($result->id);
				}
			}

			// Neuen Standardverteiler setzen
			$verteiler = \Input::post('verteiler');
			$set = array
			(
				'tstamp'   => time(),
				'standard' => '1'
			);
			\Database::getInstance()->prepare("UPDATE tl_schiedsrichterverteiler %s WHERE id = ?")
			                        ->set($set)
			                        ->execute($verteiler);

			// Standardverteilerdaten laden
			$result = \Database::getInstance()->prepare("SELECT * FROM tl_schiedsrichterverteiler WHERE standard = ?")
			                                  ->execute(1);
			if($result->numRows)
			{
				// Tabelle sr-person der Ergebnisdienst-Datenbank auslesen
				$objImportDB = \Database::getInstance(array
				(
					'dbHost'     => $GLOBALS['TL_CONFIG']['schiedsrichter_host'],
					'dbUser'     => $GLOBALS['TL_CONFIG']['schiedsrichter_user'],
					'dbPass'     => $GLOBALS['TL_CONFIG']['schiedsrichter_pass'],
					'dbDatabase' => $GLOBALS['TL_CONFIG']['schiedsrichter_db']
				));

				// Abfrage zusammenbauen
				$sql = '';
				// Feld selektion
				if($result->selektion)
				{
					$sql .= '(';
					$selektion = str_split(strtoupper($result->selektion)); // String in Array umwandeln, Zeichen in Großbuchstaben
					for($x = 0; $x < count($selektion); $x++)
					{
						$sql .= "Selektion = '".$selektion[$x]."'";
						if($x + 1 < count($selektion)) $sql .= ' OR ';
					}
					$sql .= ') ';
				}

				// Feld lizenz
				if($result->lizenz)
				{
					$lizenz = unserialize($result->lizenz);
					
					if($sql) $sql .= ' AND (';
					else $sql .= '(';
					for($x = 0; $x < count($lizenz); $x++)
					{
						$sql .= "Lizenz = '".$lizenz[$x]."'";
						if($x + 1 < count($lizenz)) $sql .= ' OR ';
					}
					$sql .= ')';
				}

				// Feld lizenzstatus
				if($result->lizenzstatus)
				{
					$lizenzstatus = unserialize($result->lizenzstatus);
					
					if($sql) $sql .= ' AND (';
					else $sql .= '(';
					for($x = 0; $x < count($lizenzstatus); $x++)
					{
						$sql .= "Lizenz_Status = '".$lizenzstatus[$x]."'";
						if($x + 1 < count($lizenzstatus)) $sql .= ' OR ';
					}
					$sql .= ')';
				}

				// Abfrage ausführen
				$objSchiedsrichter = $objImportDB->prepare('SELECT * FROM `sr-person` WHERE '.$sql)
				                                 ->execute();
				//print_r('SELECT * FROM `sr-person` WHERE '.$sql);

				// E-Mail-Adressen in Array eintragen
				$adressenSchiedsrichter = array();
				if($objSchiedsrichter->numRows)
				{
					while($objSchiedsrichter->next())
					{
						if($objSchiedsrichter->E_Mail1)
						{
							// Adresse eintragen
							$adressenSchiedsrichter[] = $objSchiedsrichter->E_Mail1;
						}
						else
						{
							// Keine Adresse vorhanden, in tl_log vermerken
							\System::log('Newsletter-Synchronisation Schiedsrichter: '.$objSchiedsrichter->Nachname.','.$objSchiedsrichter->Vorname.' ohne E-Mail', __CLASS__.'::'.__FUNCTION__, TL_NEWSLETTER);
						}
					}
				}
				// Zwangsadresse als Newsletter-Empfänger eintragen
				$adressenSchiedsrichter[] = $GLOBALS['TL_CONFIG']['schiedsrichter_newsletterMail'];
				$adressenSchiedsrichter = array_unique($adressenSchiedsrichter);

				// Adressen aus Newsletter-Tabelle auslesen
				$objNewsletter = \Database::getInstance()->prepare("SELECT * FROM tl_newsletter_recipients WHERE pid=?")
				                                         ->execute($GLOBALS['TL_CONFIG']['schiedsrichter_newsletter']);
				
				// Adresse eintragen
				$adressenNewsletter = array();
				if($objNewsletter->numRows)
				{
					while($objNewsletter->next())
					{
						$adressenNewsletter[] = $objNewsletter->email;
					}
				}
				
				// Vergleicht Array $adressenSchiedsrichter mit dem Array $adressenNewsletter und gibt die Werte aus $adressenSchiedsrichter zurück, die nicht in $adressenNewsletter enthalten sind. 
				$neuabonnenten = array_diff($adressenSchiedsrichter, $adressenNewsletter);
				// Vergleicht Array $adressenNewsletter mit dem Array $adressenSchiedsrichter und gibt die Werte aus $adressenNewsletter zurück, die nicht in $adressenSchiedsrichter enthalten sind. 
				$altabonnenten = array_diff($adressenNewsletter, $adressenSchiedsrichter);
				
				// Neue Abonnenten in den Newsletter eintragen
				foreach($neuabonnenten as $email)
				{
					\Database::getInstance()->prepare("INSERT INTO tl_newsletter_recipients (pid, tstamp, email, active) VALUES (?, ?, ?, ?)")
					                        ->execute($GLOBALS['TL_CONFIG']['schiedsrichter_newsletter'], time(), $email, 1);
				}
				
				// Gelöschte Kunden aus Newsletter löschen
				foreach($altabonnenten as $email)
				{
					\Database::getInstance()->prepare("DELETE FROM tl_newsletter_recipients WHERE pid=? AND email=?")
					                        ->execute($GLOBALS['TL_CONFIG']['schiedsrichter_newsletter'], $email);
				}
			}

			// Cookie setzen und zurückkehren (key=setDefault aus URL entfernen)
			//\System::setCookie('BE_PAGE_OFFSET', 0, 0);
			//$this->redirect(str_replace('&key=setDefault', '', \Environment::get('request')));
		}

		// Verteiler laden
		$result = \Database::getInstance()->prepare("SELECT * FROM tl_schiedsrichterverteiler WHERE published = ? ORDER BY titel ASC")
		                                  ->execute(1);
		$optionen = '';
		if($result->numRows)
		{
			while($result->next())
			{
				$optionen .= '<option value="'.$result->id.'"'.($result->standard ? ' selected' : '').'>'.$result->titel.'</option>';
			}
		}

		// Return form
		return '
<div id="tl_buttons">
<a href="'.ampersand(str_replace('&key=setDefault', '', \Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
</div>

<h2 class="sub_headline">'.$GLOBALS['TL_LANG']['tl_schiedsrichterverteiler']['setDefaultHeadline'][1].'</h2>
'.\Message::generate().'
<div class="tl_listing_container" id="tl_listing">
<form action="'.ampersand(\Environment::get('request'), true).'" id="tl_schiedsrichterverteiler_default" class="tl_form" method="post" enctype="multipart/form-data">
<div class="tl_formbody_edit">
<input type="hidden" name="FORM_SUBMIT" value="tl_schiedsrichterverteiler_default">
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">

<div class="tl_tbox">
  <h3><label for="verteiler">'.$GLOBALS['TL_LANG']['tl_schiedsrichterverteiler']['setDefaultSelectheadline'][0].'</label></h3>
  <select name="verteiler" id="verteiler" class="tl_select" onfocus="Backend.getScrollOffset()">
    '.$optionen.'
  </select>
  <p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_schiedsrichterverteiler']['setDefaultSelectheadline'][1].'</p>
</div>

</div>

<div class="tl_formbody_submit">

<div class="tl_submit_container">
  <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.$GLOBALS['TL_LANG']['tl_schiedsrichterverteiler']['setDefaultSubmit'][0].'">
  <p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_schiedsrichterverteiler']['setDefaultSubmit'][1].'</p>
</div>

</div>
</form>
</div>'; 
	}
}
