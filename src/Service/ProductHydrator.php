<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Product;
use InvalidArgumentException;

/**
 * This is currently quite a primitive class, but I have some expectations:
 *  - List of properties will rise above twenty
 *  - Elastic will hold old data, and it will need to be able to hold a previous version of data format
 *  - Some missing data can be and will be guessed, based on other properties
 *  - There mustn't be any unchecked data in the application.
 */
class ProductHydrator
{
    private function hydrate(array $data): Product
    {
        if (!isset($data['id']) || !is_string($data['id']))
        {
            throw new InvalidArgumentException('Missing required property: id');
        }

        if (!isset($data['name']) || !is_string($data['name']))
        {
            throw new InvalidArgumentException('Missing required property: name');
        }

        return new Product($data['id'], $data['name']);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hydrateElasticData(array $data): Product
    {
        return $this->hydrate($data);
    }

    /**
     * This could be delegated to some ORM, probably Doctrine
     * @throws InvalidArgumentException
     */
    public function hydrateDatabaseData(array $data): Product
    {
        return $this->hydrate($data);
    }
}
