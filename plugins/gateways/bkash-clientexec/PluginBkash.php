<?php
/*
Plugin Name: bKash Tokenized Payment Method
Developer: ExonHost
*/
require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'plugins/gateways/bkash/bKash.php';
class PluginBkash extends GatewayPlugin
{
   function getVariables()
{
    $variables = array(
        lang("Plugin Name") => array(
            "type"          => "hidden",
            "description"   => "",
            "value"         => "bKash"
        ),
        lang('Signup Name') => array(
            'type'        => 'text',
            'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
            'value'       => 'bKash Checkout'
        ),
        lang("API Username") => array(
            "type"          => "text",
            "description"   => "Enter your API Username Here",
            "value"         => ""
        ),
        lang("API Password") => array(
            "type"          => "text",
            "description"   => "Enter your API Password Here",
            "value"         => ""
        ),
        lang("App Key") => array(
            "type"          => "text",
            "description"   => "Enter your App Username Here",
            "value"         => ""
        ),
        lang("App Password") => array(
            "type"          => "text",
            "description"   => "Enter your App Password Here",
            "value"         => ""
        ),
        lang("Transaction Prefix") => array(
            "type"          => "text",
            "description"   => "Enter your Transaction Prefix Here",
            "value"         => ""
        ),
        
        lang("USDBDT Price") => array(
            "type"          => "text",
            "description"   => "Enter USD to BDT Price",
            "value"         => ""
        ),
        
        lang("Test Mode") => array(
            "type"          => "yesno",
            "description"   => "Enable Test Mode",
            "value"         => ""
        ),
    );
    return $variables;
}

   function singlepayment($params)
    {
        $query = "SELECT * FROM currency WHERE abrv = '" . $params['userCurrency'] . "'";
        $result = $this->db->query($query);
        $row = $result->fetch();
        $prefix = $row['symbol'];

        $invoiceId = $params['invoiceNumber'];
        $description = $params['invoiceDescription'];
        
        
        if($params['userCurrency']=="USD")
        {
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2)) * $params['plugin_bkash_USDBDT Price'];
        }else{
        $amount = sprintf("%01.2f", round($params["invoiceTotal"], 2));    
        }
        
        
        
        $systemUrl = $params['companyURL'];
        $firstname = $params['userFirstName'];
        $lastname = $params['userLastName'];
        $email = $params['userEmail'];

        $bar = "/";
        if (substr(CE_Lib::getSoftwareURL(), -1) == "/") {
            $bar = "";
        }
        $baseURL = CE_Lib::getSoftwareURL() . $bar;
        $CallbackURL = $baseURL . "plugins/gateways/bkash/callback.php";

        $currencyCode = $params['userCurrency'];

        $APIUsername = $params['plugin_bkash_API Username'];
        $APIPassword = $params['plugin_bkash_API Password'];
        $APPUsername = $params['plugin_bkash_App Key'];
        $APPPassword = $params['plugin_bkash_App Password'];
        
    
        
        
        $TransactionPrefix = $params['plugin_bkash_Transaction Prefix'];
        
        $TestMode = $params['plugin_bkash_Test Mode'];

        $cancel_url = $params['invoiceviewURLCancel'];

        $sanbox_url  = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout';
        $live_url    = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

        if ($TestMode == 1) {
            $payment_url = $sanbox_url;
        } else {
            $payment_url = $live_url;
        }

     $bKash = new bKash(
        $APIUsername,
        $APIPassword,
        $APPUsername,
        $APPPassword,
        $payment_url
    );

    $postData = array(
        'invoiceId' => $invoiceId,
        'description' => $description,
        'amount' => $amount,
        'systemUrl' => $systemUrl,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'callbackUrl' => $CallbackURL,
        'currencyCode' => $currencyCode,
        'APIUsername' => $APIUsername,
        'APIPassword' => $APIPassword,
        'APPUsername' => $APPUsername,
        'APPPassword' => $APPPassword,
        'TransactionPrefix' => $TransactionPrefix,
        'TestMode' => $TestMode,
        'cancel_url' => $cancel_url,
        'pay_url' => $payment_url,
    );

    $rurl = $bKash->processPayment($postData);
    exit;
        
    }
    
    function credit($params)
    {
    }
    function get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }

    
}
