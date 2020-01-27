<?php
/**
 ***********************************************************************************************
 * Verschiedene Funktionen fuer Termine
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * dat_id     - ID of the event that should be edited
 * mode   : 1 - Create a new venue
 *          2 - Delete the venue
 *          5 - Edit an existing venue

 * rol_id : vorselektierte Rolle der Rollenauswahlbox
 * copy   : true - The event of the dat_id will be copied and the base for this new event
 * number_role_select : Nummer der Rollenauswahlbox, die angezeigt werden soll
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../system/common.php');

if($_GET['mode'] == 2)
{
    $gMessage->showHtmlTextOnly(true);
}

// Initialize and check the parameters
$getVenueId              = admFuncVariableIsValid($_GET, 'ven_id', 'int');
$getMode                = admFuncVariableIsValid($_GET, 'mode',   'int', array('requireValue' => true));
$getUserId              = admFuncVariableIsValid($_GET, 'usr_id', 'int', array('defaultValue' => $gCurrentUser->getValue('usr_id')));

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->editBookings())
{
    $gMessage->show("Please log in with a booking-enabled user to edit bookings");
    // => EXIT
}


// create event object
$venue = new TableVenue($gDb);
$venue->readDataById($getVenueId);


if($getMode === 1 || $getMode === 5)  // Create a new venue or edit an existing event
{

    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'ven_'))
            {
                $venue->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gDb->startTransaction();

    // save event in database
    $returnCode = $venue->save();

    $VenueId = (int) $venue->getValue('ven_id');

    $gDb->endTransaction();

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT
}
elseif($getMode === 2)
{
    // delete current announcements, right checks were done before
    $venue->delete();

    // Delete successful -> Return for XMLHttpRequest
    echo 'done';
}
