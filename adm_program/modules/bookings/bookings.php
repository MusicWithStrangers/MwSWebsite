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
$getHeadline = 'Room Booking'; #admFuncVariableIsValid($_GET, 'headline',  'string', array('defaultValue' => $gL10n->get('DAT_DATES')));
$getId       = admFuncVariableIsValid($_GET, 'id',        'int');
$bookAdmin   =  $gCurrentUser->editBookings();
$getSnrId     = admFuncVariableIsValid($_GET, 'snr_id','int');

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
$gCurrentUserId=$gCurrentUser->getValue('usr_id');
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
    
        #determine my songs
    $sqlDates='SELECT dat_id from mws__dates where dat_begin>CURRENT_TIMESTAMP';
    // Registered songs to upcomming events from this user
    $sqlRegisteredSongs = 'SELECT mws__songs.son_id, mws__songs.son_title FROM mws__song_registration 
        INNER JOIN mws__songs ON mws__song_registration.snr_son_id = mws__songs.son_id
        INNER JOIN mws__bands ON mws__song_registration.snr_bnd_id = mws__bands.bnd_id
        WHERE mws__song_registration.snr_usr_id = '.$gCurrentUserId.'
        ORDER BY mws__song_registration.snr_bnd_id';
    $sqlRegisteredSongs = 'SELECT mws__song_registration.snr_id, CONCAT(mws__songs.son_title, \' for "\',mws__dates.dat_headline, \'"\' ) AS \'SongEvent\' FROM mws__song_registration 
        INNER JOIN mws__songs ON mws__song_registration.snr_son_id = mws__songs.son_id
        INNER JOIN mws__bands ON mws__song_registration.snr_bnd_id = mws__bands.bnd_id
        INNER JOIN mws__dates ON mws__dates.dat_id= mws__song_registration.snr_dat_id
        WHERE mws__song_registration.snr_usr_id = '.$gCurrentUserId.'
        ORDER BY mws__song_registration.snr_bnd_id';
    
    #($id, $name = null, HtmlPage $htmlPage = null, $type = 'default')
    $filterNavbar = new HtmlNavbar('menu_dates_filter', "Select song to work on", null, null);
    $form = new HtmlForm('navbar_filter_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
    $form->addInput('view', '', $getView, array('property' => HtmlForm::FIELD_HIDDEN));
    $form->addSelectBoxFromSql('sel_change_son', 'Book room to work on song:', $gDb, $sqlRegisteredSongs,array('firstEntry' => 'Select song', 'defaultValue' => $getSnrId));
    $filterNavbar->addForm($form->show(false));
    $page->addHtml($filterNavbar->show());
    
    $page->addJavascript('$(document).on("change","#sel_change_son",function(){'
            . 'self.location.href = "'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/bookings.php', array('headline' => $getHeadline)) . '&snr_id=" + $("#sel_change_son").val();'
            . '});');
    #$page->addJavascript('
    #    $("#sel_change_son").change(function() {
    #        alert("fire");
    #    });');
    #
    #
    #
    #$formsongs = new HtmlForm('navbar_change_view_form', '', $page, array('setFocus' => false));
    #$formsongs->addSelectBoxFromSql('book_son', 'Song', $gDb, $sqlRegisteredSongs);
    #$filterNavbar = new HtmlNavbar('menu_bookings_filter', null, null, 'filter');
    #$filterNavbar->addForm($formsongs->show(true));
    #$booksMenu->addForm($formsongs->show(true));
    #$page->addHtml($filterNavbar);

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
        $bookNow               = '';
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
        $now = new DateTime();
        $bookWithSongStart=$row['bookWithSongStart'];
        $bookNonSongStart=$row['bookNonSongStart'];
        $showBookNow=FALSE;
        $songId=0;


        if($firstElement)
        {
            $htmlBookElements .= '<div class="row">';
        }
        if ($now>$bookNonSongStart)
        {
            $htmlBookElements.= '<div class="panel-body">' . 'This room can be booked' ;
            If ($row['IBooked']>0)
            {
                $htmlBookElements.= ', but you already booked a slot for this room';
            }
            $htmlBookElements.=  '.</div>';
            $showBookNow=TRUE;
        } else {
            if ($now>$bookWithSongStart)
            {
                $htmlBookElements.= '<div class="panel-body">' . 'This room can only be booked yet for working on events-registered songs';
                If ($row['IBooked']>0)
                {
                    $htmlBookElements.= ', but you already booked a slot for this room';
                }
                $htmlBookElements.='. From ' . $bookNonSongStart->format('D M d'). ', ' . $bookNonSongStart->format('H:i'). ' it can be booked for anything.' . '</div>';
                if ($getSongId>0)
                {
                    $showBookNow=TRUE;
                }
            } else {
                $htmlBookElements.= '<div class="panel-body">This room cannot be booked yet. It can be booked for events-registered songs from ' . $bookWithSongStart->format('D M d'). ', ' . $bookWithSongStart->format('H:i') . '</div>';
                $showBookNow=FALSE;
            }
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
            $bookNow='';
            $moreinfo='';
            $Ibooked=FALSE;
            $deleteButton='';
            if ($slotBookings[$key]>0)
            {
                $status='Booked';
                $bookingSQLWithoutSNR='SELECT mws__bookings.boo_snr_id, mws__user_data.usd_value, mws__roombookingday.rbd_id, mws__roombookingday.rbd_roomDescription, mws__bookings.boo_slotindex, mws__users.usr_id FROM mws__bookings inner join mws__roombookingday on mws__roombookingday.rbd_id=mws__bookings.boo_rbd_id inner join mws__users on mws__users.usr_id=mws__bookings.boo_usr_id inner join mws__user_data on mws__user_data.usd_usr_id=mws__users.usr_id where mws__user_data.usd_usf_id IN (2) and mws__bookings.boo_id='.$slotBookings[$key] ;
                $bookingSQLWithSNR='SELECT mws__user_data.usd_value, mws__roombookingday.rbd_id, mws__bookings.boo_snr_id, mws__bands.bnd_name, mws__roombookingday.rbd_roomDescription, mws__dates.dat_headline,  mws__songs.son_title, mws__bookings.boo_slotindex, mws__users.usr_id, mws__song_musicianregistration.smr_id, mws__instruments.ins_name FROM mws__song_musicianregistration inner join mws__song_registration on mws__song_musicianregistration.smr_snr_id=mws__song_registration.snr_id inner join mws__bookings on mws__bookings.boo_snr_id=mws__song_registration.snr_id inner join mws__bands on mws__song_registration.snr_bnd_id=mws__bands.bnd_id inner join mws__roombookingday on mws__roombookingday.rbd_id=mws__bookings.boo_rbd_id inner join mws__dates on mws__dates.dat_id=mws__song_registration.snr_dat_id inner join mws__songs on mws__songs.son_id=mws__song_registration.snr_son_id inner join mws__instruments on mws__instruments.ins_id=mws__song_musicianregistration.smr_ins_id inner join mws__users on mws__users.usr_id=mws__bookings.boo_usr_id inner join mws__user_data on mws__user_data.usd_usr_id=mws__users.usr_id where mws__user_data.usd_usf_id IN (2) and mws__bookings.boo_id='.$slotBookings[$key] ;
                $pdoStatementNoSNR = $gDb->queryPrepared($bookingSQLWithoutSNR); // TODO add more params
                $pdoStatementWithSNR = $gDb->queryPrepared($bookingSQLWithSNR); 
                $bookedCount=$pdoStatementNoSNR->rowCount();
                if ($bookedCount>0)
                {
                    $bookedResults=array();
                    $bookedData = $pdoStatementNoSNR->fetchAll();
                    $bookedSong=False;
                    if ($bookedData[0]['boo_snr_id']>0)
                    {
                        $bookedSong=True;
                        $bookedData = $pdoStatementWithSNR->fetchAll();
                    }
                    if ($bookedData[0]['usr_id'] == $gCurrentUserId)
                    {
                        $Ibooked=TRUE;
                        $deleteButton = '
                        <a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('boo_id'=>$slotBookings[$key], 'mode'=>2,)) . '">
                        <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
                    }
                    if ($bookedSong)
                        {
                            $moreinfo='<img class="admidio-icon-help" src="' . THEME_URL . '/icons/help.png"
                            title="' . $gL10n->get('SYS_NOTE') . '" alt="Help" data-toggle="popover" data-html="true"
                            data-trigger="hover" data-placement="auto" data-content="';
                            $moreinfo.= htmlspecialchars('Song "'.$bookedData[0]['son_title'].'" for event "'.$bookedData[0]['dat_headline'].'" with:<br>');
                            $moreinfo.='<ul>';
                            foreach ($bookedData as $abook)
                            {
                                $moreinfo.= htmlspecialchars('<li>'.$abook['usd_value'].' on '.$abook['ins_name'].'</li>') ;
                            }
                            $moreinfo.='</ul>';
                            $moreinfo.= '" />';
                        }
                } 
            } else {
                if ($showBookNow)
                {
                    if ($row['IBooked']==0)
                    {             
                        $bookNow= '
                        <a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('boo_snr_id'=>$getSnrId, 'rbd_id' => $bookId, 'mode'=>6,'boo_slotindex'=>$key, 'son_id'=>$getSongId, 'boo_bookdate'=> $startValue->format('Y-m-d H:i:s'), 'headline' => $getHeadline)) . '">
                            <img src="'.THEME_URL.'/icons/edit.png" alt="Book Now" title="Book now" /></a>';
                    }
            }
            }

            $htmlBookElements.='<div class="col-sm-2 col-xs-4">'.$status.$bookNow.'</div>';
            $htmlBookElements.='<div class="col-sm-4 col-xs-4">'.$slotBookingsName[$key].$moreinfo.$deleteButton.'</div>';
            $htmlBookElements.='</div>';
        }
        

         $outputRoomDescription = $booking->getValue('rbd_roomDescription');

        $outputVenueName = $booking->getValue('ven_name');
        $bookHeadline=$outputVenueName . ': ' . $outputRoomDescription.' - '. $startValue->format('D M d');

                // change and delete is only for users with additional rights
        if ($bookAdmin)
        {
            $outputButtonEdit = '
                <a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/roombooking_new.php', array('rbd_id' => $bookId, 'headline' => $getHeadline)) . '">
                    <img src="'.THEME_URL.'/icons/edit.png" alt="' . $gL10n->get('SYS_EDIT') . '" title="' . $gL10n->get('SYS_EDIT') . '" /></a>';
            $outputButtonDelete = '
                <a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
                    href="'.safeUrl(ADMIDIO_URL.'/adm_program/system/popup_message.php', array('type' => 'rbd', 'database_id'=> $bookId,'element_id' => 'rbd_' . $bookId)) . '">
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
