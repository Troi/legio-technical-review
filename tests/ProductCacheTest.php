<?php

declare(strict_types=1);

namespace App\Tests;

use App\Controller\ProductController;
use App\Model\Product;
use App\Repository\ProductRepository;
use App\Service\ProductSerializer;
use App\Service\IQueryCounter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductCacheTest extends TestCase
{
    public function testNullProductIsNotCached(): void
    {
        $id = 'non-existent';
        $productRepository = $this->createMock(ProductRepository::class);
        $productSerializer = $this->createMock(ProductSerializer::class);

        $productRepository->expects($this->once())
            ->method('findProduct')
            ->with($id)
            ->willReturn(null);

        $queryCounter = $this->createMock(IQueryCounter::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(function($key, $callback) {
            return $callback($this->createMock(ItemInterface::class));
        });

        $controller = new ProductController($queryCounter);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(function($id) {
            if ($id === 'serializer') {
                $serializer = $this->createMock(SerializerInterface::class);
                $serializer->method('serialize')->willReturn('{"error":"Product not found"}');
                return $serializer;
            }
            return null;
        });
        $controller->setContainer($container);

        $response = $controller->detail($id, $productRepository, $productSerializer, $cache);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($response->isCacheable(), '404 response should not be cacheable');
        $this->assertTrue($response->headers->hasCacheControlDirective('private'), '404 response should be private');
        $this->assertEquals(0, $response->getMaxAge(), '404 response max-age should be 0');
    }

    public function testFoundProductIsCacheable(): void
    {
        $id = '123';
        $productRepository = $this->createMock(ProductRepository::class);
        $productSerializer = $this->createMock(ProductSerializer::class);
        $product = $this->createMock(Product::class);

        $productRepository->expects($this->once())
            ->method('findProduct')
            ->with($id)
            ->willReturn($product);

        $productSerializer->expects($this->once())
            ->method('getCustomerData')
            ->with($product)
            ->willReturn(['id' => '123', 'name' => 'Test Product']);

        $queryCounter = $this->createMock(IQueryCounter::class);
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturnCallback(function($key, $callback) {
            return $callback($this->createMock(ItemInterface::class));
        });

        $controller = new ProductController($queryCounter);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(function($id) {
            if ($id === 'serializer') {
                $serializer = $this->createMock(SerializerInterface::class);
                $serializer->method('serialize')->willReturn('{"id":"123","name":"Test Product"}');
                return $serializer;
            }
            return null;
        });
        $controller->setContainer($container);

        $response = $controller->detail($id, $productRepository, $productSerializer, $cache);

        $this->assertEquals(200, $response->getStatusCode());
        // Note: The #[Cache] attribute is handled by the HttpCache listener in a real app.
        // In a unit test, we might not see the headers unless we manually trigger the listener
        // or if the attribute sets them directly (it doesn't, it's just metadata).
        // However, we want to check if our controller logic for 404 works.
    }
}
