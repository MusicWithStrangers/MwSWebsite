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
 *          2 - Delete the booking or bookingroom or exception
 *          5 - Edit an existing bookingroom
 *          6 - Book a room
 *          7 - Create an exception
 *          8 - Create a new special booking
 *          9 - Pay for booking

 * rol_id : vorselektierte Rolle der Rollenauswahlbox
 * copy   : true - The event of the dat_id will be copied and the base for this new event
 * number_role_select : Nummer der Rollenauswahlbox, die angezeigt werden soll
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../system/common.php');
require_once(__DIR__ . '/../../libs/phpmailer/extras/EasyPeasyICS.php');

if($_GET['mode'] == 2)
{
    $gMessage->showHtmlTextOnly(true);
}

// Initialize and check the parameters
$getRoomBookingDayId    = admFuncVariableIsValid($_GET, 'rbd_id', 'int');
$getSnrId               = admFuncVariableIsValid($_GET, 'boo_snr_id', 'int');
if (!$getSnrId>0)
{
    $getSnrId               = admFuncVariableIsValid($_GET, 'snr_id', 'int');
}
$get_bexdate            = admFuncVariableIsValid($_GET, 'bex_rbd_date', 'string');
$get_bexdescription     = admFuncVariableIsValid($_GET, 'bex_description', 'string');
$getBookId              = admFuncVariableIsValid($_GET, 'boo_id', 'int');
$getExceptionId         = admFuncVariableIsValid($_GET, 'bex_id', 'int');
$getslotindex           = admFuncVariableIsValid($_GET, 'boo_slotindex', 'int');
$bookDate               = admFuncVariableIsValid($_GET, 'boo_bookdate', 'string');
$timeString             = admFuncVariableIsValid($_GET, 'slot_time', 'string');
$getMode                = admFuncVariableIsValid($_GET, 'mode',   'int', array('requireValue' => true));
$getUserId              = admFuncVariableIsValid($_GET, 'usr_id', 'int', array('defaultValue' => $gCurrentUser->getValue('usr_id')));

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->ViewBookings())
{
    $gMessage->show("Please log in with a booking-enabled user to edit bookings");
    // => EXIT
}


// create event object
$roombookingday = new TableRoomBookingDay($gDb);
$roombookingday->readDataById($getRoomBookingDayId);


