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

namespace Drewlabs\HttpGuard\OAuth2;

final class BasicAuth
{

    /**
     * creates auth credentials class
     * 
     * @param string $user 
     * @param string $password 
     * @return void 
     */
    public function __construct(private string $user, private string $password)
    {
    }

    public function user()
    {
        return $this->user;
    }


    public function password()
    {
        return $this->password;
    }
    
    /**
     * 
     * @return string 
     */
    public function toAuthorizationHeader(): string
    {
        return sprintf("basic %s:%s", $this->user, $this->password);
    }


    public function __toString()
    {
        return $this->toAuthorizationHeader();
    }
}