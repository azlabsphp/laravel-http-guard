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

use Drewlabs\Contracts\OAuth\HasAbilities;

class TransientToken implements HasAbilities
{
    /**
     * checks if the token has a given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function can($ability)
    {
        return true;
    }

    /**
     * Determine if the token is missing a given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    public function cant($ability)
    {
        return false;
    }
}
