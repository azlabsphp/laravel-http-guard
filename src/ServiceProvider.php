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
        // #region Declares or defines the auth cache and server node cache paths
        $cacheDir = \DIRECTORY_SEPARATOR . 'http-guard' . \DIRECTORY_SEPARATOR . 'cache';
        if (is_dir($this->app->storagePath()) && (null !== ($cacheDirPath = $this->makeDirectory(sprintf('%s%s', rtrim($this->app->storagePath(), \DIRECTORY_SEPARATOR), $cacheDir))))) {
            HttpGuardGlobals::cachePath($cacheDirPath . \DIRECTORY_SEPARATOR . 'auth.dump');
            HttpGuardGlobals::nodeServerCachePath($cacheDirPath . \DIRECTORY_SEPARATOR . 'node.sock');
        }
        // #endregion Declares or defines the auth cache and server node cache paths
        if (!$this->app->runningInConsole()) {
            $this->initializeAuthServerNodeChecker();
        }
    }

    public function register()
    {
        $this->app->bind(ApiTokenAuthenticatableProvider::class, function ($app) {
            $config = $app['config'];
            $factory = $config->get('auth.providers.' . $config->get('auth.guards.' . (HttpGuardGlobals::guard() ?? 'http') . '.driver') . '.factory');
            if (is_null($factory) || !is_callable($factory)) {
                return $this->createAuthenticatableProvider($app);
            }
            return call_user_func_array($factory, [$app]);
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
        return new RequestGuard(new Guard($auth, $app[ApiTokenAuthenticatableProvider::class]), $app['request'], null);
    }

    private function initializeAuthServerNodeChecker()
    {
        AuthServerNodesChecker::setClusterAvailableNodeIfMissing();
    }

    /**
     * Creates directory if exists.
     *
     * @return string|null
     */
    private function makeDirectory(string $dir)
    {
        if (is_dir($dir)) {
            return $dir;
        }
        if (@mkdir($dir, 0775, true)) {
            return $dir;
        }

        return null;
    }

    /**
     * Creates an instance of authenticatable provider
     * 
     * @param mixed $app 
     * @return AuthenticatableProvider 
     */
    private function createAuthenticatableProvider($app)
    {
        return new AuthenticatableProvider(
            static function () use ($app) {
                // #region Set global cache configuration
                $config = $app['config'];
                HttpGuardGlobals::forMemcached($config['database.stores.memcached']);
                // #endregion Set global cache configuration
                return $app[CacheProviderFactory::class]->make(HttpGuardGlobals::defaultCacheDriver());
            },
            static function (array $attributes = [], string $token = null) use ($app) {
                // #region Define user global configurations
                $config = $app['config'];
                $driver = $config->get('auth.guards.' . (HttpGuardGlobals::guard() ?? 'http') . '.driver');
                $model = $config->get('auth.providers.' . $driver . '.model');
                HttpGuardGlobals::authenticatable($model ?? (class_exists(\Drewlabs\OAuthUser\User::class) ? Drewlabs\OAuthUser\User::class : User::class));
                // #endregion Define user global configurations
                $userFactory = null;
                if ($app->bound(UserFactory::class)) {
                    $userFactory = $app[UserFactory::class];
                }
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
            static function () use ($app) {
                $config = $app['config'];
                $driver = $config->get('auth.guards.' . (HttpGuardGlobals::guard() ?? 'http') . '.driver');
                HttpGuardGlobals::defaultAuthServerNode($config->get('auth.providers.' . $driver . '.hosts.default'));
                HttpGuardGlobals::hosts($config->get('auth.providers.' . $driver . '.hosts.cluster', []));

                return AuthServerNodesChecker::getAuthServerNode();
            }
        );
    }
}