if($getMode === 1 || $getMode === 5)  // Create a roombooking or edit.
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
    $startDateTime = \DateTime::createFromFormat($gSettingsManager->getString('system_date').' '.$gSettingsManager->getString('system_time'), $_POST['rbday_startTime'].' '.$_POST['rbday_startTime_time']);
    $roombookingday->setValue('rbd_startTime', $startDateTime->format('Y-m-d H:i:s'));
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
    if ($getRoomBookingDayId>0)
    {
    // delete current announcements, right checks were done before
    $roombookingday->delete();

    // Delete successful -> Return for XMLHttpRequest
    echo 'done';
    }
    if ($getExceptionId>0)
    {
        $exception=new TableBookingException($gDb);
        $exception->readDataById($getExceptionId);
        $exception->delete();
        admRedirect($gNavigation->getUrl());
    }
    if ($getBookId>0)
    {
            $booking = new TableBooking($gDb);
            $booking->readDataById($getBookId);
            if ($booking->getValue('boo_payed')>0)
            {
                $balance=$gCurrentUser->getValue('usr_balance');
                $rbd_id=$booking->getValue('boo_rbd_id');
                $roombookingday = new TableRoomBookingDay($gDb);
                $roombookingday->readDataById($rbd_id);
                $balance=$balance + $roombookingday->getValue('rbd_slotprice');
                $gCurrentUser->setValue('usr_balance',$balance);
                #$gCurrentUser[ $getUserId
            }
            $booking->delete();
    // Delete successful -> Return for XMLHttpRequest
            //echo 'done';
            admRedirect($gNavigation->getUrl());
    }
} elseif ($getMode ===6)
{
    //Direct booking (GET not POST)
    $booking = new TableBooking($gDb);
    $booking->readDataById(0);
    $booking->setValue('boo_rbd_id', $getRoomBookingDayId);
    $booking->setValue('boo_snr_id', $getSnrId);
    $booking->setValue('boo_usr_id', $getUserId);
    $booking->setValue('boo_slotindex', $getslotindex);
    $booking->setValue('boo_bookdate', $bookDate);
    $pay_blocked=FALSE;
    $people_in_song='';
    $song_name='';
    if ($getSnrId>0)
    {
        $song_registration = new TableBandSongRegister($gDb, $id=$getSnrId);
        $song_id = $song_registration->getValue('snr_son_id');
        $song = new TableSong($gDb, $song_id);
        $song_name=$song->getValue('son_title');
        $userlist = $song->users_in_song();
        foreach ($userlist as $a_user)
        {
            $user=new User($gDb,$gProfileFields,$a_user);
            $userName=$user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME');
            $people_in_song.=$userName.', ';
            $blocked_message='';
            if (!$user->userPayedNow())
            {
                
                $pay_blocked=TRUE;
                $blocked_message = $blocked_message . $userName;
            }
        }
    }
    if ($pay_blocked)
        {
            $blocked_message="Can't book this room for the selected song, because some people in it didn't pay contribution. (".$blocked_message.")";
            $location=safeUrl(ADMIDIO_URL.'/adm_program/system/popup_message.php', array('type' => 'payup', 'name' => 'Registration failed - contribution payment behind', 'element_id' =>'nopay','database_id'=>'nopay'));
            //header('Location: '.$location);
        }
    else {
        $balance = $gCurrentUser->getValue('usr_balance','float');
        $amount = $roombookingday->getValue('rbd_slotprice','float');
        if ($amount>0)
        #if ($balance >= $amount and $amount > 0)
        {
            $newBalance=$balance-$amount;
            $gCurrentUser->setValue('usr_balance',$newBalance);
            $booking->setValue('boo_payed',1);
        } 

        $gDb->startTransaction();
        // save room booking day in database
        $returnCode = $booking->save();

        $booking = (int) $booking->getValue('boo_id');

        $gDb->endTransaction();
        
        // Send mail to participants
        // mailfrom, namefrom, subject, msg_body, delivery_confirmation, msg_to
        $mail = new Email();
        $mail->addRecipientSongRegisterId($getSnrId);
        #$mail->setCopyToSenderFlag();
        $mail->sendDataAsHtml();
        $subject='';
        $roomBookingDay = new TableRoomBookingDay($gDb,$getRoomBookingDayId);
        $roomName=$roomBookingDay->getValue('rbd_roomDescription');
        $firstSlotRemark=$roomBookingDay->getValue('rbd_first_slot_remarks');
        $lastSlotRemark=$roomBookingDay->getValue('rbd_last_slot_remarks');
        $gearRemark=$roomBookingDay->getValue('rbd_gear_available');
        global $gDb, $gProfileFields,$gCurrentUser;
        $contact=new User($gDb,$gProfileFields,$roomBookingDay->getValue('rbd_operationalContact'));
        
        $venue=new TableVenue($gDb, $roomBookingDay->getValue('rbd_venue'));
        $venueName=$venue->getValue('ven_name');
        $venueAddress=$venue->getValue('ven_address');
        $startDateTime = \DateTime::createFromFormat('Y-m-d H:i:s' ,$bookDate);
        $startTime=\DateTime::createFromFormat('Y-m-d H:i' ,$startDateTime->format('Y-m-d').' '.$timeString);
        $icsText='';
        $text='<H1>Room booked</H1>';
        $icsText.='Rehearsal\n\n';
        $text.='<table border="0">';
        $text.="<tr><td>Date:</td><td>".$startDateTime->format('d M Y').'</td></tr>';
        $text.="<tr><td>Time:</td><td>".$timeString.'</td></tr>';
        $text.="<tr><td>Room:</td><td>".$roomName.'</td></tr>';        
        $icsText.='Room: '.$roomName.'\n';
        $text.="<tr><td>Venue:</td><td>".$venueName.'</td></tr>';
        $icsText.='Venue: '.$venueName.'\n';
        $text.="<tr><td>Address:</td><td>".$venueAddress.'</td></tr>';
        $text.="<tr><td>Song:</td><td>".$song_name.'</td></tr>';
        $icsText.='Song: '.$song_name.'\n';
        $text.="<tr><td>People:</td><td>".$people_in_song.'</td></tr>';
        $icsText.='People: '.$people_in_song.'\n';
        if ($getSnrId>0)
        {
            $songregister=new TableBandSongRegister($gDb, $getSnrId);
            $band = new TableBand($gDb,$id=$songregister->getValue('snr_bnd_id'));
            $bandName=$band->getValue('bnd_name');
            $text.="<tr><td>Band:</td><td>".$bandName.'</td></tr>';
            $icsText.='Band: '.$bandName.'\n';
            $subject="Room '".$roomName."' booked for '".$bandName."' in ".$venueName;
        } else {
            $subject="Room booked";
        }
        $text.='</table>';
        if ($getslotindex==1)
        {
            $text.='<h3>You booked the 1st slot</h3>';
            $text.='<p>'.$firstSlotRemark.'</p>';
        }
        if ($getslotindex==$roomBookingDay->getValue('rbd_slotCount'))
        {
            $text.='<h3>You booked the last slot</h3>';
            $text.='<p>'.$lastSlotRemark.'</p>';
        }
        $text.='<h3>Gear available</h3>';
        $text.='<p>'.$gearRemark.'</p>';
        $text.='<p>For questons, please contact '.$contact->getValue('FIRST_NAME'). ' '.$contact->getValue('LAST_NAME').
                ' email: <a href="mailto:.'.$contact->getValue('EMAIL').'">'.$contact->getValue('EMAIL').
                '</a>, phone: '.$contact->getValue('MOBILE').'</p>';
        $ics = new EasyPeasyICS($gCurrentUser->getValue('usr_id').'_booking');
        $summary="Rehearsal with '".$bandName."' in ".$venueName;
        $ics->addEvent($startTime->getTimestamp(),$startTime->modify('+'.$roomBookingDay->getValue('rbd_slotLength').' minutes')->getTimestamp(),$summary,$icsText,'https://members.musicwithstrangers.com',$bandName);
        $ics_render=$ics->render(FALSE);
        $fname=__DIR__ . '/'.$gCurrentUser->getValue('usr_id').'_booking.ics';
        file_put_contents($fname,$ics_render);
        //$start, $end,$summary = '', $description = '', $url = '', $uid = ''
        $mail->addAttachment($fname, 'booking.ics');
        $mail->setSubject($subject);
        $mail->setText($text);

        // finally send the mail
        $sendResult = $mail->sendEmail();
        unlink($fname);
    
        $gNavigation->deleteLastUrl();

        admRedirect($gNavigation->getUrl());
        }
} elseif ($getMode ===7) {
    $exception=new TableBookingException($gDb);
    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'bex_'))
            {
                $exception->setValue($key, $value);
            }
        }
        $dformat=$gSettingsManager->getString('system_date');
        $dvalue=$_POST['bexc_date'];
        $exceptDate = DateTime::createFromFormat($dformat, $dvalue);
        if ($exceptDate===False)
        {
            $err=DateTime::getLastErrors();
        }
        $exception->setValue('bex_rbd_date', $exceptDate->format('Y-m-d H:i:s'));
        $gDb->startTransaction();
        // save room booking day in database
        $returnCode = $exception->save();

        $bex_id = (int) $roombookingday->getValue('bex_id');

        $gDb->endTransaction();
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    
} elseif($getMode === 8)  // Create a roombooking or edit.
{
    $specialBook = new TableBooking($gDb);
    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'boo_'))
            {
                $specialBook->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }
    $startDateTime = \DateTime::createFromFormat($gSettingsManager->getString('system_date'), $_POST['book_date']);
    
    $specialBook->setValue('boo_bookdate', $startDateTime->format('Y-m-d H:i:s'));
    $gDb->startTransaction();

    // save room booking day in database
    $returnCode = $specialBook->save();

    $bookingId = (int) $specialBook->getValue('boo_id');

    $gDb->endTransaction();

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT
} elseif($getMode === 9)  // Create a roombooking or edit.
{
    $balance = $gCurrentUser->getValue('usr_balance');
    $amount = admFuncVariableIsValid($_GET, 'rbd_slotprice', 'float');
    if ($balance >= $amount and $amount > 0)
    {
        $newBalance=$balance-$amount;
        $gCurrent->setValue('usr_balance',$newBalance);
    } else
    {
        echo "ToDo: make 'em pay by Paypal and save the payment in mws__payments";
    }
    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
}
