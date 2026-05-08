<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\DatasourceType;

/**
 * Creates statistics
 */
readonly class SimpleTextQueryCounter implements IQueryCounter
{
    public function __construct(
        private string $logDir,
        private string $entityName,
    )
    {
    }

    private function getLogFilePath(DatasourceType $datasourceType): string
    {
        // sampling by date is essential to keep track of changes in time, I would challenge product owner with this
        // usage of JSON is another deliberative change to discuss with product owner
        // JSON is better, but It depends on who will consume these statistics
        $date = date('Y-m');
        return sprintf(
            '%s/%s_%s_%s.json',
            rtrim($this->logDir, '/'),
            $this->entityName,
            $datasourceType->value,
            $date
        );
    }

    private function readLogData(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function writeLogData(string $filePath, array $data): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function logQuery(string $id, DatasourceType $datasourceType): void
    {
        $filePath = $this->getLogFilePath($datasourceType);
        $data = $this->readLogData($filePath);

        if (!isset($data[$id])) {
            $data[$id] = 0;
        }

        $data[$id]++;

        $this->writeLogData($filePath, $data);
    }
}
