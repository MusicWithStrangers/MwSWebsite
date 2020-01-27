<?php
/******************************************************************************
 * Appmidio Command get_member_details.php
 *
 * Funktion fuer das Admidio-Plugin Appmidio, um die Details eines Mitgliedes auszulesen
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
	global $plugin_debug, $gDb, $gValidLogin, $gCurrentUser, $gProfileFields, $plg_excluded_categories, $plg_excluded_fields;

	// Initialize and check the parameters
	$authorizedUser = true;
	if ($plugin_debug)
	{
		$getUserId = $_REQUEST['usr_id'];
	} else {
		$getUserId = admFuncVariableIsValid($_POST, 'usr_id', 'numeric', array('requireValue' => true, 'directOutput' => true));
	}

	if($gValidLogin == false)
	{
		$authorizedUser = false;
		msg_unauthorized();
	}
	else if (getCurrentDbVersion() >= '3.0.0')
	{
		// create user object
		$user = new User($gDb, $gProfileFields, $getUserId);

		// check rights for Admidio 3.0.x and higher
		if(!$gCurrentUser->hasRightViewProfile($user))
		{
			$authorizedUser = false;
			//$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
			msg_forbidden('Keine Berechtigung, die Daten dieses Profils anzuzeigen.');
		}
	}
	else if (getCurrentDbVersion() >= '2.4.0')
	{
		// create user object
		$user = new User($gDb, $gProfileFields, $getUserId);

		// check rights for Admidio 2.4.x and higher
		if(!$gCurrentUser->viewProfile($user))
		{
			$authorizedUser = false;
			//$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
			msg_forbidden('Keine Berechtigung, die Daten dieses Profils anzuzeigen.');
		}
	}
	else
	{
		// check rights for Admidio 2.3.x
		if(!$gCurrentUser->viewProfile($getUserId))
		{
			$authorizedUser = false;
			//$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
			msg_forbidden('Keine Berechtigung, die Daten dieses Profils anzuzeigen.');
		}
	}

	if ($authorizedUser)
	{
		$sql = "SELECT ";
		$sql = $sql."	cat_name ";
		$sql = $sql."	, usf_id ";
		$sql = $sql."	, usf_name ";
		$sql = $sql."	, usf_name_intern ";
		$sql = $sql."	, usf_type ";
		$sql = $sql."	, IFNULL(usd_value, '') AS usd_value ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_user_data ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_user_fields ON usf_id = usd_usf_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_categories ON cat_id = usf_cat_id ";
		$sql = $sql."WHERE ";
		$sql = $sql."	usd_usr_id = ".$getUserId." ";
		if ($gCurrentUser->editUsers() == false)
		{
		$sql = $sql."	AND ((usf_hidden = 0) OR (usd_usr_id = ".$gCurrentUser->getValue('usr_id').")) ";
		}
		if ((isset($plg_excluded_categories)) && ($plg_excluded_categories."" != ""))
		{
		$sql = $sql." AND cat_id NOT IN (".$plg_excluded_categories.") ";
		}
		if ((isset($plg_excluded_fields)) && ($plg_excluded_fields."" != ""))
		{
		$sql = $sql." AND usf_id NOT IN (".$plg_excluded_fields.") ";
		}
		$sql = $sql."ORDER BY ";
		$sql = $sql."	cat_sequence, cat_id, usf_sequence ";

		return $sql;
	}
}

?>
