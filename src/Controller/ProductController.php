<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\ProductSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product/detail/{$id}')]
    public function detail(
        string $id,
        ProductRepository $productRepository,
        ProductSerializer $productSerializer
    ): JsonResponse {
        $product = $productRepository->findProduct($id);

        if ($product === null) {
            // depends on system exception handling
            // it could/should be handled by generic Exception handled based on NotFoundException
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json(
            $productSerializer->getCustomerData($product)
        );
    }
}
