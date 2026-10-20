<?php

namespace App\Exceptions;

use RuntimeException;

final class OrderHasPaymentsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This order cannot be deleted because it has associated payments.');
    }
}
