# Laravel PayMob

[![Latest Stable Version](https://img.shields.io/packagist/v/engalirock/laravel-paymob.svg?style=flat-square)](https://packagist.org/packages/engalirock/laravel-paymob)
[![Total Downloads](https://img.shields.io/packagist/dt/engalirock/laravel-paymob.svg?style=flat-square)](https://packagist.org/packages/engalirock/laravel-paymob)
[![License](https://img.shields.io/packagist/l/engalirock/laravel-paymob.svg?style=flat-square)](https://packagist.org/packages/engalirock/laravel-paymob)

A modern, lightweight Laravel package for integrating **PayMob (Accept) Payment Gateway** in Egypt and other supported regions. Fully compatible with Laravel 10+ and PHP 8.2+.

---

## Key Features

- **Laravel Auto-Discovery** supported out of the box.
- Supports both **Legacy API** (Order & Payment Keys) and the new **Intentions API** (for unified checkout, wallets, cards, etc.).
- Integrated transaction inquiry and void/refund operations.
- Clean error debugging with HTTP status codes and curl error logs.
- Pre-built `PayMobController` template for callbacks.

---

## Table of Contents

1. [SEO & Description](#laravel-paymob)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Modern Integration (Intentions API - Recommended)](#modern-integration-intentions-api-recommended)
5. [Legacy Integration (Steps to make a transaction)](#legacy-integration-steps-to-make-a-transaction-on-paymob-servers)
6. [Debugging & Error Handling](#debugging--error-handling)
7. [PayMobController & Callbacks](#paymobcontroller)
8. [Other Methods](#other-paymob-methods)
9. [License](#license)

---

## Installation

You can install the package via Composer:

```bash
composer require engalirock/laravel-paymob
```

> [!NOTE]
> **Laravel 10+** supports package auto-discovery, so you do not need to add the ServiceProvider or Facade manually to your `config/app.php`.

If you are running an older setup or want to register it manually:

### Manual Registration (Optional)

In `config/app.php`:

```php
'providers' => [
    ...
    engalirock\PayMob\PayMobServiceProvider::class,
    ...
];

'aliases' => [
    ...
    'PayMob' => engalirock\PayMob\Facades\PayMob::class,
    ...
];
```

---

## Configuration

Publish the config file using:

```bash
php artisan vendor:publish --provider="engalirock\PayMob\PayMobServiceProvider"
```

This will create a `config/paymob.php` file. Fill in your credentials:

```php
return [
    'username'       => env('PAYMOB_USERNAME', ''),
    'password'       => env('PAYMOB_PASSWORD', ''),
    'api_key'        => env('PAYMOB_API_KEY', ''),
    'integration_id' => env('PAYMOB_INTEGRATION_ID', ''),
    'iframe_id'      => env('PAYMOB_IFRAME_ID', ''),
    
    // IP resolution setting to bypass SSL Handshake Issues (Default: force IPv4)
    'ip_resolve'     => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
];
```

Make sure to set up your **Processed Callback** and **Response Callback** URLs in your PayMob dashboard pointing to your application routes.

---

## Modern Integration (Intentions API - Recommended)

PayMob's new **Intentions API** simplifies payment flow integration by reducing multiple backend steps into a single intention.

### 1. Request Auth Token
```php
use engalirock\PayMob\Facades\PayMob;

$auth = PayMob::authPaymob();
$token = $auth->token;
```

### 2. Create Payment Intention
Use the `intention` method to build the payment request payload:
```php
$data = [
    'amount'          => 15000, // Amount in cents/piasters (e.g. 150.00 EGP)
    'currency'        => 'EGP',
    'payment_methods' => [12345, 67890], // Array of integration IDs (Card, Wallet, etc.)
    'billing' => [
        'email'        => 'user@example.com',
        'first_name'   => 'John',
        'last_name'    => 'Doe',
        'phone_number' => '+201000000000',
        'city'         => 'Cairo',
        'country'      => 'EG',
    ],
    'customer' => [
        'first_name' => 'John',
        'last_name'  => 'Doe',
        'email'      => 'user@example.com',
    ]
];

$intention = PayMob::intention($token, $data);
```

### 3. Unified Checkout URL
Generate the client checkout URL to redirect the user to the unified checkout page:
```php
$checkoutUrl = PayMob::unifiedcheckout($publicKey, $intention->client_secret);
return redirect()->away($checkoutUrl);
```

---

## Legacy Integration (Steps to make a transaction on PayMob servers)

### 1. API Authentication Request
```php
$auth = PayMob::authPaymob();
```

### 2. Order Registration Request
```php
$paymobOrder = PayMob::makeOrderPaymob(
    $auth->token,
    $auth->profile->id,
    $order->totalCost * 100, // Total amount in cents
    $order->id // Your order ID
);
```

### 3. Payment Key Generation Request
```php
$paymentKey = PayMob::getPaymentKeyPaymob(
    $integrationId, // Card integration ID
    $auth->token,
    $order->totalCost * 100,
    $paymobOrder->id,
    $user->email,
    $user->firstname,
    $user->lastname,
    $user->phone,
    $city->name,
    $country->name
);
```

### 4. Load Iframe
Use the token from the payment key payload inside the iframe:
```html
<iframe src="https://accept.paymob.com/api/acceptance/iframes/{{ config('paymob.iframe_id') }}?payment_token={{ $paymentKey->token }}"></iframe>
```

---

## Debugging & Error Handling

If a request fails, you can inspect the response status code, curl errors, and response headers:

```php
use engalirock\PayMob\Facades\PayMob;

$response = PayMob::authPaymob();

if (!$response || isset($response->detail)) {
    Log::error('PayMob Auth Failed', [
        'http_code'  => PayMob::last_http_code,
        'curl_error' => PayMob::last_curl_error,
        'headers'    => PayMob::last_response_headers,
    ]);
}
```

---

## PayMobController

A pre-built controller template `PayMobController.php` is published inside your app directory to handle response callbacks.
Update the `processedCallback` and `invoice` routes on your PayMob dashboard integration page.

---

## Other PayMob Methods

### 1. Transaction Inquiry
Verify payment details using a PayMob order ID:
```php
$inquiry = PayMob::transaction_inquiry($token, $orderId);
```

### 2. Get All Orders
```php
$orders = PayMob::getOrders($token, $page = 1);
```

### 3. Get Specific Order
```php
$order = PayMob::getOrder($token, $orderId);
```

### 4. Get All Transactions
```php
$transactions = PayMob::getTransactions($token, $page = 1);
```

### 5. Get Specific Transaction
```php
$transaction = PayMob::getTransaction($token, $transactionId);
```

### 6. Capture Authorized Transaction
```php
$capture = PayMob::capture($token, $transactionId, $amountCents);
```

---

## License

Laravel PayMob is open-sourced software licensed under the [MIT license](LICENSE).
