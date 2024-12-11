<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class CityService extends ClientService
{
    private const array CACHE_CITY = [
        'key' => 'city',
        'expired' => 2592000, //1 month
        'get' => 'city'
    ];

    public function __construct(
        protected HttpClientInterface   $httpClient,
        private readonly CacheInterface $cache,
        private readonly RequestStack $request,
        private readonly Environment $twig
    ) {
        parent::__construct($httpClient);
    }

    public function loadCity(): ?array
    {
        $url = $this->request->getCurrentRequest()?->getHost();
        $cacheKey = self::CACHE_CITY['key'] . PATH_SEPARATOR . $url;
        try {
            $city = $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) use ($url) {
                    $item->expiresAfter(self::CACHE_CITY['expired']);
                    return $this->getContent(self::CACHE_CITY['get'], $url);
                }
            );
            $this->twig->addGlobal('city', $city);

            return $city;
        } catch (InvalidArgumentException $e) {
            die($e->getMessage());
        }
    }
}