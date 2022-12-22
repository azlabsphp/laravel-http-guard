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
use Drewlabs\AuthHttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\AuthHttpGuard\Contracts\UserFactory;
use Drewlabs\AuthHttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\AuthHttpGuard\Exceptions\ServerException;
use Drewlabs\AuthHttpGuard\Exceptions\UnAuthorizedException;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\HttpClient\Contracts\HttpClientInterface;
use Drewlabs\HttpClient\Core\HttpClientCreator;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;

final class AuthenticatableProvider implements ApiTokenAuthenticatableProvider
{
    /**
     * @var AuthenticatableCacheProvider|\Closure
     */
    private $cacheProvider;

    /**
     * @var HttpClientInterface|\Closure
     */
    private $client;

    /**
     * @var bool
     */
    private $useCache = false;

    /**
     * 
     * @var UserFactory|\Closure
     */
    private $userFactory;

    /**
     * Creates authenticatable provider instance
     * 
     * @param AuthenticatableCacheProvider|\Closure $cacheProvider 
     * @param UserFactory|\Closure|null $userFactory 
     * @param null|HttpClientInterface|\Closure $client 
     * @return self
     */
    public function __construct($cacheProvider = null, $userFactory = null, $client = null)
    {
        try {
            $this->userFactory = $userFactory ?? new DefaultUserFactory;
            $this->cacheProvider = $cacheProvider ?? ArrayCacheProvider::load();
            $this->client = $client ?? function () {
                return HttpClientCreator::createHttpClient(AuthServerNodesChecker::getAuthServerNode());
            };
        } catch (\RuntimeException $e) {
            $this->useCache = true;
        }
    }

    public function __destruct()
    {
        if (($cache = $this->getCacheProvider()) instanceof ArrayCacheProvider) {
            $cache->prune();
            ArrayCacheProvider::dump($cache);
        }
    }

    /**
     * Set the user factory object to use to creates the authenticatable instance
     * 
     * @param UserFactory $userFactory 
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
     * Revoke the connected user auth token
     * 
     * @param string $token 
     * @return void 
     * @throws UnAuthorizedException 
     * @throws BadResponseException 
     * @throws ServerException 
     * @throws GuzzleException 
     */
    public function revokeOAuthToken(string $token)
    {
        try {
            $this->getClient()->withBearerToken($token)->get(HttpGuardGlobals::revokePath());
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            if (401 === $response->getStatusCode()) {
                throw new UnAuthorizedException($token, $response->getStatusCode());
            }
            throw $e;
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
            $response = $this->getClient()->withBearerToken($token)->get(HttpGuardGlobals::userPath());
            // We call the user factory create() method to build the current user from the 
            // response body of the HTTP request
            $user = is_callable($this->userFactory) ?
                ($this->userFactory)(json_decode($response->getBody()->getContents(), true), $token)
                : $this->userFactory->create(json_decode($response->getBody()->getContents(), true), $token);
            if (HttpGuardGlobals::usesCache() && $this->isAuthenticatable($user)) {
                $this->getCacheProvider()->write($token, $user);
            }
            return $user;
        } catch (BadResponseException $e) {
            if (!$e->hasResponse()) {
                return null;
            }
            $response = $e->getResponse();
            if (401 === $response->getStatusCode()) {
                throw new UnAuthorizedException($token, $response->getStatusCode());
            }
            return null;
        } catch (ServerBadResponseException $e) {
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
        return $this->getCacheProvider()->read($token);
    }

    /**
     * 
     * @return AuthenticatableCacheProvider 
     */
    public function getCacheProvider()
    {
        if (is_a($this->cacheProvider, \Closure::class)) {
            return ($this->cacheProvider)();
        }
        return $this->cacheProvider;
    }

    /**
     * 
     * @return HttpClientInterface 
     */
    public function getClient()
    {
        if (is_a($this->client, \Closure::class)) {
            return ($this->client)();
        }
        return $this->client;
    }

    /**
     * Returns true if the $instance is instance of authenticatable class 
     * 
     * @param c|Authenticatable $instance 
     * @return bool 
     */
    private function isAuthenticatable($instance)
    {
        return is_a($instance, BaseAuthenticatable::class, true) ||
            is_a($instance, Authenticatable::class, true);
    }
}
