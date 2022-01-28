<?php

namespace Drewlabs\AuthHttpGuard;

use Drewlabs\Support\Net\Ping\Client;
use Generator;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Support\Net\Ping\Method;
use RuntimeException;

class AuthServerNodesChecker
{

    /**
     * 
     * @var string
     */
    private $writePath = __DIR__ . '/../cache/node.sock';

    public function __construct(?string $writePath = null)
    {
        $this->writePath = $writePath ?? $this->writePath;
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
                return \file_put_contents(HttpGuardGlobals::nodeServerCachePath(), $primaryNode);
            }

            if ($primaryNode && static::isHostAvailable($primaryNode)) {
                return \file_put_contents(HttpGuardGlobals::nodeServerCachePath(), $primaryNode);
            }

            foreach (static::querySecondaryNodes($nodes) as $node) {
                return \file_put_contents(HttpGuardGlobals::nodeServerCachePath(), $node);
            }
            // Delete file if no node is present
            @unlink(HttpGuardGlobals::nodeServerCachePath());
        }
    }

    public static function getAuthServerNode()
    {
        $host = @file_get_contents(HttpGuardGlobals::nodeServerCachePath());
        if (FALSE === $host) {
            throw new RuntimeException("No auth server node available");
        }
        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $host)) {
            return 'http://' . gethostbyaddr($host);
        }
        return $host;
    }

    /**
     * 
     * @param array $nodes 
     * @return Generator<int, mixed, mixed, void> 
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
        $pingClient = new Client($host, null, 640);
        $result = $pingClient->request(Str::contains($host, 'localhost') ? Method::FSOCKOPEN : Method::EXEC_BIN);
        return false !== boolval($result->latency());
    }
}
