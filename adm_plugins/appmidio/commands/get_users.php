<?php
/******************************************************************************
 * Appmidio Command get_members.php
 *
 * Funktion fuer das Admidio-Plugin Appmidio, um die Mitglieder einer Rolle auszulesen
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
	global $plugin_debug, $gValidLogin, $gDb, $gCurrentUser, $gCurrentOrganization;

	// Initialize and check the parameters
	$getQuery = '';
	if ($plugin_debug)
	{
		$getQuery = $_REQUEST['q'];
	} else {
		$getQuery = admFuncVariableIsValid($_POST, 'q', 'string', array('defaultValue' => '', 'directOutput' => false));
	}

	if($gValidLogin == false)
	{
		msg_unauthorized();
	}
	else if($gCurrentUser->editUsers() == false)
	{
		//$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
		$gMessage = 'Keine Berechtigung, direkt nach Benutzern zu suchen';
		msg_forbidden('Keine Berechtigung, direkt nach Benutzern zu suchen.');
	}
	else
	{
		$sql = "SELECT ";
		$sql = $sql."	usr_id ";
		$sql = $sql."	, IFNULL(f1.usd_value, '') AS first_name ";
		$sql = $sql."	, IFNULL(f2.usd_value, '') AS last_name ";
		$sql = $sql."	, IFNULL(f3.usd_value, '') AS birthday ";
		$sql = $sql."	, (SELECT (count(*) > 0) FROM ".TABLE_PREFIX."_members INNER JOIN ".TABLE_PREFIX."_roles ON rol_id = mem_rol_id INNER JOIN ".TABLE_PREFIX."_categories ON cat_id = rol_cat_id WHERE mem_usr_id = usr_id AND mem_end = '9999-12-31' AND rol_valid = 1 AND cat_org_id = ".$gCurrentOrganization->getValue('org_id').") AS mem_leader ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_users ";
		$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f1 ON f1.usd_usr_id = usr_id AND f1.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'FIRST_NAME') ";
		$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f2 ON f2.usd_usr_id = usr_id AND f2.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'LAST_NAME') ";
		$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f3 ON f3.usd_usr_id = usr_id AND f3.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'BIRTHDAY') ";
		$sql = $sql."WHERE (";
		$sql = $sql."	f1.usd_value like '%".$getQuery."%' ";
		$sql = $sql."	OR f2.usd_value like '%".$getQuery."%' ";
		$sql = $sql."	OR f3.usd_value like '%".$getQuery."%' ";
		$sql = $sql."	) AND (SELECT count(*) FROM ".TABLE_PREFIX."_members INNER JOIN ".TABLE_PREFIX."_roles ON rol_id = mem_rol_id INNER JOIN ".TABLE_PREFIX."_categories ON cat_id = rol_cat_id WHERE mem_usr_id = usr_id AND rol_valid = 1 AND cat_org_id = ".$gCurrentOrganization->getValue('org_id').") > 0 ";
		$sql = $sql."ORDER BY ";
		$sql = $sql."	(SELECT (count(*) > 0) FROM ".TABLE_PREFIX."_members INNER JOIN ".TABLE_PREFIX."_roles ON rol_id = mem_rol_id INNER JOIN ".TABLE_PREFIX."_categories ON cat_id = rol_cat_id WHERE mem_usr_id = usr_id AND mem_end = '9999-12-31' AND rol_valid = 1 AND cat_org_id = ".$gCurrentOrganization->getValue('org_id').") DESC ";

		return $sql;
	}
}

?>
