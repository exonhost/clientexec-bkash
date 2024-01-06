<?php
/*
Plugin Name: bKash Tokenized Payment Method
Developer: ExonHost
*/
class bKash
{
    private $APIUsername;
    private $APIPassword;
    private $APPUsername;
    private $APPPassword;
    private $payUrl;

    public function __construct($APIUsername, $APIPassword, $APPUsername, $APPPassword, $payUrl)
    {
        $this->APIUsername = $APIUsername;
        $this->APIPassword = $APIPassword;
        $this->APPUsername = $APPUsername;
        $this->APPPassword = $APPPassword;
        $this->payUrl = $payUrl;
    }

    public function processPayment($postData)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->extractPostData($postData);

            $token = $this->getToken();

            $response = $this->createPayment(
                $token,
                $this->getCallbackUrl(),
                $this->getSystemUrl(),
                $this->getAmount(),
                $this->getInvoiceId(),
                $this->getTransactionPrefix(),
                $this->getCurrencyCode()
            );

            header('Location: ' . $response['bkashURL']);
        }
    }

    private function extractPostData($postData)
    {

        $this->invoiceId = $postData['invoiceId'];
        $this->description = $postData['description'];
        $this->amount = $postData['amount'];
        $this->systemUrl = $postData['systemUrl'];
        $this->firstname = $postData['firstname'];
        $this->lastname = $postData['lastname'];
        $this->email = $postData['email'];
        $this->callbackUrl = $postData['callbackUrl'];
        $this->currencyCode = $postData['currencyCode'];
        $this->APIUsername = $postData['APIUsername'];
        $this->APIPassword = $postData['APIPassword'];
        $this->APPUsername = $postData['APPUsername'];
        $this->APPPassword = $postData['APPPassword'];
        $this->TransactionPrefix = $postData['TransactionPrefix'];
        $this->TestMode = $postData['TestMode'];
        $this->cancel_url = $postData['cancel_url'];
        $this->pay_url = $postData['pay_url'];
    }

    private function getToken()
    {
        $fields = [
            'app_key'    => $this->APPUsername,
            'app_secret' => $this->APPPassword,
        ];

        $url = $this->payUrl . '/token/grant';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'username: ' . $this->APIUsername,
            'password: ' . $this->APIPassword,
        ];

        return $this->executeCurl($url, $fields, $headers, true);
    }

    private function createPayment($token, $callbackUrl, $systemUrl, $amount, $invoiceId, $TransactionPrefix, $currencyCode)
    {
        $fields = [
            'mode'                  => '0011',
            'amount'                => $amount,
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'payerReference'        => $currencyCode,
            'callbackURL'           => $callbackUrl,
            'merchantInvoiceNumber' => $invoiceId,
        ];

        $url = $this->payUrl . '/create';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . $token,
            'X-APP-KEY: ' . $this->APPUsername,
        ];

        return $this->executeCurl($url, $fields, $headers);
    }

    private function executeCurl($url, $fields, $headers, $isTokenRequest = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                'status'    => 'error',
                'message'   => 'Curl error: ' . curl_error($ch),
                'errorCode' => 'curl_error',
            ];
        }

        curl_close($ch);

        $data = json_decode($response, true);

        if ($isTokenRequest) {
            return (is_array($data) && isset($data['id_token'])) ? $data['id_token'] : null;
        }

        if (is_array($data)) {
            return $data;
        }

        return [
            'status'    => 'error',
            'message'   => 'Invalid response from bKash API.',
            'errorCode' => 'irs',
        ];
    }



    private function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    private function getSystemUrl()
    {
        return $this->systemUrl;
    }

    private function getAmount()
    {
        return $this->amount;
    }

    private function getInvoiceId()
    {
        return $this->invoiceId;
    }

    private function getTransactionPrefix()
    {
        return $this->TransactionPrefix;
    }

    private function getCurrencyCode()
    {
        return $this->currencyCode;
    }
}

$postData = $_POST;
$bKash = new bKash($postData['APIUsername'], $postData['APIPassword'], $postData['APPUsername'], $postData['APPPassword'], $postData['pay_url']);
$bKash->processPayment($postData);
?>
