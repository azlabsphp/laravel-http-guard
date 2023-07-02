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

use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Core\Helpers\Rand;
use Drewlabs\Core\Helpers\UUID;
use Drewlabs\HttpGuard\ArrayCacheProvider;
use Drewlabs\HttpGuard\HttpGuardGlobals;
use Drewlabs\HttpGuard\User;
use PHPUnit\Framework\TestCase;

class ArrayCacheProviderTest extends TestCase
{
    public function test_load_create_new_instance()
    {
        $this->cleanup();
        $instance = ArrayCacheProvider::load();
        $this->assertInstanceOf(ArrayCacheProvider::class, $instance);
    }

    public function test_write_new_key_to_cache()
    {
        $this->cleanup();
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $user = User::createFromAttributes([
            'username' => sprintf('USER%s', Rand::int(1000, 10000)),
            'is_verified' => true,
            'id' => $id,
        ]);
        $instance->write($id, $user);
        $this->assertCount(1, $instance->getState());
    }

    public function test_read_existing_key_from_cache_returns_a_user_instance()
    {
        $this->cleanup();
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $user = User::createFromAttributes([
            'username' => sprintf('USER%s', Rand::int(1000, 10000)),
            'is_verified' => true,
            'id' => $id,
        ]);
        $instance->write($id, $user);

        $this->assertInstanceOf(Authenticatable::class, $instance->read($id));
        $this->assertEquals($user, $instance->read($id));
    }

    public function test_read_non_existing_key_from_cache_returns_throws_exception()
    {
        $this->cleanup();
        $this->expectException(\Drewlabs\HttpGuard\Exceptions\AuthenticatableNotFoundException::class);
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $this->assertNull($instance->read($id));
    }

    public function test_dump_write_instance_state_to_disk_and_load_resolve_the_written_state()
    {
        $this->cleanup();
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $user = User::createFromAttributes([
            'username' => sprintf('USER%s', Rand::int(1000, 10000)),
            'is_verified' => true,
            'id' => $id,
        ]);
        $instance->write($id, $user);
        ArrayCacheProvider::dump($instance);
        $instance2 = ArrayCacheProvider::load();
        $this->assertEquals(count($instance->getState()), count($instance2->getState()));
        $this->assertEquals($instance->read($id)->getAuthUserName(), $instance->read($id)->getAuthUserName());
        $this->assertEquals($instance->read($id)->authIdentifier(), $instance->read($id)->authIdentifier());
        $this->assertEquals($user, $instance2->read($id));
    }

    private function cleanup()
    {
        if (is_file($path = HttpGuardGlobals::cachePath())) {
            unlink($path);
        }
    }
}
