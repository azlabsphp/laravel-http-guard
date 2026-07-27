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

use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\Contracts\UserFactory;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

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
        $this->app->bind(ApiTokenAuthenticatableProvider::class, function ($app) {
            $factory = $app->make('config')->config->get('auth.providers.' . $config->get('auth.guards.http.driver') . '.factory');
            if (null === $factory || !\is_callable($factory)) {
                return $this->createAuthenticatableProvider($app);
            }

            return \call_user_func_array($factory, [$app]);
        });
    }

    /**
     * Register the guard.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return RequestGuard
     */
    private function createGuardInstance($app, $auth)
    {
        return new RequestGuard(new Guard($auth, $app->make(ApiTokenAuthenticatableProvider::class)), $app->make('request'), null);
    }

    private function initializeAuthServerNodeChecker(Repository $config)
    {
        $driver = $config->get('auth.guards.http.driver');
        $cluster = $config->get('auth.providers.' . $driver . '.hosts.cluster', []);
        AuthServers::setClusterAvailableNodeIfMissing();
    }

    /**
     * Creates directory if exists.
     *
     * @return string
     */
    private function makeDirectory(string $dir)
    {
        if (is_dir($dir)) {
            return $dir;
        }
        if (@mkdir($dir, 0775, true)) {
            return $dir;
        }

        return $dir;
    }

    /**
     * Creates an instance of authenticatable provider.
     *
     * @param mixed $app
     *
     * @return AuthenticatableProvider
     */
    private function createAuthenticatableProvider($app)
    {
        $baseDirectory = \DIRECTORY_SEPARATOR . 'http-guard' . \DIRECTORY_SEPARATOR . 'cache';
        $dir = $this->makeDirectory(sprintf('%s%s', rtrim($this->app->storagePath(), \DIRECTORY_SEPARATOR), $baseDirectory));
        $path = $dir . \DIRECTORY_SEPARATOR . 'auth.dump';
        $serversPath = $dir . \DIRECTORY_SEPARATOR . 'node.sock';

        return new AuthenticatableProvider(
            static function () use ($app, $path): mixed {
                switch (strtolower($driver)) {
                    case 'array':
                        return ArrayCacheProvider::load($path);
                    case 'redis':
                        return new RedisCacheProvider($options);
                    case 'memcached':
                        $memcached = (new MemcachedConnector())->connect(
                            $options['servers'] ?? [],
                            $options['persistent_id'] ?? null,
                            $options['options'] ?? [],
                            $options['sasl'] ?? []
                        );

                        return new MemcachedCacheProvider($memcached);
                    default:
                        return ArrayCacheProvider::load($path);
                }
            },
            static function (array $attributes = [], ?string $token = null) use ($app) {
                $config = $app['config'];
                $driver = $config->get('auth.guards.http.driver');
                if (is_null($model = $config->get('auth.providers.' . $driver . '.model'))) {
                    $model = class_exists('\Drewlabs\OAuthUser\User') ? '\Drewlabs\OAuthUser\User' : User::class;
                }

                Globals::authenticatable($model);

                $userFactory = $app->bound(UserFactory::class) ? $app[UserFactory::class] : null;
                if (null === $userFactory) {
                    $userFactoryClass = $config->get('auth.providers.' . $driver . '.userFactory');
                    if ($userFactoryClass) {
                        $userFactory = \is_string($userFactoryClass) && class_exists($userFactoryClass) ? $app[$userFactoryClass] : $userFactoryClass;
                    }
                }
                if (null === $userFactory) {
                    $userFactory = $app[DefaultUserFactory::class];
                }
                if (!is_a($userFactory, UserFactory::class) && !\is_callable($userFactory)) {
                    throw new \InvalidArgumentException('User Factory must be an istance of ' . UserFactory::class . ' or callable, instance of ' . (\is_object($userFactory) && null !== $userFactory ? $userFactory::class : \gettype($userFactory)));
                }

                return \is_callable($userFactory) ? ($userFactory)($attributes, $token) : $userFactory->create($attributes, $token);
            },
            static function () use ($app, $serversPath) {
                $config = $app['config'];
                $driver = $config->get('auth.guards.http.driver');
                $primaryServer = $config->get('auth.providers.' . $driver . '.hosts.default');
                $cluster = $config->get('auth.providers.' . $driver . '.hosts.cluster', []);

                return AuthServers::getPrimaryServer($serversPath, $primaryServer);
            }
        );
    }
}
