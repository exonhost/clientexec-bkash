<?php
/*
Plugin Name: bKash Tokenized Payment Method
Developer: ExonHost
*/
class bKashQuery
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

    public function queryPayment($paymentID)
    {

        $paymentId = $paymentID;
        $token = $this->getToken();
        $context = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    "Authorization: {$token}\r\n" .
                    "X-APP-KEY: {$this->APPUsername}\r\n",
                'timeout' => 30,
                'content' => json_encode([
                    'paymentID' => $paymentId,
                ]),
            ],
        ];

        $context  = stream_context_create($context);
        $url      = $this->payUrl . '/payment/status';
        $response = file_get_contents($url, false, $context);
        $data     = json_decode($response, true);

        return $data;
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
    
}
?>