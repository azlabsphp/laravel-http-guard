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

interface BasicAuthInterface
{
    /**
     * return basic auth user credential
     * 
     * @return string 
     */
    public function user();


    /**
     * return basic auth password credential
     * 
     * @return string 
     */
    public function password();
}