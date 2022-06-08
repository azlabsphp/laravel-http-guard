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

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\AuthHttpGuard\Contracts\UserFactory;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use InvalidArgumentException;

class ServiceProvider extends SupportServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('http', function ($app) use ($auth) {
                return tap($this->createGuardInstance($app, $auth), static function ($guard) use ($app) {
                    $app->refresh('request', $guard, 'setRequest');
                });
            });
        });

        if (!$this->app->runningInConsole()) {
            $this->initializeAuthServerNodeChecker();
        }
    }

    public function register()
    {
        $this->app->bind(ApiTokenAuthenticatableProvider::class, static function ($app) {
            return new AuthenticatableProvider(
                function () use ($app) {
                    $factory = new CacheProviderFactory();
                    $config = $app['config'];
                    // Load memcached configurations
                    HttpGuardGlobals::forMemcached($config['database.stores.memcached']);
                    // Load user configuration
                    $name = HttpGuardGlobals::guard() ?? 'http';
                    $driver = $config->get('auth.guards.' . $name . '.driver');
                    $model = $config->get('auth.providers.' . $driver . '.model');
                    $authServerNode = $config->get('auth.providers.' . $driver . '.hosts.default');
                    $cluster = $config->get('auth.providers.' . $driver . '.hosts.cluster');
                    HttpGuardGlobals::authenticatableClass($model ?? (class_exists(\Drewlabs\OAuthUser\User::class) ? Drewlabs\OAuthUser\User::class : User::class));
                    HttpGuardGlobals::defaultAuthServerNode($authServerNode);
                    HttpGuardGlobals::hosts($cluster);
                    $factory->make(HttpGuardGlobals::defaultCacheDriver());
                },
                function (array $attributes = [], ?string $token = null) use ($app) {
                    /**
                     * @var UserFactory
                     */
                    $userFactory = null;
                    if ($app->bound(UserFactory::class)) {
                        $userFactory = $app[UserFactory::class];
                    }
                    if (null === $userFactory) {
                        $config = $app['config'];
                        $driver = $config->get('auth.guards.' . (HttpGuardGlobals::guard() ?? 'http') . '.driver');
                        $userFactoryClass = $config->get('auth.providers.' . $driver . '.userFactory');
                        if ($userFactoryClass && class_exists($userFactoryClass)) {
                            $userFactory = $app[$userFactoryClass];
                        }
                    }
                    if (null === $userFactory) {
                        /**
                         * @var UserFactory
                         */
                        $userFactory = $app[DefaultUserFactory::class];
                    }

                    if (!is_a($userFactory, UserFactory::class) && !is_callable($userFactory)) {
                        throw new InvalidArgumentException('User Factory must be an istance of ' . UserFactory::class . ' or callable, instance of ' . (is_object($userFactory) && !is_null($userFactory) ? get_class($userFactory) : gettype($userFactory)));
                    }

                    return is_callable($userFactory) ? ($userFactory)($attributes, $token) : $userFactory->create($attributes, $token);
                }
            );
        });
    }

    /**
     * Register the guard.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return RequestGuard
     */
    private function createGuardInstance($app, $auth)
    {
        return new RequestGuard(
            new Guard($auth, $app[ApiTokenAuthenticatableProvider::class]),
            $app['request'],
            null
        );
    }

    private function initializeAuthServerNodeChecker()
    {
        AuthServerNodesChecker::setClusterAvailableNodeIfMissing();
    }
}
