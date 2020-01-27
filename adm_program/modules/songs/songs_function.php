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
 * mode   : 1 - Create a new song
 *          2 - Delete the song
 *          3 - User added to the song
 *          4 - User removed a song
 *          5 - Edit an existing song
 *          6 - Create a new band
 *          7 - Delete a band
 *          8 - Register new song/band to event
 *          9 - Remove band/song from event
 *          10 - Edit an existing band
 *          11 - Edit band/song/event
 *          12 - Register new musician to a registered song
 *          13 - Remove musician from a song
 * 
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
$getDateId              = admFuncVariableIsValid($_GET, 'dat_id', 'int');
$getMode                = admFuncVariableIsValid($_GET, 'mode',   'int', array('requireValue' => true));
$getSongId              = admFuncVariableIsValid($_GET, 'son_id', 'int');
$getBandId              = admFuncVariableIsValid($_GET, 'bnd_id', 'int');
$getCopy                = admFuncVariableIsValid($_GET, 'copy',   'bool');
$getNumberRoleSelect    = admFuncVariableIsValid($_GET, 'number_role_select', 'int');
$getUserId              = admFuncVariableIsValid($_GET, 'usr_id', 'int', array('defaultValue' => $gCurrentUser->getValue('usr_id')));
$getSongRegistId        =admFuncVariableIsValid($_GET, 'snr_id', 'int');
$getMusicianRegistId    =admFuncVariableIsValid($_POST, 'smr_id', 'int');

$participationPossible  = true;
$editDateAllows         =false;
if (count($gCurrentUser->getAllEditableCategories('DAT')) > 0)
{
        $editDateAllows=true;
}

// Alle Funktionen, ausser Exportieren und anmelden, duerfen nur eingeloggte User
require(__DIR__ . '/../../system/login_valid.php');

if($getCopy)
{
    $originalDateId = $getDateId;
    $getDateId      = 0;
}

// create event object
if ($getMode<6)
{
    $song = new TableSong($gDb);
    $song->readDataById($getSongId);
} elseif ($getMode<12) {
    $band = new TableBand($gDb);
    $band->readDataById($getBandId);
}
/**
 * mode   : 1 - Create a new song          Allowed for band owner and events manager (rol_dates role)
 *          2 - Delete the song            Allowed for band owner and events manager
 *          3 - User added to the song     Allowed for band owner and events manager
 *          4 - User removed a song        Allowed for band owner and events manager
 *          5 - Edit an existing song      Allowed for band owner and events manager
 *          6 - Create a new band          Allowed for all rol_bookandregister when event accepts song registration
 *          7 - Delete a band              Allowed for band owner and events manager
 *          8 - Register band to event     Allowed for band owner and events manager
 *          9 - Remove band from event     Allowed for band owner and events manager
 *          10 - Edit an existing band     Allowed for band owner and events manager
 */

// SONG
if (in_array($getMode, array(1, 2, 5), true))
{
    if ($getSongId > 0)
    {
        // check if the current user has the right to edit this song
        if (!$song->isEditable())
        {
            $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
            // => EXIT
        }
    }
    else
    {
        // check if the user has the right to edit at least one category
        //if (count($gCurrentUser->getAllEditableCategories('BAR')) === 0)
        //{
        //    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
        //    // => EXIT
        //}
    }
}

if($getMode === 1 || $getMode === 5)  // Create a new song or edit an existing song
{

    // ------------------------------------------------
    // check if all necessary fields are filled
    // ------------------------------------------------

    if(strlen($_POST['son_title']) === 0)
    {
        $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_TITLE'))));
        // => EXIT
    }
    
    // check if the current user is allowed to use the selected category
    //if(!in_array((int) $_POST['dat_cat_id'], $gCurrentUser->getAllEditableCategories('DAT'), true))
    //{
    //    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    //    // => EXIT
    //}

    if(!isset($_POST['son_is_original']))
    {
        $_POST['son_is_original'] = 0;
    }

    // make html in description secure
    $_POST['bnd_description'] = admFuncVariableIsValid($_POST, 'bnd_description', 'html');

    // ------------------------------------------------
    // Prüfen ob gewaehlter Raum bereits zu dem Termin reserviert ist
    // ------------------------------------------------

    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'son_'))
            {
                $song->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gDb->startTransaction();

    // save event in database
    $returnCode = $song->save();

    $sonId = (int) $song->getValue('son_id');

    $gDb->endTransaction();

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT
}
elseif($getMode === 2)
{
    // Delete registrations with song
    // 
    // delete current announcements, right checks were done before
    $song->delete();

    // Delete successful -> Return for XMLHttpRequest
    echo 'done';
}

