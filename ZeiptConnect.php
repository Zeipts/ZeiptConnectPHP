<?php
/**
 * Created by PhpStorm.
 * User: aleorn
 * Date: 2018-10-11
 * Time: 21:51
 */

namespace Zeipt;

class ZeiptConnect
{
    public $authToken; //Token issued by Zeipt.io - only cloud services now
    public $authUsername; //Legacy auth issued by Zeipt.io
    public $authPassword; //Legacy auth issued by Zeipt.io
    public $routeCardRegisterFailed; //Route to redirect user to if card register fails
    public $routeCardRegisterCancelled; //Route to redirect user to if card register is cancelled
    public $routeCardRegisterSuccess; //Route to redirect user to if card register is successful
    private static $baseUrl = "https://zeipt.nu:443/echo";

    public function __construct($token, $username, $password)
    {
        $this->authToken = $token;
        $this->authUsername = $username;
        $this->authPassword = $password;
    }

    public function RegisterCustomer($customerId)
    {
        $curl_post_data = array(
            'provider_gcid' => $customerId
        );
        $response = $this->doPost($curl_post_data, '/registerprovidergcid');
        return strpos($response, 'DONE') !== FALSE;
    }

    public function GetReceipts($customerId, $from, $to)
    {
        $curl_post_data = array(
            'provicer_gcid' => $customerId,
            'all_data' => array(
                'from_timestamp' => $from,
                'to_timestamp' => $to,
                'all_receipts' => true
            )
        );
        return json_decode($this->doPost($curl_post_data, '/customer/receipt'));
    }

    public function GetCard($customerId, $transferNr)
    {
        $curl_post_data = array(
            'provicer_gcid' => $customerId,
            'zeipt_card_transnr' => $transferNr
        );
        return json_decode($this->doPost($curl_post_data, '/customer/card'));
    }

    public function CreateCardRegister($customerId, $successRoute, $failRoute, $cancelRoute)
    {
        $this->routeCardRegisterCancelled = $cancelRoute;
        $this->routeCardRegisterSuccess = $successRoute;
        $this->routeCardRegisterFailed = $failRoute;

        $service_url = 'https://zeipt.io/zeipt/RegisterCard/';
        $curl = curl_init($service_url);
        $curl_post_data = array(
            'GCID' => $customerId
        );
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        curl_setopt($curl, CURLOPT_USERPWD, "$this->authUsername:$this->authPassword");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $curl_response = curl_exec($curl);
        curl_close($curl);
        return $curl_response;
    }

    private function doPost($payload, $endpoint, $headers = [])
    {
        $headers = $this->BaseHeader($headers);
        $curl = curl_init(ZeiptConnect::$baseUrl . $endpoint);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(
            $payload
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    private function BaseHeader($existing = [])
    {
        $existing[] = 'Content-Type: application/json';
        $existing[] = 'auth_token: ' . $this->authToken;
        return $existing;
    }
}