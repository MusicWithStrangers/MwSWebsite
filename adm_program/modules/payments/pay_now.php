<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(__DIR__ . '/../../system/common.php');

$getPayAmount           = number_format(admFuncVariableIsValid($_GET, 'amount', 'numeric'),2);
$getPayDescription      = admFuncVariableIsValid($_GET, 'description', 'string');
$getPaySource           = admFuncVariableIsValid($_GET, 'source', 'int');
$getContributionId      = admFuncVariableIsValid($_GET, 'contribution_id', 'id');
$getBookingId           = admFuncVariableIsValid($_GET, 'booking_id', 'id');
$getUserId              = admFuncVariableIsValid($_GET, 'usr_id', 'int', array('defaultValue' => $gCurrentUser->getValue('usr_id')));


try {
    /*
     * Initialize the Mollie API library with your API key.
     *
     * See: https://www.mollie.com/dashboard/developers/api-keys
     */
    require "initialize.php";

    /*
     * Generate a unique order id for this example. It is important to include this unique attribute
     * in the redirectUrl (below) so a proper return page can be shown to the customer.
     */
    if (!empty($getBookingId))
    {
        $pay->setValue('pay_booking_id',$getBookingId);
    }
    if (!empty($getContributionId))
    {
        $pay->setValue('pay_contribution_id', $getContributionId);
    }
    $pay->setValue('pay_descripion',$getPayDescription);
    $pay->setValue('pay_amount',floatval($getPayAmount));
    $pay->setValue('pay_user',$getUserId);
    $pay->save();
    $orderId = $pay->getValue('pay_id');

    /*
     * Determine the url parts to these example files.
     */
    $protocol = isset($_SERVER['HTTPS']) && strcasecmp('off', $_SERVER['HTTPS']) !== 0 ? "https" : "http";
    $hostname = $_SERVER['HTTP_HOST'];
    $hostname='members.musicwithstrangers.com';
    $path = dirname(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);

    
    /*
     * Payment parameters:
     *   amount        Amount in EUROs. This example creates a â‚¬ 10,- payment.
     *   description   Description of the payment.
     *   redirectUrl   Redirect location. The customer will be redirected there after the payment.
     *   webhookUrl    Webhook location, used to report when the payment changes state.
     *   metadata      Custom metadata that is stored with the payment.
     */
    $payment = $mollie->payments->create([
        "amount" => [
            "currency" => "EUR",
            "value" => $getPayAmount // You must send the correct number of decimals, thus we enforce the use of strings
        ],
        "description" => 'Contribution "' . $getPayDescription.'"'. " Order #{$orderId}",
        "redirectUrl" => "{$protocol}://{$hostname}{$path}/payments/return.php?order_id={$orderId}",
        "webhookUrl" => "https://members.musicwithstrangers.com/adm_program/modules/payments/payments/webhook.php",
    ]);

    /*
     * In this example we store the order with its payment status in a database.
     */
    database_write($orderId, $payment->status);

    /*
     * Send the customer off to complete the payment.
     * This request should always be a GET, thus we enforce 303 http response code
     */
    header("Location: " . $payment->getCheckoutUrl(), true, 303);
} catch (\Mollie\Api\Exceptions\ApiException $e) {
    echo "API call failed: " . htmlspecialchars($e->getMessage());
}
