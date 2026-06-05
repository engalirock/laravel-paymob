<?php


return [

    /*
    |--------------------------------------------------------------------------
    | PayMob Default Order Model
    |--------------------------------------------------------------------------
    |
    | This option defines the default Order model.
    |
    */

    'order' => [
        'model' => 'App\Order'
    ],

    /*
    |--------------------------------------------------------------------------
    | PayMob username and password
    |--------------------------------------------------------------------------
    |
    | This is your PayMob username and password to make auth request.
    |
    */

    'username' => '',
    'password' => '',

    /*
    |--------------------------------------------------------------------------
    | PayMob integration id and iframe id
    |--------------------------------------------------------------------------
    |
    | This is your PayMob integration id and iframe id.
    |
    */

    'integration_id' => '',
    'iframe_id' => '',

    /*
    |--------------------------------------------------------------------------
    | cURL IP Resolution
    |--------------------------------------------------------------------------
    |
    | Force resolving IP addresses to IPv4 or IPv6 to avoid SSL handshake
    | issues depending on your hosting environment.
    | Options: CURL_IPRESOLVE_V4, CURL_IPRESOLVE_V6, or CURL_IPRESOLVE_WHATEVER
    | Default: CURL_IPRESOLVE_V4 (Forced IPv4)
    |
    */

    'ip_resolve' => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
];

