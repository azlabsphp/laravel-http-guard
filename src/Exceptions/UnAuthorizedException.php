<?php

namespace Drewlabs\AuthHttpGuard\Exceptions;

use Exception;
use Throwable;

class UnAuthorizedException extends Exception
{
    public function __construct(string $token, int $statusCode = 401)
    {
        $message = "Authentication server returns a 401 response for request with token $token";
        parent::__construct($message, $statusCode, null);
    }
}