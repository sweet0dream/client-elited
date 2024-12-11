<?php

namespace App\Twig;

use App\Service\CityService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private ?array $city;

    public function __construct(
        private readonly CityService $cityService,
        private readonly RequestStack $requestStack
    )
    {
        $this->city = $this->cityService->loadCity();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('generate_meta', [$this, 'filterGenerateMeta']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getMenu', [$this, 'functionGetMenu']),
            new TwigFunction('getTemplate', [$this, 'functionGetTemplate']),
        ];
    }

    public function filterGenerateMeta(
        array $homeMeta,
        array $addMeta
    ): array
    {
        return array_merge(
            array_combine(['/'], $homeMeta),
            array_combine(array_map(fn($item) => '/' . $item, $this->city['route']), $addMeta)
        );
    }

    public function functionGetMenu(): array
    {
        return array_combine(
            array_map(
                fn($item) => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/' . $item,
                array_keys($this->city['meta'])
            ),
            array_map(
                fn($item) => [
                    'item' => $item['item'][1],
                    'title' => $item[1]
                ],
                $this->city['meta']
            )
        );
    }

    public function functionGetTemplate(): string
    {
        return explode('.', $this->requestStack->getCurrentRequest()->getHost())[1];
    }
}