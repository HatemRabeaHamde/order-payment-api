<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case Paypal = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => 'Credit Card',
            self::Paypal => 'PayPal',
        };
    }
}
