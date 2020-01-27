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
$getSnrId             = admFuncVariableIsValid($_GET, 'snr_id',              'int');
$getFullScreen        = admFuncVariableIsValid($_GET, 'full_screen',         'bool');
$songRegister         = new TableBandSongRegister($gDb);
$songRegister->readDataById($getSnrId);
$date                 = new TableDate($gDb);
$dateId               = $songRegister->getValue('snr_dat_id');
$song                 = new TableSong($gDb);
$song -> readDataById($songRegister->getValue('snr_son_id'));
$songTitle=$song->getValue('son_title');
$date->readDataById($dateId);
$eventName=$date->getValue('dat_headline');
// determine all roles relevant data
$htmlSubHeadline = '';

$gCurrentUserId=$gCurrentUser->getValue('usr_id');
$usrName=$gCurrentUser->getValue('FIRST_NAME') . ' ' . $gCurrentUser->getValue('LAST_NAME');

$htmlRegistered=new HtmlTable('registered_table', null,true,true);
$htmlRegistered->addRowHeadingByArray(array('', 'Musician', 'Instrument'));

$sql = 'SELECT u.usr_id, b.bnd_name, s.son_title, r.smr_snr_id , r.smr_id, i.ins_name, r.smr_id, GROUP_CONCAT(d.usd_value order by d.usd_usf_id DESC SEPARATOR \' \') as \'name\'
    FROM mws__users as u
        inner join mws__user_data as d on d.usd_usr_id = u.usr_id 
        inner join mws__song_musicianregistration r ON r.smr_usr_id=u.usr_id
        inner join mws__instruments i on r.smr_ins_id=i.ins_id
        inner join mws__song_registration sr on r.smr_snr_id=sr.snr_id
        inner join mws__songs s on s.son_id = sr.snr_son_id
        inner join mws__bands b on b.bnd_id = sr.snr_bnd_id
     WHERE r.smr_snr_id = ? -- $$getSnrId    
     AND d.usd_usf_id IN (1,2)
     GROUP BY r.smr_id';
$queryParams = array($getSnrId);
$musiciansStatement = $gDb->queryPrepared($sql, $queryParams);
if ($musiciansStatement->rowCount()>0)
{
    $musiciansData      = $musiciansStatement->fetchAll();
    foreach ($musiciansData as $aMusician)
    {
        $editDeleteRegister = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('son_id' => $aMusician['smr_id'], 'mode' => 12)).'">'.
            '<img src="'.THEME_URL.'/icons/delete.png" alt="delete musician from song" title="delete musician from song" /></a>';
        $htmlRegistered->addRowByArray(array($editDeleteRegister,$aMusician['name'] , $aMusician['ins_name']));
    }
}
// define title (html) and headline
$headline = 'Song registration for ' . $eventName;
$title = 'Songs:';
$gNavigation->addUrl(CURRENT_URL);

$datatable = true;
$hoverRows = true;

// create html page object
$page = new HtmlPage();
$page->enableModal();   

$page->setTitle($title);
$page->setHeadline($headline);

$page->addHtml('<h5>'.$htmlSubHeadline.'</h5>');

// get module menu
$listsMenu = $page->getMenu();

$listsMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// link to print overlay and exports
//$listsMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');

// create html page object
//$pagef = new HtmlPage($headline);

//$pagef->addJavascriptFile(ADMIDIO_URL . '/adm_program/system/js/date-functions.js');
$sqlInstruments = 'Select ins_id, ins_name from mws__instruments';
$sqlData = array();
$sqlData['query'] = 'SELECT usr_id, CONCAT(last_name.usd_value, \' \', first_name.usd_value) AS name
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
                      WHERE rol_id IN ('.replaceValuesArrWithQM($gCurrentUser->getAllVisibleRoles()).')
                        AND rol_valid   = 1
                        AND cat_name_intern <> \'EVENTS\'
                        AND ( cat_org_id = ? -- $gCurrentOrganization->getValue(\'org_id\')
                            OR cat_org_id IS NULL )
                        AND mem_begin <= ? -- DATE_NOW
                        AND mem_end   >= ? -- DATE_NOW
                        AND usr_valid  = 1
                   ORDER BY last_name.usd_value, first_name.usd_value, usr_id';
$sqlData['params'] = array_merge(
    array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id')
    ),
    $gCurrentUser->getAllVisibleRoles(),
    array(
        $gCurrentOrganization->getValue('org_id'),
        DATE_NOW,
        DATE_NOW
    )
);
    

// add back link to module menu
$bandMenu = $page->getMenu();
$bandMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
$mode=12; 
$form1 = new HtmlForm('song_registeredit', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/songs/songs_function.php', array('type'=>'band', 'mode' => $mode, 'smr_snr_id' =>$getSnrId)), $page);
$form1->openGroupBox('gb_myRegistrations', 'Musicians registered for song \'' . $songTitle . '\'');
$form1->addHtml($htmlRegistered->show());
$form1->addInput('smr_snr_id','Register Id',$getSnrId, array('property' => HtmlForm::FIELD_HIDDEN));
$form1->closeGroupBox();
$form1->openGroupBox('gb_newRegistrations','Register new musicisans');

$form1->addSelectBoxFromSql('smr_usr_id', 'Musician', $gDb, $sqlData,
    array('property' => HtmlForm::FIELD_REQUIRED, 'search' => true));
$form1->addSelectBoxFromSql('smr_ins_id', 'Instrument', $gDb, $sqlInstruments,
    array('property' => HtmlForm::FIELD_REQUIRED, 'search' => true));
$form1->addSubmitButton('btn_send', 'Add musician');
$form1->closeGroupBox();

$page->addHtml($form1->show(false));

// show complete html page
$page->show();
