<?php

namespace Drewlabs\AuthHttpGuard\Exceptions;

use Exception;

class TokenExpiresException extends Exception
{
    public function __construct(string $token, int $statusCode = 401)
    {
        $message = "Authentication token $token has expired";
        parent::__construct($message, $statusCode, null);
    }
}