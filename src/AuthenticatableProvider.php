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

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Curl\REST\Client;
use Drewlabs\Curl\REST\Exceptions\BadRequestException;
use Drewlabs\Curl\REST\Exceptions\RequestException;
use Drewlabs\Curl\REST\Response;
use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\HttpGuard\Contracts\UserFactory as AbstractUserFactory;
use Drewlabs\HttpGuard\Exceptions\ServerException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;

final class AuthenticatableProvider implements ApiTokenAuthenticatableProvider
{
    /**
     * @var AuthenticatableCacheProvider|\Closure
     */
    private $cacheProvider;

    /**
     * @var string
     */
    private $host;

    /**
     * @var bool
     */
    private $useCache = false;

    /**
     * @var UserFactory|\Closure
     */
    private $userFactory;

    /**
     * creates authenticatable provider instance.
     *
     * @param AuthenticatableCacheProvider|\Closure $cacheProvider
     * @param AbstractUserFactory|\Closure|null             $userFactory
     * @param string|\Closure|null                  $host
     *
     * @return static
     */
    public function __construct($cacheProvider = null, $userFactory = null, $host = null)
    {
        try {
            $this->userFactory = $userFactory ?? new UserFactory;
            $this->cacheProvider = $cacheProvider;
            $this->host = $host;
        } catch (\Throwable $e) {
            $this->useCache = true;
        }
    }

    public function __destruct()
    {
        if (($cache = $this->cacheProvider) instanceof ArrayCacheProvider) {
            $cache->prune();
            ArrayCacheProvider::dump($cache);
        }
    }

    /**
     * set the user factory object to use to creates the authenticatable instance.
     *
     * @return static
     */
    public function setUserFactory(UserFactory $userFactory)
    {
        if (null !== $userFactory) {
            $this->userFactory = $userFactory;
        }

        return $this;
    }

    /**
     * revokes the connected user auth token.
     *
     * @throws AuthorizationException
     * @throws RequestException
     * @throws ServerException
     *
     * @return void
     */
    public function revokeOAuthToken(string $token)
    {
        try {
            Client::new()->withBearerToken($token)->get($this->makeRequestURL(Globals::revokeTokenPath()));
        } catch (RequestException $e) {
            if (401 === $e->getStatus()) {
                throw new AuthorizationException($token, $e->getStatus());
            }
            throw $e;
        } catch (BadRequestException $e) {
            $response = $e->getResponse();
            if (401 === $response->getStatus()) {
                throw new AuthorizationException($token, $response->getStatus());
            }

            return null;
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getByOAuthToken(string $token): ?Authenticatable
    {
        if (Globals::usesCache() && $this->useCache) {
            return $this->getAuthenticatableFromCache($token);
        }

        try {
            /** @var Response */
            $response = Client::new()->withBearerToken($token)->get($this->makeRequestURL(Globals::userPath()));
            $user = \is_callable($this->userFactory) ?
                ($this->userFactory)($response->getBody(), $token)
                : $this->userFactory->create($response->getBody(), $token);
            if (Globals::usesCache() && $this->isAuthenticatable($user)) {
                $this->getCacheProvider()->write($token, $user);
            }

            return $user;
        } catch (RequestException $e) {
            if (401 === $e->getStatus()) {
                throw new AuthorizationException($token, $e->getStatus());
            }

            return null;
        } catch (BadRequestException $e) {
            $response = $e->getResponse();
            if (401 === $response->getStatus()) {
                throw new AuthorizationException($token, $response->getStatus());
            }

            return null;
        } catch (\Exception $e) {
            if (Globals::usesCache()) {
                return $this->getAuthenticatableFromCache($token);
            }
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getAuthenticatableFromCache(string $token)
    {
        $cacheProvider = $this->getCacheProvider();
        $user = $cacheProvider->read($token);
        if (($user instanceof User) && $user->tokenExpires()) {
            $cacheProvider->delete($token);
            throw new TokenExpiresException($token);
        }
        return $user;
    }

    /**
     * @return AuthenticatableCacheProvider
     */
    public function getCacheProvider()
    {
        if (is_a($this->cacheProvider, \Closure::class)) {
            $this->cacheProvider = ($this->cacheProvider)();
        }

        return $this->cacheProvider;
    }

    /**
     * returns true if the $instance is instance of authenticatable class.
     *
     * @param Authenticatable $instance
     *
     * @return bool
     */
    private function isAuthenticatable($instance)
    {
        return is_a($instance, BaseAuthenticatable::class, true) || is_a($instance, Authenticatable::class, true);
    }

    /**
     * @internal
     * 
     * @return string
     */
    private function makeRequestURL(string $path)
    {
        $host = is_a($this->host, \Closure::class) ? ($this->host)() : $this->host;

        return sprintf('%s/%s', rtrim($host ?? ''), ltrim($path ?? '', '/'));
    }
}
