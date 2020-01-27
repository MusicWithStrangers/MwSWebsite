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
	global $plugin_debug, $gValidLogin, $gCurrentUser, $gDb;

	// Initialize and check the parameters
	$getOrderBy = '';
	$getExMembers = 0;
	if ($plugin_debug)
	{
		$getRoleId = $_REQUEST['rol_id'];
		if(isset($_REQUEST['ex'])) {
			$getExMembers = $_REQUEST['ex'];
		}
		if(isset($_REQUEST['orderby'])) {
			$getOrderBy = $_REQUEST['orderby'];
		}
	} else {
		$getRoleId = admFuncVariableIsValid($_POST, 'rol_id', 'numeric', array('requireValue' => true, 'directOutput' => true));
		if(isset($_POST['ex'])) {
			$getExMembers = admFuncVariableIsValid($_POST, 'ex', 'numeric', array('directOutput' => true));
		}
		if(isset($_POST['orderby'])) {
			$getOrderBy = admFuncVariableIsValid($_POST, 'orderby', 'string', array('defaultValue' => '', 'validValues' => array('first_name, last_name', 'last_name, first_name'), 'directOutput' => true));
		}
	}
	// Rollenobjekt erzeugen
	$role = new TableRoles($gDb, $getRoleId);

	if(!$gValidLogin)
	{
		msg_unauthorized();
	}
	else if(!$role->isVisible())
	{
		//$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
		$gMessage = 'keine Berechtigung, die Daten dieser Rolle anzuzeigen';
		msg_forbidden('Keine Berechtigung, die Daten dieser Rolle anzuzeigen.');
	}
	else
	{
		$sql = "SELECT ";
		$sql = $sql."	mem_usr_id AS usr_id ";
		$sql = $sql."	, IFNULL(f1.usd_value, '') AS first_name ";
		$sql = $sql."	, IFNULL(f2.usd_value, '') AS last_name ";
		$sql = $sql."	, IFNULL(f3.usd_value, '') AS birthday ";
		$sql = $sql."	, mem_leader AS mem_leader ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_members ";
		$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f1 ON f1.usd_usr_id = mem_usr_id AND f1.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'FIRST_NAME') ";
		$sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f2 ON f2.usd_usr_id = mem_usr_id AND f2.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'LAST_NAME') ";
		if ($gCurrentUser->editUsers() == false)
		{
		    $sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f3 ON f3.usd_usr_id = mem_usr_id AND f3.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'BIRTHDAY' AND usf_hidden = 0) ";
		} else {
		    $sql = $sql."	LEFT JOIN ".TABLE_PREFIX."_user_data AS f3 ON f3.usd_usr_id = mem_usr_id AND f3.usd_usf_id = (SELECT usf_id FROM ".TABLE_PREFIX."_user_fields WHERE usf_name_intern = 'BIRTHDAY') ";
		}
		$sql = $sql."WHERE ";
		$sql = $sql."	mem_rol_id = ".$getRoleId." ";
		if ($getExMembers == 0) {
		    $sql = $sql."	AND '".DATE_NOW."' BETWEEN mem_begin AND mem_end ";
		} else {
		    $sql = $sql."	AND mem_end < '".DATE_NOW."' ";
		}
		$sql = $sql."ORDER BY ";
		$sql = $sql."	mem_leader DESC ";
		if ($getOrderBy != '') {
		    $sql = $sql."	, ".$getOrderBy." ";
		}

		return $sql;
	}
}

?>
