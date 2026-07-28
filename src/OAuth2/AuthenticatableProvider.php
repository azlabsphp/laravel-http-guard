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

namespace Drewlabs\HttpGuard\OAuth2;

use Drewlabs\HttpGuard\Contracts\ApiTokenAuthenticatableProvider;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Curl\REST\Client as Http;
use Drewlabs\Curl\REST\Exceptions\BadRequestException;
use Drewlabs\HttpGuard\Exceptions\AuthorizationException;
use Drewlabs\HttpGuard\Exceptions\ServerException;
use Drewlabs\HttpGuard\Contracts\AuthenticatableCacheProvider;
use Drewlabs\Contracts\OAuth\PersonalAccessToken;
use Drewlabs\HttpGuard\Contracts\BasicAuthSeverInterface;
use Drewlabs\HttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\HttpGuard\Exceptions\TokenExpiresException;
use Drewlabs\HttpGuard\User;

final class AuthenticatableProvider implements ApiTokenAuthenticatableProvider
{
    /** @var AuthenticatableCacheProvider|\Closure */
    private $cache;

    /** @var BasicAuthSeverInterface */
    private $server;

    /**
     * creates authenticatable provider
     * 
     * @param BasicAuthSeverInterface $server
     * @param AuthenticatableCacheProvider|\Closure $cache
     * 
     * @return void 
     */
    public function __construct(BasicAuthSeverInterface $server, $cache = null) {
        $this->cache = $cache;
        $this->server = $server;
    }

    #[\Override]
    public function getByOAuthToken(string $token): Authenticatable
    {
        /** @var PersonalAccessToken|null */
        $authToken = null;
        try {
            $response = Http::new()
                ->withBasicAuth($this->server->auth()->user(), $this->server->auth()->password())
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->post($this->server->tokenPath(), ['grant_type' => 'bearer_token', 'token' => $token]);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $json = $response->getJson();
                $expiresAt = null;
                $scopes = [];

                if (isset($json['expires_at']) && is_int($json['expires_at'])) {
                    $expiresAt = (new \DateTimeImmutable())->setTimestamp($json['expires_at']);
                }

                if (isset($json['scopes'])) {
                    $scopes = \is_string($json['scopes']) ? str_split($json['scopes']) : $json['scopes'];
                }

                if (isset($json['scope'])) {
                    $scopes = \is_string($json['scope']) ? str_split($json['scope']) : $json['scope'];
                }

                $authToken = new AccessToken($token, $scopes, $expiresAt);
            }
        } catch (\Throwable $e) {
            if (is_null($authToken =  $this->handleError($e, $token))) {
                return $this->loadFromCache($token);
            }

            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        if (is_null($authToken)) {
            throw new AuthorizationException($token, 401);
        }

        /** @var Authenticatable|null */
        $user = null;
        $userResponse = Http::new()->withBearerToken($token)->get($this->server->mePath());

        if ($userResponse->getStatusCode() >= 200 && $userResponse->getStatusCode() < 300) {
            $json = $response->getJson();
            if (array_key_exists('sub', $json) && array_key_exists('name', $json)) {
                $user = new User($json['sub'], $json['name'], null, [], [], $json);
                $user = $user->withAccessToken($authToken);
            }
        } else {

            throw new ServerBadResponseException($userResponse->getReasonPhrase(), $userResponse->getStatusCode());
        }

        if (is_null($user)) {
            throw new ServerBadResponseException('user not found', 400);
        }

        return $user;
    }

    #[\Override]
    public function revokeOAuthToken(string $token): bool
    {
        $response = Http::new()
            ->withBearerToken($token)
            ->get($this->server->revokePath(), ['params' => ['revoked' => true]]);

        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }


    public function loadFromCache(string $token)
    {
        $cacheProvider = $this->getCache();
        $user = $cacheProvider->read($token);
        if ($user instanceof User && $user->tokenExpires()) {

            $cacheProvider->delete($token);
            throw new TokenExpiresException($token);
        }

        return $user;
    }

    /**
     * @return AuthenticatableCacheProvider
     */
    private function getCache()
    {
        if (is_a($this->cache, \Closure::class)) {
            $this->cache = call_user_func($this->cache);
        }

        return $this->cache;
    }


    private function handleError(\Throwable $e, string $token)
    {
        if ($e instanceof BadRequestException) {
            $response = $e->getResponse();
            if (401 === $response->getStatus()) {
                throw new AuthorizationException($token, $response->getStatus());
            }

            return null;
        }
    }
}
