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

namespace Drewlabs\HttpGuard\Contracts;

interface ServerInterface
{
    /**
     * returns the api me url
     * 
     * @return string 
     */
    public function mePath();

    /**
     * returns the token url
     * 
     * @return string 
     */
    public function tokenPath();

    /**
     * returns the revoke token url
     * 
     * @return string 
     */
    public function revokePath();
}