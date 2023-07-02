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
     * @param mixed $abstract
     *
     * @return \Closure
     */
    public static function createResolver($abstract = null)
    {
        /*
         * @return \Illuminate\Contracts\Container\Container|mixed
         */
        return static function ($context = null) use ($abstract) {
            if ($context) {
                return null === $abstract ? $context : $context->get($abstract);
            }

            return null === $abstract ? self::getContainerInstance() : self::getContainerInstance()->make($abstract);
        };
    }

    /**
     * @return \Illuminate\Contracts\Container\Container
     */
    private static function getContainerInstance()
    {
        return forward_static_call([\Illuminate\Container\Container::class, 'getInstance']);
    }
}
