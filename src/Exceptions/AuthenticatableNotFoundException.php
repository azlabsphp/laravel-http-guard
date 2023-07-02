<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\HttpGuard\Exceptions;

class AuthenticatableNotFoundException extends \Exception
{
    /**
     * Creates class instance.
     */
    public function __construct(string $id)
    {
        $message = "No user found matching provided $id token";
        parent::__construct($message);
    }
}
