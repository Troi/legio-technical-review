<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Product;
use App\Service\IElasticSearchDriver;
use App\Service\IMySQLDriver;
use App\Service\ProductHydrator;
use Symfony\Component\HttpClient\Exception\TimeoutException;

class ProductRepository
{

    public function __construct(
        private ProductHydrator $productHydrator,
        private IElasticSearchDriver $elasticSearchDriver,
        private IMySQLDriver $mySQLDriver
    )
    {
    }

    /**
     * This could be solved by Chain of Responsibility Design Pattern,
     * but I decided to use simple try-catch-finally block for readability and simplicity
     *
     * When there will be more variability or more nested conditions and fallbacks, I would rewrite it to Chain
     */
    public function findProduct(string $id): ?Product
    {
        try {
            return $this->productHydrator->hydrateElasticData(
                $this->elasticSearchDriver->findById($id)
            );
        } catch (\InvalidArgumentException) {
            // log bad elastic data
        } catch (TimeoutException) {
            // log elastic timeout,
        } finally {
            return $this->productHydrator->hydrateDatabaseData(
                $this->mySQLDriver->findProduct($id)
            );
        }
    }
}
