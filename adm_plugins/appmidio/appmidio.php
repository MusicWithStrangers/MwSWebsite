<?php
/******************************************************************************
 * Appmidio
 *
 * Version 1.7.3
 *
 * Plugin das für die Android-App Appmidio
 *
 * Compatible with Admidio version 3.3.x
 *
 * Übergaben:
 *
 * Copyright    : (c) 2013-2015 The Zettem Team
 * Homepage     : https://play.google.com/store/apps/details?id=de.zettem.Appmidio
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version 1.0.0: zettem
 * Datum        : 12.01.2013
 *
 * Version 1.0.1: zettem
 * Datum        : 19.01.2013
 * Änderung     : - inaktive Rollen werden nicht mehr an die App übermittelt
 *                - nur noch Anzeige von Rollenzuordnungen gem. Berechtigung
 *                - keine Fehlermeldung mehr bei Einbau des Plugins in die Admidio-Seite
 *
 * Version 1.0.2: zettem
 * Datum        : 22.01.2013
 * Änderung     : - Sicherheitslücken im Plugin geschlossen (Danke an Admidio Team für die Hinweise)
 *
 * Version 1.0.3: zettem
 * Datum        : 23.01.2013
 * Änderung     : - noch weitere Sicherheitslücken im Plugin geschlossen (Danke an Admidio Team für die Hinweise)
 *
 * Version 1.1.0: zettem
 * Datum        : 17.02.2013
 * Änderung     : - Anzeige eines QRcodes, wenn App in Admidio integriert wird
 *                - Anzeige von ehemaligen Mitgliedern einer Rolle (über Kontext-Menü)
 *                - Anzeige einer Geburtstagsliste
 *                - Anzeige von Ankündigungen
 *                - Integration von Google-Maps und Google-Navigator
 *                - und zudem für erste Beta-Tests (muss über config.php aktiviert werden):
 *                  - Bearbeiten von Profilen (über Admidio-Webseite)
 *                  - Bearbeiten von Rollenzugehörigkeiten (über Admidio-Webseite)
 *                  - Mailversand an Rollen (über Admidio-Webseite)
 *
 * Version 1.2.0: zettem
 * Datum        : 02.04.2013
 * Änderung     : - Anzeige der Leiter von Rollen
 *                - Anzeige von Terminen
 *                - Anpassungen für Admidio 2.4.x
 *
 * Version 1.3.0: zettem
 * Datum        : 08.12.2014
 * Änderung     : - Neuerungen:
 *                  - Organisationsauswahl
 *                  - Suche nach Mitgliedern (nur mit Berechtigung für Benutzerverwaltung)
 *                  - Sortieren von Mitgliederlisten  (nach Alter oder Nachname oder Vorname)
 *                - Fehlerkorrekturen/Bugfixes
 *                  - Umlaute in Terminen
 *                  - Textbezeichnungen vom Mitgliedsbeitrag-Pluigin
 *                  - Ermittlung von Jubiläen im get_birthday-Command
 *                 - div. Optimierungen
 *
 * Version 1.3.2: zettem
 * Datum        : 01.03.2015
 * Änderung     : - Korrektur für die Anzeige der Geburtstage in der Mitgliederliste
 *
 * Version 1.4.0: zettem
 * Datum        : 12.07.2015
 * Änderung     : - Anpassungen für Admidio 3.0
 *
 * Version 1.5.0: fasse
 * Datum        : 07.02.2016
 * Änderung     : - Compatibel with Admidio 3.1
 *
 * Version 1.6.0: fasse
 * Date         : 10.12.2016
 * Changes      : - Compatibel with Admidio 3.2
 *
 * Version 1.6.1: fasse
 * Date         : 23.05.2017
 * Changes      : - Fix problem with PHP 7
 *
 * Version 1.7.0: fasse
 * Date         : 25.02.2018
 * Changes      : - Compatibel with Admidio 3.3
 *
 * Version 1.7.2: fasse
 * Date         : 02.04.2018
 * Changes      : - Compatibel with Admidio 3.3
 *                - Members of a role weren't shown if end date wasn't 31.12.9999
 *
 * Version 1.7.3: fasse
 * Date         : 06.05.2018
 * Changes      : - Show current members if they have a valid membership at acutal date
 *
*****************************************************************************/

