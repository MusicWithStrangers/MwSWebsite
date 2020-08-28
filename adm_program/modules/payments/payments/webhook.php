<?php
/*
 * How to verify Mollie API Payments in a webhook.
 *
 * See: https://docs.mollie.com/guides/webhooks
 */
$myfile = fopen("webhook_log.txt", "w") or die("Unable to open file!");

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
    
    //$payment = $mollie->payments->get($_POST["order_id"]);
    $payment = $mollie->payments->get($_POST["id"]);
    $orderId = $payment->metadata->order_id;
    foreach ($payment->metadata as $param_name => $param_val) {
        fwrite($myfile, $param_name . ": ". $param_val. " \n");
    }
    /*
     * Update the order in the database.
     */
    
    if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {
        /*
         * The payment is paid and isn't refunded or charged back.
         * At this point you'd probably want to start the process of delivering the product to the customer.
         */
        $pay=new TablePay($gDb);
        $pay->readDataById($orderId);
        foreach ($_POST as $key => $value) // TODO possible security issue
        {
            if (admStrStartsWith($key, 'pay'))
            {
                $pay->setValue($key, $value);
            }
        }
        $pay->setValue('pay_status',$payment->status);
        $pay->save();
        $message = 'webhook done. payment status: '.$payment->status;
    
    } elseif ($payment->isOpen()) {
        /*
         * The payment is open.
         */
    } elseif ($payment->isPending()) {
        /*
         * The payment is pending.
         */
    } elseif ($payment->isFailed()) {
        /*
         * The payment has failed.
         */
    } elseif ($payment->isExpired()) {
        /*
         * The payment is expired.
         */
    } elseif ($payment->isCanceled()) {
        /*
         * The payment has been canceled.
         */
    } elseif ($payment->hasRefunds()) {
        /*
         * The payment has been (partially) refunded.
         * The status of the payment is still "paid"
         */
    } elseif ($payment->hasChargebacks()) {
        /*
         * The payment has been (partially) charged back.
         * The status of the payment is still "paid"
         */
    }
} catch (\Mollie\Api\Exceptions\ApiException $e) {
    echo "<script type='text/javascript'>alert('webhook failed');</script>"; 
    echo "API call failed: " . htmlspecialchars($e->getMessage());
}
fclose($myfile);