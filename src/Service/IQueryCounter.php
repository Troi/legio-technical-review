<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\DatasourceType;

interface IQueryCounter
{
    public function logQuery(string $id, DatasourceType $datasourceType): void;
}
