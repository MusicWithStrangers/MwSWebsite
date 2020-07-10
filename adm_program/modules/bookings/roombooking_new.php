<?php
/**
 ***********************************************************************************************
 * Create and edit bookings entries
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * id         - Id of one bookings entry that should be shown
 * headline   - Title of the bookings module. This will be shown in the whole module.
 *              (Default) GBO_GUESTBOOK
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../system/common.php');

// Initialize and check the parameters
$getrbd_id    = admFuncVariableIsValid($_GET, 'rbd_id',       'int');
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string');

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->editBookings()) //viewBookings())
{
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

// Gaestebuchobjekt anlegen
$roombookingDay = new TableRoomBookingDay($gDb);

if(isset($_SESSION['roombook_entry_request']))
{
    // durch fehlerhafte Eingabe ist der User zu diesem Formular zurueckgekehrt
    // nun die vorher eingegebenen Inhalte ins Objekt schreiben
    $_SESSION['rbday_request']['rbd_startTime'] = $_SESSION['rbday_request']['rbday_startTime'].' '.$_SESSION['rbday_request']['rbday_startTime_time'];
    $bookings->setArray($_SESSION['roombook_entry_request']);
    unset($_SESSION['roombook_entry_request']);
} else {
    if ($getrbd_id > 0)
    {
        $roombookingDay->readDataById($getrbd_id);
    }
}


// create html page object
$page = new HtmlPage($getHeadline);

// add back link to module menu
$bookingsCreateMenu = $page->getMenu();
$bookingsCreateMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// Html des Modules ausgeben
if ($getrbd_id > 0)
{
    $mode = '5';
    $headline = $getHeadline . ' - ' . $gL10n->get('SYS_EDIT_ENTRY');
}
else
{
    $mode = '1';
    $headline = $getHeadline . ' - ' . $gL10n->get('SYS_WRITE_ENTRY');
}
$sqlAdmins='select * from mws__roles where rol_bookingadmin=1';
$AdminData= $gDb->queryPrepared($sqlAdmins);
$adminroles=array();
if ($AdminData->rowCount()>0)
{
    $AdminFetch      = $AdminData->fetchAll();
    foreach ($AdminFetch as $anAdmin)
    {
        $adminroles[]=$anAdmin['rol_id'];
    }
}
$sqlDataVenue = 'select ven_id, ven_name from mws__venues';
$sqlDataContact=array();
$sqlDataContact['query'] = 'SELECT usr_id, CONCAT(last_name.usd_value, \' \', first_name.usd_value) AS name
                       FROM '.TBL_MEMBERS.'
                 INNER JOIN '.TBL_ROLES.'
                         ON rol_id = mem_rol_id
                 INNER JOIN '.TBL_CATEGORIES.'
                         ON cat_id = rol_cat_id
                 INNER JOIN '.TBL_USERS.'
                         ON usr_id = mem_usr_id
                  LEFT JOIN '.TBL_USER_DATA.' AS last_name
                         ON last_name.usd_usr_id = usr_id
                        AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
                  LEFT JOIN '.TBL_USER_DATA.' AS first_name
                         ON first_name.usd_usr_id = usr_id
                        AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
                      WHERE rol_id IN ('.replaceValuesArrWithQM($adminroles).')
                        AND rol_valid   = 1
                        AND cat_name_intern <> \'EVENTS\'
                        AND ( cat_org_id = ? -- $gCurrentOrganization->getValue(\'org_id\')
                            OR cat_org_id IS NULL )
                        AND mem_begin <= ? -- DATE_NOW
                        AND mem_end   >= ? -- DATE_NOW
                        AND usr_valid  = 1
                   ORDER BY last_name.usd_value, first_name.usd_value, usr_id';

$sqlDataContact['params'] = array_merge(
    array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id')
    ),
    $adminroles,
    array(
        $gCurrentOrganization->getValue('org_id'),
        DATE_NOW,
        DATE_NOW
    )
);
// show form
$buttonURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/venue_new.php'); //, array('rol_id' => $dateRolId));
$outputButtonAddVenue = '
    <button class="btn btn-default" onclick="window.location.href=\'' . $buttonURL . '\'">
        <img src="'.THEME_URL.'/icons/add.png" alt="Add venue" />Add venue</button>';
$form = new HtmlForm('roombooking_edit_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('rbd_id' => $getrbd_id, 'headline' => $getHeadline, 'mode' => $mode)), $page);
$form->addSelectBoxFromSql('rbd_venue', 'Venue', $gDb, $sqlDataVenue,
    array('property' => HtmlForm::FIELD_REQUIRED, 'search' => true, 'defaultValue' => $roombookingDay->getValue('rbd_venue')));
$form->addSelectBoxFromSql('rbd_operationalContact', 'MWS Contact', $gDb, $sqlDataContact,
    array('property' => HtmlForm::FIELD_REQUIRED, 'search' => true,'defaultValue' => $roombookingDay->getValue('rbd_operationalContact')));
$form->addInput(
    'rbd_slotLength', 'Slot length (minutes)', $roombookingDay->getValue('rbd_slotLength'),
    array('type' => 'number', 'minNumber' => 15, 'maxNumber' => 540, 'step' => 1)
);
$form->addInput(
    'rbday_startTime', 'Start time/day', $roombookingDay->getValue('rbd_startTime', $gSettingsManager->getString('system_date').' '.$gSettingsManager->getString('system_time')),
    array('type' => 'datetime','property' => HtmlForm::FIELD_REQUIRED)
);
$form->addInput(
    'rbd_slotCount', 'Number of slots', $roombookingDay->getValue('rbd_slotCount'),
    array('type' => 'number', 'minNumber' => 1, 'maxNumber' => 20, 'step' => 1)
);
$form->addInput(
    'rbd_hoursBookingSNR', 'Hours ahead booking for songs', $roombookingDay->getValue('rbd_hoursBookingSNR'),
    array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 200, 'step' => 1,'helpTextIdLabel' => 'RBD_HOURSBOOKSNR_LINK')
);
$form->addInput(
    'rbd_hoursBookingNonSNR', 'Hours ahead booking for non-songs', $roombookingDay->getValue('rbd_hoursBookingNonSNR'),
    array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 200, 'step' => 1,'helpTextIdLabel' => 'RBD_HOURSBOOKNONSNR_LINK')
);
$form->addInput(
    'rbd_slotprice', 'Rent per slot', $roombookingDay->getValue('rbd_slotpricce'),
    array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 200, 'step' => 1, 'property' => HtmlForm::FIELD_REQUIRED)
);
//$form->addInput(
//    'rbd_repeatdays', 'Repeat frequency (days)', $roombookingDay->getValue('rbd_rbd_repeatdays'),
//    array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 20, 'step' => 1)
//);
$form->addInput(
    'rbd_roomDescription', 'Room description', $roombookingDay->getValue('rbd_roomDescription'),array('property' => HtmlForm::FIELD_REQUIRED)
);
$form->addCheckbox('rbd_weekly','Weekly', $roombookingDay->getValue('rbd_weekly'),array('helpTextIdLabel' => 'RBD_WEEKLY_LINK'));
$form->addCheckbox('rbd_enable','Enabled', $roombookingDay->getValue('rbd_enable'), array('helpTextIdLabel' => 'RBD_ENABLED_LINK'));
$form->addCheckbox('rbd_autoDisable','Auto disable', $roombookingDay->getValue('rbd_Autodisable'),array('helpTextIdLabel' => 'RBD_AUTODISABLE_LINK'));
$form->openGroupBox('gb_description0', 'Gear available', 'admidio-panel-editor');
$form->addEditor('rbd_gear_available', '', $roombookingDay->getValue('rbd_gear_available'));
$form->closeGroupBox();
$form->openGroupBox('gb_description1', 'Notes for first slot users', 'admidio-panel-editor');
$form->addEditor('rbd_first_slot_remark', '', $roombookingDay->getValue('rbd_first_slot_remark'));
$form->closeGroupBox();
$form->openGroupBox('gb_description2', 'Notes for last slot users', 'admidio-panel-editor');
$form->addEditor('rbd_last_slot_remark', '', $roombookingDay->getValue('rbd_last_slot_remark'));
$form->closeGroupBox();


// show information about user who creates the recordset and changed it
$form->addSubmitButton('btn_save', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL.'/icons/disk.png'));
//$form->addHtml(admFuncShowCreateChangeInfoById(
//    (int) $bookings->getValue('gbo_usr_id_create'), $bookings->getValue('gbo_timestamp_create'),
//    (int) $bookings->getValue('gbo_usr_id_change'), $bookings->getValue('gbo_timestamp_change')
//));

// add form to html page and show page
$buttonURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/venue_new.php'); //, array('rol_id' => $dateRolId));
$outputButtonAddVenue = '
    <button class="btn btn-default" onclick="window.location.href=\'' . $buttonURL . '\'">
        <img src="'.THEME_URL.'/icons/add.png" alt="Add venue" />Add venue</button>';
//$get_boo_rbd_id    = admFuncVariableIsValid($_GET, 'boo_rbd_id',       'int');
//$get_boo_bookdata    = admFuncVariableIsValid($_GET, 'boo_bookdate',   'string');
$exceptURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/exceptions_new.php', array('boo_rbd_id' => $getrbd_id)); //, array('rol_id' => $dateRolId));
$outputButtonExceptions = '
    <button class="btn btn-default" onclick="window.location.href=\'' . $exceptURL . '\'">
    <img src="'.THEME_URL.'/icons/dates.png" alt="Weekly exceptions" />Weekly Exceptions</button>';
$page->addHtml('<div class="btn-group">'.$outputButtonExceptions.'</div>');
$page->addHtml('<div class="btn-group">'.$outputButtonAddVenue.'</div>');
$page->addHtml($form->show(false));
$page->show();
