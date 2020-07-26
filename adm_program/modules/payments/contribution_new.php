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
$getfee_id    = admFuncVariableIsValid($_GET, 'fee_id',       'int');
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string');

if ($getHeadline == "")
{
    $getHeadline = 'Contribution item';
}

// check if the module is enabled and disallow access if it's disabled
if (!$gCurrentUser->editFinance())
{
    $gMessage->show("Please log in with a finance admin enabled user to edit bookings");
    // => EXIT
}

// set headline of the script
if ($getfee_id > 0)
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
$fee = new TableContribution($gDb);

// create html page object
$page = new HtmlPage($headline);

// add back link to module menu
$feeCreateMenu = $page->getMenu();
$feeCreateMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// Html des Modules ausgeben
if ($getfeeId > 0)
{
    $mode = '5';
}
else
{
    $mode = '1';
}
// show form
$form = new HtmlForm('fee_edit_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/payment_function.php', array('fee_id' => $getfeeId, 'headline' => $getHeadline, 'mode' => $mode)), $page);
$form->addInput(
    'fee_description', 'Description', $fee->getValue('fee_description'),array('property' => HtmlForm::FIELD_REQUIRED)
);
$form->addInput(
    'fee_amount', 'Contribution amount', $fee->getValue('fee_amount'),array('type' => 'number')
);
$form->addInput(
    'fee_from', $gL10n->get('SYS_START'), $fee->getValue('fee_from', $gSettingsManager->getString('system_date')),
    array('type' => 'date', 'maxLength' => 10)
);
$form->addInput(
    'fee_to', $gL10n->get('SYS_END'), $fee->getValue('fee_to', $gSettingsManager->getString('system_date')),
    array('type' => 'date', 'maxLength' => 10)
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
