<?php

use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PaypalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Each entry maps a payment method value to the strategy class that
    | implements PaymentGatewayInterface, plus its credentials. To add a
    | new gateway: create a class implementing PaymentGatewayInterface,
    | add a case to App\Enums\PaymentMethod, then register it here.
    |
    */

    'gateways' => [
        'credit_card' => [
            'class' => CreditCardGateway::class,
            'key' => env('CREDIT_CARD_GATEWAY_KEY'),
            'secret' => env('CREDIT_CARD_GATEWAY_SECRET'),
        ],

        'paypal' => [
            'class' => PaypalGateway::class,
            'key' => env('PAYPAL_GATEWAY_KEY'),
            'secret' => env('PAYPAL_GATEWAY_SECRET'),
        ],
    ],

];
