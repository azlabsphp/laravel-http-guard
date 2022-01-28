<?php

namespace Drewlabs\AuthHttpGuard\Exceptions;

use Exception;

class AuthenticatableNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        $message = "No user found matching provided $id token";

        parent::__construct($message);
    }
}