<?php

namespace App\Exceptions;

use RuntimeException;

final class OrderNotConfirmedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Payments can only be processed for orders in the confirmed status.');
    }
}
