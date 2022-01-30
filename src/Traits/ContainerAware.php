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

namespace Drewlabs\AuthHttpGuard\Traits;

use Psr\Container\ContainerInterface;

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
         * @return ContainerInterface|\Illuminate\Container\Container|mixed
         */
        return static function ($container = null) use ($abstract) {
            $laravelContainerClass = \Illuminate\Container\Container::class;
            if (null === $container && class_exists($laravelContainerClass)) {
                $container = forward_static_call([$laravelContainerClass, 'getInstance']);
            }
            if (null === $abstract) {
                return $container;
            }
            if ($container instanceof \ArrayAccess) {
                return $container[$abstract];
            }
            if (
                class_exists($laravelContainerClass) &&
                $container instanceof \Illuminate\Container\Container
            ) {
                return $container->make($abstract);
            }
            if ($container instanceof ContainerInterface) {
                return $container->get($abstract);
            }
            if (!\is_object($container)) {
                throw new \Exception('A container instance is required to create a resolver');
            }
            throw new \InvalidArgumentException(\get_class($container).' is not a '.ContainerInterface::class.' nor '.\Illuminate\Container\Container::class.' and is not array accessible');
        };
    }
}
