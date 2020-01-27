<?php
/******************************************************************************
 * Appmidio Command get_dates.php
 *
 * Funktion fuer das Admidio-Plugin Appmidio, um die Termine auszulesen
 *
 * Copyright    : (c) 2013-2014 The Zettem Team
 * Homepage     : https://play.google.com/store/apps/details?id=de.zettem.Appmidio
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
*****************************************************************************/

require_once($rootPath. '/adm_program/system/common.php');
require_once($rootPath. '/'.FOLDER_PLUGINS. '/'.$pluginFolder.'/functions/common.php');


function sql_command()
{
	global $plugin_debug, $gValidLogin, $gProfileFields, $plg_birthday_anniversaries, $plg_birthday_roles, $gCurrentOrganization;

	if($gValidLogin == false)
	{
		msg_unauthorized();
	}
	else
	{
		$getPrevDays = 0;
		$getNextDays = 30;
		$getOrderBy = 'asc, last_name, first_name';
		if ($plg_birthday_roles == '') {
			$rol_sql = 'IS NOT NULL';
		} else {
			$rol_sql = 'IN ('.$plg_birthday_roles.')';
		}
		if ($plugin_debug)
		{
			if(isset($_REQUEST['prevdays'])) {
				$getPrevDays = $_REQUEST['prevdays'];
			}
			if(isset($_REQUEST['nextdays'])) {
				$getNextDays = $_REQUEST['nextdays'];
			}
			if(isset($_REQUEST['orderby'])) {
				$getOrderBy = $_REQUEST['orderby'];
			}
		} else {
			if(isset($_POST['prevdays'])) {
				$getPrevDays = admFuncVariableIsValid($_POST, 'prevdays', 'numeric', array('directOutput' => true));
			}
			if(isset($_POST['nextdays'])) {
				$getNextDays = admFuncVariableIsValid($_POST, 'nextdays', 'numeric', array('directOutput' => true));
			}
			if(isset($_POST['orderby'])) {
				$getOrderBy = admFuncVariableIsValid($_POST, 'orderby', 'string', array('defaultValue' => '', 'validValues' => array('asc, first_name, last_name', 'asc, last_name, first_name', 'desc, first_name, last_name', 'desc, last_name, first_name'), 'directOutput' => true));
			}
		}

		$sql    = "SELECT DISTINCT usr_id
			                           ,last_name.usd_value as last_name
		                           ,first_name.usd_value as first_name
		                           ,birthday.bday as birthday
		                           ,birthday.bdate as bdate
		                           ,DATEDIFF(birthday.bdate, '".DATETIME_NOW."') AS days_to_bdate
		                           ,YEAR(bdate) - YEAR(bday) AS age
		                           ,(INSTR(',".$plg_birthday_anniversaries.",', CONCAT(',', CONVERT((YEAR(bdate) - YEAR(bday)), CHAR(5)), ',')) > 0) AS has_anniversary
		             FROM ". TBL_USERS. " users
		             JOIN (
		            (SELECT
		                usd_usr_id,
		                usd_value AS bday,
		                CONCAT(year('".DATETIME_NOW."'), '-', month(usd_value),'-', dayofmonth(bd1.usd_value)) AS bdate
		                FROM ". TBL_USER_DATA. " bd1
		                WHERE DATEDIFF(CONCAT(year('".DATETIME_NOW."'), '-', month(usd_value),'-', dayofmonth(bd1.usd_value)), '".DATETIME_NOW."') BETWEEN -$getPrevDays AND $getNextDays
		                        AND usd_usf_id = ". $gProfileFields->getProperty('BIRTHDAY', "usf_id"). ")
		        UNION
		            (SELECT
		                usd_usr_id,
		                usd_value AS bday,
		                CONCAT(year('".DATETIME_NOW."')-1, '-', month(usd_value),'-', dayofmonth(bd2.usd_value)) AS bdate
		                FROM ". TBL_USER_DATA. " bd2
		                WHERE DATEDIFF(CONCAT(year('".DATETIME_NOW."')-1, '-', month(usd_value),'-', dayofmonth(bd2.usd_value)), '".DATETIME_NOW."') BETWEEN -$getPrevDays AND $getNextDays
		                        AND usd_usf_id = ". $gProfileFields->getProperty('BIRTHDAY', "usf_id"). ")
		        UNION
		            (SELECT
		                usd_usr_id,
		                usd_value AS bday,
		                CONCAT(year('".DATETIME_NOW."')+1, '-', month(usd_value),'-', dayofmonth(bd3.usd_value)) AS bdate
		                FROM ". TBL_USER_DATA. " bd3
		                WHERE DATEDIFF(CONCAT(year('".DATETIME_NOW."')+1, '-', month(usd_value),'-', dayofmonth(bd3.usd_value)), '".DATETIME_NOW."') BETWEEN -$getPrevDays AND $getNextDays
		                        AND usd_usf_id = ". $gProfileFields->getProperty('BIRTHDAY', "usf_id"). ")
		         ) AS birthday
		               ON birthday.usd_usr_id = usr_id
		             LEFT JOIN ". TBL_USER_DATA. " as last_name
		               ON last_name.usd_usr_id = usr_id
		              AND last_name.usd_usf_id = ". $gProfileFields->getProperty('LAST_NAME', "usf_id"). "
		             LEFT JOIN ". TBL_USER_DATA. " as first_name
		               ON first_name.usd_usr_id = usr_id
		              AND first_name.usd_usf_id = ". $gProfileFields->getProperty('FIRST_NAME', "usf_id"). "
		             LEFT JOIN ". TBL_USER_DATA. " as email
		               ON email.usd_usr_id = usr_id
		              AND email.usd_usf_id = ". $gProfileFields->getProperty('EMAIL', "usf_id"). "
		             LEFT JOIN ". TBL_USER_DATA. " as gender
		               ON gender.usd_usr_id = usr_id
		              AND gender.usd_usf_id = ". $gProfileFields->getProperty('GENDER', "usf_id"). "
		             LEFT JOIN ". TBL_MEMBERS. "
		               ON mem_usr_id = usr_id
		              AND mem_begin <= '".DATE_NOW."'
		              AND mem_end    > '".DATE_NOW."'
		             JOIN ". TBL_ROLES. "
		               ON mem_rol_id = rol_id
		              AND rol_valid  = 1
		             JOIN ". TBL_CATEGORIES. "
		               ON rol_cat_id = cat_id
		            WHERE usr_valid = 1
		              AND mem_rol_id ".$rol_sql."
		              AND cat_org_id = ". $gCurrentOrganization->getValue("org_id"). "
		            ORDER BY days_to_bdate ".$getOrderBy;

			return $sql;
	}
}

?>
