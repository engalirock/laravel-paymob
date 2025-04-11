<?php

/**
 * IoC PayMob
 *
 * @author Ali Taha
 * @license MIT
 */

namespace engalirock\PayMob;


class PayMob
{
    public function __construct()
    {
        //
    }

    /**
     * Send POST cURL request to paymob servers.
     *
     * @param  string  $url
     * @param  array  $json
     * @return array
     */
    protected function cURL($url, $json, $headers=[])
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array_merge(['Content-Type: application/json'], $headers);

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }

    /**
     * Send GET cURL request to paymob servers.
     *
     * @param  string  $url
     * @return array
     */
    protected function GETcURL($url)
    {
        // Create curl resource
        $ch = curl_init($url);

        // Request headers
        $headers = array();
        $headers[] = 'Content-Type: application/json';

        // Return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // $output contains the output string
        $output = curl_exec($ch);

        // Close curl resource to free up system resources
        curl_close($ch);
        return json_decode($output);
    }

    /**
     * Request auth token from paymob servers.
     *
     * @return array
     */
    public function authPaymob()
    {
        // Request body
        $json = [
            'api_key' => config('paymob.api_key'),
        ];

        // Send curl
        $auth = $this->cURL(
            'https://accept.paymob.com/api/auth/tokens',
            $json
        );

        return $auth;
    }

    // https://developers.paymob.com/egypt/checkout-copy-1/create-intention-payment-api
    public function intention($token, $data)
    {
        // Request body
        $json = [
            "amount"=> $data['amount'] ?? 0,
            "currency"=> $data['currency'] ?? "",
            "payment_methods"=>$data['payment_methods'] ?? [],
            "items"=>$data['items'] ?? [],
            "billing_data"=>$data['billing'] ?? [],
            "customer"=>$data['customer'] ?? [],
        ];

        if(isset($data['extras'])){
            $json['extras'] = $data['extras'];
        }

        // Send curl
        return $this->cURL('https://accept.paymob.com/v1/intention/', $json, ['Authorization: Token '.$token]);
    }

    public function transaction_inquiry($token, $order_id){
        // Request body
        $json = ["order_id"=> $order_id];

        // Send curl
        return $this->cURL('https://accept.paymob.com/api/ecommerce/orders/transaction_inquiry', $json, ['Authorization: Bearer '.$token]);
    }

    public function unifiedcheckout($publicKey, $clientSecret)
    {
        return sprintf('https://accept.paymob.com/unifiedcheckout/?publicKey=%s&clientSecret=%s', $publicKey, $clientSecret);
    }

    /**
     * Register order to paymob servers
     *
     * @param  string  $token
     * @param  int  $merchant_id
     * @param  int  $amount_cents
     * @param  int  $merchant_order_id
     * @return array
     */
    public function makeOrderPaymob($token, $merchant_id, $amount_cents, $merchant_order_id)
    {
        // Request body
        $json = [
            'merchant_id'            => $merchant_id,
            'amount_cents'           => $amount_cents,
            'merchant_order_id'      => $merchant_order_id,
            'currency'               => 'EGP',
            'notify_user_with_email' => true
        ];

        // Send curl
        $order = $this->cURL(
            'https://accept.paymob.com/api/ecommerce/orders?token=' . $token,
            $json
        );

        return $order;
    }

    /**
     * Get payment key to load iframe on paymob servers
     *
     * @param  string  $token
     * @param  int  $amount_cents
     * @param  int  $order_id
     * @param  string  $email
     * @param  string  $fname
     * @param  string  $lname
     * @param  int  $phone
     * @param  string  $city
     * @param  string  $country
     * @return array
     */
    public function getPaymentKeyPaymob(
        $integration_id,
        $token,
        $amount_cents,
        $order_id,
        $email   = 'NA',
        $fname   = 'NA',
        $lname   = 'NA',
        $phone   = 'NA',
        $city    = 'NA',
        $country = 'NA'
    ) {
        // Request body
        $json = [
            'amount_cents' => $amount_cents,
            'expiration'   => 36000,
            'order_id'     => $order_id,
            "billing_data" => [
                "email"        => $email,
                "first_name"   => $fname,
                "last_name"    => $lname,
                "phone_number" => $phone,
                "city"         => $city,
                "country"      => $country,
                'street'       => 'NA',
                'building'     => 'NA',
                'floor'        => 'NA',
                'apartment'    => 'NA',
                'shipping_method' => 'PKG',
                'postal_code'    => 'NA',
                'state'    => 'NA',
            ],
            'currency'            => 'EGP',
            'card_integration_id' => $integration_id
        ];

        // Send curl
        $payment_key = $this->cURL(
            'https://accept.paymob.com/api/acceptance/payment_keys?token=' . $token,
            $json
        );

        return $payment_key;
    }

    /**
     * Make payment for API (moblie clients).
     *
     * @param  string  $token
     * @param  int  $card_number
     * @param  string  $card_holdername
     * @param  int  $card_expiry_mm
     * @param  int  $card_expiry_yy
     * @param  int  $card_cvn
     * @param  int  $order_id
     * @param  string  $firstname
     * @param  string  $lastname
     * @param  string  $email
     * @param  string  $phone
     * @return array
     */
    public function makePayment(
        $token,
        $card_number,
        $card_holdername,
        $card_expiry_mm,
        $card_expiry_yy,
        $card_cvn,
        $order_id,
        $firstname,
        $lastname,
        $email,
        $phone
    ) {
        // JSON body.
        $json = [
            'source' => [
                'identifier'        => $card_number,
                'sourceholder_name' => $card_holdername,
                'subtype'           => 'CARD',
                'expiry_month'      => $card_expiry_mm,
                'expiry_year'       => $card_expiry_yy,
                'cvn'               => $card_cvn
            ],
            'billing' => [
                'first_name'   => $firstname,
                'last_name'    => $lastname,
                'email'        => $email,
                'phone_number' => $phone,
            ],
            'payment_token' => $token
        ];

        // Send curl
        $payment = $this->cURL(
            'https://accept.paymobsolutions.com/api/acceptance/payments/pay',
            $json
        );

        return $payment;
    }

    /**
     * Capture authed order.
     *
     * @param  string  $token
     * @param  int  $transactionId
     * @param  int  amount
     * @return array
     */
    public function capture($token, $transactionId, $amount)
    {
        // JSON body.
        $json = [
            'transaction_id' => $transactionId,
            'amount_cents'   => $amount
        ];

        // Send curl.
        $res = $this->cURL(
            'https://accept.paymobsolutions.com/api/acceptance/capture?token=' . $token,
            $json
        );

        return $res;
    }

    /**
     * Get PayMob all orders.
     *
     * @param  string  $authToken
     * @param  string  $page
     * @return Response
     */
    public function getOrders($authToken, $page = 1)
    {
        $orders = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/ecommerce/orders?page={$page}&token={$authToken}"
        );

        return $orders;
    }

    /**
     * Get PayMob order.
     *
     * @param  string  $authToken
     * @param  int  $orderId
     * @return Response
     */
    public function getOrder($authToken, $orderId)
    {
        $order = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/ecommerce/orders/{$orderId}?token={$authToken}"
        );

        return $order;
    }

    /**
     * Get PayMob all transactions.
     *
     * @param  string  $authToken
     * @param  string  $page
     * @return Response
     */
    public function getTransactions($authToken, $page = 1)
    {
        $transactions = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/acceptance/transactions?page={$page}&token={$authToken}"
        );

        return $transactions;
    }

    /**
     * Get PayMob transaction.
     *
     * @param  string  $authToken
     * @param  int  $transactionId
     * @return Response
     */
    public function getTransaction($authToken, $transactionId)
    {
        $transaction = $this->GETcURL(
            "https://accept.paymobsolutions.com/api/acceptance/transactions/{$transactionId}?token={$authToken}"
        );

        return $transaction;
    }
}
