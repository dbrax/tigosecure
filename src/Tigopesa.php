<?php

namespace Epmnzava\Tigopesa;

use Exception;
use GuzzleHttp\Client;
use Log;
use Throwable;

class Tigopesa
{

    public $client_secret;
    public $client_id;
    public $base_url;
    public $pin;
    public $account_id;
    public $account_number;
    public $redirect_url;
    public $callback_url;
    public $lang;
    public $currency_code;
    public  const ACCESS_TOKEN_ENDPOINT = "/v1/oauth/generate/accesstoken?grant_type=client_credentials";
    public const PAYMENT_AUTHORIZATION_ENDPOINT = "/v1/tigo/payment-auth/authorize";


    public function __construct($client_secret, $client_id, $base_url, $pin, $account_id, $account_number, $redirect_url, $callback_url, $lang, $currency_code)
    {

        $this->client_secret = $client_secret;
        $this->client_id = $client_id;
        $this->base_url = $base_url;
        $this->pin = $pin;
        $this->account_id = $account_id;
        $this->account_number = $account_number;
        $this->redirect_url = $redirect_url;
        $this->callback_url = $callback_url;
        $this->lang = $lang;
        $this->currency_code = $currency_code;
    }

    /**
     *  Fetch access_token
     */
    private function access_token()
    {


        $access_token_url = $this->base_url . self::ACCESS_TOKEN_ENDPOINT;

        $data = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ];


        $client = new  Client;
        $response = $client->request('POST', $access_token_url, [
            'form_params' => $data
        ]);

        return json_decode($response->getBody())->accessToken;
    }



    /**
     * make_payment
     *
     * @param $customer_firstname
     * @param $customer_lastname
     * @param $customer_email
     * @param $amount
     * @param $reference_id
     * @return mixed
     */
    public function make_payment(
        $customer_firstname,
        $customer_lastname,
        $customer_email,
        $amount,
        $reference_id
    ) {

        $token = $this->access_token();

        $response = $this->makePaymentRequest(
            $token,
            $amount,
            $reference_id,
            $customer_firstname,
            $customer_lastname,
            $customer_email
        );


        return json_decode($response);
    }


    /**
     * @param $amount
     * @param $refersence_id
     * @param $customer_firstname
     * @param $custormer_lastname
     * @param $customer_email
     * @return string
     *
     * function that creates payment authentication json
     */

    public function createPaymentAuthJson(
        $amount,
        $refecence_id,
        $customer_firstname,
        $custormer_lastname,
        $customer_email
    ) {


        $paymentJson = '{
  "MasterMerchant": {
    "account": "' . $this->account_number . '",
    "pin": "' . $this->pin . '",
    "id": "' . $this->account_id . '",
  },
  "Merchant": {
    "reference": "",
    "fee": "0.00",
    "currencyCode": ""
  },
  "Subscriber": {
    "account": "",
    "countryCode": "255",
    "country": "TZA",
    "firstName": "' . $customer_firstname . '",
    "lastName": "' . $custormer_lastname . '",
    "emailId": "' . $customer_email . '"
  },
  "redirectUri":" ' . $this->redirect_url . '",
  "callbackUri": "' . $this->callback_url . '",
  "language": "' . $this->lang . '",
  "terminalId": "",
  "originPayment": {
    "amount": "' . $amount . '",
    "currencyCode": "' . $this->currency_code . '",
    "tax": "0.00",
    "fee": "0.00"
  },
  "exchangeRate": "1",
  "LocalPayment": {
    "amount": "' . $amount . '",
    "currencyCode": "' . $this->currency_code . '"
  },
  "transactionRefId": "' . $refecence_id . '"
}';




        return $paymentJson;
    }


    /**
     * Using Curl Request
     * @param string $base_url
     * @param $issuedToken
     * @param $amount
     * @param $refecence_id
     * @param $customer_firstname
     * @param $custormer_lastname
     * @param $customer_email
     * @return bool|string
     *
     */
    public function makePaymentRequest($issuedToken, $amount, $refecence_id, $customer_firstname, $custormer_lastname, $customer_email)
    {

        $paymentAuthUrl = $this->base_url . self::PAYMENT_AUTHORIZATION_ENDPOINT;
        $ch = curl_init($paymentAuthUrl);
        curl_setopt_array($ch, array(
            CURLOPT_URL => $paymentAuthUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $this->createPaymentAuthJson($amount, $refecence_id, $customer_firstname, $custormer_lastname, $customer_email),
            CURLOPT_HTTPHEADER => array(
                "accesstoken:" . $issuedToken,
                "cache-control: no-cache",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }
}
