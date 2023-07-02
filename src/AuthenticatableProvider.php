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
use Drewlabs\HttpGuard\Contracts\UserFactory;
use Drewlabs\HttpGuard\Exceptions\ServerException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\UnAuthorizedException;
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
     * Creates authenticatable provider instance.
     *
     * @param AuthenticatableCacheProvider|\Closure $cacheProvider
     * @param UserFactory|\Closure|null             $userFactory
     * @param string|\Closure|null                  $host
     *
     * @return self
     */
    public function __construct($cacheProvider = null, $userFactory = null, $host = null)
    {
        try {
            $this->userFactory = $userFactory ?? new DefaultUserFactory();
            $this->cacheProvider = $cacheProvider ?? ArrayCacheProvider::load();
            $this->host = $host ?? static function () {
                return AuthServerNodesChecker::getAuthServerNode();
            };
        } catch (\RuntimeException $e) {
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
     * Set the user factory object to use to creates the authenticatable instance.
     *
     * @return self
     */
    public function setUserFactory(UserFactory $userFactory)
    {
        if (null !== $userFactory) {
            $this->userFactory = $userFactory;
        }

        return $this;
    }

    /**
     * Revoke the connected user auth token.
     *
     * @throws UnAuthorizedException
     * @throws RequestException
     * @throws ServerException
     *
     * @return void
     */
    public function revokeOAuthToken(string $token)
    {
        try {
            Client::new()->withBearerToken($token)->get($this->makeRequestURL(HttpGuardGlobals::revokePath()));
        } catch (RequestException $e) {
            if (401 === $e->getStatus()) {
                throw new UnAuthorizedException($token, $e->getStatus());
            }
            throw $e;
        } catch (BadRequestException $e) {
            $response = $e->getResponse();
            if (401 === $response->getStatus()) {
                throw new UnAuthorizedException($token, $response->getStatus());
            }

            return null;
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getByOAuthToken(string $token): ?Authenticatable
    {
        // GET USER FROM CACHE IF AUTH SERVERS ARE NOT AVAILABLE
        if (HttpGuardGlobals::usesCache() && $this->useCache) {
            return $this->getAuthenticatableFromCache($token);
        }
        try {
            /**
             * @var Response
             */
            $response = Client::new()->withBearerToken($token)->get($this->makeRequestURL(HttpGuardGlobals::userPath()));
            // We call the user factory create() method to build the current user from the
            // response body of the HTTP request
            $user = \is_callable($this->userFactory) ?
                ($this->userFactory)($response->getBody(), $token)
                : $this->userFactory->create($response->getBody(), $token);
            if (HttpGuardGlobals::usesCache() && $this->isAuthenticatable($user)) {
                $this->getCacheProvider()->write($token, $user);
            }

            return $user;
        } catch (RequestException $e) {
            if (401 === $e->getStatus()) {
                throw new UnAuthorizedException($token, $e->getStatus());
            }

            return null;
        } catch (BadRequestException $e) {
            $response = $e->getResponse();
            if (401 === $response->getStatus()) {
                throw new UnAuthorizedException($token, $response->getStatus());
            }

            return null;
        } catch (\Exception $e) {
            if (HttpGuardGlobals::usesCache()) {
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
            // Case the token has expired, we remove the authenticatable instance from cache
            $cacheProvider->delete($token);
            throw new TokenExpiresException($token);
        }
    }

    /**
     * @return AuthenticatableCacheProvider
     */
    public function getCacheProvider()
    {
        if (is_a($this->cacheProvider, \Closure::class)) {
            // We resolve the instance from the closure so that the next calls
            // uses the provideded instance
            $this->cacheProvider = ($this->cacheProvider)();
        }

        return $this->cacheProvider;
    }

    /**
     * @return string
     */
    private function makeRequestURL(string $path)
    {
        $host = is_a($this->host, \Closure::class) ? ($this->host)() : $this->host;

        return sprintf('%s/%s', rtrim($host ?? ''), ltrim($path ?? '', '/'));
    }

    /**
     * Returns true if the $instance is instance of authenticatable class.
     *
     * @param Authenticatable $instance
     *
     * @return bool
     */
    private function isAuthenticatable($instance)
    {
        return is_a($instance, BaseAuthenticatable::class, true) || is_a($instance, Authenticatable::class, true);
    }
}
