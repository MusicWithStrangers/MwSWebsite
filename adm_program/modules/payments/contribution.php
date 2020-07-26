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

function getAdministrationLink($rowIndex, $payId, $msgSubject)
{
    global $gL10n;

    return '
        <a class="admidio-icon-link" data-toggle="modal" data-target="#admidio_modal"
            href="' . safeUrl(ADMIDIO_URL . '/adm_program/system/popup_message.php', array('type' => 'fee', 'element_id' => 'row_message_' . $rowIndex, 'name' => $msgSubject, 'database_id' => $payId)) . '">
            <img src="' . THEME_URL . '/icons/delete.png" alt="' . 'Remove contribution item' . '" title="' . 'Remove contribution?' . '" />
        </a>';
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

$headline = 'Contribution items';

// add current url to navigation stack
$gNavigation->clear();
$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage($headline);
$page->enableModal();

// get module menu for contribution
$contributionMenu = $page->getMenu();


$contributionMenu->addItem(
    'admMenuItemNewPm', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/contribution_new.php'),
    'New contribution item', '/finance.png'
);

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

// open some additional functions for contribution
$usrId = (int) $gCurrentUser->getValue('usr_id');
$rowIndex = 0;

global $gDb;

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

    $table->addRowByArray(
        array(
            $description,
            $amount,
            $from,
            $to,
            getAdministrationLink($rowIndex, $feeId, $description)
        ),
        'row_message_'.$rowIndex
    );
}

// special settings for the table
$table->setDatatablesOrderColumns(array(array(4, 'desc')));

// add table to the form
$page->addHtml($table->show());

// add form to html page and show page
$page->show();
