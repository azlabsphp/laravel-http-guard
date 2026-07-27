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


/**
 * @internal
 */
final class AuthServers
{
    public static function setClusterAvailableNodeIfMissing(string $writePath, array $cluster = [])
    {
        if (!file_exists($writePath)) {
            static::setAvailableNode($writePath, $cluster);
        }
    }

    public static function setAvailableNode(string $writePath, array $cluster = [])
    {
        if (!empty($cluster)) {
            /** @var int|null */
            $primary = null;
            foreach ($cluster as $index => $node) {
                if (true === $node['primary'] ?? false) {
                    $primary = $index;
                    break;
                }
            }

            /** @var string|null */
            $primaryNode = null;

            if (null !== $primary) {
                $primaryNode = $cluster[$primary]['host'] ?? null;
                unset($cluster[$primary]);
            }

            if (!class_exists('\Drewlabs\Net\Ping\Client')) {
                return static::writeCache($writePath, $primaryNode);
            }

            if ($primaryNode && static::isAvailable($primaryNode)) {
                return static::writeCache($writePath, $primaryNode);
            }

            foreach (static::getAvailableSecondaryServers($cluster) as $node) {
                return static::writeCache($writePath, $node);
            }

            if (file_exists($writePath)) {
                @unlink($writePath);
            }
        }
    }

    public static function getPrimaryServer(string $writePath, string $primaryServer)
    {
        ;

        if (false === ($host = @file_get_contents($writePath))) {
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
    private static function getAvailableSecondaryServers(array $nodes)
    {
        foreach ($nodes as $node) {
            if (($host = $node['host'] ?? null) && static::isAvailable($host)) {
                yield $host;
                break;
            }
        }
    }

    private static function isAvailable(string $host)
    {
        if (class_exists('\Drewlabs\Net\Ping\Client') && class_exists('\Drewlabs\Net\Ping\Method')) {
            $client = new \Drewlabs\Net\Ping\Client($host, null, 640);
            $result = $client->request(Str::contains($host, 'localhost') ? \Drewlabs\Net\Ping\Method::FSOCKOPEN : \Drewlabs\Net\Ping\Method::EXEC_BIN);
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
