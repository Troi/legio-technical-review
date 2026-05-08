<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\DatasourceType;
use App\Repository\ProductRepository;
use App\Service\IQueryCounter;
use App\Service\ProductSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{

    public function __construct(
        private readonly IQueryCounter $productQueryCounter
    )
    {
    }

    #[Route('/product/detail/{$id}')]
    public function detail(
        string            $id,
        ProductRepository $productRepository,
        ProductSerializer $productSerializer,
        CacheInterface    $cache,
    ): JsonResponse
    {
        $this->productQueryCounter->logQuery($id, DatasourceType::HTTP);

        $productData = $cache->get('product_detail_' . $id, function (ItemInterface $item) use ($id, $productRepository, $productSerializer) {
            $product = $productRepository->findProduct($id);

            if ($product === null) {
                // I don't want to cache 404 results
                $item->expiresAfter(-1);
                return null;
            }

            return $productSerializer->getCustomerData($product);
        });

        if ($productData === null) {
            // depends on system exception handling
            // it could/should be handled by generic Exception handled based on NotFoundException
            return $this->json(['error' => 'Product not found'], 404)
                ->setPrivate()
                ->setMaxAge(0);
        }

        return $this->json($productData);
    }
}
