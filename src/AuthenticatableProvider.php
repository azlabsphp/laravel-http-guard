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
use Drewlabs\HttpGuard\Contracts\ServerInterface;
use Drewlabs\HttpGuard\Exceptions\ServerException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;
use Drewlabs\HttpGuard\Contracts\UserFactory as AbstractUserFactory;

final class AuthenticatableProvider implements ApiTokenAuthenticatableProvider
{
    /**
     * @var AuthenticatableCacheProvider|\Closure
     */
    private $cache;

    /** @var ServerInterface */
    private $server;

    /** @var 'cache'|'request' */
    private $priority;

    /** @var AbstractUserFactory|\Closure */
    private $factory;

    /**
     * creates authenticatable provider instance.
     *
     * @param AuthenticatableCacheProvider|\Closure $cache
     * @param AbstractUserFactory|\Closure $factory             User factory dependency
     *
     */
    public function __construct(ServerInterface $server, $factory, $cache = null, string $priority = 'request')
    {
        $this->server = $server;
        $this->cache = $cache;
        $this->priority = $priority;
        $this->factory = $factory;
    }

    /**
     * revokes the connected user auth token.
     *
     * @throws AuthorizationException
     * @throws RequestException
     * @throws ServerException
     *
     * @return bool
     */
    public function revokeOAuthToken(string $token)
    {
        try {
            Client::new()->withBearerToken($token)->get($this->server->revokePath());
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

            return false;
        } catch (\Exception $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }

    public function getByOAuthToken(string $token): ?Authenticatable
    {
        if ($this->cache && 'request' !== $this->priority) {
            return $this->fromCache($token);
        }

        try {
            /** @var Response */
            $response = Client::new()->withBearerToken($token)->get($this->server->tokenPath());
            $json = $response->getBody();
            $user = \is_callable($this->factory) ? call_user_func($this->factory, $json) : $this->factory->create($json, $token);
            if ($this->cache && $this->isAuthenticatable($user)) {
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
            if ($this->cache) {
                return $this->fromCache($token);
            }
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function fromCache(string $token)
    {
        $cacheProvider = $this->getCacheProvider();
        $user = $cacheProvider->read($token);
        if (($user instanceof LegacyUser) && $user->tokenExpires()) {
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
        if (is_a($this->cache, \Closure::class)) {
            $this->cache = ($this->cache)();
        }

        return $this->cache;
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
}
