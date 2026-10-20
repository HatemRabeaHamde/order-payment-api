<?php

namespace App\Exceptions;

use RuntimeException;

final class UnsupportedPaymentGatewayException extends RuntimeException
{
    public function __construct(string $method)
    {
        parent::__construct("No payment gateway is configured for method [{$method}].");
    }
}
