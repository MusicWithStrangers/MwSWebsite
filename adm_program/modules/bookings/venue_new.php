<?php
/**
 ***********************************************************************************************
 * Create and edit guestbook entries
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * id         - Id of one guestbook entry that should be shown
 * headline   - Title of the guestbook module. This will be shown in the whole module.
 *              (Default) GBO_GUESTBOOK
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../system/common.php');

// Initialize and check the parameters
$getven_id    = admFuncVariableIsValid($_GET, 'ven_id',       'int');
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string');

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->editBookings())
{
    $gMessage->show("Please log in with a booking-enabled user to edit bookings");
    // => EXIT
}

// set headline of the script
if ($getven_id > 0)
{
    $headline = $getHeadline . ' - ' . $gL10n->get('SYS_EDIT_ENTRY');
}
else
{
    $headline = $getHeadline . ' - ' . $gL10n->get('SYS_WRITE_ENTRY');
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

// Gaestebuchobjekt anlegen
$venue = new TableVenue($gDb);


// create html page object
$page = new HtmlPage($headline);

// add back link to module menu
$venueCreateMenu = $page->getMenu();
$venueCreateMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// Html des Modules ausgeben
if ($getvenId > 0)
{
    $mode = '5';
}
else
{
    $mode = '1';
}
// show form
$form = new HtmlForm('venue_edit_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/bookings/venue_function.php', array('ven_id' => $getvenId, 'headline' => $getHeadline, 'mode' => $mode)), $page);
$form->addInput(
    'ven_name', 'Venue name', $venue->getValue('ven_name'),array('property' => HtmlForm::FIELD_REQUIRED)
);
$form->addInput(
    'ven_address', 'Venue address', $venue->getValue('ven_address')
);
$form->addInput(
    'ven_city', 'Venue city', $venue->getValue('ven_city')
);


// show information about user who creates the recordset and changed it
$form->addSubmitButton('btn_save', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL.'/icons/disk.png'));
//$form->addHtml(admFuncShowCreateChangeInfoById(
//    (int) $guestbook->getValue('gbo_usr_id_create'), $guestbook->getValue('gbo_timestamp_create'),
//    (int) $guestbook->getValue('gbo_usr_id_change'), $guestbook->getValue('gbo_timestamp_change')
//));

// add form to html page and show page
$page->addHtml($form->show(false));
$page->show();
