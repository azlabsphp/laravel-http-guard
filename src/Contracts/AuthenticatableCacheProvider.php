<?php

namespace Drewlabs\AuthHttpGuard\Contracts;

use Drewlabs\Contracts\Auth\Authenticatable;

interface AuthenticatableCacheProvider
{
    /**
     * Write the authenticatable data to cache
     * 
     * @param string $id 
     * @param Authenticatable $user 
     * @return mixed 
     */
    public function write(string $id, Authenticatable $user);

    /**
     * Read the authenticatable data from cache
     * 
     * @param string $id 
     * @return Authenticatable|null
     */
    public function read(string $id): ?Authenticatable;

    /**
     * Removes the authenticatable data from cache
     * 
     * @param string $id 
     * @return mixed
     */
    public function delete(string $id);

    /**
     * Removes all stales authenticatables from cache
     * 
     * @return mixed 
     */
    public function prune();
}
