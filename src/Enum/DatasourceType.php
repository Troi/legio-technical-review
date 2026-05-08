<?php

declare(strict_types=1);

namespace App\Enum;

enum DatasourceType: string
{
    case HTTP = 'http';
    case MYSQL = 'mysql';
    case ELASTICSEARCH = 'elasticsearch';
}
