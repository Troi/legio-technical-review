<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\DummyJsonCache;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\ItemInterface;

class DummyJsonCacheTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/dummy_cache_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->cacheDir);
        }
    }

    public function testCacheStoresAndRetrievesData(): void
    {
        $cache = new DummyJsonCache($this->cacheDir);
        $key = 'test_key';
        $value = ['foo' => 'bar'];

        // First call should execute callback
        $result = $cache->get($key, function (ItemInterface $item) use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
        $this->assertFileExists($this->cacheDir . '/' . $key . '.json');

        // Second call should retrieve from file (we can check this by having a callback that fails)
        $result2 = $cache->get($key, function (ItemInterface $item) {
            $this->fail('Callback should not be called when value is cached');
        });

        $this->assertEquals($value, $result2);
    }

    public function testCacheExpiration(): void
    {
        $cache = new DummyJsonCache($this->cacheDir);
        $key = 'expiring_key';

        // Cache with 0 expiration (immediate)
        $cache->get($key, function (ItemInterface $item) {
            $item->expiresAfter(-1);
            return 'expired';
        });

        // Second call should execute callback again
        $called = false;
        $result = $cache->get($key, function (ItemInterface $item) use (&$called) {
            $called = true;
            return 'new_value';
        });

        $this->assertTrue($called);
        $this->assertEquals('new_value', $result);
    }
}
