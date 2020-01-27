<?php
/******************************************************************************
 * Appmidio Command get_member_roles.php
 *
 * Funktion fuer das Admidio-Plugin Appmidio, um die Rollen eines Mitgliedes auszulesen
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
	global $plugin_debug, $gDb, $gValidLogin, $gCurrentUser, $gCurrentOrganization, $gProfileFields, $plg_excluded_categories, $plg_excluded_roles;

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
		$sql = $sql."	1 AS grp_id ";
		$sql = $sql."	, 'Rollenmitgliedschaften' AS grp_name ";
		$sql = $sql."	, cat_sequence ";
		$sql = $sql."	, '' AS org_shortname ";
		$sql = $sql."	, cat_name ";
		$sql = $sql."	, rol_name ";
		$sql = $sql."	, IFNULL(mem_begin,'') AS mem_begin ";
		$sql = $sql."	, '' AS mem_end ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_members ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_roles ON mem_rol_id = rol_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_categories ON rol_cat_id = cat_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_organizations ON cat_org_id = org_id ";
		$sql = $sql."WHERE ";
		$sql = $sql."	mem_usr_id = ".$getUserId." ";
		$sql = $sql."	AND mem_end = '9999-12-31' ";
		$sql = $sql."	AND org_id = '".$gCurrentOrganization->getValue('org_id')."' ";
		$sql = $sql."	AND cat_type = 'ROL' ";
		$sql = $sql."	AND rol_valid = 1 ";
		$sql = $sql."	AND cat_name_intern <> 'EVENTS' ";
		$sql = $sql."	AND ((rol_this_list_view = 2) ";
		$sql = $sql."		OR ((rol_this_list_view = 1) ";
		$sql = $sql."			AND ((SELECT count(mem_id) FROM ".TABLE_PREFIX."_members WHERE mem_rol_id = rol_id AND mem_usr_id = ".$gCurrentUser->getValue('usr_id')." AND mem_end = '9999-12-31') >= 1)) ";
		$sql = $sql."		OR ((SELECT count(m.mem_id) ";
		$sql = $sql."			FROM ";
		$sql = $sql."				".TABLE_PREFIX."_categories c ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_roles r ON r.rol_cat_id = c.cat_id ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_members m ON m.mem_rol_id = r.rol_id ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_organizations o ON c.cat_org_id = o.org_id ";
		$sql = $sql."			WHERE ";
		$sql = $sql."				c.cat_type = 'ROL' ";
		$sql = $sql."				AND o.org_id = '".$gCurrentOrganization->getValue('org_id')."' ";
		$sql = $sql."			    AND r.rol_all_lists_view = 1 ";
		$sql = $sql."				AND m.mem_usr_id = ".$gCurrentUser->getValue('usr_id').") >= 1)) ";
		if ((isset($plg_excluded_roles)) && ($plg_excluded_roles."" != ""))
		{
		$sql = $sql."	AND rol_id NOT IN (".$plg_excluded_roles.") ";
		}
		$sql = $sql."UNION ";
		$sql = $sql."SELECT ";
		$sql = $sql."	2 AS grp_id ";
		$sql = $sql."	, 'ehemalige Rollenmitgliedschaften' AS grp_name ";
		$sql = $sql."	, cat_sequence ";
		$sql = $sql."	, org_shortname ";
		$sql = $sql."	, cat_name ";
		$sql = $sql."	, rol_name ";
		$sql = $sql."	, IFNULL(mem_begin,'') AS mem_begin ";
		$sql = $sql."	, IFNULL(mem_end,'') AS mem_end ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_members ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_roles ON mem_rol_id = rol_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_categories ON rol_cat_id = cat_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_organizations ON cat_org_id = org_id ";
		$sql = $sql."WHERE ";
		$sql = $sql."	mem_usr_id = ".$getUserId." ";
		$sql = $sql."	AND mem_end <> '9999-12-31' ";
		$sql = $sql."	AND org_id = '".$gCurrentOrganization->getValue('org_id')."' ";
		$sql = $sql."	AND cat_type = 'ROL' ";
		$sql = $sql."	AND rol_valid = 1 ";
		$sql = $sql."	AND cat_name_intern <> 'EVENTS' ";
		$sql = $sql."	AND ((rol_this_list_view = 2) ";
		$sql = $sql."		OR ((rol_this_list_view = 1) ";
		$sql = $sql."			AND ((SELECT count(mem_id) FROM ".TABLE_PREFIX."_members WHERE mem_rol_id = rol_id AND mem_usr_id = ".$gCurrentUser->getValue('usr_id')." AND mem_end = '9999-12-31') >= 1)) ";
		$sql = $sql."		OR ((SELECT count(m.mem_id) ";
		$sql = $sql."			FROM ";
		$sql = $sql."				".TABLE_PREFIX."_categories c ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_roles r ON r.rol_cat_id = c.cat_id ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_members m ON m.mem_rol_id = r.rol_id ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_organizations o ON c.cat_org_id = o.org_id ";
		$sql = $sql."			WHERE ";
		$sql = $sql."				c.cat_type = 'ROL' ";
		$sql = $sql."				AND o.org_id = '".$gCurrentOrganization->getValue('org_id')."' ";
		$sql = $sql."			    AND r.rol_all_lists_view = 1 ";
		$sql = $sql."				AND m.mem_usr_id = ".$gCurrentUser->getValue('usr_id').") >= 1)) ";
		if ((isset($plg_excluded_roles)) && ($plg_excluded_roles."" != ""))
		{
		$sql = $sql."	AND rol_id NOT IN (".$plg_excluded_roles.") ";
		}
		$sql = $sql."UNION ";
		$sql = $sql."SELECT ";
		$sql = $sql."	3 AS grp_id ";
		$sql = $sql."	, 'Rollenmitgliedschaften anderer Organisationen' AS grp_name ";
		$sql = $sql."	, cat_sequence ";
		$sql = $sql."	, org_shortname ";
		$sql = $sql."	, cat_name ";
		$sql = $sql."	, rol_name ";
		$sql = $sql."	, IFNULL(mem_begin,'') AS mem_begin ";
		$sql = $sql."	, '' AS mem_end ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_members ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_roles ON mem_rol_id = rol_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_categories ON rol_cat_id = cat_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_organizations ON cat_org_id = org_id ";
		$sql = $sql."WHERE ";
		$sql = $sql."	mem_usr_id = ".$getUserId." ";
		$sql = $sql."	AND '".DATE_NOW."' BETWEEN mem_begin AND mem_end ";
		$sql = $sql."	AND org_id <> '".$gCurrentOrganization->getValue('org_id')."' ";
		$sql = $sql."	AND cat_type = 'ROL' ";
		$sql = $sql."	AND rol_valid = 1 ";
		$sql = $sql."	AND cat_name_intern <> 'EVENTS'";
		$sql = $sql."	AND ((rol_this_list_view = 2) ";
		$sql = $sql."		OR ((rol_this_list_view = 1) ";
		$sql = $sql."			AND ((SELECT count(mem_id) FROM ".TABLE_PREFIX."_members WHERE mem_rol_id = rol_id AND mem_usr_id = ".$gCurrentUser->getValue('usr_id')." AND mem_end = '9999-12-31') >= 1)) ";
		$sql = $sql."		OR ((SELECT count(m.mem_id) ";
		$sql = $sql."			FROM ";
		$sql = $sql."				".TABLE_PREFIX."_categories c ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_roles r ON r.rol_cat_id = c.cat_id ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_members m ON m.mem_rol_id = r.rol_id ";
		$sql = $sql."				INNER JOIN ".TABLE_PREFIX."_organizations o ON c.cat_org_id = o.org_id ";
		$sql = $sql."			WHERE ";
		$sql = $sql."				c.cat_type = 'ROL' ";
		$sql = $sql."				AND o.org_id = '".$gCurrentOrganization->getValue('org_id')."' ";
		$sql = $sql."			    AND r.rol_all_lists_view = 1 ";
		$sql = $sql."				AND m.mem_usr_id = ".$gCurrentUser->getValue('usr_id').") >= 1)) ";
		if ((isset($plg_excluded_roles)) && ($plg_excluded_roles."" != ""))
		{
		$sql = $sql."	AND rol_id NOT IN (".$plg_excluded_roles.") ";
		}
		$sql = $sql."ORDER BY ";
		$sql = $sql."	grp_id, org_shortname, cat_sequence, rol_name ";

		return $sql;
	}
}

?>
