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

namespace Drewlabs\AuthHttpGuard;

class MemcachedConnector
{
    /**
     * @param mixed $id Connection id
     *
     * @return \Memcached
     */
    public function connect(array $servers, $id = null, array $options = [], array $credentials = [])
    {
        $memcached = $this->resolveInstance($id, $options, $credentials);
        if (empty($memcached->getServerList())) {
            foreach ($servers as $server) {
                $memcached->addServer(
                    $server['host'] ?? null,
                    $server['port'] ?? null,
                    $server['weight'] ?? 0
                );
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
    private function resolveInstance($connection, ?array $options = [], ?array $sasl = [])
    {
        $memcached = new \Memcached($connection);

        // Set auth parameters
        $sasl = array_values(array_filter($sasl ?? []));
        if (!empty($sasl)) {
            $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $memcached->setSaslAuthData(...$sasl);
        }

        // Set options
        foreach ($options ?? [] as $key => $value) {
            $memcached->setOption($value);
        }

        return $memcached;
    }
}
