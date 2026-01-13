<?php

namespace App\Twig\Runtime;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getIssLocationData()
    {
        return $this->cache->get('iss_location_data', function (ItemInterface $item): array  {
            $item->expiresAfter(5);
            $response = $this->client->request('GET', 'https://api.wheretheiss.at/v1/satellites/25544');
            return $response->toArray();
        });
    }
}
