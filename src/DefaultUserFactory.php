<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\AuthHttpGuard\Contracts\UserFactory;
use Drewlabs\AuthHttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\Contracts\OAuth\HasApiTokens;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\AuthHttpGuard\Traits\AttributesAware;

class DefaultUserFactory implements UserFactory
{
    public function create(array $attributes = [], ?string $token = null)
    {
        $class = HttpGuardGlobals::authenticatableClass();
        if (!class_exists($class) || !$this->isAttributeAware($class)) {
            throw new \Exception('Authenticatable class must define a createFromAttributes static method or use ' . AttributesAware::class . ' trait!');
        }
        $user = forward_static_call([$class, 'createFromAttributes'], Arr::except($attributes, ['accessToken']));
        if ($this->supportsTokens($user)) {
            /**
             * @var AccessToken
             */
            $accessToken = AccessToken::createFromAttributes($serialized['accessToken'] ?? []);
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
        $isStatic = static function ($object, $method) {
            try {
                return (new \ReflectionMethod($object, $method))->isStatic();
            } catch (\ReflectionException $e) {
                return false;
            }
        };
        return \count(array_intersect([AttributesAware::class, \Drewlabs\Support\Traits\AttributesAware::class], drewlabs_class_recusive_uses($object))) > 0 ||
            (method_exists($object, 'createFromAttributes') && $isStatic($object, 'createFromAttributes'));
    }
}
