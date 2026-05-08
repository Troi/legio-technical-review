<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Dummy implementation of IMySQLDriver driver, it is out of scope of tested solution
 * Implementation is given by the legacy system
 */
class IMySQLDriver
{
    /**
     * I expect that it always returns valid data because of integrity validations
     * @param string $id
     * @return array
     */
    public function findProduct($id)
    {
        return [
            'id' => $id,
            'name' => sprintf('Product #%s', $id),
        ];
    }
}
