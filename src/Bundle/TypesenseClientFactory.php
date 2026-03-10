<?php

declare(strict_types=1);

namespace Enabel\Typesense\Bundle;

use Typesense\Client;

final class TypesenseClientFactory
{
    public static function create(string $url, string $apiKey): Client
    {
        $parsed = parse_url($url);
        assert(is_array($parsed));

        return new Client([
            'api_key' => $apiKey,
            'nodes' => [
                [
                    'host' => $parsed['host'] ?? 'localhost',
                    'port' => (string) ($parsed['port'] ?? 8108),
                    'protocol' => $parsed['scheme'] ?? 'http',
                ],
            ],
        ]);
    }
}
