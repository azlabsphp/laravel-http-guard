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
use Drewlabs\HttpGuard\OAuth2\Server as OAuth2Server;

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

        $this->app->register(AuthenticatableProvider::class, function ($app) {
            return $this->createAuthenticatableProvider($app);
        });

        $this->app->register(OAuth2Provider::class, function ($app) {
            $config = $app->make('config');
            $name = $config->get('auth.guards.http.driver');
            $driver = $config->get(sprintf('auth.providers.%s.cache.driver', $name), null);
            $baseDirectory = $this->baseDirectory();
            $cachePath = $config->get(sprintf('auth.providers.%s.cache.path', $name), "$baseDirectory" . \DIRECTORY_SEPARATOR . "auth.dump");
            $serversPath = $baseDirectory . \DIRECTORY_SEPARATOR . 'node.sock';
            $primaryServer = $config->get('auth.providers.' . $name . '.hosts.primary');

            $server = new OAuth2Server(function () use ($serversPath, $primaryServer) {
                return AuthServers::getPrimaryServer($serversPath, $primaryServer);
            });

            list($user, $password) = [$config->get(sprintf('auth.providers.%s.oauth2.client_id', $name)), $config->get(sprintf('auth.providers.%s.oauth2.client_secret', $name))];

            if (is_string($user) && is_string($password)) {
                $server = $server->withBasicAuth($user, $password);
            }

            return new OAuth2Provider($server, $driver ? $this->cacheDriverFactory($config, $driver, $cachePath) : null);
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
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return AuthenticatableProvider
     */
    private function createAuthenticatableProvider($app)
    {
        /** @var Repository */
        $config = $app->make('config');
        $name = $config->get('auth.guards.http.driver');
        $cacheDriver = $config->get(sprintf('auth.providers.%s.cache.driver', $name), null);
        $json = $config->get(sprintf('auth.providers.%s.json', $name), []);

        $baseDirectory = $this->baseDirectory();
        $cachePath = $config->get(sprintf('auth.providers.%s.cache.path', $name), "$baseDirectory" . \DIRECTORY_SEPARATOR . "auth.dump");

        $serversPath = $baseDirectory . \DIRECTORY_SEPARATOR . 'node.sock';
        $primaryServer = $config->get('auth.providers.' . $name . '.hosts.primary');

        return new AuthenticatableProvider(
            new Server(static function () use ($serversPath, $primaryServer) {
                return AuthServers::getPrimaryServer($serversPath, $primaryServer);
            }),
            static function (array $attributes = [], ?string $token = null) use ($app, $json) {
                $config = $app->make('config');
                $driver = $config->get('auth.guards.http.driver');


                if ($className = $config->get('auth.providers.' . $driver . '.user_factory')) {
                    return $app->make($className)->create($attributes, $token);
                }

                $properties = new JsonProperties;
                if (is_array($json) && !empty($json)) {

                    if (array_key_exists('sub', $json)) {
                        $properties = $properties->setUserIdProperty($json['sub']);
                    }

                    if (array_key_exists('name', $json)) {
                        $properties = $properties->setUsernameProperty($json['name']);
                    }

                    if (array_key_exists('password', $json)) {
                        $properties = $properties->setPasswordProperty($json['password']);
                    }

                    if (array_key_exists('token', $json)) {
                        $properties = $properties->setAccessTokenProperty($json['token']);
                    }

                    if (array_key_exists('remember', $json)) {
                        $properties = $properties->setRememberTokenProperty($json['remember']);
                    }

                    if (array_key_exists('authorizations', $json)) {
                        $properties = $properties->setAuthorizationsProperty($json['authorizations']);
                    }

                    if (array_key_exists('groups', $json)) {
                        $properties = $properties->setGroupsProperty($json['groups']);
                    }
                }

                $factory =  new UserFactory($config->get('auth.providers.' . $driver . '.model', User::class), $properties);

                return $factory->create($attributes, $token);
            },
            $cacheDriver ? $this->cacheDriverFactory($config, $cacheDriver, $cachePath) : null
        );
    }


    /**
     * return a cache driver matching the provided driver name
     * 
     * @param Repository $config 
     * @param string $driver 
     * @param string $path 
     * @return CacheProvider|Redis|Memcached 
     */
    private function cacheDriverFactory(Repository $config, string $driver, string $path)
    {
        switch (strtolower($driver)) {
            case 'array':
                return CacheProvider::load($path);
            case 'redis':
                return new Redis($config->get('cache.stores.redis', []));
            case 'memcached':
                $options = $config->get('cache.stores.memcached', []);
                $memcached = (new Connector())->connect(
                    $options['servers'] ?? [],
                    $options['persistent_id'] ?? null,
                    $options['options'] ?? [],
                    $options['sasl'] ?? []
                );
                return new Memcached($memcached);
            default:
                throw new \RuntimeException('unsupported cache driver');
        }
    }

    /**
     * returns the base http guard directory
     * 
     * @return string 
     */
    private function baseDirectory()
    {
        $dirname = \DIRECTORY_SEPARATOR . 'http-guard' . \DIRECTORY_SEPARATOR . 'cache';
        return $this->makeDirectory(sprintf('%s%s', rtrim($this->app->storagePath(), \DIRECTORY_SEPARATOR), $dirname));
    }
}
