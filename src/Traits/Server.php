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

namespace Drewlabs\HttpGuard\Traits;


/**
 * @method string getBaseUrl()
 */
trait Server
{
    /** @var string */
    private $me = 'api/me';

    /** @var string */
    private $token = 'oauth/token';

    /** @var string */
    private $logout = 'oauth/revoke';

    public function mePath()
    {
        return $this->url($this->me);
    }

    public function tokenPath()
    {
        return $this->url($this->token);
    }

    public function revokePath()
    {
        return $this->url($this->logout);
    }

    /**
     * create endpoint url from the provided path value
     * 
     * @param string $path 
     * @return string 
     */
    private function url(string $path)
    {
        return sprintf('%s/%s', rtrim($this->getBaseUrl()), ltrim($path ?? '', '/'));
    }
}