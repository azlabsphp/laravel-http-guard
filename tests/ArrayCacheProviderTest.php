<?php

use Drewlabs\AuthHttpGuard\ArrayCacheProvider;
use Drewlabs\AuthHttpGuard\HttpGuardGlobals;
use Drewlabs\AuthHttpGuard\User;
use Drewlabs\Contracts\Auth\Authenticatable;
use Drewlabs\Core\Helpers\Rand;
use Drewlabs\Core\Helpers\UUID;
use PHPUnit\Framework\TestCase;

class ArrayCacheProviderTest extends TestCase
{

    private function cleanup()
    {
        if (is_file($path = HttpGuardGlobals::cachePath())) {
            unlink($path);
        }
    }

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
            'username' => sprintf("USER%s", Rand::int(1000, 10000)),
            'is_verified' => true,
            'id' => $id
        ]);
        $instance->write($id, $user);
        $this->assertEquals(1, count($instance->getState()));
    }

    public function test_read_existing_key_from_cache_returns_a_user_instance()
    {
        $this->cleanup();
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $user = User::createFromAttributes([
            'username' => sprintf("USER%s", Rand::int(1000, 10000)),
            'is_verified' => true,
            'id' => $id
        ]);
        $instance->write($id, $user);

        $this->assertInstanceOf(Authenticatable::class, $instance->read($id));
        $this->assertEquals($user, $instance->read($id));
    }


    public function test_read_non_existing_key_from_cache_returns_throws_exception()
    {
        $this->cleanup();
        $this->expectException(\Drewlabs\AuthHttpGuard\Exceptions\AuthenticatableNotFoundException::class);
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $this->assertEquals(null, $instance->read($id));
    }


    public function test_dump_write_instance_state_to_disk_and_load_resolve_the_written_state()
    {
        $this->cleanup();
        $instance = ArrayCacheProvider::load();
        $id = UUID::create();
        $user = User::createFromAttributes([
            'username' => sprintf("USER%s", Rand::int(1000, 10000)),
            'is_verified' => true,
            'id' => $id
        ]);
        $instance->write($id, $user);
        ArrayCacheProvider::dump($instance);
        $instance2 = ArrayCacheProvider::load();
        $this->assertEquals($instance->getState(), $instance2->getState());
        $this->assertEquals($user, $instance2->read($id));
    }
}