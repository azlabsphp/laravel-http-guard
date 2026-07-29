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

use Closure;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Curl\REST\Client;
use Drewlabs\Curl\REST\Exceptions\BadRequestException;
use Drewlabs\Curl\REST\Exceptions\RequestException as BaseRequestException;
use Drewlabs\Curl\REST\Response;
use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\HttpGuard\Contracts\ServerInterface;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\Exceptions\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable as BaseAuthenticatable;
use Drewlabs\HttpGuard\Contracts\UserFactory as AbstractUserFactory;
use Drewlabs\HttpGuard\Exceptions\RequestException;
use Drewlabs\HttpGuard\Exceptions\ServerBadResponseException;

final class AuthenticatableProvider implements ApiTokenAuthenticatableProvider
{
    /** @var AuthenticatableCacheProvider|\Closure|null */
    private $cache;

    /** @var ServerInterface */
    private $server;

    /** @var AbstractUserFactory|\Closure */
    private $factory;

    /**
     * Creates authenticatable provider instance.
     *
     * @param ServerInterface $server Authentication server configuration
     * @param AbstractUserFactory|\Closure $factory User factory dependency
     * @param AuthenticatableCacheProvider|\Closure|null $cache
     */
    public function __construct(ServerInterface $server, $factory, $cache = null)
    {
        $this->server = $server;
        $this->factory = $factory;
        $this->cache = $cache;
    }

    #[\Override]
    public function revokeOAuthToken(string $token): void
    {
        try {
            $response = Client::new()->withBearerToken($token)->get($this->server->revokePath());

            if (!$response->ok()) {
                throw new ServerBadResponseException($response);
            }

            if ($cache = $this->getCache()) {
                $cache->delete($token);
            }
        } catch (BaseRequestException $e) {
            if (401 === $e->getStatus()) {
                throw new AuthorizationException($token, $e->getStatus());
            }
            throw new RequestException($e->getMessage(), (int)$e->getCode(), $e);
        } catch (BadRequestException $e) {
            if (($response = $e->getResponse()) && 401 === $response->getStatus()) {
                throw new AuthorizationException($token, $response->getStatus());
            }

            throw new ServerBadResponseException($e->getResponse());
        }
    }

    #[\Override]
    public function getByOAuthToken(string $token): Authenticatable
    {
        try {
            /** @var Response $response */
            $response = Client::new()->withBearerToken($token)->get($this->server->mePath());
            $json = $response->getJson();

            $user = \is_callable($this->factory) 
                ? call_user_func($this->factory, $json, $token) 
                : $this->factory->create($json, $token);

            if (($cache = $this->getCache()) && $this->isAuthenticatable($user)) {
                $cache->write($token, $user);
            }

            return $user;
        } catch (BaseRequestException $e) {
            if (401 === $e->getStatus()) {
                throw new AuthorizationException($token, $e->getStatus());
            }

            throw new RequestException($e->getMessage(), (int)$e->getCode(), $e);
        } catch (BadRequestException $e) {
            if (($response = $e->getResponse()) && 401 === $response->getStatus()) {
                throw new AuthorizationException($token, $response->getStatus());
            }

            throw new ServerBadResponseException($e->getResponse());
        }
    }

    private function fromCache(string $token): ?Authenticatable
    {
        if (is_null($cache = $this->getCache())) {
            return null;
        }

        if (is_null($user = $cache->read($token))) {
            return $user;
        }

        if ($user instanceof User && $user->tokenExpires()) {
            $cache->delete($token);
            throw new TokenExpiresException($token);
        }

        return $user;
    }

    public function getCache(): ?AuthenticatableCacheProvider
    {
        if (is_a($this->cache, \Closure::class)) {
            $this->cache = call_user_func($this->cache);
        }

        return $this->cache;
    }

    /**
     * Returns true if $instance implements an authenticatable interface.
     *
     * @param mixed $instance
     *
     * @return bool
     */
    private function isAuthenticatable($instance): bool
    {
        return is_a($instance, BaseAuthenticatable::class, true) || is_a($instance, Authenticatable::class, true);
    }
}