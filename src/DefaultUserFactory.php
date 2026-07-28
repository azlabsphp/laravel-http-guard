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

use Drewlabs\HttpGuard\Contracts\UserFactory as AbstractUserFactory;
use Drewlabs\Contracts\Auth\Authenticatable;

/** @deprecated use `UserFactory` class instead */
final class DefaultUserFactory implements AbstractUserFactory
{

    /** @var UserFactory */
    private $factory;

    /**
     * user factory class instance
     * 
     * @param class-string<Authenticatable> $model 
     * @param JsonProperties $properties 
     * @return void 
     */
    public function __construct($model, JsonProperties $properties)
    {
        $this->factory = new UserFactory($model, $properties);
    }

    public function create(array $attributes = [], ?string $token = null)
    {
        return $this->factory->create($attributes, $token);
    }
}
