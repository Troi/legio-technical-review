<?php

declare(strict_types=1);

namespace App\Model;

/**
 * DTO for Two properties is overkill, but it's only a simple example
 * I expect that product will have tens of properties and a quarter of them will have deeper structure like parameters
 */
readonly class Product
{
    public function __construct(
        private string $id,
        private string $name
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

}
