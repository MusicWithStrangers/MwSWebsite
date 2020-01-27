<?php
/******************************************************************************
 * Appmidio Command get_organisations.php
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
 	$sql = "SELECT ";
	$sql = $sql."	org_id ";
	$sql = $sql."	, org_longname ";
	$sql = $sql."	, org_shortname ";
	$sql = $sql."	, org_org_id_parent ";
	$sql = $sql."	, org_homepage ";
	$sql = $sql."FROM ";
	$sql = $sql."	".TABLE_PREFIX."_organizations ";

	return $sql;
}
?>
