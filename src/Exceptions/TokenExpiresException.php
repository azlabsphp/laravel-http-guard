<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\AuthHttpGuard\Exceptions;

class TokenExpiresException extends \Exception
{
    public function __construct(string $token, int $statusCode = 401)
    {
        $message = "Authentication token $token has expired";
        parent::__construct($message, $statusCode, null);
    }
}
