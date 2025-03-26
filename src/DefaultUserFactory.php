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

use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Reflector;
use Drewlabs\HttpGuard\Contracts\UserFactory;
use Drewlabs\HttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\HttpGuard\Traits\AttributesAware;

class DefaultUserFactory implements UserFactory
{
    public function create(array $attributes = [], ?string $token = null)
    {
        $class = HttpGuardGlobals::authenticatable();
        if (!class_exists($class) || !$this->isAttributeAware($class)) {
            throw new \Exception('Authenticatable class must define a createFromAttributes static method or use '.AttributesAware::class.' trait!');
        }
        $user = forward_static_call([$class, 'createFromAttributes'], Arr::except($attributes, ['accessToken']));
        if ($this->supportsTokens($user)) {
            /**
             * @var AccessToken
             */
            $accessToken = AccessToken::createFromAttributes($attributes['accessToken'] ?? []);
            // When the accessToken attribute is null we throw a new ServerBadResponseException
            if (null === $accessToken) {
                throw new ServerBadResponseException('Access token is required for authenticatable classes that supports token');
            }
            $accessToken->setAccessToken($token);
            $user->withAccessToken($accessToken);
        }

        return $user;
    }

    /**
     * checks if the tokenable model supports API tokens.
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
        $object = \is_object($object) ? $object::class : $object;
        $isStatic = static function ($object, $method) {
            try {
                return (new \ReflectionMethod($object, $method))->isStatic();
            } catch (\ReflectionException $e) {
                return false;
            }
        };

        return \count(array_intersect([AttributesAware::class, \Drewlabs\Support\Traits\AttributesAware::class], Reflector::usesRecursive($object))) > 0
            || (method_exists($object, 'createFromAttributes') && $isStatic($object, 'createFromAttributes'));
    }
}
