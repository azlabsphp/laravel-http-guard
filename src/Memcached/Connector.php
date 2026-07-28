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

namespace Drewlabs\HttpGuard\Memcached;

class Connector
{
    /**
     * @param mixed $id Connection id
     *
     * @return \Memcached
     */
    public function connect(array $servers, $id = null, array $options = [], array $credentials = [])
    {
        $memcached = $this->createInstance($id, $options, $credentials);
        if (empty($memcached->getServerList())) {
            foreach ($servers as $server) {
                $memcached->addServer($server['host'] ?? null, $server['port'] ?? null, $server['weight'] ?? 0);
            }
            if (false === $memcached->getStats()) {
                throw new \RuntimeException('No Available memcached server found in the server list');
            }
        }

        return $memcached;
    }

    /**
     * @param mixed $connection
     *
     * @return \Memcached
     */
    private function createInstance($connection, ?array $options = [], ?array $sasl = [])
    {
        if (!class_exists(\Memcached::class)) {
            throw new \RuntimeException('ext-memcached is required to use the memcached driver');
        }

        $client = new \Memcached($connection);

        $sasl = array_values(array_filter($sasl ?? []));
        if (!empty($sasl)) {
            $client->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $client->setSaslAuthData(...$sasl);
        }

        foreach ($options ?? [] as $key => $value) {
            $client->setOption($value);
        }

        return $client;
    }
}
