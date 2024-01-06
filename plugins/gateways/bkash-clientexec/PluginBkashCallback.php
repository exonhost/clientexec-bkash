<?php
/*
Plugin Name: bKash Tokenized Payment Method
Developer: ExonHost
*/
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice.php';
require_once 'plugins/gateways/bkash/bKashQuery.php';

class PluginBkashCallback extends PluginCallback
{
    
  public function processCallback()
{
    $cPlugin = new Plugin('', 'bkash', $this->user);
    $bkashuser = trim($cPlugin->GetPluginVariable("plugin_bkash_API Username"));
    $bkashpass = trim($cPlugin->GetPluginVariable("plugin_bkash_API Password"));
    $bkashapikey = trim($cPlugin->GetPluginVariable("plugin_bkash_App Key"));
    $bkashkeypassword = trim($cPlugin->GetPluginVariable("plugin_bkash_App Password"));
    $bkashtestmode = trim($cPlugin->GetPluginVariable("plugin_bkash_Test Mode"));
    $bkashtrx = trim($cPlugin->GetPluginVariable("plugin_bkash_Transaction Prefix"));
    $paymentID = $_REQUEST['paymentID'];

    $sanbox_url  = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout';
    $live_url    = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

    if ($bkashtestmode == 1) {
        $payment_url = $sanbox_url;
    } else {
        $payment_url = $live_url;
    }

    $bar = "/";
    if (substr(CE_Lib::getSoftwareURL(), -1) == "/") {
        $bar = "";
    }
    $baseURL = CE_Lib::getSoftwareURL() . $bar;


    $bkashQuery = new bKashQuery($bkashuser, $bkashpass, $bkashapikey, $bkashkeypassword, $payment_url);
    
    $callbackData = $bkashQuery->queryPayment($paymentID);


    $amount = $callbackData['amount'];
    $invoiceId = $callbackData['merchantInvoice'];
    $paymentID = $callbackData['paymentID'];
    $currencyCode = $callbackData['payerReference'];
    $exchangeRate = !empty($cPlugin->GetPluginVariable("plugin_bkash_USDBDT Price")) ? $cPlugin->GetPluginVariable("plugin_bkash_USDBDT Price") : 1;

    if ($currencyCode !== 'BDT') {
        $amount /= $exchangeRate;
    }

    $price = $amount . " " . $currencyCode;
    $cPlugin = new Plugin($invoiceId, 'bkash', $this->user);
    $cPlugin->setAmount($amount);
    $cPlugin->setAction('charge');

    $status = $callbackData['verificationStatus'];

    if ($status === 'Complete') {
        $transaction = "bKash payment of $price Successful (Order ID: " . $invoiceId . ") and (Payment ID: " . $paymentID . ")";
        // Create plug-in class to interact with CE
        if ($cPlugin->IsUnpaid() == 1) {
            $cPlugin->PaymentAccepted($amount, $transaction);
            $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $invoiceId;
            header("Location: " . $returnURL);
            exit;
        } else {
            $this->handleError("Invoice is already paid.");
        }
    } else {
        $transaction = "bKash payment of $price Failed (Order ID: " . $invoiceId . ") and (Payment ID: " . $paymentID . ")";
        $cPlugin->PaymentRejected($transaction);
        $returnURL = CE_Lib::getSoftwareURL() . "/index.php?fuse=billing&cancel=1&controller=invoice&view=invoice&id=" . $invoiceId;
        header("Location: " . $returnURL);
        exit;
    }
}

    
    
    
    
    
    
}
