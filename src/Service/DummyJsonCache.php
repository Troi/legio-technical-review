<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * If I could, I would not implement my own dummy cache, but it was in requirements.
 * I at least wanted to keep CacheInterface compatibility
 * to not invent a new interface to later easier replacement.
 *
 * I would use built-in mechanisms of Symphony over the HTTP / Controller layer.
 */
class DummyJsonCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        $filePath = sprintf('%s%s.json', $this->cacheDir, $key);

        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            if ($data !== null && (isset($data['expires_at']) && $data['expires_at'] > time())) {
                return $data['value'];
            }
            // Expired or invalid
            unlink($filePath);
        }

        $item = new DummyCacheItem($key);
        $value = $callback($item);

        $data = [
            'value' => $value,
            'expires_at' => time() + $item->getExpiration(),
        ];

        file_put_contents($filePath, json_encode($data));

        return $value;
    }

    public function delete(string $key): bool
    {
        $filePath = $this->cacheDir . $key . '.json';
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true;
    }
}

/**
 * Minimal implementation of ItemInterface for DummyJsonCache
 */
class DummyCacheItem implements ItemInterface
{
    private string $key;
    private int $expiration = 3600;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return null;
    }

    public function isHit(): bool
    {
        return false;
    }

    public function set(mixed $value): static
    {
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        if ($expiration instanceof \DateTimeInterface) {
            $this->expiration = $expiration->getTimestamp() - time();
        }
        return $this;
    }

    public function expiresAfter(\DateInterval|int|null $time): static
    {
        if ($time instanceof \DateInterval) {
            $now = new \DateTime();
            $this->expiration = $now->add($time)->getTimestamp() - time();
        } elseif (is_int($time)) {
            $this->expiration = $time;
        }
        return $this;
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function tag(string|iterable $tags): static
    {
        return $this;
    }

    public function getMetadata(): array
    {
        return [];
    }
}
