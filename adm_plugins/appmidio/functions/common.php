<?php
/******************************************************************************
 * Appmidio
 *
 * JSON-Funktionen fuer das Admidio-Plugin Appmidio
 *
 * Copyright    : (c) 2013-2015 The Zettem Team
 * Homepage     : https://play.google.com/store/apps/details?id=de.zettem.Appmidio
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
*****************************************************************************/

require_once($rootPath. '/adm_program/system/common.php');

function json_result ($sql = '')
{
 	global $gDb, $gL10n, $gProfileFields;

	$output = array();

	if($sql !== '')
	{
		// Daten aus der DB abfragen
		// SYS_-Bezeichnungen werden mit den sprachabhängigen Bezeichnungen ersetzt
		$resultStatement = $gDb->query($sql);
		while($row = $resultStatement->fetch(PDO::FETCH_ASSOC))
		{
			$usf_id = 0;
			foreach($row as $key => $val)
			{
				// bei jeder Spalte ausser bei der Spalte usf_name_intern wird der Wert "übersetzt"
				if ($key != 'usf_name_intern')
				{
					if ((substr($row[$key], 0, 4) == "SYS_") || (substr($row[$key], 0, 4) == "INS_") || (substr($row[$key], 0, 4) == "PMB_"))
					{
						$row[$key] = $gL10n->get($row[$key]);
					}
				}
				if ($key == 'usf_id')
				{
					$usf_id = $row[$key];
				}
				if ($key == 'usd_value' && $usf_id > 0)
				{
					// wenn es sich bei dem Daten um das Land handelt, wird der Ländercode durch die Bezeichnung ersetzt
					if ($usf_id == $gProfileFields->getProperty('COUNTRY', 'usf_id'))
					{
						$row['usd_value'] = $gL10n->getCountryByCode($row['usd_value']);
					}
					elseif ($gProfileFields->getPropertyById($usf_id, 'usf_type') == 'DROPDOWN'
					||      $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'RADIO_BUTTON')
					{
						// show selected text of optionfield or combobox
						$arrListValues = $gProfileFields->getPropertyById($usf_id, 'usf_value_list', 'text');
						$row['usd_value'] = $arrListValues[$row['usd_value']];
					}
				}
				if ($key == 'dat_country')
				{
					if (isset($row['dat_country']) && $row['dat_country'].'' != '')
					{
						$row['dat_country'] = $gL10n->getCountryByCode($row['dat_country']);
					}
				}
			}
			$output[] = $row;
		}
	}
	if (count($output) === 0)
	{
		return '';
	}
	else
	{
		// Ergebnis im JSON-Format zurückgeben
		return json_encode($output);
	}
}


function getCurrentDbVersion ()
{
 	global $gDb, $g_organization;

	if($gDb->query('SELECT 1 FROM '.TABLE_PREFIX.'_components', false) == false)
	{
		// V2
		// in Admidio version 2 the database version was stored in preferences table
		$sql = "SELECT ";
		$sql = $sql."	prf_value AS db_version ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_preferences ";
		$sql = $sql."	JOIN ".TABLE_PREFIX."_organizations ON org_id = prf_org_id ";
		$sql = $sql."WHERE ";
		$sql = $sql."	prf_name = 'db_version' ";
		$sql = $sql."	AND org_shortname = '".$g_organization."' ";
	}
	else
	{
		// V3
		// read system component
		$sql = "SELECT ";
		$sql = $sql."	com_version AS db_version ";
		$sql = $sql."FROM ";
		$sql = $sql."	".TABLE_PREFIX."_components ";
		$sql = $sql."WHERE ";
		$sql = $sql."	com_type = 'SYSTEM' ";
		$sql = $sql."	AND com_name_intern = 'CORE' ";
	}

	// Daten aus der DB abfragen
	$resultStatement = $gDb->query($sql);
	$row = $resultStatement->fetch(PDO::FETCH_ASSOC);

	return $row['db_version'];
}


function msg_unauthorized()
{
	?>
	<html><head>
	<title>401 Authorization Required</title>
	</head><body>
	<h1>Authorization Required</h1>
	<p>This server could not verify that you are authorized to access the document
	requested. Either you supplied the wrong credentials (e.g., bad password), or your
	browser doesn't understand how to supply the credentials required.</p>
	<hr>
	</body></html>
	<?php
	exit();
}

function msg_forbidden($msg_text = '')
{
	?>
	<html><head>
	<title>403 Forbidden</title>
	</head><body>
	<h1>Forbidden</h1>
	<p><?php echo($msg_text);  ?></p>
	<hr>
	</body></html>
	<?php
	exit();
}

function msg_not_found($msg_text = '')
{
	?>
	<html><head>
	<title>404 Not Found</title>
	</head><body>
	<h1>Not Found</h1>
	<p><?php echo($msg_text);  ?></p>
	<hr>
	</body></html>
	<?php
	exit();
}

?>
