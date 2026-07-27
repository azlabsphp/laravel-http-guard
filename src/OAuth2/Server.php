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

use Closure;

final class Server
{
    /** @var string */
    private $me = 'api/me';

    /** @var string */
    private $token = 'oauth/token';

    /** @var string */
    private $logout = 'oauth/revoke';

    /** @var BasicAuth */
    private $auth;

    /** @var string */
    private $base_url;

    /**
     * creates server class instance
     * 
     * @param string|Closure $base_url_factory
     * 
     * @return void 
     */
    public function __construct(private $base_url_factory) {}

    /**
     * return the server base url
     * 
     * @return string 
     */
    public function getBaseUrl()
    {
        if (is_null($this->base_url)) {
            $this->base_url = call_user_func($this->base_url_factory);
        }

        return $this->base_url;
    }

    /**
     * set basic authorization credential on the server instance
     * 
     * @param string $user 
     * @param string $password 
     * @return void 
     */
    public function withBasicAuth(string $user, string $password)
    {
        $this->auth = new BasicAuth($user, $password);

        return $this;
    }


    public function auth()
    {
        return $this->auth;
    }

    public function mePath()
    {
        return $this->me;
    }


    public function tokenPath()
    {
        return $this->token;
    }


    public function revokePath()
    {
        return $this->logout;
    }
}
