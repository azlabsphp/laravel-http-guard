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

use Drewlabs\HttpGuard\Contracts\BasicAuthInterface;

final class BasicAuth implements BasicAuthInterface
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

    /**
     * return basic auth user credential
     * 
     * @return string 
     */
    public function user()
    {
        return $this->user;
    }


    /**
     * return basic auth password credential
     * 
     * @return string 
     */
    public function password()
    {
        return $this->password;
    }


    public function __toString()
    {
        return sprintf("basic %s:%s", $this->user, $this->password);
    }
}