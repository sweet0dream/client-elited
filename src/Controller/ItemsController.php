<?php

namespace App\Controller;

use App\Service\ItemsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ItemsController extends AbstractController
{
    private string $template;

    public function __construct(
        private readonly ItemsService $itemsService,
        private readonly RequestStack $requestStack
    ) {
        $this->template = explode('.', $this->requestStack->getCurrentRequest()->getHost())[1];
    }

    private function renderItems(
        string $type = null,
        int $id = null
    ): Response
    {
        $route = $this->itemsService->getRoute();

        $items = $this->itemsService->getItems(
            !is_null($type) ? array_search($type, $route) : $type,
            $id
        );

        $render = match (true) {
            is_null($items) => [
                'view' => '404.html.twig',
            ],
            (!is_null($id) && empty(array_filter($items))) || (!is_null($type) && !array_search($type, $route)) => [
                'view' => $this->template . '/404.html.twig',
            ],
            default => [
                'view' => is_null($id)
                    ? $this->template . '/items/section.html.twig'
                    : $this->template . '/items/view.html.twig',
                'data' => is_null($id)
                    ? ['items' => $this->itemsService->separateItems($items)]
                    : [
                        'item' => $items[key($items)],
                        'reviews' => $this->itemsService->getReviewsItem(key($items))
                    ],
                'response' => true
            ]
        };

        return $this->render(
            $render['view'],
            $render['data'] ?? [],
            (new Response())->setStatusCode([Response::HTTP_NOT_FOUND, Response::HTTP_OK][(int)isset($render['response'])])
        );
    }

    #[Route('/', name: 'items_index')]
    public function index(): Response
    {
        return $this->renderItems();
    }

    #[Route('/{type}', name: 'items_by_type')]
    public function section(
        string $type
    ): Response
    {
        return $this->renderItems($type);
    }

    #[Route('/{type}/{id}', name: 'items_show')]
    public function show(
        string $type,
        int $id
    ): Response
    {
        return $this->renderItems($type, $id);
    }
}
