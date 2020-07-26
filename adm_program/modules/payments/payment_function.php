<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(__DIR__ . '/../../system/common.php');

$getPayId               = admFuncVariableIsValid($_GET, 'pay_id', 'int');
$getFeeId               = admFuncVariableIsValid($_GET, 'fee_id', 'int');
$getBookingId           = admFuncVariableIsValid($_GET, 'boo_id', 'id');
$getUserId              = admFuncVariableIsValid($_GET, 'usr_id', 'int', array('defaultValue' => $gCurrentUser->getValue('usr_id')));
$getMode                = admFuncVariableIsValid($_GET, 'mode',   'int', array('requireValue' => true));

$contribution = new TableContribution($gDb);

if($getMode === 1 || $getMode === 5)  // Create a roombooking or edit.
{
    if (!$gCurrentUser->editFinance())
    {
        $gMessage->show("Please log in with a booking-enabled user to edit bookings");
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
}