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
$get_boo_rbd_id    = admFuncVariableIsValid($_GET, 'boo_rbd_id',       'int');
#$get_boo_bookdate    = admFuncVariableIsValid($_GET, 'boo_bookdate',   'string');
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string');
$exception=new TableBookingException($gDb);
$gCurrentUserId=$gCurrentUser->getValue('usr_id');

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->editBookings()) //viewBookings())
{
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $getHeadline);

// Gaestebuchobjekt anlegen
$roombookingDay = new TableRoomBookingDay($gDb);

// create html page object
$page = new HtmlPage($headline);
$page->enableModal();

// add back link to module menu
$bookingsCreateMenu = $page->getMenu();
$bookingsCreateMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

$exceptSQL='SELECT DAYOFWEEK(rbd_startTime) as weekday, rbd_id, ven_name, rbd_roomDescription, bex_id, bex_rbd_date, DATE_FORMAT(bex_rbd_date,\'%a %d %b %Y\') as \'exdate\', bex_description FROM mws__bookexceptions INNER JOIN mws__roombookingday on mws__bookexceptions.bex_rbd_id=mws__roombookingday.rbd_id INNER JOIN mws__venues ON mws__venues.ven_id=mws__roombookingday.rbd_venue WHERE bex_rbd_id='.$get_boo_rbd_id.' and rbd_weekly=1 ORDER BY bex_rbd_date';
$pdoStatement = $gDb->queryPrepared($exceptSQL); 
$exceptCount=$pdoStatement->rowCount();
if ($exceptCount>0)
{
    $exeptResults=array();
    $exceptData = $pdoStatement->fetchAll();
}

$page->addHtml('<div>');
    $page->addHtml('<div class="panel panel-primary">
                        <div class="panel-heading">Exceptions</div>
                        <div class="panel-body">');
                            foreach($exceptData as $except)
                            { 
                                $deleteButton = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('bex_id'=>$except['bex_id'], 'mode'=>2,)) . '">
                                    <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
                                $page->addHtml('<div class="admidio-exceptions-item" id="exc_1">');
                                $page->addHtml($except['ven_name'].' - '.$except['rbd_roomDescription'].', '.$except['exdate'].' - '.$except['bex_description'].' '.$deleteButton);
                                $page->addHtml('</div>');
                            }
                            $form = new HtmlForm('roombooking_exception_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('rbd_id' => $get_boo_rbd_id, 'mode' => 7)), $page);
                            $form->addInput('bex_rbd_id', 'Room booking', $get_boo_rbd_id,array('type' => 'number', 'property' => HtmlForm::FIELD_HIDDEN));
                            $form->addInput('bexc_date' , 'Date', Date($gSettingsManager->getString('system_date')), array('type' => 'date','property' => HtmlForm::FIELD_REQUIRED));
                            $form->addInput('bex_description','Description', '' , array('maxLength' => 100));
                            $form->addSubmitButton('btn_save', 'Save new exception', array('icon' => THEME_URL.'/icons/disk.png'));
                            $page->addHtml($form->show(false));
                        $page->addHtml('</div>');
                    $page->addHtml('</div>');
    $specialSQL='SELECT boo_id,boo_slotindex, boo_bookdate, DATE_FORMAT(boo_bookdate,\'%a %d %b %Y\') as \'bookdate\',boo_slotindex, boo_comment, rbd_roomDescription, ven_name FROM mws__bookings inner join mws__roombookingday on boo_rbd_id=rbd_id inner join mws__venues on ven_id=rbd_venue WHERE boo_specialbooking=1 and boo_rbd_id='.$get_boo_rbd_id;
    $pdoStatement = $gDb->queryPrepared($specialSQL); 
    $specialCount=$pdoStatement->rowCount();
    if ($specialCount>0)
    {
        $specialResults=array();
        $specialData = $pdoStatement->fetchAll();
    }
    $page->addHtml('<div class="panel panel-primary">
                    <div class="panel-heading">Special bookings</div>
                    <div class="panel-body">');
                        foreach($specialData as $special)
                            { 
                            $deleteButton = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('boo_id'=>$special['boo_id'], 'mode'=>2)) . '">
                                <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
                                $page->addHtml('<div class="admidio-exceptions-item" id="exc_1">');
                                $page->addHtml($special['ven_name'].' - '.$special['rbd_roomDescription'].', '.$special['bookdate'].' - [slot '.$special['boo_slotindex']. '] - '.$special['boo_comment'].' '.$deleteButton);
                                $page->addHtml('</div>');
                            }
                            $form = new HtmlForm('special_booking_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/booking_function.php', array('rbd_id' => $get_boo_rbd_id, 'mode' => 8)), $page);
                            $form->addInput('boo_rbd_id', 'Room booking', $get_boo_rbd_id,array('type' => 'number', 'property' => HtmlForm::FIELD_HIDDEN));
                            $form->addInput('boo_specialbooking', 'Special booking', 1,array('type' => 'number', 'property' => HtmlForm::FIELD_HIDDEN));
                            $form->addInput('boo_usr_id', 'User', $gCurrentUserId,array('property' => HtmlForm::FIELD_HIDDEN));
                            $form->addInput('book_date' , 'Date', Date($gSettingsManager->getString('system_date')), array('type' => 'date','property' => HtmlForm::FIELD_REQUIRED));
                            $form->addInput('boo_slotindex', 'Slot index', 1, array('type' => 'number', 'minNumber' => 1, 'maxNumber' => 999, 'step' => 1, ));
                            $form->addInput('boo_comment','Comment', '' , array('maxLength' => 100));
                            $form->addSubmitButton('btn_save', 'Save special booking', array('icon' => THEME_URL.'/icons/disk.png'));
                            $page->addHtml($form->show(false));
                    $page->addHtml('</div>');
                $page->addHtml('</div>');
$page->addHtml('</div>');

$page->show();
