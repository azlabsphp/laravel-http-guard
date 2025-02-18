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

namespace Drewlabs\HttpGuard\Traits;

use Drewlabs\Core\Helpers\Arr;

trait AttributesAware
{
    /**
     * @var array<string|mixed>
     */
    private $attributes = [];

    public function __set(string $name, $value)
    {
        $this->setAttribute($name, $value);
    }

    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * @return mixed
     */
    public static function createFromAttributes(array $attributes)
    {
        $reflector = new \ReflectionClass(__CLASS__);
        if ($reflector->isAbstract()) {
            throw new \LogicException('Class is not instanciable...');
        }
        if ($reflector->isInstantiable()) {
            return static::createNewArgsInstance($reflector, $attributes);
        }
        try {
            return static::createByReflectedConstructor($reflector, $attributes);
        } catch (\ReflectionException $e) {
            return new self();
        }
    }

    private static function validateConstructorFirstParameter(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        if (
            ($type instanceof \ReflectionNamedType && 'array' === $type->getName())
            || !$parameter->hasType()
        ) {
            return;
        }
        throw new \LogicException(__CLASS__.' must have only one required parameter which must be of type array');
    }

    private static function validateConstructorLeastParameters(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            if (!$parameter->isOptional()) {
                throw new \LogicException(__CLASS__.' must have only one required parameter which must be of type array');
            }
        }
    }

    private static function createNewArgsInstance(\ReflectionClass $reflector, array $attributes = [])
    {
        $constructor = $reflector->getConstructor();
        if (null === $constructor) {
            return new static();
        }
        $parameters = $constructor->getParameters();
        static::validateConstructorFirstParameter($parameters[0]);
        if (1 !== \count($parameters)) {
            static::validateConstructorLeastParameters(\array_slice($parameters, 1));
        }

        return $reflector->newInstanceArgs([$attributes]);
    }

    private static function createByReflectedConstructor(\ReflectionClass $reflector, array $attributes = [])
    {
        $constructor = $reflector->getConstructor();
        if (null === $constructor) {
            return new static();
        }
        $parameters = $constructor->getParameters();
        static::validateConstructorFirstParameter($parameters[0]);
        if (1 !== \count($parameters)) {
            static::validateConstructorLeastParameters(\array_slice($parameters, 1));
        }
        $constructor->setAccessible(true);
        $object = $reflector->newInstanceWithoutConstructor();
        $constructor->getClosure($object)->__invoke($attributes);

        return $object;
    }

    /**
     * Set value for attibute with name `$name`.
     *
     * @param mixed $value
     *
     * @return void
     */
    private function setAttribute(string $name, $value)
    {
        Arr::set($this->attributes, $name, $value);
    }

    /**
     * Get value for attibute with name `$name`.
     *
     * @return mixed
     */
    private function getAttribute(string $name)
    {
        return Arr::get($this->attributes ?? [], $name, null);
    }
}
