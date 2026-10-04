<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Plugin\Image;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Panth\ImageSeo\Plugin\Image\ProductImagePlugin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductImagePluginTest extends TestCase
{
    private function config(bool $enabled): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnCallback(
            static fn(string $path, string $scope = 'default') => $enabled
                && $path === ImageTemplateResolver::XML_PATH_ENABLED
                && $scope === ScopeInterface::SCOPE_STORE
        );
        return $config;
    }

    private function helper(?ProductInterface $product): ImageHelper
    {
        $helper = $this->createStub(ImageHelper::class);
        $property = new \ReflectionProperty(ImageHelper::class, '_product');
        $property->setValue($helper, $product);
        return $helper;
    }

    private function product(string $name): ProductInterface
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getName')->willReturn($name);
        return $product;
    }

    private function plugin(bool $enabled, ?LoggerInterface $logger = null): ProductImagePlugin
    {
        return new ProductImagePlugin($this->config($enabled), $logger ?? $this->createStub(LoggerInterface::class));
    }

    public function testDisabledReturnsResultUnchanged(): void
    {
        $this->assertNull($this->plugin(false)->afterGetLabel($this->helper($this->product('Shoe')), null));
    }

    public function testExistingLabelIsKept(): void
    {
        $this->assertSame(
            'Merchant label',
            $this->plugin(true)->afterGetLabel($this->helper($this->product('Shoe')), 'Merchant label')
        );
    }

    public static function emptyLabelProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => ["  \t"],
            'false' => [false],
        ];
    }

    #[DataProvider('emptyLabelProvider')]
    public function testEmptyLabelFallsBackToProductName(mixed $label): void
    {
        $this->assertSame('Shoe', $this->plugin(true)->afterGetLabel($this->helper($this->product('Shoe')), $label));
    }

    public function testEmptyProductNameKeepsResult(): void
    {
        $this->assertSame('', $this->plugin(true)->afterGetLabel($this->helper($this->product('')), ''));
    }

    public function testMissingProductKeepsResult(): void
    {
        $this->assertNull($this->plugin(true)->afterGetLabel($this->helper(null), null));
    }

    public function testNameFailureIsLoggedAndResultKept(): void
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getName')->willThrowException(new \RuntimeException('broken'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] getLabel fallback failed: broken');

        $this->assertSame('', $this->plugin(true, $logger)->afterGetLabel($this->helper($product), ''));
    }
}
