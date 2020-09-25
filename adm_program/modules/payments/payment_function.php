<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *  * mode: 1 - Create new
 *          2 - Delete contribution
 *          3 - admin pay contribution
 *          4 - admin delete payment
 *          5 - Edit existing
 */

require_once(__DIR__ . '/../../system/common.php');

$getPayId               = admFuncVariableIsValid($_GET, 'pay_id', 'int');
$getFeeId               = admFuncVariableIsValid($_GET, 'fee_id', 'int');
$getBookingId           = admFuncVariableIsValid($_GET, 'boo_id', 'id');
$getUserId              = admFuncVariableIsValid($_GET, 'pay_user', 'id');
$getPayDescription      = admFuncVariableIsValid($_GET, 'pay_description', 'text');
$getPayAmount           = number_format(admFuncVariableIsValid($_GET, 'pay_amount', 'numeric'),2);
$getMode                = admFuncVariableIsValid($_GET, 'mode',   'int', array('requireValue' => true));
#$adminPay               = admFuncVariableIsValid($_GET, 'pay_by_admin', 'boolean');

$paymentSources = new TablePaymentSources($gDb);
$contribution = new TableContribution($gDb);
$payment = new TablePay($gDb);

if($getMode === 1 || $getMode === 5) 
{
    if (!$gCurrentUser->editFinance())
    {
        $gMessage->show("Please log in with a booking-enabled user to edit payments");
        // => EXIT
    }
    try
    {
        // write all POST parameters into the date object
        foreach($_POST as $key => $value) // TODO possible security issue
        {
            if(admStrStartsWith($key, 'fee_'))
            {
                $contribution->setValue($key, $value);
            }
        }
    }
    catch(AdmException $e)
    {
        $e->showHtml();
    }
    $startDateTime = \DateTime::createFromFormat($gSettingsManager->getString('system_date'), $_POST['fee_from']);
    $contribution->setValue('fee_from', $startDateTime->format('Y-m-d'));
    $endDateTime = \DateTime::createFromFormat($gSettingsManager->getString('system_date'), $_POST['fee_to']);
    $contribution->setValue('fee_to', $endDateTime->format('Y-m-d'));
    $gDb->startTransaction();

    // save room booking day in database
    $returnCode = $contribution->save();

    $contributionId= (int) $contribution->getValue('fee_id');

    $gDb->endTransaction();

    $gNavigation->deleteLastUrl();

    admRedirect($gNavigation->getUrl());
} elseif ($getMode == 3)
    {
    if (!$gCurrentUser->editFinance())
    {
        $gMessage->show("Please log in with a booking-enabled user to edit payments");
        // => EXIT
    }
    $pay = new TablePay($gDb);
    $paymentSources = new TablePaymentSources($gDb);
    if (!empty($getFeeId))
    {
        $pay->setValue('pay_contribution_id', $getFeeId);
        $pay->setValue('pay_source', $paymentSources->getContributionTypeId());
        $pay->setValue('pay_descripion',$getPayDescription);
        $pay->setValue('pay_amount',floatval($getPayAmount));
        $pay->setValue('pay_by_admin',1);
        $pay->setValue('pay_user',$getUserId);
        $pay->setValue('pay_status',1);
        $gDb->startTransaction();
        $pay->save();
        $gDb->endTransaction();

        $gNavigation->deleteLastUrl();

        admRedirect($gNavigation->getUrl());
    }

} elseif ($getMode == 2)
{
    # delete contribution
    $sql='SELECT * FROM mws__payments WHERE pay_contribution_id = '. $getFeeId;
    $Pdo=  $gDb->queryPrepared($sql );
    $count=$Pdo->rowCount();

    while ($row = $Pdo->fetch())
    {
        $id = $row['pay_id'];
        $pay= new TablePay();
        $pay->readDataById($id);
        $pay->delete();
    }
    $contribution->readDataById($getFeeId);
    $contribution->delete();
    echo('done');
} elseif ($getMode == 4)
{
     #admin delete payment
    $payment->readDataById($getPayId);
    $payment->delete();
    echo('done');
}