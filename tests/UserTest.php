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

use Drewlabs\AuthHttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\AuthHttpGuard\User;
use Drewlabs\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_user_constructor_throws_exception_for_invalid_attributes()
    {
        $attributes = [
            'id' => 1,
            'username' => 'APPSYSADMIN',
            'is_active' => true,
            'remember_token' => null,
            'user_details' => [
                'id' => 1,
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
                'emails' => [
                    0 => 'contact@azlabs.tg',
                ],
            ],
            'double_auth_active' => false,
            'channels' => [],
            'is_verified' => true,
            'authorizations' => [
                0 => 'all',
                1 => 'sys:all',
            ],
            'roles' => [
                0 => 'SYSADMIN',
            ],
            'accessToken' => [
                'provider' => 'drewlabs:jwt',
                'id' => '1',
                'idToken' => 'kg8PVAEryEr2s9d1NRqFiwIehM3bMRFgK2U6Y13VaPL',
                'scopes' => [
                    0 => 'all',
                    1 => 'sys:all',
                ],
                'expiresAt' => '2022-05-31T11:43:15+0000',
                'iat' => '2022-05-30T11:43:15+0000',
                'iss' => 'IDENTITY WEB SERVICE',
            ],
        ];
        $user = User::createFromAttributes($attributes);
        $this->assertInstanceOf(Authenticatable::class, $user);
    }

    public function test_user_constructor_passes_for_valid_attributes()
    {
        $this->expectException(ServerBadResponseException::class);
        $attributes = [
            'version' => 'v1.0.0',
            'id' => 1,
        ];
        $user = User::createFromAttributes($attributes);
        $this->assertInstanceOf(Authenticatable::class, $user);
    }
}
