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

trait ContainerAware
{
    /**
     * @template T
     * 
     * @param class-string<T> $abstract
     *
     * @return \Closure($context = null): T|null
     */
    public static function createResolver($abstract)
    {
        return static function ($context = null) use ($abstract) {
            return $context ? $context->get($abstract) : self::getContainerInstance()->make($abstract);
        };
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
     */
    private static function getContainerInstance()
    {
        return forward_static_call(['\Illuminate\Container\Container', 'getInstance']);
    }
}
