<?php declare(strict_types=1);

namespace Shopware\Core\Content\Test\Product;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\StorefrontFunctionalTestBehaviour;

class ProductControllerTest extends TestCase
{
    use StorefrontFunctionalTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp(): void
    {
        $this->productRepository = $this->getContainer()->get('product.repository');
        $this->connection = $this->getContainer()->get(Connection::class);
    }

    public function testProductList(): void
    {
        $manufacturerId = Uuid::uuid4()->getHex();
        $taxId = Uuid::uuid4()->getHex();

        $client = $this->getStorefrontClient();
        $salesChannelId = $this->salesChannelIds[count($this->salesChannelIds) - 1];

        $this->productRepository->create([
            [
                'id' => Uuid::uuid4()->getHex(),
                'name' => 'Test',
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['id' => $manufacturerId, 'name' => 'test'],
                'tax' => ['id' => $taxId, 'taxRate' => 17, 'name' => 'with id'],
                'visibilities' => [
                    ['salesChannelId' => $salesChannelId, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ],
        ], Context::createDefaultContext());

        $client->request('GET', '/storefront-api/v1/product');

        static::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertNotEmpty($content);
        static::assertArrayHasKey('total', $content);
        static::assertArrayHasKey('data', $content);
        static::assertGreaterThan(0, $content['total']);
        static::assertNotEmpty($content['data']);

        foreach ($content['data'] as $product) {
            static::assertArrayHasKey('calculatedListingPrice', $product);
            static::assertArrayHasKey('calculatedPriceRules', $product);
            static::assertArrayHasKey('calculatedPrice', $product);
            static::assertArrayHasKey('price', $product);
            static::assertArrayHasKey('name', $product);
            static::assertArrayHasKey('id', $product);
        }
    }

    public function testProductDetail(): void
    {
        $productId = Uuid::uuid4()->getHex();
        $manufacturerId = Uuid::uuid4()->toString();
        $taxId = Uuid::uuid4()->toString();

        $client = $this->getStorefrontClient();
        $salesChannelId = $this->salesChannelIds[count($this->salesChannelIds) - 1];

        $this->productRepository->create([
            [
                'id' => $productId,
                'name' => 'Test',
                'price' => ['gross' => 10, 'net' => 9],
                'manufacturer' => ['id' => $manufacturerId, 'name' => 'test'],
                'tax' => ['id' => $taxId, 'taxRate' => 17, 'name' => 'with id'],
                'visibilities' => [
                    ['salesChannelId' => $salesChannelId, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ],
        ], Context::createDefaultContext());

        $client->request('GET', '/storefront-api/v1/product/' . $productId);

        static::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $content = json_decode($client->getResponse()->getContent(), true);

        static::assertEquals($productId, $content['data']['id']);
        static::assertEquals(10, $content['data']['price']['gross']);
        static::assertEquals('test', $content['data']['manufacturer']['name']);
        static::assertEquals('with id', $content['data']['tax']['name']);
        static::assertEquals(17, $content['data']['tax']['taxRate']);
    }
}
