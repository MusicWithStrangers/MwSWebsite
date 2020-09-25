<?php
/**
 ***********************************************************************************************
 * PM list page
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../system/common.php');
// require_once(__DIR__ . '/payment_function.php');

$userId             = admFuncVariableIsValid($_GET, 'select_usr_id',              'int');
global $gDb;
global $gProfileFields;
        
$userName='';
if (!$userId == null)
{
    $user = new User($gDb, $gProfileFields,$userId);

    $userName=$user->getValue('FIRST_NAME'). ' '.$user->getValue('LAST_NAME');
}

// check for valid login
if (!$gValidLogin)
{
    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    // => EXIT
}

// check if the call of the page was allowed
if (!$gCurrentUser->editFinance())
{
    $gMessage->show("Please log in with a booking-enabled user to edit bookings");
    // => EXIT
}

// Initialize and check the parameters
$getPayId = admFuncVariableIsValid($_GET, 'fee_id', 'int', array('defaultValue' => 0));

function getAdministrationLink($rowIndex, $feeId, $msgSubject, $usrId, $amount, $description, $userName,$hasPayed)
{
    global $gL10n;
    $adminButtons='';
    $adminButtons.= '<a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
                    href="'.safeUrl(ADMIDIO_URL.'/adm_program/system/popup_message.php', array('type' => 'fee', 'element_id' => 'fee_id_' . $feeId,
                    'name' => $msgSubject , 'database_id' => $feeId)) . '">
                    <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
    return $adminButtons;
}

function getAdministrationLinkPersonal($rowIndex, $feeId, $msgSubject, $usrId, $amount, $description, $userName,$hasPayed, $payId)
{
    global $gL10n;
    $adminButtons='';
    if ($hasPayed)
    {
    $adminButtons.= '<a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
            href="'.safeUrl(ADMIDIO_URL.'/adm_program/system/popup_message.php', array('type' => 'pay', 'element_id' => 'pay_id_' . $payId,
            'name' => $msgSubject , 'database_id' => $payId)) . '">
            <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
    }

    if (!$usrId == Null and !$hasPayed)
    {
        $adminButtons.='
        <a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
        href="' .  safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/payment_function.php', array('mode' => 3, 'pay_user' => $usrId, 'fee_id'=>$feeId, 'pay_amount'=>$amount, 'pay_description'=>$description )) . '"
            target="_blank">    
        <img src="' . THEME_URL . '/icons/finance.png" alt="' . 'Admin pay override for ' . $userName  . '" title="' . 'Admin pay override for ' . $userName. '" /> 
        </a>';
    }
    return $adminButtons;
}
function getAdministrationLinkPayOverride($rowIndex, $feeId, $msgSubject, $usrId, $amount, $description, $userName,$hasPayed)
{
    global $gL10n;
    $adminButtons='';
    if (!$usrId == Null and !$hasPayed)
    {
        $adminButtons.='
        <a class="admidio-icon-link" 
        href="' .  safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/payment_function.php', array('mode' => 3, 'pay_user' => $usrId, 'fee_id'=>$feeId, 'pay_amount'=>$amount, 'pay_description'=>$description )) . '"
            target="_blank">    
        <img src="' . THEME_URL . '/icons/finance.png" alt="' . 'Admin pay override for ' . $userName  . '" title="' . 'Admin pay override for ' . $userName. '" /> 
        </a>';
    }
    return $adminButtons;
}

if ($getPayId > 0)
{
    $delContribution = new TableContribution($gDb, $getPayId);

    // Function to delete message
    $delete = $delContribution->delete();
    if ($delete)
    {
        echo 'done';
    }
    else
    {
        echo 'delete not OK';
    }
    exit();
}

// member list
//members:
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

$headline = 'Contribution items';

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, 'Contribution items');

// create html page object
$page = new HtmlPage($headline);
$page->enableModal();

$filterNavbar = new HtmlNavbar('menu_member_filter', null, null, 'User filter');
$form = new HtmlForm('navbar_user_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/contribution.php'), $page, array('type' => 'navbar', 'setFocus' => false));
# public function addSelectBoxFromSql($id, $label, Database $database, $sql, array $options = array())
$form->addSelectBoxFromSql('select_usr_id', 'Member select (for admin pay override)', $gDb, $sqlData, array('search' => true));
$form->addSubmitButton('btn_send', $gL10n->get('SYS_OK'));
$filterNavbar->addForm($form->show(false));
$page->addHtml($filterNavbar->show());

// get module menu for contribution
$contributionMenu = $page->getMenu();


$contributionMenu->addItem(
    'admMenuItemNewPm', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/contribution_new.php'),
    'New contribution item', '/finance.png'
);

// List all contribution items:
$table = new HtmlTable('adm_lists_table', $page, true, true);

$table->setColumnAlignByArray(array('left', 'left', 'left', 'left', 'right'));

$table->addRowHeadingByArray(array(
    'Description',
    'Amount',
    'From',
    'To',
    ''
));
$table->disableDatatablesColumnsSort(array(5));

$usrId = (int) $gCurrentUser->getValue('usr_id');
$rowIndex = 0;

$sql = 'SELECT * FROM mws__contribution_fees';

$allContributionsPdo=  $gDb->queryPrepared($sql );
$contributionCount=$allContributionsPdo->rowCount();

while ($row = $allContributionsPdo->fetch())
{
    ++$rowIndex;
    $description = $row['fee_description'];
    $amount = $row['fee_amount'];
    $from = $row['fee_from'];
    $to = $row['fee_to'];
    $feeId = (int) $row['fee_id'];
    
    $sqlp = 'SELECT pay_user, pay_status fee_id,fee_description,pay_status, fee_amount,fee_from,fee_to FROM mws__contribution_fees INNER JOIN mws__payments ON mws__payments.pay_contribution_id=mws__contribution_fees.fee_id WHERE pay_status=1 and pay_user='.$userId.' and fee_id='.$feeId;
    $payPdo=  $gDb->queryPrepared($sqlp );
    $payCount=$payPdo->rowCount();
    $hasPayed=0;
    if ($payCount>0) $hasPayed=1;
    $table->addRowByArray(
        array(
            $description,
            $amount,
            $from,
            $to,
            getAdministrationLink($rowIndex, $feeId, $description,$userId, $amount, $description, $userName,$hasPayed)
        ),
        'fee_id_'.$feeId
    );
}

# personal contribution table

$tablePersonal = new HtmlTable('adm_lists_table_personal', $page, true, true);

$tablePersonal->setColumnAlignByArray(array('left', 'left', 'left', 'left', 'right'));

$tablePersonal->addRowHeadingByArray(array(
    'Description',
    'Amount',
    'From',
    'To',
    ''
));
$tablePersonal->disableDatatablesColumnsSort(array(5));


// special settings for the table
$tablePersonal->setDatatablesOrderColumns(array(array(4, 'desc')));

$sqlp = 'SELECT pay_user, pay_id, pay_status fee_id,fee_description,pay_status, fee_amount,fee_from,fee_to FROM mws__contribution_fees INNER JOIN mws__payments ON mws__payments.pay_contribution_id=mws__contribution_fees.fee_id WHERE pay_user='.$userId;

$allContributionsPdo=  $gDb->queryPrepared($sqlp );
$contributionCount=$allContributionsPdo->rowCount();

while ($row = $allContributionsPdo->fetch())
{
    ++$rowIndex;
    $has_payed=$row['pay_status'];
    $description = $row['fee_description'];
    $amount = $row['fee_amount'];
    $from = $row['fee_from'];
    $to = $row['fee_to'];
    $feeId = (int) $row['fee_id'];
    $payId=$row['pay_id'];
    if ($has_payed)
    {
        $tablePersonal->addRowByArray(
            array(
                $description,
                $amount,
                $from,
                $to,
                getAdministrationLinkPersonal($rowIndex, $feeId, $description,$userId, $amount, $description, $userName,$has_payed, $payId)
            ),
            'pay_id_'.$payId
        );
    }
}

// List contribution items for admin override pay:
$tableAdminPay = new HtmlTable('adm_listspay_table', $page, true, true);

$tableAdminPay->setColumnAlignByArray(array('left', 'left', 'left', 'left', 'right'));

$tableAdminPay->addRowHeadingByArray(array(
    'Description',
    'Amount',
    'From',
    'To',
    ''
));
$tableAdminPay->disableDatatablesColumnsSort(array(5));

$usrId = (int) $gCurrentUser->getValue('usr_id');
$rowIndex = 0;

$sql = 'SELECT * FROM mws__contribution_fees';

$allContributionsPdo=  $gDb->queryPrepared($sql );
$contributionCount=$allContributionsPdo->rowCount();

while ($row = $allContributionsPdo->fetch())
{
    ++$rowIndex;
    $description = $row['fee_description'];
    $amount = $row['fee_amount'];
    $from = $row['fee_from'];
    $to = $row['fee_to'];
    $feeId = (int) $row['fee_id'];
    
    $sqlp = 'SELECT pay_user, pay_id, pay_status fee_id,fee_description,pay_status, fee_amount,fee_from,fee_to FROM mws__contribution_fees INNER JOIN mws__payments ON mws__payments.pay_contribution_id=mws__contribution_fees.fee_id WHERE pay_status=1 and pay_user='.$userId.' and fee_id='.$feeId;
    $payPdo=  $gDb->queryPrepared($sqlp );
    $payCount=$payPdo->rowCount();
    $hasPayed=0;
    if ($payCount>0) $hasPayed=1;
    if (!$hasPayed)
    {
        $tableAdminPay->addRowByArray(
            array(
                $description,
                $amount,
                $from,
                $to,
                getAdministrationLinkPayOverride($rowIndex, $feeId, $description,$userId, $amount, $description, $userName,$hasPayed)
            ),
            'fee_id_'.$feeId
        );
    }

}

// add table to the form
if (!$userId == Null)
{
    //$page->addHtml('<div>Click euro icon in table below for administration override on payment status for '.$userName.'</div>');
} else {
    $page->addHtml('<div>Select a member for info on user-specific contribution payments.</div>');
}
$page->addHtml('<h3>All existing contribution items:</h2>');
$page->addHtml($table->show());
if (!$userId == Null)
{
    $page->addHtml('<h3>Payments made for '.$userName.' : </h2>');
    $page->addHtml($tablePersonal->show());
    $page->addHtml('<h3>Manually set payment status to payed for '.$userName.' : </h2>');
    $page->addHtml($tableAdminPay->show());
}


// add form to html page and show page
$page->show();
