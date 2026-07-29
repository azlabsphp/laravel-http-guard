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

namespace Drewlabs\HttpGuard\Tests;

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\HttpGuard\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_user_constructor_throws_exception_for_invalid_attributes()
    {
        $attributes = [
            'is_active' => true,
            'remember_token' => null,
            'firstname' => 'ADMIN',
            'lastname' => 'MASTER',
            'address' => null,
            'phone_number' => null,
            'postal_code' => null,
            'birthdate' => null,
            'sex' => null,
            'profile_url' => null,
            'deleted_at' => null,
            'created_at' => '2022-05-30T10:01:04.000000Z',
            'updated_at' => '2022-05-30T10:01:04.000000Z',
            'emails' => ['contact@azlabs.tg'],
            'double_auth_active' => false,
            'is_verified' => true,
            'accessToken' => [
                'provider' => 'drewlabs:jwt',
                'id' => '1',
                'idToken' => 'kg8PVAEryEr2s9d1NRqFiwIehM3bMRFgK2U6Y13VaPL',
                'scopes' => ['all', 'sys:all'],
                'expiresAt' => '2022-05-31T11:43:15+0000',
                'iat' => '2022-05-30T11:43:15+0000',
                'iss' => 'IDENTITY WEB SERVICE',
            ],
        ];
        $user = new User(1, 'APPSYSADMIN', null, null, ['all', 'sys:all'], ['SYSADMIN'], $attributes);
        $this->assertInstanceOf(Authenticatable::class, $user);
    }
}
