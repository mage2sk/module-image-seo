<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Plugin\Image;

use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Model\Product;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Panth\ImageSeo\Plugin\Image\GalleryImageSeoPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GalleryImageSeoPluginTest extends TestCase
{
    private function gallery(?Product $product): Gallery
    {
        $gallery = $this->createStub(Gallery::class);
        $gallery->method('getProduct')->willReturn($product);
        return $gallery;
    }

    private function product(string $name = 'Red Shoe'): Product
    {
        $product = $this->createStub(Product::class);
        $product->method('getName')->willReturn($name);
        return $product;
    }

    private function resolver(bool $enabled, array $resolved = ['alt' => 'Alt', 'title' => 'Title']): ImageTemplateResolver
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isGalleryEnabled')->willReturn($enabled);
        $resolver->method('resolve')->willReturn($resolved);
        return $resolver;
    }

    private function plugin(ImageTemplateResolver $resolver, ?LoggerInterface $logger = null): GalleryImageSeoPlugin
    {
        return new GalleryImageSeoPlugin($resolver, $logger ?? $this->createStub(LoggerInterface::class));
    }

    public function testNonStringAndEmptyResultsAreReturnedAsStrings(): void
    {
        $plugin = $this->plugin($this->resolver(true));
        $gallery = $this->gallery($this->product());

        $this->assertSame('', $plugin->afterGetGalleryImagesJson($gallery, null));
        $this->assertSame('', $plugin->afterGetGalleryImagesJson($gallery, ''));
    }

    public function testDisabledGalleryLeavesJsonUntouched(): void
    {
        $json = '[{"caption":"","file":"/a/b.jpg"}]';

        $this->assertSame(
            $json,
            $this->plugin($this->resolver(false))->afterGetGalleryImagesJson($this->gallery($this->product()), $json)
        );
    }

    public function testMissingProductLeavesJsonUntouched(): void
    {
        $json = '[{"caption":""}]';

        $this->assertSame($json, $this->plugin($this->resolver(true))->afterGetGalleryImagesJson($this->gallery(null), $json));
    }

    public function testInvalidOrEmptyJsonIsReturnedAsIs(): void
    {
        $plugin = $this->plugin($this->resolver(true));
        $gallery = $this->gallery($this->product());

        $this->assertSame('not-json', $plugin->afterGetGalleryImagesJson($gallery, 'not-json'));
        $this->assertSame('[]', $plugin->afterGetGalleryImagesJson($gallery, '[]'));
    }

    public function testPlaceholderCaptionsAreReplacedWithPositionedAlt(): void
    {
        $images = [
            ['caption' => '', 'file' => '/r/e/red-front.jpg'],
            ['caption' => 'Red Shoe', 'file' => '/r/e/red-side.jpg'],
            ['caption' => 'Main Product Photo', 'file' => '/r/e/red-back.jpg'],
            ['caption' => 'red-top', 'file' => '/r/e/red-top.jpg'],
            ['caption' => 'red-sole.jpg', 'file' => '/r/e/red-sole.jpg'],
            ['caption' => 'Hand-written caption', 'file' => '/r/e/red-box.jpg'],
        ];

        $out = json_decode(
            $this->plugin($this->resolver(true, ['alt' => 'Buy Red Shoe', 'title' => 'Red Shoe Title']))
                ->afterGetGalleryImagesJson($this->gallery($this->product()), json_encode($images)),
            true
        );

        $this->assertSame('Buy Red Shoe - Image 1 of 6', $out[0]['caption']);
        $this->assertSame('Buy Red Shoe - Image 2 of 6', $out[1]['caption']);
        $this->assertSame('Buy Red Shoe - Image 3 of 6', $out[2]['caption']);
        $this->assertSame('Buy Red Shoe - Image 4 of 6', $out[3]['caption']);
        $this->assertSame('Buy Red Shoe - Image 5 of 6', $out[4]['caption']);
        $this->assertSame('Hand-written caption', $out[5]['caption']);
        foreach ($out as $image) {
            $this->assertSame('Red Shoe Title', $image['title']);
        }
    }

    public function testSingleImageGetsAltWithoutPositionSuffix(): void
    {
        $json = json_encode([['caption' => 'image', 'file' => '/x/y.jpg']]);

        $out = json_decode(
            $this->plugin($this->resolver(true, ['alt' => 'Only Alt', 'title' => 'T']))
                ->afterGetGalleryImagesJson($this->gallery($this->product()), $json),
            true
        );

        $this->assertSame('Only Alt', $out[0]['caption']);
    }

    public function testExistingCustomTitleIsKept(): void
    {
        $json = json_encode([['caption' => '', 'title' => 'My own title', 'file' => '/x/y.jpg']]);

        $out = json_decode(
            $this->plugin($this->resolver(true))->afterGetGalleryImagesJson($this->gallery($this->product()), $json),
            true
        );

        $this->assertSame('My own title', $out[0]['title']);
        $this->assertSame('Alt', $out[0]['caption']);
    }

    public function testEmptyResolvedTitleFallsBackToTheNewCaption(): void
    {
        $json = json_encode([['caption' => '', 'file' => '/x/y.jpg']]);

        $out = json_decode(
            $this->plugin($this->resolver(true, ['alt' => 'Alt text', 'title' => '']))
                ->afterGetGalleryImagesJson($this->gallery($this->product()), $json),
            true
        );

        $this->assertSame('Alt text', $out[0]['title']);
    }

    public function testEmptyAltKeepsCaptionAndTitleFallsBackToProductName(): void
    {
        $json = json_encode([['file' => '/x/y.jpg']]);

        $out = json_decode(
            $this->plugin($this->resolver(true, ['alt' => '', 'title' => '']))
                ->afterGetGalleryImagesJson($this->gallery($this->product('Lamp')), $json),
            true
        );

        $this->assertArrayNotHasKey('caption', $out[0]);
        $this->assertSame('Lamp', $out[0]['title']);
    }

    public function testNonArrayEntriesArePreserved(): void
    {
        $json = json_encode(['skip-me', ['caption' => '', 'file' => '/x/y.jpg']]);

        $out = json_decode(
            $this->plugin($this->resolver(true))->afterGetGalleryImagesJson($this->gallery($this->product()), $json),
            true
        );

        $this->assertSame('skip-me', $out[0]);
        $this->assertSame('Alt - Image 2 of 2', $out[1]['caption']);
    }

    public function testMarkupInAltIsHexEncodedAndSlashesAreNotEscaped(): void
    {
        $json = json_encode([['caption' => '', 'file' => '/x/y.jpg']]);

        $out = $this->plugin($this->resolver(true, ['alt' => '<b>Bold</b>', 'title' => 'a/b']))
            ->afterGetGalleryImagesJson($this->gallery($this->product()), $json);

        $this->assertStringContainsString('u003Cbu003EBold', str_replace(chr(92), '', $out));
        $this->assertSame('<b>Bold</b>', json_decode($out, true)[0]['caption']);
        $this->assertStringNotContainsString('<b>', $out);
        $this->assertStringContainsString('"file":"/x/y.jpg"', $out);
    }

    public function testResolverFailureIsLoggedAndOriginalJsonReturned(): void
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isGalleryEnabled')->willReturn(true);
        $resolver->method('resolve')->willThrowException(new \RuntimeException('fail'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] GalleryImageSeoPlugin failed', ['error' => 'fail']);

        $json = '[{"caption":""}]';

        $this->assertSame(
            $json,
            $this->plugin($resolver, $logger)->afterGetGalleryImagesJson($this->gallery($this->product()), $json)
        );
    }

    private function collection(array $labels): \Magento\Framework\Data\Collection
    {
        $collection = new \Magento\Framework\Data\Collection(
            $this->createStub(\Magento\Framework\Data\Collection\EntityFactoryInterface::class)
        );
        foreach ($labels as $i => $label) {
            $collection->addItem(new \Magento\Framework\DataObject([
                'id' => $i + 1,
                'label' => $label,
                'file' => '/r/e/red-' . $i . '.jpg',
            ]));
        }
        return $collection;
    }

    private function labels(\Magento\Framework\Data\Collection $collection): array
    {
        $labels = [];
        foreach ($collection as $item) {
            $labels[] = $item->getData('label');
        }
        return $labels;
    }

    public function testGalleryImageLabelsPlaceholdersAreReplacedAndMerchantLabelsKept(): void
    {
        $collection = $this->collection([null, 'Image', 'Red Shoe', 'red-3', 'Merchant label']);

        $result = $this->plugin($this->resolver(true, ['alt' => 'Buy Red Shoe', 'title' => 'T']))
            ->afterGetGalleryImages($this->gallery($this->product()), $collection);

        $this->assertSame($collection, $result);
        $this->assertSame([
            'Buy Red Shoe - Image 1 of 5',
            'Buy Red Shoe - Image 2 of 5',
            'Buy Red Shoe - Image 3 of 5',
            'Buy Red Shoe - Image 4 of 5',
            'Merchant label',
        ], $this->labels($collection));
    }

    public function testGalleryImageLabelSingleImageHasNoPositionAndIsIdempotent(): void
    {
        $collection = $this->collection(['main product photo']);
        $plugin = $this->plugin($this->resolver(true, ['alt' => 'Only Alt', 'title' => 'T']));
        $gallery = $this->gallery($this->product());

        $plugin->afterGetGalleryImages($gallery, $collection);
        $plugin->afterGetGalleryImages($gallery, $collection);

        $this->assertSame(['Only Alt'], $this->labels($collection));
    }

    public function testGalleryImageLabelsUntouchedWhenDisabledOrNoProductOrEmptyAlt(): void
    {
        $collection = $this->collection(['Image']);

        $this->plugin($this->resolver(false))->afterGetGalleryImages($this->gallery($this->product()), $collection);
        $this->plugin($this->resolver(true))->afterGetGalleryImages($this->gallery(null), $collection);
        $this->plugin($this->resolver(true, ['alt' => '', 'title' => '']))
            ->afterGetGalleryImages($this->gallery($this->product()), $collection);

        $this->assertSame(['Image'], $this->labels($collection));
    }

    public function testGalleryImagesNonIterableOrEmptyResultReturnedAsIs(): void
    {
        $plugin = $this->plugin($this->resolver(true));
        $gallery = $this->gallery($this->product());
        $empty = $this->collection([]);

        $this->assertNull($plugin->afterGetGalleryImages($gallery, null));
        $this->assertSame($empty, $plugin->afterGetGalleryImages($gallery, $empty));
    }

    public function testGalleryImagesNonDataObjectItemsAreSkippedButCounted(): void
    {
        $item = new \Magento\Framework\DataObject(['label' => '', 'file' => '/a/b.jpg']);

        $this->plugin($this->resolver(true))
            ->afterGetGalleryImages($this->gallery($this->product()), ['skip', $item]);

        $this->assertSame('Alt - Image 2 of 2', $item->getData('label'));
    }

    public function testGalleryImagesResolverFailureIsLoggedAndResultReturned(): void
    {
        $resolver = $this->createStub(ImageTemplateResolver::class);
        $resolver->method('isGalleryEnabled')->willReturn(true);
        $resolver->method('resolve')->willThrowException(new \RuntimeException('boom'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] GalleryImageSeoPlugin::afterGetGalleryImages failed', ['error' => 'boom']);
        $collection = $this->collection(['Image']);

        $this->assertSame(
            $collection,
            $this->plugin($resolver, $logger)->afterGetGalleryImages($this->gallery($this->product()), $collection)
        );
        $this->assertSame(['Image'], $this->labels($collection));
    }

    public function testJsonCaptionKeepsLabelAlreadyRewrittenByGalleryImagesPlugin(): void
    {
        $json = json_encode([
            ['caption' => 'Buy Red Shoe - Image 1 of 2', 'file' => '/a.jpg'],
            ['caption' => 'Buy Red Shoe - Image 2 of 2', 'file' => '/b.jpg'],
        ]);

        $out = json_decode(
            $this->plugin($this->resolver(true, ['alt' => 'Buy Red Shoe', 'title' => 'T']))
                ->afterGetGalleryImagesJson($this->gallery($this->product()), $json),
            true
        );

        $this->assertSame('Buy Red Shoe - Image 1 of 2', $out[0]['caption']);
        $this->assertSame('Buy Red Shoe - Image 2 of 2', $out[1]['caption']);
        $this->assertSame('T', $out[1]['title']);
    }
}
