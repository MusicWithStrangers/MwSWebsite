<?php
/******************************************************************************
 * Appmidio Command get_roles.php
 *
 * Funktion fuer das Admidio-Plugin Appmidio, um die Rollen auszulesen
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
	global $gValidLogin, $gCurrentUser, $gCurrentOrganization, $plg_excluded_categories, $plg_excluded_roles;

	if($gValidLogin == false)
	{
		msg_unauthorized();
	}
	else
	{
	 	$sql = "SELECT ";
		$sql = $sql."	cat_id ";
		$sql = $sql."	, cat_org_id ";
		$sql = $sql."	, cat_name ";
		$sql = $sql."	, rol_id ";
		$sql = $sql."	, rol_name ";
		$sql = $sql."	, rol_description ";
		$sql = $sql."	, (SELECT count(mem_id) FROM ".TABLE_PREFIX."_members WHERE mem_rol_id = rol_id AND mem_end = '9999-12-31') AS mem_count ";
		$sql = $sql."	, (SELECT count(mem_id) FROM ".TABLE_PREFIX."_members WHERE mem_rol_id = rol_id AND mem_end < '9999-12-31') AS mem_count_ex ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_categories ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_roles ON rol_cat_id = cat_id ";
		$sql = $sql."	INNER JOIN ".TABLE_PREFIX."_organizations ON cat_org_id = org_id ";
		$sql = $sql."WHERE ";
		$sql = $sql."	cat_type = 'ROL' ";
		$sql = $sql."	AND rol_valid = 1 ";
		$sql = $sql."	AND cat_name_intern <> 'EVENTS' ";
		$sql = $sql."	AND org_shortname = '".$gCurrentOrganization->getValue('org_shortname')."' ";
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
		$sql = $sql."				AND o.org_shortname = '".$gCurrentOrganization->getValue('org_shortname')."' ";
		$sql = $sql."			    AND r.rol_all_lists_view = 1 ";
		$sql = $sql."				AND m.mem_usr_id = ".$gCurrentUser->getValue('usr_id').") >= 1)) ";
		if ((isset($plg_excluded_categories)) && ($plg_excluded_categories."" != ""))
		{
		$sql = $sql."	AND cat_id NOT IN (".$plg_excluded_categories.") ";
		}
		if ((isset($plg_excluded_roles)) && ($plg_excluded_roles."" != ""))
		{
		$sql = $sql."	AND rol_id NOT IN (".$plg_excluded_roles.") ";
		}
		$sql = $sql."ORDER BY ";
		$sql = $sql."	cat_sequence, rol_name ";

		return $sql;
	}
}

?>
