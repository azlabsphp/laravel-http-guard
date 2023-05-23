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

class AuthenticatableNotFoundException extends \Exception
{
    /**
     * Creates class instance
     * 
     * @param string $id 
     */
    public function __construct(string $id)
    {
        $message = "No user found matching provided $id token";
        parent::__construct($message);
    }
}
