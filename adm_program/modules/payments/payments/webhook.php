<?php
error_reporting(E_ALL); // Error/Exception engine, always use E_ALL
ini_set('ignore_repeated_errors', TRUE); // always use TRUE
ini_set('display_errors', FALSE); // Error/Exception display, use FALSE only in production environment or real server. Use TRUE in development environment
ini_set('log_errors', TRUE); // Error/Exception file logging engine.
ini_set('error_log', 'errors.log'); // Logging file path

require_once(__DIR__ . '/../../../system/common.php');

/*
 * How to verify Mollie API Payments in a webhook.
 *
 * See: https://docs.mollie.com/guides/webhooks
 */

try {
    /*
     * Initialize the Mollie API library with your API key.
     *
     * See: https://www.mollie.com/dashboard/developers/api-keys
     */
    require "../initialize.php";

    /*
     * Retrieve the payment's current state.
     */
    $payment = $mollie->payments->get($_POST["id"]);
    $orderId = $payment->metadata->order_id;

    $pay = new TablePay($gDb);
    $pay->readDataById($orderId);

    /*
     * Update the order in the database.
     */
    if ($payment->isPaid() && (!$payment->hasRefunds()) && (!$payment->hasChargebacks())) {
        /*
         * The payment is paid and isn't refunded or charged back.
         * At this point you'd probably want to start the process of delivering the product to the customer.
         */

        $pay->setValue('pay_status', 1); // TODO: Status 1 is Paid
    } elseif ($payment->isOpen()) {
        /*
         * The payment is open.
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Open
    } elseif ($payment->isPending()) {
        /*
         * The payment is pending.
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Pending
    } elseif ($payment->isFailed()) {
        /*
         * The payment has failed.
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Failed
    } elseif ($payment->isExpired()) {
        /*
         * The payment is expired.
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Expired
    } elseif ($payment->isCanceled()) {
        /*
         * The payment has been canceled.
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Canceled
    } elseif ($payment->hasRefunds()) {
        /*
         * The payment has been (partially) refunded.
         * The status of the payment is still "paid"
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Refunds
    } elseif ($payment->hasChargebacks()) {
        /*
         * The payment has been (partially) charged back.
         * The status of the payment is still "paid"
         */
        $pay->setValue('pay_status', 0); // TODO: Status 1 is Chargeback
    }

    $pay->save();
} catch (\Mollie\Api\Exceptions\ApiException $e) {
    echo "<script type='text/javascript'>alert('webhook failed');</script>"; 
    echo "API call failed: " . htmlspecialchars($e->getMessage());
}