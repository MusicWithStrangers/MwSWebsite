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
 *          8 - Register band to event
 *          9 - Remove band from event
 *          10 - Edit an existing band
 */
require_once(__DIR__ . '/../../system/common.php');
require(__DIR__ . '/../../system/login_valid.php');

// Initialize and check the parameters
$getSongId   = admFuncVariableIsValid($_GET, 'son_id',   'int');
$getHeadline = admFuncVariableIsValid($_GET, 'headline', 'string', array('defaultValue' => 'Song'));
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
    $mode = 5;
}
elseif($getSongId > 0)
{
    $headline = $gL10n->get('SYS_EDIT_VAR', array($getHeadline));
    $mode = 5;
}
else
{
    $headline = $gL10n->get('SYS_CREATE_VAR', array($getHeadline));
    $mode = 1;
}

$gNavigation->addUrl(CURRENT_URL, $headline);

// create date object
$song = new TableSong($gDb);
$song->setValue('son_usr_id',$getUserId);

if($getSongId > 0)
{
    // read data from database
    $song->readDataById($getSongId);
}

// create html page object
$page = new HtmlPage($headline);

$page->addJavascriptFile(ADMIDIO_URL . '/adm_program/system/js/date-functions.js');

// add back link to module menu
$bandMenu = $page->getMenu();
$bandMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// show form
$form = new HtmlForm('song_edit_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('son_id' => $getSongId, 'type'=>'song', 'mode' => $mode, 'copy' => $getCopy)), $page);

$form->openGroupBox('gb_title_songif', $gL10n->get('SYS_TITLE').' & Song info');
$form->addInput('son_usr_id', "User ID", $song->getValue('son_usr_id'),
    array('property' => HtmlForm::FIELD_HIDDEN));
$form->addInput('son_title', "Song title", $song->getValue('son_title'),
    array('maxLength' => 25, 'property' => HtmlForm::FIELD_REQUIRED));
$form->addInput('son_artist', "Song artist", $song->getValue('son_artist'),
    array('maxLength' => 25));
$form->addInput('son_duration', "Song duration [min]", $song->getValue('son_duration'),
    array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 60, 'step' => 0.5));
$form->addCheckbox('son_is_original', 'Is an original song', (bool) $song->getValue('son_is_original'));

$form->closeGroupBox();

$form->addSubmitButton('btn_save', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL.'/icons/disk.png'));
$form->addHtml(admFuncShowCreateChangeInfoById(
    (int) $song->getValue('son_timestamp_create'), $song->getValue('son_timestamp_create'),
    $song->getValue('son_timestamp_change')
));

// add form to html page and show page
$page->addHtml($form->show(false));
$page->show();
