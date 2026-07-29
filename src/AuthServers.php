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

use Drewlabs\Core\Helpers\Str;
use LogicException;

/**
 * @internal
 */
final class AuthServers
{
    /**
     * query the cluster nodes and write the first available server as ip or domain name to disk
     * 
     * @param string $path 
     * @param array $cluster
     *  
     * @return false|null 
     */
    public static function setAvailableServer(string $path, array $cluster = [])
    {
        if (!empty($cluster)) {
            /** @var int|null */
            $primary = null;

            foreach ($cluster as $index => $server) {
                if (true === $server['primary'] ?? false) {
                    $primary = $index;
                    break;
                }
            }

            /** @var string|null */
            $primaryServer = null;

            if (null !== $primary) {
                $primaryServer = $cluster[$primary]['host'] ?? null;
                unset($cluster[$primary]);
            }

            if (!class_exists('\Drewlabs\Net\Ping\Client')) {
                static::write($path, $primaryServer);
                return;
            }

            if ($primaryServer && static::isAvailable($primaryServer)) {
                static::write($path, $primaryServer);
                return;
            }

            foreach (static::getAvailableServer($cluster) as $server) {
                static::write($path, $server);
            }
        }
    }

    public static function getPrimaryServer(string $path, ?string $primaryServer = null)
    {
        if (false === ($host = @file_get_contents($path))) {
            $host = $primaryServer;
        }

        if (!$host) {
            throw new \RuntimeException('No auth server node available');
        }

        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $host)) {
            if (str_contains($host, ':')) {
                $components = parse_url($host);
                return sprintf('%s://%s%s', $components['scheme'] ?? 'http', $components['host'] ?? 'localhost', isset($components['port']) ? sprintf(':%s', $components['port']) : '');
            }

            return 'http://'.gethostbyaddr($host);
        }

        return $host;
    }

    /**
     * @return \Generator<int, mixed, mixed, void>
     */
    private static function getAvailableServer(array $nodes)
    {
        foreach ($nodes as $node) {
            if (($host = $node['host'] ?? null) && static::isAvailable($host)) {
                yield $host;
                break;
            }
        }
    }

    /**
     * checks if a server is online
     * 
     * @param string $host 
     * @return bool|true 
     */
    private static function isAvailable(string $host)
    {
        if (class_exists('\Drewlabs\Net\Ping\Client') && class_exists('\Drewlabs\Net\Ping\Method')) {
            $client = new \Drewlabs\Net\Ping\Client($host, null, 640);
            $response = $client->request(Str::contains($host, 'localhost') ? \Drewlabs\Net\Ping\Method::FSOCKOPEN : \Drewlabs\Net\Ping\Method::EXEC_BIN);
            return false !== (bool) $response->latency();
        }

        return true;
    }

    /**
     * write available server information to disk
     * 
     * @param string $path 
     * @param null|string $data 
     * @return false 
     * @throws LogicException 
     */
    private static function write(string $path, ?string $data)
    {
        if (($dirname = @pathinfo($path, \PATHINFO_DIRNAME)) === false) {
            throw new \LogicException('Failed to create file at path '.$path);
        }

        self::makeDirectory($dirname);

        $f = @fopen($path, 'w');
        if ($f && flock($f, \LOCK_EX | \LOCK_NB)) {
            fwrite($f, $data);
            flock($f, \LOCK_UN);
            @fclose($f);
        }

        return false;
    }

    /**
     * creates directory if not exists with configured permissions
     * 
     * @param string $path 
     * @param null|int $mode 
     * @return void 
     * @throws LogicException 
     */
    private static function makeDirectory(string $path, ?int $mode = 0755)
    {
        if (!@mkdir($path, $mode, true)) {
            $mkdirError = error_get_last();
        }

        clearstatcache(false, $path);
        if (!is_dir($path)) {
            throw new \LogicException($mkdirError['message'] ?? '');
        }
    }
}
