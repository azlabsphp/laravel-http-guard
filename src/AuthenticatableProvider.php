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
use Drewlabs\AuthHttpGuard\Exceptions\ServerException;
use Drewlabs\AuthHttpGuard\Exceptions\UnAuthorizedException;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\HttpClient\Contracts\HttpClientInterface;
use Drewlabs\HttpClient\Core\HttpClientCreator;
use Drewlabs\AuthHttpGuard\Traits\AttributesAware;
use GuzzleHttp\Exception\BadResponseException;

final class AuthenticatableProvider implements ApiTokenAuthenticatableProvider
{
    /**
     * @var AuthenticatableCacheProvider
     */
    private $cache;

    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var bool
     */
    private $useCache = false;

    public function __construct(?AuthenticatableCacheProvider $cache = null)
    {
        $this->cache = $cache ?? ArrayCacheProvider::load();
        try {
            $this->client = HttpClientCreator::createHttpClient(AuthServerNodesChecker::getAuthServerNode());
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

    public function revokeOAuthToken(string $token)
    {
        try {
            $this->client
                ->withBearerToken($token)
                ->get(HttpGuardGlobals::revokePath());
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
            $response = $this->client
                ->withBearerToken($token)
                ->get(HttpGuardGlobals::userPath());
            /**
             * @var array
             */
            $serialized = json_decode($response->getBody()->getContents(), true);
            $class = HttpGuardGlobals::authenticatableClass();
            if (!class_exists($class) || !$this->isAttributeAware($class)) {
                throw new \Exception('Authenticatable class must define a createFromAttributes static method or use ' . AttributesAware::class . ' trait!');
            }
            $user = forward_static_call([$class, 'createFromAttributes'], Arr::except($serialized, ['accessToken']));
            if ($this->supportsTokens($user)) {
                /**
                 * @var AccessToken
                 */
                $accessToken = AccessToken::createFromAttributes($serialized['accessToken'] ?? []);
                $accessToken->setAccessToken($token);
                $user->withAccessToken($accessToken);
            }
            if (HttpGuardGlobals::usesCache()) {
                $this->cache->write($token, $user);
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
        } catch (\Exception $e) {
            if (HttpGuardGlobals::usesCache()) {
                return $this->getAuthenticatableFromCache($token);
            }
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getAuthenticatableFromCache(string $token)
    {
        return $this->cache->read($token);
    }

    public function getCacheProvider()
    {
        return $this->cache;
    }

    /**
     * Determine if the tokenable model supports API tokens.
     *
     * @param mixed $tokenable
     *
     * @return bool
     */
    private function supportsTokens($tokenable = null)
    {
        return $tokenable instanceof HasApiTokens || (method_exists($tokenable, 'token') && method_exists($tokenable, 'withAccessToken'));
    }

    private function isAttributeAware($object)
    {
        $object = \is_object($object) ? \get_class($object) : $object;
        $is_static = static function ($object, $method) {
            try {
                return (new \ReflectionMethod($object, $method))->isStatic();
            } catch (\ReflectionException $e) {
                return false;
            }
        };
        return \count(array_intersect([AttributesAware::class, \Drewlabs\Support\Traits\AttributesAware::class], drewlabs_class_recusive_uses($object))) > 0 ||
            (method_exists($object, 'createFromAttributes') && $is_static($object, 'createFromAttributes'));
    }
}
