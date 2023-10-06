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

class AuthServerNodesChecker
{
    public static function setClusterAvailableNodeIfMissing()
    {
        if (!file_exists(HttpGuardGlobals::nodeServerCachePath())) {
            static::setAvailableNode();
        }
    }

    public static function setAvailableNode()
    {
        $nodes = HttpGuardGlobals::hosts();
        if (!empty($nodes)) {
            /**
             * @var int
             */
            $primary = null;
            foreach ($nodes as $index => $node) {
                if (true === $node['primary'] ?? false) {
                    $primary = $index;
                    break;
                }
            }
            if (null !== $primary) {
                $primaryNode = $nodes[$primary]['host'] ?? null;
                unset($nodes[$primary]);
            }

            // TODO : Use the default primary node if the Ping executor is not provided
            if (!class_exists(Client::class)) {
                return static::writeCache(HttpGuardGlobals::nodeServerCachePath(), $primaryNode);
            }

            if ($primaryNode && static::isHostAvailable($primaryNode)) {
                return static::writeCache(HttpGuardGlobals::nodeServerCachePath(), $primaryNode);
            }

            foreach (static::querySecondaryNodes($nodes) as $node) {
                return static::writeCache(HttpGuardGlobals::nodeServerCachePath(), $node);
            }
            // Delete file if no node is present
            if (file_exists(HttpGuardGlobals::nodeServerCachePath())) {
                @unlink(HttpGuardGlobals::nodeServerCachePath());
            }
        }
    }

    public static function getAuthServerNode()
    {
        $host = @file_get_contents(HttpGuardGlobals::nodeServerCachePath());
        if (false === $host) {
            $host = HttpGuardGlobals::defaultAuthServerNode();
        }
        if (!$host) {
            throw new \RuntimeException('No auth server node available');
        }

        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $host)) {
            if (false !== strpos($host, ':')) {
                $components = parse_url($host);
                return sprintf("%s://%s%s", $components['scheme'] ?? 'http', $components['host'] ?? 'localhost', isset($components['port']) ?sprintf(":%s", $components['port']) : '');

            }
            return 'http://'.gethostbyaddr($host);
        }

        return $host;
    }

    /**
     * @return \Generator<int, mixed, mixed, void>
     */
    private static function querySecondaryNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            if (($host = $node['host'] ?? null) && static::isHostAvailable($host)) {
                yield $host;
                break;
            }
        }
    }

    private static function isHostAvailable(string $host)
    {
        if (class_exists(\Drewlabs\Net\Ping\Client::class)) {
            $pingClient = new \Drewlabs\Net\Ping\Client($host, null, 640);
            $result = $pingClient->request(Str::contains($host, 'localhost') ? \Drewlabs\Net\Ping\Method::FSOCKOPEN : \Drewlabs\Net\Ping\Method::EXEC_BIN);

            return false !== (bool) $result->latency();
        }

        return true;
    }

    private static function writeCache(string $path, ?string $data)
    {
        if (($dirname = @pathinfo($path, \PATHINFO_DIRNAME)) === false) {
            throw new \LogicException('Failed to create file at path '.$path);
        }
        self::createDirectoryIfNotExists($dirname);
        $fd = @fopen($path, 'w');
        if ($fd && flock($fd, \LOCK_EX | \LOCK_NB)) {
            fwrite($fd, $data);
            flock($fd, \LOCK_UN);
            @fclose($fd);
        }

        return false;
    }

    private static function createDirectoryIfNotExists(string $path, ?int $mode = 0755)
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
