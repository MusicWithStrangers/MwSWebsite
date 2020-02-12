<?php
/**
 ***********************************************************************************************
 * Show a list of all events
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 * Parameters:
 *
 * mode      - actual : (Default) shows actual dates and all events in future
 *             old    : shows events in the past
 *             all    : shows all events in past and future
 * start     - Position of query recordset where the visual output should start
 * headline  - Headline shown over events
 *             (Default) Events
 * cat_id    - show all events of calendar with this id
 * id        - Show only one event
 * show      - all               : (Default) show all events
 *           - maybe_participate : Show only events where the current user participates or could participate
 *           - only_participate  : Show only events where the current user participates
 * date_from - set the minimum date of the events that should be shown
 *             if this parameter is not set than the actual date is set
 * date_to   - set the maximum date of the events that should be shown
 *             if this parameter is not set than this date is set to 31.12.9999
 * view_mode - Content output in 'html' or 'print' view
 * view      - Content output in different views like 'detail', 'list'
 *             (Default: according to preferences)
 *****************************************************************************/
require_once(__DIR__ . '/../../system/common.php');

unset($_SESSION['dates_request']);

// Initialize and check the parameters
$getMode     = admFuncVariableIsValid($_GET, 'mode',      'string', array('defaultValue' => 'actual', 'validValues' => array('actual', 'old', 'all')));
$getHeadline = 'Bookings'; #admFuncVariableIsValid($_GET, 'headline',  'string', array('defaultValue' => $gL10n->get('DAT_DATES')));
$getId       = admFuncVariableIsValid($_GET, 'id',        'int');
$bookAdmin   =  $gCurrentUser->editBookings();

// create object and get recordset of available dates

try
{
    $books = new ModuleBookings();
    $booksResult=$books->getDataSet();
    $books->setParameter('mode', $getMode);
    $books->setParameter('cat_id', $getCatId);
    $books->setParameter('id', $getId);
    $books->setParameter('show', $getShow);
    $books->setParameter('view_mode', $getViewMode);
}
catch(AdmException $e)
{
    $e->showHtml();
    // => EXIT
}
// Navigation of the module starts here
$gNavigation->addStartUrl(CURRENT_URL, $books->getHeadline($getHeadline));

// create html page object
$page = new HtmlPage($books->getHeadline($getHeadline));
$page->enableModal();

$datatable  = true;
$hoverRows  = true;
$classTable = 'table';

// get module menu
$booksMenu = $page->getMenu();


// Add new roombooking
if($getId === 0 && $bookAdmin)
{
    $booksMenu->addItem(
        'admMenuItemAdd', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/roombooking_new.php', array('headline' => $getHeadline)),
        $gL10n->get('SYS_CREATE_VAR', array($getHeadline)), 'add.png'
    );
}

if($gCurrentUser->isAdministrator())
{
    // show link to system preferences of weblinks
    $booksMenu->addItem(
        'admMenuItemPreferencesLinks', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/preferences/preferences.php', array('show_option' => 'events')),
        $gL10n->get('SYS_MODULE_PREFERENCES'), 'options.png', 'right', 'menu_item_extras'
    );
}


