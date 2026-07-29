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
    public function __construct(BasicAuthSeverInterface $server, $cache = null)
    {
        $this->cache = $cache;
        $this->server = $server;
    }

    #[\Override]
    public function getByOAuthToken(string $token): Authenticatable
    {
        /** @var PersonalAccessToken|null */
        $authToken = null;

        /** @var User|null */
        $user = null;

        try {
            $response = Http::new()
                ->withBasicAuth($this->server->auth()->user(), $this->server->auth()->password())
                ->asUrlEncoded()
                ->post($this->server->tokenPath(), ['grant_type' => 'bearer_token', 'token' => $token]);

            if (!$response->ok()) {
                throw new ServerBadResponseException($response);
            }

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

            # create a temporary user from the token sub property
            if (array_key_exists('sub', $json)) {
                $user = new User($json['sub'], null);
            }
        } catch (\Throwable $e) {

            $user = is_null($authToken = $this->handleError($e, $token)) ? $this->loadFromCache($token) : null;
            if (!is_null($user)) {
                return $user;
            }

            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }


        if (is_null($authToken)) {
            throw new AuthorizationException($token, 401);
        }

        try {
            $userResponse = Http::new()->withBearerToken($token)->get($this->server->mePath());
            if (!$userResponse->ok()) {
                throw new ServerBadResponseException($userResponse);
            }

            $json = $userResponse->getJson();
            if (array_key_exists('sub', $json) && array_key_exists('name', $json)) {
                $user = new User($json['sub'], $json['name'], null, [], [], $json);
            }
        } catch (\Throwable $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        if (is_null($user)) {
            throw new ServerException('user not found', 400);
        }

        return $user->withAccessToken($authToken);
    }

    #[\Override]
    public function revokeOAuthToken(string $token): bool
    {
        try {

            $response = Http::new()
                ->withBearerToken($token)
                ->get($this->server->revokePath(), ['params' => ['revoked' => true]]);

            return $response->ok();

        } catch (BadRequestException $e) {
            throw new ServerBadResponseException($e->getResponse());
        }
    }


    public function loadFromCache(string $token)
    {
        if (is_null($cache = $this->getCache())) {
            return null;
        }

        $user = $cache->read($token);
        if ($user instanceof User && $user->tokenExpires()) {

            $cache->delete($token);
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
