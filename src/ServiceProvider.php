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
use Drewlabs\HttpGuard\OAuth2\AuthenticatableProvider as OAuth2Provider;
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Drewlabs\HttpGuard\Memcached\Client as Memcached;
use Drewlabs\HttpGuard\Memcached\Connector;
use Drewlabs\HttpGuard\Redis\Client as Redis;

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
    }

    public function register()
    {
        $this->app->bind(ApiTokenAuthenticatableProvider::class, function ($app) {
            /** @var Repository */
            $config = $app->make('config');
            return $app->make($config->get('auth.providers.' . $config->get('auth.guards.http.driver') . '.provider', OAuth2Provider::class));
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
        /** @var Repository */
        $config = $app->make('config');
        return new RequestGuard(
            new Guard(
                $auth,
                $app->make(ApiTokenAuthenticatableProvider::class),
                $config->get('auth.defaults.guard', 'web')
            ),
            $app->make('request'),
            null
        );
    }

    // private function initializeAuthServerNodeChecker(Repository $config)
    // {
    //     $driver = $config->get('auth.guards.http.driver');
    //     $cluster = $config->get('auth.providers.' . $driver . '.hosts.cluster', []);
    //     AuthServers::setClusterAvailableNodeIfMissing();
    // }

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
        // $baseDirectory = \DIRECTORY_SEPARATOR . 'http-guard' . \DIRECTORY_SEPARATOR . 'cache';
        // $dir = $this->makeDirectory(sprintf('%s%s', rtrim($this->app->storagePath(), \DIRECTORY_SEPARATOR), $baseDirectory));
        // $path = $dir . \DIRECTORY_SEPARATOR . 'auth.dump';
        // $serversPath = $dir . \DIRECTORY_SEPARATOR . 'node.sock';
        // static function () use ($app, $serversPath) {
        //     $config = $app['config'];
        //     $driver = $config->get('auth.guards.http.driver');
        //     $primaryServer = $config->get('auth.providers.' . $driver . '.hosts.default');
        //     // $cluster = $config->get('auth.providers.' . $driver . '.hosts.cluster', []);

        //     return AuthServers::getPrimaryServer($serversPath, $primaryServer);
        // }
        return new AuthenticatableProvider(
            new Server,
            static function (array $attributes = [], ?string $token = null) use ($app) {
                $config = $app['config'];
                $driver = $config->get('auth.guards.http.driver');
                if (is_null($model = $config->get('auth.providers.' . $driver . '.model'))) {
                    $model = class_exists('\Drewlabs\OAuthUser\User') ? '\Drewlabs\OAuthUser\User' : User::class;
                }

                $properties = new JsonProperties;

                #TODO: load json properties from laravel configuration
                return new UserFactory($model, $properties);
            },
            static function () use ($app, $path): mixed {
                switch (strtolower($driver)) {
                    case 'array':
                        return CacheProvider::load($path);
                    case 'redis':
                        return new Redis($options);
                    case 'memcached':
                        $memcached = (new Connector())->connect($options['servers'] ?? [], $options['persistent_id'] ?? null, $options['options'] ?? [], $options['sasl'] ?? [] );
                        return new Memcached($memcached);
                    default:
                        return CacheProvider::load($path);
                }
            }
        );
    }
}
