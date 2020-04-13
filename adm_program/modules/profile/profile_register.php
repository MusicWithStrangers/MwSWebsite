<?php
/**
 ***********************************************************************************************
 * Create or edit a user profile
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * user_id    : ID of the user who should be edited
 * new_user   : 0 - Edit user of the user id
 *              1 - Create a new user
 *              2 - Create a registration
 *              3 - assign/accept a registration
 * lastname   : (Optional) Lastname could be set and will than be preassigned for new users
 * firstname  : (Optional) First name could be set and will than be preassigned for new users
 * copy       : true - The user of the user_id will be copied and the base for this new user
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../system/common.php');


$registrationOrgId = $gCurrentOrganization->getValue('org_id');

// read user data
$user = new User($gDb, $gProfileFields, $getUserId);

// set headline of the script
$headline="Music with Strangers - Registration";
$page = new HtmlPage($headline);
$page.
$page->enableModal();

$page->show();
