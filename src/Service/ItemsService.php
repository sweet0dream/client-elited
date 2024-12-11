<?php

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ItemsService extends ClientService {

    private const array CACHE_ITEMS_ID = [
        'key' => 'items',
        'expired' => 3600, //one hour
        'get' => 'items/ids'
    ];

    private const array CACHE_ITEM = [
        'key' => 'item',
        'expired' => 604800, //one week
        'get' => 'item'
    ];

    private const array CACHE_ITEM_REVIEWS = [
        'key' => 'reviews',
        'expired' => 3600, //one hour
        'get' => 'item/%s/reviews'
    ];

    private string $domain;
    private ?array $loadCity;

    public function __construct(
        protected HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly CityService $city,
        private readonly RequestStack $request
    ) {
        $this->loadCity = $this->city->loadCity();
        $this->domain = $this->request->getCurrentRequest()->getHost();
        parent::__construct($httpClient);
    }

    private function getItemsId(): ?array
    {
        $cacheKey = self::CACHE_ITEMS_ID['key'] . PATH_SEPARATOR . $this->loadCity['key'];
        try {
            return $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) {
                    $item->expiresAfter(self::CACHE_ITEMS_ID['expired']);
                    return $this->getContent(self::CACHE_ITEMS_ID['get'], $this->domain);
                }
            );
        } catch (InvalidArgumentException $e) {
            die($e->getMessage());
        }
    }

    private function getAllItems(): ?array
    {
        $itemsId = $this->getItemsId();

        if (is_null($itemsId)) {
            return null;
        }

        foreach ($itemsId as $itemId) {
            $cacheKey = self::CACHE_ITEM['key'] . PATH_SEPARATOR . $itemId;
            try {
                $items[(int)$itemId] = $this->cache->get(
                    $cacheKey,
                    function (ItemInterface $item) use ($itemId) {
                        $item->expiresAfter(self::CACHE_ITEM['expired']);
                        return $this->getContent(self::CACHE_ITEM['get'] . DIRECTORY_SEPARATOR . $itemId, $this->domain);
                    }
                );
            } catch (InvalidArgumentException $e) {
                die($e->getMessage());
            }
        }

        return $items ?? [];
    }

    public function getRoute(): ?array
    {
        return $this->loadCity['route'] ?? null;
    }

    public function getItems(
        string $type = null,
        int $id = null
    ): ?array
    {
        $items = $this->getAllItems();

        return !is_null($items) ? array_filter($items, function ($item) use ($type, $id) {
            if (!is_null($type)) {
                if (!is_null($id)) {
                    return $item['type']['key'] == $type && $item['id'] == $id ? $item : null;
                }
                return $item['type']['key'] == $type ? $item : null;
            }
            return $item;
        }) : null;
    }

    public function separateItems(array $items): array
    {
        $separate = array_fill_keys(['premium', 'vip', 'active', 'nonactive'], null);

        array_walk($items, function ($item) use (&$separate) {
            if ($item['status']['active']) {
                switch (true) {
                    case $item['status']['premium']:
                        $separate['premium'][$item['id']] = $item;
                        break;
                    case $item['status']['vip']:
                        $separate['vip'][$item['id']] = $item;
                        break;
                }
                $separate['active'][$item['id']] = $item;
            } else {
                $separate['nonactive'][$item['id']] = $item;
            }
        });

        return $separate;
    }

    public function getReviewsItem(int $itemId): ?array
    {
        $cacheKey = self::CACHE_ITEM_REVIEWS['key'] . PATH_SEPARATOR . $itemId;
        try {
            return $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) use ($itemId) {
                    $item->expiresAfter(self::CACHE_ITEM['expired']);
                    return $this->getContent(
                        sprintf(self::CACHE_ITEM_REVIEWS['get'], $itemId),
                        $this->domain
                    );
                }
            );
        } catch (InvalidArgumentException $e) {
            die($e->getMessage());
        }
    }

    public function clearCacheItem(?int $itemId = null): void
    {
        if (!is_null($itemId)) {
            $this->cache->deleteItem(self::CACHE_ITEM_REVIEWS['key'] . PATH_SEPARATOR . $itemId);
        }
        $this->cache->deleteItem(self::CACHE_ITEMS_ID['key'] . PATH_SEPARATOR . $this->loadCity['key']);
    }
}