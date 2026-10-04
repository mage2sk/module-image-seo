<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Model\ImageSeo;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ImageSeo\Model\ImageSeo\AltGenerator;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ImageTemplateResolverTest extends TestCase
{
    private function config(array $flags, array $values = []): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnCallback(
            static fn(string $path, string $scope = 'default') =>
                $scope === ScopeInterface::SCOPE_STORE && !empty($flags[$path])
        );
        $config->method('getValue')->willReturnCallback(
            static fn(string $path) => $values[$path] ?? null
        );
        return $config;
    }

    private function product(string $name = 'Red Shoe', string $sku = 'RS-1', int $id = 7): ProductInterface
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getName')->willReturn($name);
        $product->method('getSku')->willReturn($sku);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    private function realGenerator(): AltGenerator
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getName')->willReturn('Shop');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new AltGenerator(
            $this->createStub(ScopeConfigInterface::class),
            $storeManager,
            $this->createStub(LoggerInterface::class)
        );
    }

    public function testIsEnabledReadsTheStoreScopedFlag(): void
    {
        $on = new ImageTemplateResolver(
            $this->realGenerator(),
            $this->config([ImageTemplateResolver::XML_PATH_ENABLED => true]),
            $this->createStub(LoggerInterface::class)
        );
        $off = new ImageTemplateResolver(
            $this->realGenerator(),
            $this->config([]),
            $this->createStub(LoggerInterface::class)
        );

        $this->assertTrue($on->isEnabled());
        $this->assertFalse($off->isEnabled());
    }

    public function testGalleryRequiresBothFlags(): void
    {
        $both = new ImageTemplateResolver($this->realGenerator(), $this->config([
            ImageTemplateResolver::XML_PATH_ENABLED => true,
            ImageTemplateResolver::XML_PATH_GALLERY_ENABLED => true,
        ]), $this->createStub(LoggerInterface::class));
        $galleryOnly = new ImageTemplateResolver($this->realGenerator(), $this->config([
            ImageTemplateResolver::XML_PATH_GALLERY_ENABLED => true,
        ]), $this->createStub(LoggerInterface::class));
        $moduleOnly = new ImageTemplateResolver($this->realGenerator(), $this->config([
            ImageTemplateResolver::XML_PATH_ENABLED => true,
        ]), $this->createStub(LoggerInterface::class));

        $this->assertTrue($both->isGalleryEnabled());
        $this->assertFalse($galleryOnly->isGalleryEnabled());
        $this->assertFalse($moduleOnly->isGalleryEnabled());
    }

    public function testDisabledReturnsProductNameWithoutRendering(): void
    {
        $generator = $this->createMock(AltGenerator::class);
        $generator->expects($this->never())->method('generate');

        $resolver = new ImageTemplateResolver(
            $generator,
            $this->config([], [ImageTemplateResolver::XML_PATH_ALT_TEMPLATE => 'Buy {{name}}']),
            $this->createStub(LoggerInterface::class)
        );

        $this->assertSame('Red Shoe', $resolver->getAlt($this->product()));
        $this->assertSame('Red Shoe', $resolver->getTitle($this->product()));
    }

    public function testResolvePassesTrimmedTemplatesAndProductContextToTheGenerator(): void
    {
        $product = $this->product('Blue Hat', 'BH-2');
        $generator = $this->createMock(AltGenerator::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with('Buy {{name}}', '{{name}} - {{sku}}', $product, ['name' => 'Blue Hat', 'sku' => 'BH-2'])
            ->willReturn(['alt' => 'A', 'title' => 'T']);

        $resolver = new ImageTemplateResolver($generator, $this->config([], [
            ImageTemplateResolver::XML_PATH_ALT_TEMPLATE => '  Buy {{name}}  ',
            ImageTemplateResolver::XML_PATH_TITLE_TEMPLATE => "{{name}} - {{sku}}\n",
        ]), $this->createStub(LoggerInterface::class));

        $this->assertSame(['alt' => 'A', 'title' => 'T'], $resolver->resolve($product));
    }

    public function testMissingTemplatesArePassedAsEmptyStrings(): void
    {
        $generator = $this->createMock(AltGenerator::class);
        $generator->expects($this->once())
            ->method('generate')
            ->with('', '', $this->anything(), $this->anything())
            ->willReturn(['alt' => 'x', 'title' => 'y']);

        $resolver = new ImageTemplateResolver($generator, $this->config([]), $this->createStub(LoggerInterface::class));

        $this->assertSame(['alt' => 'x', 'title' => 'y'], $resolver->resolve($this->product()));
    }

    public function testEnabledAltAndTitleComeFromTheRenderedTemplates(): void
    {
        $resolver = new ImageTemplateResolver($this->realGenerator(), $this->config(
            [ImageTemplateResolver::XML_PATH_ENABLED => true],
            [
                ImageTemplateResolver::XML_PATH_ALT_TEMPLATE => 'Buy {{name}} at {{store}}',
                ImageTemplateResolver::XML_PATH_TITLE_TEMPLATE => '{{name|upper}} ({{sku}})',
            ]
        ), $this->createStub(LoggerInterface::class));

        $this->assertSame('Buy Red Shoe at Shop', $resolver->getAlt($this->product()));
        $this->assertSame('RED SHOE (RS-1)', $resolver->getTitle($this->product()));
    }

    public function testGeneratorFailureIsLoggedAndFallsBackToTheName(): void
    {
        $generator = $this->createStub(AltGenerator::class);
        $generator->method('generate')->willThrowException(new \RuntimeException('boom'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] ImageTemplateResolver failed', ['error' => 'boom', 'product_id' => 42]);

        $resolver = new ImageTemplateResolver($generator, $this->config([]), $logger);

        $this->assertSame(
            ['alt' => 'Lamp', 'title' => 'Lamp'],
            $resolver->resolve($this->product('Lamp', 'L', 42))
        );
    }
}
