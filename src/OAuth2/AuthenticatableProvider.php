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

use Closure;
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
    /** @var AuthenticatableCacheProvider|\Closure|null */
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

            if (isset($json['expires_at']) && is_int($json['expires_at'])) {
                $expiresAt = (new \DateTimeImmutable())->setTimestamp($json['expires_at']);
            }

            $rawScopes = $json['scopes'] ?? $json['scope'] ?? [];
            $scopes = \is_string($rawScopes) ? explode(' ', $rawScopes) : $rawScopes;

            $authToken = new AccessToken($token, $scopes, $expiresAt);

            # create a temporary user from the token sub property
            if (array_key_exists('sub', $json)) {
                $user = new User($json['sub'], null);
            }
        } catch (\Throwable $e) {

            if (!is_null($user = $this->handleError($e, $token))) {
                return $user;
            }

            throw new ServerException($e->getMessage(), (int)$e->getCode(), $e);
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
            if (is_null($user)) {
                throw new ServerException($e->getMessage(), (int)$e->getCode(), $e);
            }

            trigger_error("failed to fetch user info from authentication server, using a transient user with only user id", E_USER_WARNING);
        }

        if (is_null($user)) {
            throw new ServerException('user not found', 400);
        }

        $user = $user->withAccessToken($authToken);

        if (($cache = $this->getCache())) {
            $cache->write($token, $user);
        }

        return $user;
    }

    #[\Override]
    public function revokeOAuthToken(string $token): void
    {
        try {

            $response = Http::new()
                ->withBearerToken($token)
                ->get($this->server->revokePath(), ['params' => ['revoked' => true]]);

            $ok = $response->ok();

            if (!$ok) {
                throw new ServerBadResponseException($response);
            }

            if (!is_null($cache = $this->getCache())) {
                $cache->delete($token);
            }
        } catch (BadRequestException $e) {
            throw new ServerBadResponseException($e->getResponse());
        }
    }


    public function loadFromCache(string $token): ?Authenticatable
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

    private function getCache(): ?AuthenticatableCacheProvider
    {
        if (is_a($this->cache, \Closure::class)) {
            $this->cache = call_user_func($this->cache);
        }

        return $this->cache;
    }


    private function handleError(\Throwable $e, string $token): ?Authenticatable
    {
        if ($e instanceof BadRequestException) {
            $response = $e->getResponse();
            if (401 === $response->getStatus()) {
                throw new AuthorizationException($token, $response->getStatus());
            }
        }

        return $this->loadFromCache($token);
    }
}
