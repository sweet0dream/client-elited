<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClientService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {}

    protected function getContent(
        string $url,
        ?string $domain
    ): ?array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'http://rest.local/' . $url,
                [
                    'headers' => [
                        'domain' => $domain,
                    ]
                ]
            );

            return $response->getStatusCode() === 200 ? json_decode($response->getContent(), true) : null;
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            die($e->getMessage());
        }
    }
}