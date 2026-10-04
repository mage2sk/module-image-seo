<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Plugin\Image;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Panth\ImageSeo\Plugin\Image\ImageAttributesPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ImageAttributesPluginTest extends TestCase
{
    private function helper(?ProductInterface $product): ImageHelper
    {
        $helper = $this->createStub(ImageHelper::class);
        $property = new \ReflectionProperty(ImageHelper::class, '_product');
        $property->setValue($helper, $product);
        return $helper;
    }

    private function product(): ProductInterface
    {
        $product = $this->createStub(ProductInterface::class);
        $product->method('getName')->willReturn('Red Shoe');
        return $product;
    }

    public function testDisabledReturnsOriginalLabelWithoutResolving(): void
    {
        $resolver = $this->createMock(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(false);
        $resolver->expects($this->never())->method('getAlt');

        $plugin = new ImageAttributesPlugin($resolver, $this->createStub(LoggerInterface::class));

        $this->assertSame('orig', $plugin->afterGetLabel($this->helper($this->product()), 'orig'));
    }

    public function testHelperWithoutProductKeepsOriginalLabel(): void
    {
        $resolver = $this->createMock(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->expects($this->never())->method('getAlt');

        $plugin = new ImageAttributesPlugin($resolver, $this->createStub(LoggerInterface::class));

        $this->assertSame('orig', $plugin->afterGetLabel($this->helper(null), 'orig'));
    }

    public function testRenderedAltReplacesTheLabel(): void
    {
        $product = $this->product();
        $resolver = $this->createMock(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->expects($this->once())->method('getAlt')->with($product)->willReturn('Buy Red Shoe');

        $plugin = new ImageAttributesPlugin($resolver, $this->createStub(LoggerInterface::class));

        $this->assertSame('Buy Red Shoe', $plugin->afterGetLabel($this->helper($product), 'orig'));
    }

    public function testEmptyAltKeepsOriginalLabel(): void
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->method('getAlt')->willReturn('');

        $plugin = new ImageAttributesPlugin($resolver, $this->createStub(LoggerInterface::class));

        $this->assertNull($plugin->afterGetLabel($this->helper($this->product()), null));
    }

    public function testResolverFailureIsLoggedAndOriginalLabelKept(): void
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isEnabled')->willReturn(true);
        $resolver->method('getAlt')->willThrowException(new \RuntimeException('bad'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] ImageAttributesPlugin::afterGetLabel failed', ['error' => 'bad']);

        $plugin = new ImageAttributesPlugin($resolver, $logger);

        $this->assertSame('orig', $plugin->afterGetLabel($this->helper($this->product()), 'orig'));
    }
}
