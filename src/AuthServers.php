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
    public static function setClusterAvailableNodeIfMissing()
    {
        if (!file_exists(Globals::nodesCachePath())) {
            static::setAvailableNode();
        }
    }

    public static function setAvailableNode()
    {
        $nodes = Globals::hosts();
        if (!empty($nodes)) {
            /** @var int|null */
            $primary = null;
            foreach ($nodes as $index => $node) {
                if (true === $node['primary'] ?? false) {
                    $primary = $index;
                    break;
                }
            }

            /** @var string|null */
            $primaryNode = null;

            if (null !== $primary) {
                $primaryNode = $nodes[$primary]['host'] ?? null;
                unset($nodes[$primary]);
            }

            if (!class_exists('\Drewlabs\Net\Ping\Client')) {
                return static::writeCache(Globals::nodesCachePath(), $primaryNode);
            }

            if ($primaryNode && static::isAvailable($primaryNode)) {
                return static::writeCache(Globals::nodesCachePath(), $primaryNode);
            }

            foreach (static::getAvailableSecondaryServers($nodes) as $node) {
                return static::writeCache(Globals::nodesCachePath(), $node);
            }

            if (file_exists(Globals::nodesCachePath())) {
                @unlink(Globals::nodesCachePath());
            }
        }
    }

    public static function getPrimaryServer()
    {
        $host = @file_get_contents(Globals::nodesCachePath());
        if (false === $host) {
            $host = Globals::primaryServer();
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