// BAND

if (in_array($getMode, array(6, 7, 10), true))
{
    if ($getBandId > 0)
    {
        // check if the current user has the right to edit this song
        if (!$band->isEditable())
        {
            $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
            // => EXIT
        }
    }
    else
    {
        // check if the user has the right to edit at least one category
        //if (count($gCurrentUser->getAllEditableCategories('BAR')) === 0)
        //{
        //    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
        //    // => EXIT
        //}
    }
}

if($getMode === 6 || $getMode === 10)  // Create a new song or edit an existing song
{

    // ------------------------------------------------
    // check if all necessary fields are filled
    // ------------------------------------------------

    if(strlen($_POST['bnd_name']) === 0)
    {
        $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_TITLE'))));
        // => EXIT
    }
    
    // check if the current user is allowed to use the selected category
    //if(!in_array((int) $_POST['dat_cat_id'], $gCurrentUser->getAllEditableCategories('DAT'), true))
    //{
    //    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    //    // => EXIT
    //}

    // make html in description secure
    $_POST['bnd_description'] = admFuncVariableIsValid($_POST, 'bnd_description', 'html');

    // ------------------------------------------------
    // Prüfen ob gewaehlter Raum bereits zu dem Termin reserviert ist
    // ------------------------------------------------

    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'bnd_'))
            {
                $band->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gDb->startTransaction();

    // save event in database
    $returnCode = $band->save();

    $bndId = (int) $band->getValue('bnd_id');

    $gDb->endTransaction();

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT
}
elseif($getMode === 7)
{
    // delete current announcements, right checks were done before
    $band->delete();

    // Delete successful -> Return for XMLHttpRequest
    echo 'done';
} elseif ($getMode === 8)
{
    $regist = new TableBandSongRegister($gDb);
    $regist->readDataById($getSongRegistId);
    try
    {
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'snr_'))
            {
                $regist->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gDb->startTransaction();

    // save event in database
    $returnCode = $regist->save();

    $snrId = (int) $regist->getValue('snr_id');

    $gDb->endTransaction();

    //$gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT

} elseif ($getMode === 9)
{
    // delete song from event
    $sql='Select * from mws__song_musicianregistration where smr_snr_id = ? -- $getSongRegistId';
    $queryParams = array($getSongRegistId);
    $musiciansReg = $gDb->queryPrepared($sql, $queryParams);
    $musiciansregister = new TableMusicianRegister($gDb);
    if ($musiciansReg->rowCount()>0)
    {
        $musiciansData      = $musiciansReg->fetchAll();
        foreach ($musiciansData as $aMusician)
        {
            $smrId=$aMusician['smr_id'];
            $musiciansregister->readDataById($smrId);
            $musiciansregister->delete();
        }
    }
    $songsregister = new TableBandSongRegister($gDb);
    $songsregister->readDataById($getSongRegistId);
    $songsregister->delete();
    echo 'done';
} elseif ($getMode === 12)
{
    $regist = new TableMusicianRegister($gDb);
    $regist->readDataById($getMusicianRegistId);
    try
    {
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'smr_'))
            {
                $regist->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }

    $gDb->startTransaction();

    // save event in database
    $returnCode = $regist->save();

    $smrId = (int) $regist->getValue('smr_id');

    $gDb->endTransaction();

    //$gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
    // => EXIT
}


