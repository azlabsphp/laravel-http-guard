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

namespace Drewlabs\HttpGuard;

use Closure;
use Drewlabs\HttpGuard\Contracts\ServerInterface;
use Drewlabs\HttpGuard\Traits\Server as ServerTrait;

final class Server implements ServerInterface
{
    use ServerTrait;

    /** @var string */
    private $base_url;

    /** @var string|Closure  */
    private $base_url_factory;

    /**
     * creates server class instance
     * 
     * @param string|Closure $base_url
     * 
     * @return void 
     */
    public function __construct($base_url)
    {
        $this->base_url_factory = $base_url;
    }

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

}
