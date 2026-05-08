<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Enum\DatasourceType;
use App\Model\Product;
use App\Repository\ProductRepository;
use App\Service\IElasticSearchDriver;
use App\Service\IMySQLDriver;
use App\Service\IQueryCounter;
use App\Service\ProductHydrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TimeoutException;

class ProductRepositoryTest extends TestCase
{
    private ProductHydrator $productHydrator;
    private $elasticSearchDriver;
    private $mySQLDriver;
    private $productQueryCounter;
    private ProductRepository $repository;

    protected function setUp(): void
    {
        $this->productHydrator = new ProductHydrator();
        $this->elasticSearchDriver = new IElasticSearchDriver();
        $this->mySQLDriver = $this->createMock(IMySQLDriver::class);
        $this->productQueryCounter = $this->createMock(IQueryCounter::class);

        $this->repository = new ProductRepository(
            $this->productHydrator,
            $this->elasticSearchDriver,
            $this->mySQLDriver,
            $this->productQueryCounter
        );
    }

    public function testFindProductHappyPassElastic(): void
    {
        $id = '122'; // Even ID returns valid data in dummy driver

        $this->mySQLDriver->expects($this->never())
            ->method('findProduct');

        $this->productQueryCounter->expects($this->once())
            ->method('logQuery')
            ->with($id, DatasourceType::ELASTICSEARCH);

        $product = $this->repository->findProduct($id);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($id, $product->getId());
        $this->assertEquals('Product #122', $product->getName());
    }

    public function testFindProductFallbackOnInvalidData(): void
    {
        $id = '123'; // Odd ID returns invalid data in dummy driver
        $mysqlData = ['id' => $id, 'name' => 'MySQL Product'];

        $this->mySQLDriver->expects($this->once())
            ->method('findProduct')
            ->with($id)
            ->willReturn($mysqlData);

        $this->productQueryCounter->expects($this->exactly(2))
            ->method('logQuery')
            ->with($this->callback(function($idArg) use ($id) {
                return $idArg === $id;
            }), $this->logicalOr(
                $this->equalTo(DatasourceType::ELASTICSEARCH),
                $this->equalTo(DatasourceType::MYSQL)
            ));

        $product = $this->repository->findProduct($id);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($id, $product->getId());
        $this->assertEquals('MySQL Product', $product->getName());
    }

    public function testFindProductFallbackOnTimeout(): void
    {
        $id = '124'; // Even ID but we will force a mock here if we want timeout
        // Actually, if we want to test timeout, we still need to mock the driver
        // because the dummy driver doesn't throw TimeoutException.

        $elasticDriverMock = $this->createMock(IElasticSearchDriver::class);
        $elasticDriverMock->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willThrowException(new TimeoutException());

        $repositoryWithMock = new ProductRepository(
            $this->productHydrator,
            $elasticDriverMock,
            $this->mySQLDriver,
            $this->productQueryCounter
        );

        $mysqlData = ['id' => $id, 'name' => 'MySQL Product'];

        $this->mySQLDriver->expects($this->once())
            ->method('findProduct')
            ->with($id)
            ->willReturn($mysqlData);

        $this->productQueryCounter->expects($this->exactly(2))
            ->method('logQuery')
            ->with($this->callback(function($idArg) use ($id) {
                return $idArg === $id;
            }), $this->logicalOr(
                $this->equalTo(DatasourceType::ELASTICSEARCH),
                $this->equalTo(DatasourceType::MYSQL)
            ));

        $product = $repositoryWithMock->findProduct($id);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($id, $product->getId());
        $this->assertEquals('MySQL Product', $product->getName());
    }
}
