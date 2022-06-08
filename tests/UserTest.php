<?php

use Drewlabs\AuthHttpGuard\Exceptions\ServerBadResponseException;
use Drewlabs\AuthHttpGuard\User;
use Drewlabs\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{

    public function test_user_constructor_throws_exception_for_invalid_attributes()
    {
        $attributes = array(
            'id' => 1,
            'username' => 'APPSYSADMIN',
            'is_active' => true,
            'remember_token' => NULL,
            'user_details' =>
            array(
                'id' => 1,
                'firstname' => 'ADMIN',
                'lastname' => 'MASTER',
                'address' => NULL,
                'phone_number' => NULL,
                'postal_code' => NULL,
                'birthdate' => NULL,
                'sex' => NULL,
                'profile_url' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2022-05-30T10:01:04.000000Z',
                'updated_at' => '2022-05-30T10:01:04.000000Z',
                'emails' =>
                array(
                    0 => 'contact@azlabs.tg',
                ),
            ),
            'double_auth_active' => false,
            'channels' =>
            array(),
            'is_verified' => true,
            'authorizations' =>
            array(
                0 => 'all',
                1 => 'sys:all',
            ),
            'roles' =>
            array(
                0 => 'SYSADMIN',
            ),
            'accessToken' =>
            array(
                'provider' => 'drewlabs:jwt',
                'id' => '1',
                'idToken' => 'kg8PVAEryEr2s9d1NRqFiwIehM3bMRFgK2U6Y13VaPL',
                'scopes' =>
                array(
                    0 => 'all',
                    1 => 'sys:all',
                ),
                'expiresAt' => '2022-05-31T11:43:15+0000',
                'iat' => '2022-05-30T11:43:15+0000',
                'iss' => 'IDENTITY WEB SERVICE',
            ),
        );
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