if($booksResult['totalCount'] === 0)
{
    // No events found
    if($getId > 0)
    {
        $page->addHtml('<p>' . $gL10n->get('SYS_NO_ENTRY') . '</p>');
    }
    else
    {
        $page->addHtml('<p>' . $gL10n->get('SYS_NO_ENTRIES') . '</p>');
    }
}
else
{
    // create dummy date object
    $booking = new TableBooking($gDb);
    $firstElement = true;
    $htmlBookElements = '';

    foreach($booksResult['recordset'] as $row)
    {
        // write of current event data to date object
        #$date->setArray($row);
        $booking->setArray($row[0]);
        $bookId       = (int) $booking->getValue('rbd_id');

        // initialize all output elements
        $attentionDeadline  = '';
        $outputEndDate      = '';
        $outputButtonIcal   = '';
        $outputButtonEdit   = '';
        $outputButtonDelete = '';
        $outputButtonCopy   = '';
        $outputButtonParticipantsEmail  = '';
        $outputButtonSongRegister       = '';
        $outputButtonParticipantsAssign = '';
        $outputLinkLocation    = '';
        $outputLinkRoom        = '';
        $outputNumberMembers   = '';
        $outputNumberLeaders   = '';
        $outputMaxSlotDuration = '';
        $outputMaxSlotSongs    = '';
        $outputVenueContact    = '';
        $outputSetupPlan       = '';
        $outputConcept         = '';
        $outputFinancial       = '';
        $outputDeadline        = '';
        $htmlBookElements      = '';
        $bookElements          = array();
        $participantsArray     = array();
        $participateModalForm  = false;
        $participationPossible = true;
        $startValue = $row['slotstart'];

        if($firstElement)
        {
            $htmlBookElements .= '<div class="row">';
        }
        if ($row['canbook']===0)
        {
            $htmlBookElements.= '<div class="panel-body">' . 'This room is not open for booking at this moment' . '</div>';
        } else {
            $htmlBookElements.= '<div class="panel-body">' . $startValue->format('M d'). ' from ' . $startValue->format('H:i') . '</div>';
        }
        $htmlBookElements.='</div>';
        $slotTimes=$row['slotTimes'];
        $slotBookings=$row['slotBookings'];
        $slotBookingsName=$row['slotBookingsName'];
        #foreach ($slotTimes as $slotTime)
        foreach($slotTimes as $key => $value)
        {
            $htmlBookElements.='<div class="row">';
            $htmlBookElements.='<div class="col-sm-2 col-xs-4">'.$key.'</div>';
            $htmlBookElements.='<div class="col-sm-2 col-xs-4">'.$value->format('H:i').'</div>';
            $status='Free';
            if ($slotBookings[$key]>0)
            {
                $status='Booked';
            }
            $htmlBookElements.='<div class="col-sm-2 col-xs-4">'.$status.'</div>';
            $htmlBookElements.='<div class="col-sm-2 col-xs-4">'.$slotBookingsName[$key].'</div>';
            $htmlBookElements.='</div>';
        }
        

         $outputRoomDescription = $booking->getValue('rbd_roomDescription');

        $outputVenueName = $booking->getValue('ven_name');
        $bookHeadline=$outputVenueName . ': ' . $outputRoomDescription;

                // change and delete is only for users with additional rights
        if ($bookAdmin)
        {
            $outputButtonEdit = '
                <a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/roombooking_new.php', array('rbd_id' => $bookId, 'headline' => $getHeadline)) . '">
                    <img src="'.THEME_URL.'/icons/edit.png" alt="' . $gL10n->get('SYS_EDIT') . '" title="' . $gL10n->get('SYS_EDIT') . '" /></a>';
            $outputButtonDelete = '
                <a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
                    href="'.safeUrl(ADMIDIO_URL.'/adm_program/system/popup_message.php', array('type' => 'rbd', 'element_id' => 'rbd_' . $bookId)) . '">
                    <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
        }

        $page->addHtml('
            <div class="panel panel-primary ' . $cssClassHighlight . '" id="rbd_id' . $bookId . '">
                <div class="panel-heading">
                    <div class="pull-left">
                        <img class="admidio-panel-heading-icon" src="'.THEME_URL.'/icons/bookings.png" alt="' . $bookHeadline . '" />' .
                         $bookHeadline . '
                    </div>
                    <div class="pull-right text-right">' .
                        $outputButtonEdit . $outputButtonDelete . '
                    </div>
                </div>
                <div class="panel-body">
                    ' . $htmlBookElements . '<br />' );

        $page->addHtml('
            </div>
            <div class="panel-footer"></div>
            </div>');

        if($booksResult['canbook'] )
        {
                                                    
            $buttonBookURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_show.php', array('mode' => 'html', 'rbd_id' => $bookId));
            $outputButtonSongRegister  = '
                        <button class="btn btn-default" onclick="window.location.href=\'' . $buttonBookURL . '\'"><img src="' . THEME_URL . '/icons/room.png" alt="Book room" />' . 'Book room' . '</button>';
        }
        

    }  // End foreach

}
// If necessary show links to navigate to next and previous recordsets of the query
$baseUrl = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/bookings.php', array('mode' => $getMode, 'headline' => $getHeadline, ));
$page->addHtml(admFuncGeneratePagination($baseUrl, $bookResult['totalCount'],12, 1));
$page->show();
