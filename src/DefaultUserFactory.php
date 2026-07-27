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

/** @deprecated use `UserFactory` class instead */
class DefaultUserFactory implements AbstractUserFactory
{

    /** @var UserFactory */
    private $factory;

    public function __construct()
    {
        $this->factory = new UserFactory;
    }

    public function create(array $attributes = [], ?string $token = null)
    {
        return $this->factory->create($attributes, $token);
    }
}
