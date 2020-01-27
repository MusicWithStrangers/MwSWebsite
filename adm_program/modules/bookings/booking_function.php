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
 * mode   : 1 - Create a new bookingroom
 *          2 - Delete the bookingroom
 *          5 - Edit an existing bookingroom

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
$getRoomBookingDay       = admFuncVariableIsValid($_GET, 'rbd_id', 'int');
$getMode                = admFuncVariableIsValid($_GET, 'mode',   'int', array('requireValue' => true));
$getUserId              = admFuncVariableIsValid($_GET, 'usr_id', 'int', array('defaultValue' => $gCurrentUser->getValue('usr_id')));

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->editBookings())
{
    $gMessage->show("Please log in with a booking-enabled user to edit bookings");
    // => EXIT
}


// create event object
$roombookingday = new TableRoomBookingDay($gDb);
$roombookingday->readDataById($getRoomBookingDayId);


if($getMode === 1 || $getMode === 5)  // Create a new venue or edit an existing event
{
    if(!isset($_POST['rbd_enable']))
    {
        $_POST['rbd_enable'] = 0;
    }
        if(!isset($_POST['rbd_autoDisable']))
    {
        $_POST['rbd_autoDisable'] = 0;
    }
    if(!isset($_POST['dat_weekly']))
    {
        $_POST['dat_weekly'] = 0;
    }
    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'rbd_'))
            {
                $roombookingday->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gDb->startTransaction();

    // save room booking day in database
    $returnCode = $roombookingday->save();

    $roombookingdayId = (int) $roombookingday->getValue('rbd_id');

    $gDb->endTransaction();

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT
}
elseif($getMode === 2)
{
    // delete current announcements, right checks were done before
    $roombookingday->delete();

    // Delete successful -> Return for XMLHttpRequest
    echo 'done';
}
