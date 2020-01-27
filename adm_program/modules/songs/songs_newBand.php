<?php
/**
 ***********************************************************************************************
 * Create and edit dates
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * dat_id   - ID of the event that should be edited
 * headline - Headline for the event
 *            (Default) Events
 * copy : true - The event of the dat_id will be copied and the base for this new event
 ***********************************************************************************************
 *  * mode   : 1 - Create a new song
 *          2 - Delete the song
 *          3 - User added to the song
 *          4 - User removed a song
 *          5 - Edit an existing song
 *          6 - Create a new band
 *          7 - Delete a band
 *          8 - Register band+song to event
 *          9 - Remove band from event
 *          10 - Edit an existing band
 */
require_once(__DIR__ . '/../../system/common.php');
require(__DIR__ . '/../../system/login_valid.php');

// Initialize and check the parameters
$getBandId   = admFuncVariableIsValid($_GET, 'bnd_id',   'int');
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string', array('defaultValue' => 'Band'));
$getCopy     = admFuncVariableIsValid($_GET, 'copy',     'bool');
$getUserId   = $gCurrentUser->getValue('usr_id');
// check if module is active
if((int) $gSettingsManager->get('enable_dates_module') === 0)
{
    // Module is not active
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

// lokale Variablen der Uebergabevariablen initialisieren
$dateRegistrationPossible = false;
$dateCurrentUserAssigned  = false;
$roleViewSet              = array();

// set headline of the script
if($getCopy)
{
    $headline = $gL10n->get('SYS_COPY_VAR', array($getHeadline));
    $mode = 10;
}
elseif($getBandId > 0)
{
    $headline = $gL10n->get('SYS_EDIT_VAR', array($getHeadline));
    $mode = 10;
}
else
{
    $headline = $gL10n->get('SYS_CREATE_VAR', array($getHeadline));
    $mode = 6;
}

$gNavigation->addUrl(CURRENT_URL, $headline);

// create date object
$band = new TableBand($gDb);
$band->setValue('bnd_usr_id',$getUserId);

if($getBandId > 0)
{
    // read data from database
    $band->readDataById($getBandId);
}

// create html page object
$page = new HtmlPage($headline);

$page->addJavascriptFile(ADMIDIO_URL . '/adm_program/system/js/date-functions.js');

// add back link to module menu
$bandMenu = $page->getMenu();
$bandMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// show form
$form = new HtmlForm('band_edit_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('bnd_id' => $getBandId, 'type'=>'band', 'mode' => $mode, 'copy' => $getCopy)), $page);
$form->addHtml('<div>Please take some time to add a promotional band description, picture or video. You can add it later, but it helps our organizer to better promote our events so potential visitors can be shown what kind of music to expect.</div>');
$form->openGroupBox('gb_title_bandif', 'Band info');
$form->addInput('bnd_usr_id', "User ID", $band->getValue('bnd_usr_id'),
    array('property' => HtmlForm::FIELD_HIDDEN));
$form->addInput('bnd_name', "Band name", $band->getValue('bnd_name'),
    array('maxLength' => 100, 'property' => HtmlForm::FIELD_REQUIRED));
$form->addInput('bnd_promovideo_url', "Promo Video URL", $band->getValue('bnd_promovideo_url'),
    array('maxLength' => 100));
$form->addInput('bnd_page_url', "Band Page URL", $band->getValue('bnd_page_url'),
    array('maxLength' => 100));

$form->closeGroupBox();

$form->openGroupBox('gb_description', '(promotional) Band description', 'admidio-panel-editor');
$form->addEditor('bnd_description', '', $band->getValue('bnd_description'));
$form->closeGroupBox();

$form->addSubmitButton('btn_save', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL.'/icons/disk.png'));
$form->addHtml(admFuncShowCreateChangeInfoById(
    (int) $band->getValue('dat_usr_id'), $band->getValue('bnd_timestamp_create'),
    $band->getValue('dat_timestamp_change')
));

// add form to html page and show page
$page->addHtml($form->show(false));
$page->show();
