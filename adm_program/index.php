<?php
/**
 ***********************************************************************************************
 * List of all modules and administration pages of Admidio
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

// if config file doesn't exists, than show installation dialog
global $gValidLogin, $gDb, $gCurrentOrganization, $gCurrentUser;

if (!is_file(dirname(__DIR__) . '/adm_my_files/config.php'))
{
    header('Location: installation/index.php');
    exit();
}

require_once(__DIR__ . '/system/common.php');
$currentUserId = (int) $gCurrentUser->getValue('usr_id');
$headline = 'MWS '.$gL10n->get('SYS_OVERVIEW');

// Navigation of the module starts here
$gNavigation->addStartUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage($headline);

// main menu of the page
$mainMenu = $page->getMenu();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if($gValidLogin)
{
    // show link to own profile
    $mainMenu->addItem(
        'adm_menu_item_my_profile', ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php',
        $gL10n->get('PRO_MY_PROFILE'), 'profile.png'
    );
    // show logout link
    $mainMenu->addItem(
        'adm_menu_item_logout', ADMIDIO_URL . '/adm_program/system/logout.php',
        $gL10n->get('SYS_LOGOUT'), 'door_in.png'
    );
}
else
{
    // show login link
    $mainMenu->addItem(
        'adm_menu_item_login', ADMIDIO_URL . '/adm_program/system/login.php',
        $gL10n->get('SYS_LOGIN'), 'key.png'
    );

    if($gSettingsManager->getBool('registration_enable_module'))
    {
        // show registration link
        $mainMenu->addItem(
            'adm_menu_item_registration', ADMIDIO_URL . FOLDER_MODULES . '/registration/registration.php',
            $gL10n->get('SYS_REGISTRATION'), 'new_registrations.png'
        );
    }
}

// display Menu
//$page->addHtml($page->showMainMenu());
  if ($gValidLogin)
        {
        $getUserId = (int) $gCurrentUser->getValue('usr_id');

        $instrument_sql='SELECT uin_id, uin_offering_description, ins_name, off_description FROM mws__user_instruments inner join mws__instruments on uin_ins_id=mws__instruments.ins_id inner join mws__offering on mws__offering.off_id=uin_off_id WHERE uin_usr_id='.$getUserId;
        $instrumentData= $gDb->queryPrepared($instrument_sql);
        $page->addHtml('<div class="media-left" id="profile_instruments_box">');
                    # Contribution payments
            $page->addHtml('<h2>Contribution</h2>');
            $page->addHtml('<div class="panel-body row" id="contribution">');
            $payed = $gCurrentUser->userPayedNow();
            $untilDate=  $gCurrentUser->userPayedUntil();
            if ($untilDate)
            {
                $page->addHtml('You have payed contribution until: '.$untilDate->format('D M d'));
            } else {
                $page->addHtml('You have not payed contribution. Please pay any of the contribution options below to become a member of Music with Strangers:');
                $valid_contributions_sql='SELECT * from mws__contribution_fees WHERE mws__contribution_fees.fee_to>CURRENT_TIMESTAMP';
                $pdoStatement = $gDb->queryPrepared($valid_contributions_sql);
                $contr_count = $pdoStatement->rowCount();
                if ($contr_count>0)
                {
                    $table = new HtmlTable('adm_lists_table', $page, true, FALSE);
                    $table->setColumnAlignByArray(array('left', 'left', 'left', 'left', 'right'));
                    $table->addRowHeadingByArray(array(
                        'Description',
                        'Amount',
                        'To',
                        ''
                    ));
                    $table->disableDatatablesColumnsSort(array(5));
                    // open some additional functions for contribution
                    $usrId = (int) $gCurrentUser->getValue('usr_id');
                    $rowIndex = 0;
                    while ($row = $pdoStatement->fetch())
                    {
                        ++$rowIndex;
                        $description = $row['fee_description'];
                        $amount = number_format($row['fee_amount'],2);
                        $display_amount = $amount;
                        $from = strtotime($row['fee_from']);
                        $to = strtotime($row['fee_to']);
                        $toDate=new DateTime();
                        $toDate->setTimestamp($to);
                        $toString=$toDate->format('M d Y');
                        $feeId = (int) $row['fee_id'];
                        $sql_payed = 'SELECT * from mws__payments WHERE pay_contribution_id = ? AND pay_user = ? AND pay_status = 1';
                        $arr = array($feeId, $usrId);
                        $stmt = $gDb->queryPrepared($sql_payed, $arr);
                        echo $stmt->rowCount();
                        $buttonURL = safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/payments/pay_now.php', array('source' => 1, 'contribution_id' => $feeId, 'amount' => $amount, 'description' => $description));
                        if ($stmt->rowCount() > 0) {
                            $outputButton = '<button class="btn btn-success">Ok!</button>';
                        } else {
                            $outputButton = '<button class="btn btn-default" onclick="window.location.href=\'' . $buttonURL . '\'"><img src="' . THEME_URL . '/icons/finance.png" alt="Pay contribution" />' . 'Pay' . '</button>';
                        }
                        if ($amount == 0) {
                            // Free
                            $display_amount = "FREE";
                        }
                        $table->addRowByArray(
                            array(
                                $description,
                                $display_amount,
                                $toString,
                                $outputButton
                            ),
                            'row_message_'.$rowIndex
                        );
                    }

                    // special settings for the table
                    $table->setDatatablesOrderColumns(array(array(4, 'desc')));
                    // add table to the form
                    $page->addHtml($table->show());
                }
            }
            $page->addHtml('</div>');
                if ($instrumentData->rowCount()>0)
                {
                    $page->addHtml('<h2>Instruments and interests you registered:</h2>');
                    $instrumentfetch      = $instrumentData->fetchAll();
                    $page->addHtml('<div class="panel-body row" id="profile_instruments_box_body">');
                    foreach ($instrumentfetch as $instrument)
                    {
                        $deleteButton = '<a class="admidio-icon-link" href="'.safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile_function.php', array('uin_id'=>$instrument['uin_id'], 'mode'=>10,)) . '">
                                <img src="'.THEME_URL.'/icons/delete.png" alt="' . $gL10n->get('SYS_DELETE') . '" title="' . $gL10n->get('SYS_DELETE') . '" /></a>';
                        $page->addHtml('<div class="col-sm-4">'.$deleteButton.' '.$instrument['ins_name'].'</div>');
                        $page->addHtml('<div class="col-sm-4">Interest: '.$instrument['off_description'].'</div>');
                        $page->addHtml('<div class="col-sm-4">Style: '.$instrument['uin_offering_description'].'</div>');
                    }
                    $page->addHtml('</div>');
                } else {
                    $page->addHtml('<h2>You did not register any instruments or interests yet!</h2>');
                }
                $page->addHtml('<div class="panel-body row" id="profile_instruments_box_body">'
                        . '<div class="col-sm-10"><b>Add instruments:</b><br>');
                        $form = new HtmlForm('add_instrument_form', safeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile_function.php', array('user_id' => $currentUserId, 'mode' => 9)), $page);
                        $form->addInput('uin_usr_id', 'User', $getUserId,array('property' => HtmlForm::FIELD_HIDDEN));
                        $sqlInstruments='Select * from mws__instruments';
                        $sqlOffering='Select * from mws__offering';
                        $form->addSelectBoxFromSql('uin_ins_id', 'Instrument', $gDb, $sqlInstruments, array('property' => HtmlForm::FIELD_REQUIRED, 'search' => true));
                        $form->addSelectBoxFromSql('uin_off_id', 'Interest joining bands', $gDb, $sqlOffering, array('property' => HtmlForm::FIELD_REQUIRED, 'search' => true, 'defaultValue'=>1));
                        $form->addInput('uin_offering_description', 'What genre, style, thing has your interest?', '', array('type' => 'text'));
                        $form->addSubmitButton('btn_add', 'Add', array('icon' => THEME_URL.'/icons/add.png'));
                        $page->addHtml($form->show(false));
            $page->addHtml('</div></div></div>');
           
            $page->addHtml('<h2>Upcomming Events</h2>');
            $sqlEvents='SELECT * FROM `mws__dates` WHERE dat_begin >= NOW()';
            $eventData= $gDb->queryPrepared($sqlEvents);
            if ($eventData->rowCount()>0)
            {
            }                    
        }
        else
        {
                $page->addHtml('<div class="media-left" id="association">');
                $page->addHtml('This domain is for registered members of the Music with Strangers association only.<br>');
                $page->addHtml('Please log in or register to our association.');
                $page->addHtml('</div>');
        }
$page->show();

