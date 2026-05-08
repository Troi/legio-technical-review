<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Product;

class ProductSerializer
{
    /**
     * Filter data subset for customer view - Filter out all sensitive information
     * @return array<string, scalar>
     */
    public function getCustomerData(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
        ];
    }

    /**
     * Usage plan for eshop administrator
     * @return array<string, scalar>
     */
    public function getAdminData(Product $product): array
    {
        return $this->getCustomerData($product);
    }

    /**
     * Usage plan for briefer formats
     * @return array<string, scalar>
     */
    public function getShortData(Product $product): array
    {
        return $this->getCustomerData($product);
    }
}
