<?php
/******************************************************************************
 * Appmidio Command get_preferences.php
 *
 * Funktion fuer das Admidio-Plugin Appmidio, um die aktuellen Einstellungen auszulesen
 *
 * Copyright    : (c) 2013-2015 The Zettem Team
 * Homepage     : https://play.google.com/store/apps/details?id=de.zettem.Appmidio
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
*****************************************************************************/

require_once($rootPath. '/adm_program/system/common.php');
require_once($rootPath. '/'.FOLDER_PLUGINS. '/'.$pluginFolder.'/functions/common.php');


function sql_command()
{
	global $plugin_version, $gValidLogin, $gCurrentUser, $gCurrentOrganization, $gSettingsManager, $plg_enable_birthday_module, $plg_enable_admidio_edit, $plg_enable_admidio_mail;

 	$sql = "SELECT ";
	$sql = $sql."	org_id ";
	$sql = $sql."	, org_longname ";
	$sql = $sql."	, org_shortname ";
	$sql = $sql."	, org_org_id_parent ";
	$sql = $sql."	, org_homepage ";
	$sql = $sql."	, ".$gSettingsManager->get('enable_announcements_module')." AS enable_announcements_module ";
	$sql = $sql."	, ".$gSettingsManager->get('enable_dates_module')." AS enable_dates_module ";
	$sql = $sql."	, ".$plg_enable_birthday_module." AS enable_birthday_module ";
	$sql = $sql."	, ".$plg_enable_admidio_edit." AS enable_admidio_edit ";
	$sql = $sql."	, ".$plg_enable_admidio_mail." AS enable_admidio_mail ";
	$sql = $sql."	, '".getCurrentDbVersion()."' AS db_version ";
	$sql = $sql."	, '".$plugin_version."' AS plugin_version ";
	$sql = $sql."	, '".$gValidLogin."' AS valid_login ";
	$sql = $sql."	, '".$gCurrentUser->getValue('usr_id')."' AS usr_id ";
	$sql = $sql."	, '".$gCurrentUser->getValue('usr_login_name')."' AS usr_login_name ";
	$sql = $sql."	, IFNULL(f1.usd_value, '') AS first_name ";
	$sql = $sql."	, IFNULL(f2.usd_value, '') AS last_name ";
	$sql = $sql."FROM ";
	$sql = $sql."	".TABLE_PREFIX."_organizations ";
	$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f1 ON f1.usd_usr_id = ".$gCurrentUser->getValue('usr_id')." AND f1.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'FIRST_NAME') ";
	$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f2 ON f2.usd_usr_id = ".$gCurrentUser->getValue('usr_id')." AND f2.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'LAST_NAME') ";
	$sql = $sql."WHERE ";
	$sql = $sql."	org_id = ".$gCurrentOrganization->getValue('org_id')." ";

	return $sql;
}
?>
