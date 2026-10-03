<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Plugin\Image;

use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Panth\ImageSeo\Plugin\Image\ImageFactoryPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ImageFactoryPluginTest extends TestCase
{
    private function product(int $id = 5): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn($id);
        return $product;
    }

    public function testDisabledLeavesBlockUntouched(): void
    {
        $resolver = $this->createMock(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(false);
        $resolver->expects($this->never())->method('getAlt');
        $block = new DataObject(['label' => 'Original']);

        $result = (new ImageFactoryPlugin($resolver, $this->createStub(LoggerInterface::class)))
            ->afterCreate($this->createStub(ImageFactory::class), $block, $this->product());

        $this->assertSame($block, $result);
        $this->assertSame('Original', $block->getData('label'));
    }

    public function testNonDataObjectResultIsReturnedAsIs(): void
    {
        $resolver = $this->createMock(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->expects($this->never())->method('getAlt');

        $result = (new ImageFactoryPlugin($resolver, $this->createStub(LoggerInterface::class)))
            ->afterCreate($this->createStub(ImageFactory::class), 'not-a-block', $this->product());

        $this->assertSame('not-a-block', $result);
    }

    public function testRenderedAltIsStampedOntoTheBlockLabel(): void
    {
        $product = $this->product();
        $resolver = $this->createMock(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->expects($this->once())->method('getAlt')->with($product)->willReturn('Buy Red Shoe');
        $block = new DataObject(['label' => 'Red Shoe']);

        $result = (new ImageFactoryPlugin($resolver, $this->createStub(LoggerInterface::class)))
            ->afterCreate($this->createStub(ImageFactory::class), $block, $product);

        $this->assertSame($block, $result);
        $this->assertSame('Buy Red Shoe', $block->getData('label'));
    }

    public function testEmptyAltKeepsExistingLabel(): void
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->method('getAlt')->willReturn('');
        $block = new DataObject(['label' => 'Keep']);

        (new ImageFactoryPlugin($resolver, $this->createStub(LoggerInterface::class)))
            ->afterCreate($this->createStub(ImageFactory::class), $block, $this->product());

        $this->assertSame('Keep', $block->getData('label'));
    }

    public function testFailureIsLoggedWithProductIdAndBlockReturned(): void
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->method('getAlt')->willThrowException(new \RuntimeException('oops'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] ImageFactoryPlugin::afterCreate failed', ['error' => 'oops', 'product_id' => 77]);
        $block = new DataObject(['label' => 'Keep']);

        $result = (new ImageFactoryPlugin($resolver, $logger))
            ->afterCreate($this->createStub(ImageFactory::class), $block, $this->product(77));

        $this->assertSame($block, $result);
        $this->assertSame('Keep', $block->getData('label'));
    }
}
