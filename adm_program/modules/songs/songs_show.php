<?php
/**
 ***********************************************************************************************
 * Show role members list
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode:            Output(html, print, csv-ms, csv-oo, pdf, pdfl)
 * date_from:       Value for the start date of the date range filter (default: current date)
 * date_to:         Value for the end date of the date range filter (default: current date)
 * lst_id:          Id of the list configuration that should be shown.
 *                  If id is null then the default list of the role will be shown.
 * rol_id:          Id of the role whose members should be shown
 * show_former_members: 0 - (Default) show members of role that are active within the selected date range
 *                      1 - show only former members of the role
 * full_screen:     false - (Default) show sidebar, head and page bottom of html page
 *                  true  - Only show the list without any other html unnecessary elements
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../system/common.php');

// Initialize and check the parameters
$getMode              = admFuncVariableIsValid($_GET, 'mode',                'string', array('defaultValue' => 'html', 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl')));
$getDateId            = admFuncVariableIsValid($_GET, 'dat_id',              'int');
$getFullScreen        = admFuncVariableIsValid($_GET, 'full_screen',         'bool');
$date                 = new TableDate($gDb);

// determine all roles relevant data
$roleName        = $gL10n->get('LST_VARIOUS_ROLES');
$htmlSubHeadline = '';
$showLinkMailToList = true;
$date->readDataById($getDateId);
$dateMaxSongs=$date->getValue('dat_maxSlotSongs');
$dateMaxSongParticipate=$date->getValue('dat_maxSlotParticipate');
$dateMaxSlotDuration=$date->getValue('dat_maxSlotDuration');
$gCurrentUserId=$gCurrentUser->getValue('usr_id');
$usrName=$gCurrentUser->getValue('FIRST_NAME') . ' ' . $gCurrentUser->getValue('LAST_NAME');
$eventName=$date->getValue('dat_headline');
$countSongOwner=0;
$countBandOwner=0;                // TODO: parametrize max bands
$countSongParticipate=0;
$countBandParticipate=0;
$countAllBands=0;
$countAllSongs=0;
$MaxSongParticipate=100;         // TODO: parametrize max song participate
$dateMaxTotalBands=100;
$dateMaxTotalSongs=100;
$dateMaxBandOwner=1;
$outputButtonNewBand='';
$outputButtonNewSongs='';
$songsUser          = array();
$songsAll           = array();
$bandsUser          = array();
$bandsAll           = array();
$songsUserUnregistered = array();
$bandsUserUnregistered = array();

//Unregistered bands from this user
$sql='select band.* from mws__bands band where band.bnd_usr_id= ? -- $gCurrentUserId 
         AND not exists ( select 1 from mws__song_registration regist where regist.snr_bnd_id = band.bnd_id)';
$queryParams = array($gCurrentUserId);
$unregisteredBands=$gDb->queryPrepared($sql, $queryParams);
$countUnregisteredBands=$unregisteredBands->rowCount();
if ($countUnregisteredBands > 0)
{
    // get unregistered bands from this user
   $unregisteredBandData      = $unregisteredBands->fetchAll();
    foreach ($unregisteredBandData as $aBand)
    {
        $bandsUserUnregistered[$aBand['bnd_id']] = $aBand['bnd_name'];
    }
}

//unregistered songs from this user
$sql='select song.* from mws__songs song where song.son_usr_id= ? -- $gCurrentUserId 
         AND not exists ( select 1 from mws__song_registration regist where regist.snr_son_id = song.son_id)';
$queryParams = array($gCurrentUserId);
$unregisteredSongs=$gDb->queryPrepared($sql, $queryParams);
$countUnregisteredSongs=$unregisteredSongs->rowCount();
if ($countUnregisteredSongs > 0)
{
    // get ungregistered songs from this user
    $songsDataUser      = $unregisteredSongs->fetchAll();
    foreach ($songsDataUser as $aSong)
    {
        $songsUserUnregistered[$aSong['son_id']] = $aSong['son_title'];
    }
}


// Registered songs to this event from this user
$sql = 'SELECT mws__songs.son_id, mws__bands.bnd_name, mws__bands.bnd_id, mws__songs.son_title, mws__song_registration.snr_id FROM mws__song_registration 
            INNER JOIN mws__songs ON mws__song_registration.snr_son_id = mws__songs.son_id
            INNER JOIN mws__bands ON mws__song_registration.snr_bnd_id = mws__bands.bnd_id
            WHERE mws__song_registration.snr_usr_id = ? -- $gCurrentUserId 
            AND mws__song_registration.snr_dat_id = ? -- $getDateId 
            ORDER BY mws__song_registration.snr_bnd_id';
$queryParams = array($gCurrentUserId, $getDateId);
$userSongsStatement = $gDb->queryPrepared($sql, $queryParams);
$countSongParticipate=$userSongsStatement->rowCount();
$htmlRegistered=new HtmlTable('registered_table', null,true,true);

if ($countSongParticipate > 0)
{
    // get registered songs
    $songsDataUser      = $userSongsStatement->fetchAll();
    $htmlRegistered->addRowHeadingByArray(array('', 'Band', 'Song', 'Musicians'));
    foreach ($songsDataUser as $aSong)
    {
        $songsUser[$aSong['son_id']] = $aSong['son_title'];
        $editUnregisterSong='<a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
                        href="'.safeUrl(ADMIDIO_URL.'/adm_program/system/popup_message.php', array('type' => 'snr', 'element_id' => 'snr_' . $aSong['snr_id'],
                        'name' => $aSong['son_title'], 'database_id' => $aSong['snr_id'])) . '">
                        <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
                            

        
        //$editUnregisterSong = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('snr_id' => $aSong['snr_id'], 'mode' => 9)).'">'.
        //        '<img src="'.THEME_URL.'/icons/delete.png" alt="unregister song" title="unregister song" /></a>';
        $editDeleteSong = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('son_id' => $aSong['snr_id'], 'mode' => 2)).'">'.
                '<img src="'.THEME_URL.'/icons/delete.png" alt="delete song" title="delete song" /></a>';
        $editDeleteBand = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('bnd_id' => $aSong['bnd_id'], 'mode' => 7)).'">'.
        '<img src="'.THEME_URL.'/icons/delete.png" alt="delete band" title="delete band" /></a>';
        $editEditSong = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_newSong.php', array('son_id' => $aSong['son_id'], 'mode' => 5)).'">'.
                '<img src="'.THEME_URL.'/icons/edit.png" alt="edit song" title="edit song" /></a>';
        $editEditBand = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_newBand.php', array('bnd_id' => $aSong['bnd_id'], 'mode' => 10)).'">'.
        '<img src="'.THEME_URL.'/icons/edit.png" alt="edit band" title="edit band" /></a>';
        $editEditMusicians = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_newMusicians.php', array('snr_id' => $aSong['snr_id'], 'mode' => 5)).'">'.
                '<img src="'.THEME_URL.'/icons/edit.png" alt="edit musicians" title="edit musicians" /></a>';
        
        $sql = 'SELECT u.usr_id, b.bnd_name, s.son_title, r.smr_snr_id , r.smr_id, i.ins_name, r.smr_id, GROUP_CONCAT(d.usd_value order by d.usd_usf_id DESC SEPARATOR \' \') as \'name\'
        FROM mws__users as u
            inner join mws__user_data as d on d.usd_usr_id = u.usr_id 
            inner join mws__song_musicianregistration r ON r.smr_usr_id=u.usr_id
            inner join mws__instruments i on r.smr_ins_id=i.ins_id
            inner join mws__song_registration sr on r.smr_snr_id=sr.snr_id
            inner join mws__songs s on s.son_id = sr.snr_son_id
            inner join mws__bands b on b.bnd_id = sr.snr_bnd_id
         WHERE r.smr_snr_id = ? -- $getsnr_id    
         AND d.usd_usf_id IN (1,2)
         GROUP BY r.smr_id';
        $getsnr_id=$aSong['snr_id'];
        $queryParams = array($getsnr_id);
        $musiciansStatement = $gDb->queryPrepared($sql, $queryParams);
        $musicianshtml=' ';
        if ($musiciansStatement->rowCount()>0)
        {
            $musiciansData      = $musiciansStatement->fetchAll();
            foreach ($musiciansData as $aMusician)
            {
                $musicianshtml.=  '<small>' . $aMusician['name'] . ' [' . $aMusician['ins_name'] . '], </small>';
            }
        }
        $htmlRegistered->addRowByArray(array($editUnregisterSong,$aSong['bnd_name'] . $editEditBand,$aSong['son_title'] . $editEditSong, $musicianshtml. $editEditMusicians),'snr_'.$getsnr_id);
    }
}
//All registered bands from user to this event
$sql = 'SELECT mws__bands.bnd_id, mws__bands.bnd_name FROM mws__song_registration 
         INNER JOIN mws__bands ON mws__song_registration.snr_bnd_id = mws__bands.bnd_id
         WHERE mws__song_registration.snr_usr_id = ? -- $gCurrentUserId 
         AND mws__song_registration.snr_dat_id = ? -- $getDateId
         GROUP BY snr_bnd_id ORDER BY snr_bnd_id';
$queryParams = array($gCurrentUserId, $getDateId);
$userBandStatement = $gDb->queryPrepared($sql, $queryParams);
$countBandParticipate=$userBandStatement->rowCount();
if ($countBandParticipate >0)
{
    $bandsData= $userBandStatement->fetchAll();
    foreach ($bandsData as $aBand)
    {
        $bandsUser[$aBand['bnd_id']] = $aBand['bnd_name'];
    }
}

// All songs registered to other events
$sql = 'SELECT mws__songs.son_id, mws__songs.son_title FROM mws__song_registration
        INNER JOIN mws__songs ON mws__song_registration.snr_son_id = mws__songs.son_id
        WHERE mws__song_registration.snr_usr_id = ? -- $gCurrentUserId 
        AND mws__song_registration.snr_dat_id <> ? -- $getDateId
        ORDER BY mws__song_registration.snr_usr_id';
$queryParams = array($gCurrentUserId, $getDateId);
$allSongsStatement = $gDb->queryPrepared($sql, $queryParams);
$countAllSongs=$allSongsStatement->rowCount();
$songsNotHereYet=$songsUserUnregistered;
if ($countAllSongs > 0)
{
    // get registered songs
    $songsData      = $allSongsStatement->fetchAll();  
    foreach ($songsData as $aSong)
    {
        $songsAll[$aSong['son_id']] = $aSong['son_title'];
        $songsNotHereYet[$aSong['son_id']] = $aSong['son_title'];
    }
}
//All registered bands to other events from this user
$sql = 'SELECT mws__bands.bnd_id, mws__bands.bnd_name FROM mws__bands 
         WHERE mws__bands.bnd_usr_id = ? -- $gCurrentUserId';
$queryParams = array($gCurrentUserId);
$allBandStatement = $gDb->queryPrepared($sql, $queryParams);
$countAllBands=$allBandStatement->rowCount();
if ($countAllBands >0)
{
    $bandsData= $allBandStatement->fetchAll();
    foreach ($bandsData as $aBand)
    {
        $bandsAll[$aBand['bnd_id']] = $aBand['bnd_name'];
    }
}

//$songsNotHereYet=array_merge($songsAll,$songsUserUnregistered);
if ($countBandParticipate < $dateMaxBandOwner || $countAllBands<$dateMaxTotalBands)
{
    //Show new band button'
    $newBandURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_newBand.php', array('mode' => 'html', 'newband' => 1));
    $outputButtonNewBand  = '<button class="btn btn-default" onclick="window.location.href=\'' . $newBandURL . '\'"><img src="' . THEME_URL . '/icons/user_administration.png" alt="Register Songs" />' . 'Create new Band' . '</button>';
}
if ($countSongParticipate < $dateMaxSongs || $countAllSongs<$dateMaxTotalSongs)
{
    //Show new song button'
    $newSongURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_newSong.php', array('mode' => 'html', 'newsong' => 1));
    $outputButtonNewSong  = '<button class="btn btn-default" onclick="window.location.href=\'' . $newSongURL . '\'"><img src="' . THEME_URL . '/icons/user_administration.png" alt="Register Songs" />' . 'Create new Song' . '</button>';
}

        // check if user has right to view all roles
        // only users with the right to assign roles can view inactive roles
//        if (!$gCurrentUser->hasRightViewRole($roleId)
//        || ((int) $role['rol_valid'] === 0 && !$gCurrentUser->checkRolesRight('rol_assign_roles')))
//        {
//            $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
//            // => EXIT
//        }


//$htmlSubHeadline ="jaja";

// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$classTable  = '';
$orientation = '';

switch ($getMode)
{
    case 'csv-ms':
        $separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
        $valueQuotes = '"';  // all values should be set with quotes
        $getMode     = 'csv';
        $charset     = 'iso-8859-1';
        break;
    case 'csv-oo':
        $separator   = ',';  // a CSV file should have a comma
        $valueQuotes = '"';  // all values should be set with quotes
        $getMode     = 'csv';
        $charset     = 'utf-8';
        break;
    case 'pdf':
        $classTable  = 'table';
        $orientation = 'P';
        $getMode     = 'pdf';
        break;
    case 'pdfl':
        $classTable  = 'table';
        $orientation = 'L';
        $getMode     = 'pdf';
        break;
    case 'html':
        $classTable  = 'table table-condensed';
        break;
    case 'print':
        $classTable  = 'table table-condensed table-striped';
        break;
    default:
        break;
}

// Array to assign names to tables
$arrColName = array(
    'usr_login_name'       => $gL10n->get('SYS_USERNAME'),
    'usr_photo'            => $gL10n->get('PHO_PHOTO'),
    'mem_begin'            => $gL10n->get('SYS_START'),
    'mem_end'              => $gL10n->get('SYS_END'),
    'mem_leader'           => $gL10n->get('SYS_LEADERS'),
    'mem_approved'         => $gL10n->get('LST_PARTICIPATION_STATUS'),
    'mem_usr_id_change'    => $gL10n->get('LST_USER_CHANGED'),
    'mem_timestamp_change' => $gL10n->get('SYS_CHANGED_AT'),
    'mem_comment'          => $gL10n->get('SYS_COMMENT'),
    'mem_count_guests'     => $gL10n->get('LST_SEAT_AMOUNT')
);

// Array for valid columns visible for current user.
// Needed for PDF export to set the correct colspan for the layout
// Maybe there are hidden fields.
$arrValidColumns = array();

//$mainSql = ''; // Main SQL statement for lists
//$csvStr = ''; // CSV file as string
//try
//{
//    // create list configuration object and create a sql statement out of it
//    $list = new ListConfiguration($gDb, $getListId);
//    $mainSql = $list->getSQL($roleIds, $getShowFormerMembers, $startDateEnglishFormat, $endDateEnglishFormat, $relationTypeIds);
//}
//catch (AdmException $e)
//{
//    $e->showHtml();
//}
// determine the number of users in this list
//$listStatement = $gDb->query($mainSql); // TODO add more params
//$numMembers = $listStatement->rowCount();

// get all members and their data of this list in an array
//$membersList = $listStatement->fetchAll(\PDO::FETCH_BOTH);

//$userIdList = array();
//foreach ($membersList as $member)
//{
//    $user = new User($gDb, $gProfileFields, $member['usr_id']);
//
//    // besitzt der User eine gueltige E-Mail-Adresse? && aktuellen User ausschließen
//    if (strValidCharacters($user->getValue('EMAIL'), 'email') && (int) $gCurrentUser->getValue('usr_id') !== (int) $member['usr_id'])
//    {
//        $userIdList[] = $member['usr_id'];
//    }
//}

// define title (html) and headline
$headline = 'Song registration for ' . $eventName;
$title = 'Songs:';

// if html mode and last url was not a list view then save this url to navigation stack
if ($getMode === 'html' && !admStrContains($gNavigation->getUrl(), 'songs_show.php'))
{
    $gNavigation->addUrl(CURRENT_URL);
}

if ($getMode !== 'csv')
{
    $datatable = false;
    $hoverRows = false;

    if ($getMode !== 'html')
    {
        if ($getShowFormerMembers === 1)
        {
            $htmlSubHeadline .= ' - '.$gL10n->get('LST_FORMER_MEMBERS');
        }
        else
        {
            if ($getDateFrom === DATE_NOW && $getDateTo === DATE_NOW)
            {
                $htmlSubHeadline .= ' - '.$gL10n->get('LST_ACTIVE_MEMBERS');
            }
            else
            {
                $htmlSubHeadline .= ' - '.$gL10n->get('LST_MEMBERS_BETWEEN_PERIOD', array($dateFrom, $dateTo));
            }
        }
    }


if ($getMode === 'html')
    {
        $datatable = true;
        $hoverRows = true;

        // create html page object
        $page = new HtmlPage();
        $page->enableModal();   

        if ($getFullScreen)
        {
            $page->hideThemeHtml();
        }

        $page->setTitle($title);
        $page->setHeadline($headline);

        // Only for active members of a role and if user has right to view former members
        if (false)
        {
            // create filter menu with elements for start-/enddate
            $filterNavbar = new HtmlNavbar('menu_list_filter', null, null, 'filter');
            $form = new HtmlForm('navbar_filter_form', ADMIDIO_URL.FOLDER_MODULES.'/lists/lists_show.php', $page, array('type' => 'navbar', 'setFocus' => false));
            $form->addSubmitButton('btn_send', $gL10n->get('SYS_OK'));
            $filterNavbar->addForm($form->show(false));
            $page->addHtml($filterNavbar->show());
        }

        $page->addHtml('<h5>'.$htmlSubHeadline.'</h5>');

        // get module menu
        $listsMenu = $page->getMenu();

        $listsMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

        // link to print overlay and exports
        //$listsMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');

        // create html page object
        $pagef = new HtmlPage($headline);

        $pagef->addJavascriptFile(ADMIDIO_URL . '/adm_program/system/js/date-functions.js');

        // add back link to module menu
        $bandMenu = $page->getMenu();
        $bandMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
        $mode=11;
        $form1 = new HtmlForm('song_registeredit', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('type'=>'band', 'mode' => $mode, 'dat_id' => $getDateId)), $pagef);
        $form1->openGroupBox('gb_myRegistrations', 'Songs registered by ' . $usrName);
        $form1->addHtml($htmlRegistered->show());
        $form1->addInput('date_id','Event Id',$getDateId, array('property' => HtmlForm::FIELD_HIDDEN));
        $form1->closeGroupBox();
        $mode=8;
        $form2 = new HtmlForm('song_register', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('type'=>'band', 'mode' => $mode)), $pagef);
        $form2->openGroupBox('gb_myUnRegistrations', 'Register additional songs');
        $form2->addSelectBox('snr_bnd_id', 'Band title', $bandsAll, array('property' => HtmlForm::FIELD_REQUIRED));
        $form2->addSelectBox('snr_son_id', 'Song title', $songsNotHereYet, array('property' => HtmlForm::FIELD_REQUIRED));
        $form2->addInput('snr_dat_id','Event Id',$getDateId, array('property' => HtmlForm::FIELD_HIDDEN));
        $form2->addInput('snr_usr_id','User Id',$gCurrentUserId, array('property' => HtmlForm::FIELD_HIDDEN));
        
        $form2->addSubmitButton('btn_send_new', 'Register');
        $form2->closeGroupBox();
        
        $form3 = new HtmlForm('song_register', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('type'=>'band', 'mode' => $mode)), $pagef);
        $form3->openGroupBox('gb_myUnRegistrations', 'Songs to participate');
        $form3->closeGroupBox();
        $page->addHtml($form1->show(false));
        $page->addHtml($form2->show(false));
        $page->addHTML('<div class="btn-group">' .$outputButtonNewBand . $outputButtonNewSong . '</div>');
        $page->addHtml($form3->show(false));
        $table = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
        $table->setDatatablesRowsPerPage($gSettingsManager->getInt('lists_members_per_page'));
    }
    else
    {
        $table1 = new HtmlTable('adm_lists_table', $page, $hoverRows, $datatable, $classTable);
    }
}

// initialize array parameters for table and set the first column for the counter
if ($getMode === 'html')
{
    // in html mode we group leaders. Therefore we need a special hidden column.
    $columnAlign  = array('left', 'left');
    $columnValues = array($gL10n->get('SYS_ABR_NO'), $gL10n->get('INS_GROUPS'));
}
else
{
    $columnAlign  = array('left');
    $columnValues = array($gL10n->get('SYS_ABR_NO'));
}



// headlines for columns


if ($getMode === 'html' || $getMode === 'print')
{
    //$table->setColumnAlignByArray($columnAlign);
    //$table->addRowHeadingByArray($columnValues);
}
else
{
    $table->addTableBody();
}

$lastGroupHead = null; // Mark for change between leader and member
$listRowNumber = 1;


$filename = '';

if ($getMode === 'html' || $getMode === 'print')
{
    // add table list to the page
    
    $page->addHtml($table->show());
    

    // show complete html page
    $page->show();
}
