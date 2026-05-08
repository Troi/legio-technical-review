<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpClient\Exception\TimeoutException;

/**
 * Dummy implementation of ElasticSearch driver, it is out of scope of tested solution
 *  Implementation is given by the legacy system
 */
class IElasticSearchDriver
{
    /**
     * I expect that it can return garbage data
     * Reasons:
     *  - The source of truth is in DB
     *  - Elastic isn't reliable long-term storage
     *  - Elastic's data aren't migrated when the data structure is changed
     * @param string $id
     * @throws TimeoutException I expect that elastic connector handles it, so I don't implement it by myself
     * @return array
     */
    public function findById($id)
    {
        // Return garbage data in 50% of cases
        if (intval($id) % 2 === 1) {
            return [
                'invalid' => true,
                'id' => $id,
            ];
        }

        return [
            'id' => $id,
            'name' => sprintf('Product #%s', $id),
        ];
    }
}