$plugin_version    = '1.5.0';
$plugin_debug      = 1;
$possible_commands = array(
						'gp'  => 'get_preferences',
						'go'  => 'get_organisations',
						'ga'  => 'get_announcements',
						'gb'  => 'get_birthdays',
						'gd'  => 'get_dates',
						'gr'  => 'get_roles',
						'gm'  => 'get_members',
						'gu'  => 'get_users',
						'gmd' => 'get_member_details',
						'gmr' => 'get_member_roles'
						);

$rootPath = dirname(dirname(__DIR__));
$pluginFolder = basename(__DIR__);

require_once($rootPath. '/adm_program/system/common.php');
require_once(__DIR__. '/functions/common.php');
if(file_exists(__DIR__. '/config.php')) {
	require_once(__DIR__. '/config.php');
}

// pruefen, ob alle Einstellungen in config.php gesetzt wurden
// falls nicht, hier noch mal die Default-Werte setzen
if(isset($plg_show_title) == false || is_numeric($plg_show_title) == false)
{
    $plg_show_title = 1;
}

if(isset($plg_enable_admidio_edit) == false || is_numeric($plg_enable_admidio_edit) == false)
{
    $plg_enable_admidio_edit = 0;
}

if(isset($plg_enable_admidio_mail) == false || is_numeric($plg_enable_admidio_mail) == false)
{
    $plg_enable_admidio_mail = 0;
}

if(isset($plg_enable_birthday_module) == false || is_numeric($plg_enable_birthday_module) == false)
{
    $plg_enable_birthday_module = 1;
}

if(isset($plg_birthday_anniversaries) == false || is_string($plg_birthday_anniversaries) == false)
{
    $plg_birthday_anniversaries = "10,18,20,30,40,50,60,65,70,75,80,85,90,95,100,105,110,115,120";
}

if(isset($plg_birthday_roles) == false || is_string($plg_birthday_roles) == false)
{
    $plg_birthday_roles = "";
}

if(isset($plg_excluded_categories) == false || is_string($plg_excluded_categories) == false)
{
    $plg_excluded_categories = "";
}

if(isset($plg_excluded_roles) == false || is_string($plg_excluded_roles) == false)
{
    $plg_excluded_roles = "";
}

if(isset($plg_excluded_fields) == false || is_string($plg_excluded_fields) == false)
{
    $plg_excluded_fields = "";
}


// Initialize and check the parameters
$getCommand = '';
if ($plugin_debug)
{
	if(isset($_REQUEST['cmd']))
	{
		$getCommand = $_REQUEST['cmd'];
	}
} else {
	if(isset($_POST['cmd']))
	{
		$getCommand = admFuncVariableIsValid($_POST, 'cmd', 'string', array('requireValue' => true, 'validValues' => array_keys($possible_commands), 'directOutput' => true));
	}
}

if ($getCommand == '')
{
	// Prüfen, ob Aufruf von einer alten App-Version erfolgt
	$getQuery = '';
	if(isset($_POST['query']))
	{
		$getQuery = admFuncVariableIsValid($_POST, 'query', 'string', array('requireValue' => true, 'validValues' => array('curuser', 'roles', 'members', 'details', 'detrols'), 'directOutput' => true));
	}
	if ($getQuery != '') {
		// Aufrufen des bisherigen Plugins, da noch eine alte App-Version die Anfrage stellt
		include (__DIR__. '/appmidio_103.php');
		exit();
	} else {
		// Prüfen, ob Titel gezeigt werden soll oder nicht
		if($plg_show_title == 1) {
			echo '<div id="plugin_'. $pluginFolder. '" class="admPluginContent">
			<div class="admPluginHeader"><h3>Appmidio</h3></div>
			<div class="admPluginBody">';
		}

		$png_title = 'Appmidio-Plugin V'.$plugin_version;
		$png_link = 'https://play.google.com/store/apps/details?id=de.zettem.Appmidio';
		$qrcodepng = ADMIDIO_URL.'/'.FOLDER_PLUGINS.'/'.$pluginFolder.'/functions/phpqrcode_png.php?text='.$png_link;

		echo '<a href="'.$png_link.'" target="_blank"><img src="'.$qrcodepng.'" title="'.$png_title.'" alt="'.$png_title.'" /></a>';

		// Prüfen, ob Titel gezeigt werden soll oder nicht
		if($plg_show_title == 1) {
			echo '</div></div>';
		}
	}
}
else
{
	header('Content-Type: application/json; charset=utf-8');

	require_once(__DIR__. '/commands/'.$possible_commands[$getCommand].'.php');
	$sql = sql_command();
	print(json_result($sql));
}
?>
