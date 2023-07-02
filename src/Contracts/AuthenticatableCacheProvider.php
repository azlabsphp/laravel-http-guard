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

namespace Drewlabs\HttpGuard\Contracts;

use Drewlabs\Contracts\Auth\Authenticatable;

interface AuthenticatableCacheProvider
{
    /**
     * Write the authenticatable data to cache.
     *
     * @return mixed
     */
    public function write(string $id, Authenticatable $user);

    /**
     * Read the authenticatable data from cache.
     */
    public function read(string $id): ?Authenticatable;

    /**
     * Removes the authenticatable data from cache.
     *
     * @return mixed
     */
    public function delete(string $id);

    /**
     * Removes all stales authenticatables from cache.
     *
     * @return mixed
     */
    public function prune();
}
