<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Model\ImageSeo;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\ImageSeo\Model\ImageSeo\AltGenerator;
use Panth\ImageSeo\Model\ImageSeo\NullVisionAdapter;
use Panth\ImageSeo\Model\ImageSeo\VisionAdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AltGeneratorTest extends TestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    private function generator(
        ?VisionAdapterInterface $vision = null,
        ?LoggerInterface $logger = null,
        string $storeName = 'Main Store'
    ): AltGenerator {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getName')->willReturn($storeName);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new AltGenerator(
            $this->createStub(ScopeConfigInterface::class),
            $storeManager,
            $logger ?? $this->createStub(LoggerInterface::class),
            $vision
        );
    }

    private function entity(string $name = 'Entity Name', ?string $sku = 'ENT-1', ?string $category = null): object
    {
        return new class ($name, $sku, $category) {
            /**
             * @param string $name
             * @param string|null $sku
             * @param string|null $category
             */
            public function __construct(
                private string $name,
                private ?string $sku,
                private ?string $category
            ) {
            }

            /**
             * @return string
             */
            public function getName(): string
            {
                return $this->name;
            }

            /**
             * @return string|null
             */
            public function getSku(): ?string
            {
                return $this->sku;
            }

            /**
             * @return object|null
             */
            public function getCategory(): ?object
            {
                if ($this->category === null) {
                    return null;
                }
                return new class ($this->category) {
                    /**
                     * @param string $name
                     */
                    public function __construct(private string $name)
                    {
                    }

                    /**
                     * @return string
                     */
                    public function getName(): string
                    {
                        return $this->name;
                    }
                };
            }
        };
    }

    private function tempImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imgseo');
        file_put_contents($path, 'img');
        $this->tempFiles[] = $path;
        return $path;
    }

    public function testContextTokensAreRenderedIntoAltAndTitle(): void
    {
        $result = $this->generator()->generate(
            'Buy {{name}} - {{sku}}',
            '{{name}} by Shop',
            $this->entity(),
            ['name' => 'Red Shoe', 'sku' => 'RS-42']
        );

        $this->assertSame(['alt' => 'Buy Red Shoe - RS-42', 'title' => 'Red Shoe by Shop'], $result);
    }

    public function testContextValueWinsOverEntityValue(): void
    {
        $result = $this->generator()->generate('{{name}}', '', $this->entity('From Entity'), ['name' => 'From Context']);

        $this->assertSame('From Context', $result['alt']);
    }

    public function testEntityGettersAreUsedWhenContextIsMissing(): void
    {
        $result = $this->generator()->generate('{{name}} {{sku}}', '', $this->entity('Blue Hat', 'BH-1'));

        $this->assertSame('Blue Hat BH-1', $result['alt']);
    }

    public function testTokenNamesAreCaseInsensitiveAndAllowSpaces(): void
    {
        $result = $this->generator()->generate('{{ NAME }} / {{Sku}}', '', $this->entity('Cap', 'C-9'));

        $this->assertSame('Cap / C-9', $result['alt']);
    }

    public function testDottedTokenResolvesTheBaseToken(): void
    {
        $result = $this->generator()->generate('{{name.raw}}', '', $this->entity('Dotted'));

        $this->assertSame('Dotted', $result['alt']);
    }

    public function testStoreTokenReadsTheCurrentStoreName(): void
    {
        $result = $this->generator(null, null, 'Euro Store')
            ->generate('{{name}} at {{store}}', '', $this->entity('Mug'));

        $this->assertSame('Mug at Euro Store', $result['alt']);
    }

    public function testStoreLookupFailureIsLoggedAndRendersEmpty(): void
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] token resolve failed', ['token' => 'store', 'error' => 'no store']);

        $generator = new AltGenerator(
            $this->createStub(ScopeConfigInterface::class),
            $storeManager,
            $logger
        );

        $this->assertSame('Mug', $generator->generate('{{name}} | {{store}}', '', $this->entity('Mug'))['alt']);
    }

    public function testCategoryTokenUsesTheEntityCategoryName(): void
    {
        $result = $this->generator()
            ->generate('{{name}} in {{category}}', '', $this->entity('Lamp', null, 'Lighting'));

        $this->assertSame('Lamp in Lighting', $result['alt']);
    }

    public function testCategoryTokenIsEmptyWithoutCategory(): void
    {
        $result = $this->generator()->generate('{{category}} - {{name}}', '', $this->entity('Lamp'));

        $this->assertSame('Lamp', $result['alt']);
    }

    public function testEntityWithoutGettersRendersEmptyTokens(): void
    {
        $result = $this->generator()
            ->generate('{{sku}}{{category}}', '', new \stdClass(), ['name' => 'Ctx']);

        $this->assertSame('Ctx', $result['alt']);
    }

    public function testUnknownTokenFallsBackToTheEntityName(): void
    {
        $result = $this->generator()->generate('{{color}}', '', $this->entity('Fallback'));

        $this->assertSame(['alt' => 'Fallback', 'title' => 'Fallback'], $result);
    }

    public function testNonScalarContextValueIsIgnoredForTokens(): void
    {
        $result = $this->generator()
            ->generate('{{sku}}', '', $this->entity('X', 'FROM-ENTITY'), ['sku' => ['a']]);

        $this->assertSame('FROM-ENTITY', $result['alt']);
    }

    public function testPlainTemplateIsReturnedTrimmed(): void
    {
        $result = $this->generator()->generate('  Static alt  ', '  Static title ', $this->entity());

        $this->assertSame(['alt' => 'Static alt', 'title' => 'Static title'], $result);
    }

    public static function filterProvider(): array
    {
        return [
            'upper' => ['{{name|upper}}', 'red shoe', 'RED SHOE'],
            'lower' => ['{{name | lower}}', 'Red SHOE', 'red shoe'],
            'title' => ['{{name|title}}', 'red shoe', 'Red Shoe'],
            'strip' => ['{{name|strip}}', "<b>Red</b>\n\n  shoe", 'Red shoe'],
            'truncate long' => ['{{name|truncate:5}}', 'Abcdefgh', 'Abcd...'],
            'truncate short' => ['{{name|truncate:20}}', 'Short', 'Short'],
            'truncate default 60' => ['{{name|truncate}}', str_repeat('a', 61), str_repeat('a', 59) . '...'],
            'truncate trims trailing space' => ['{{name|truncate:5}}', 'abc defgh', 'abc...'],
            'default used' => ["{{name|default:'Unnamed'}}", '', 'Unnamed'],
            'default with double quotes' => ['{{name|default:"Untitled"}}', '', 'Untitled'],
            'default skipped' => ["{{name|default:'Unnamed'}}", 'Real', 'Real'],
            'unknown filter ignored' => ['{{name|sparkle}}', 'Keep', 'Keep'],
            'chain' => ['{{name|strip|upper|truncate:4}}', '<i>abcdef</i>', 'ABC...'],
            'multibyte upper' => ['{{name|upper}}', "gr\u{fc}n", "GR\u{dc}N"],
        ];
    }

    #[DataProvider('filterProvider')]
    public function testFilters(string $template, string $name, string $expected): void
    {
        $result = $this->generator()->generate($template, '', new \stdClass(), ['name' => $name]);

        $this->assertSame($expected, $result['alt']);
    }

    public static function cleanupProvider(): array
    {
        return [
            'dangling trailing dash' => ['{{name}} - {{sku}}', 'Shoe'],
            'dangling leading pipe' => ['{{sku}} | {{name}}', 'Shoe'],
            'double comma collapsed' => ['{{name}}, {{sku}}, Shop', 'Shoe, Shop'],
            'leading comma removed' => ['{{sku}}, {{name}}', 'Shoe'],
            'extra spaces collapsed' => ['{{name}}    {{sku}}   Shop', 'Shoe Shop'],
            'dash then pipe normalised' => ['{{name}} - {{sku}} | Shop', 'Shoe | Shop'],
        ];
    }

    #[DataProvider('cleanupProvider')]
    public function testOutputCleanupRemovesSeparatorsLeftByEmptyTokens(string $template, string $expected): void
    {
        $result = $this->generator()->generate($template, '', new \stdClass(), ['name' => 'Shoe', 'sku' => '']);

        $this->assertSame($expected, $result['alt']);
    }

    public function testEmptyAltTemplateFallsBackToContextNameAndTitleUsesItsOwnTemplate(): void
    {
        $result = $this->generator()
            ->generate('', 'Title {{sku}}', $this->entity(), ['name' => 'Ctx Name', 'sku' => 'S1']);

        $this->assertSame(['alt' => 'Ctx Name', 'title' => 'Title S1'], $result);
    }

    public function testEmptyTitleCopiesAlt(): void
    {
        $result = $this->generator()->generate('Alt {{name}}', '', $this->entity('Pen'));

        $this->assertSame('Alt Pen', $result['title']);
    }

    public function testEverythingEmptyYieldsEmptyStrings(): void
    {
        $this->assertSame(['alt' => '', 'title' => ''], $this->generator()->generate('', '', null));
    }

    public function testLongOutputIsTruncatedTo250Characters(): void
    {
        $long = str_repeat('a', 300);
        $result = $this->generator()->generate('{{name}}', '{{name}}', null, ['name' => $long]);

        $this->assertSame(250, mb_strlen($result['alt']));
        $this->assertSame(str_repeat('a', 247) . '...', $result['alt']);
        $this->assertSame($result['alt'], $result['title']);
    }

    public function testTruncationCountsMultibyteCharacters(): void
    {
        $long = str_repeat("\u{e9}", 260);
        $result = $this->generator()->generate('{{name}}', '', null, ['name' => $long]);

        $this->assertSame(250, mb_strlen($result['alt'], 'UTF-8'));
        $this->assertStringEndsWith('...', $result['alt']);
    }

    public function testOutputAtExactly250CharactersIsKept(): void
    {
        $exact = str_repeat('b', 250);

        $this->assertSame($exact, $this->generator()->generate('{{name}}', '', null, ['name' => $exact])['alt']);
    }

    public function testVisionAdapterFillsAltAndTitleWhenTemplateIsEmpty(): void
    {
        $image = $this->tempImage();
        $vision = $this->createMock(VisionAdapterInterface::class);
        $vision->expects($this->once())
            ->method('describe')
            ->with($image, ['sku' => 'S'])
            ->willReturn(['alt' => 'A red shoe on white', 'title' => 'Red shoe']);

        $result = $this->generator($vision)->generate('{{name}}', '', null, ['sku' => 'S'], $image);

        $this->assertSame(['alt' => 'A red shoe on white', 'title' => 'Red shoe'], $result);
    }

    public function testVisionTitleDefaultsToVisionAlt(): void
    {
        $vision = $this->createStub(VisionAdapterInterface::class);
        $vision->method('describe')->willReturn(['alt' => 'Vision alt']);

        $result = $this->generator($vision)->generate('', '', null, [], $this->tempImage());

        $this->assertSame(['alt' => 'Vision alt', 'title' => 'Vision alt'], $result);
    }

    public function testVisionDoesNotOverrideTemplatedTitle(): void
    {
        $vision = $this->createStub(VisionAdapterInterface::class);
        $vision->method('describe')->willReturn(['alt' => 'Vision alt', 'title' => 'Vision title']);

        $result = $this->generator($vision)->generate('', 'Template title', null, [], $this->tempImage());

        $this->assertSame(['alt' => 'Vision alt', 'title' => 'Template title'], $result);
    }

    public function testVisionIsSkippedWhenTemplateProducedAnAlt(): void
    {
        $vision = $this->createMock(VisionAdapterInterface::class);
        $vision->expects($this->never())->method('describe');

        $result = $this->generator($vision)->generate('{{name}}', '', null, ['name' => 'Tpl'], $this->tempImage());

        $this->assertSame('Tpl', $result['alt']);
    }

    public function testVisionIsSkippedWhenImageFileDoesNotExist(): void
    {
        $vision = $this->createMock(VisionAdapterInterface::class);
        $vision->expects($this->never())->method('describe');

        $missing = sys_get_temp_dir() . '/imgseo-missing-' . uniqid() . '.jpg';
        $result = $this->generator($vision)->generate('', '', null, ['name' => 'Fallback'], $missing);

        $this->assertSame('Fallback', $result['alt']);
    }

    public function testVisionIsSkippedWithoutImagePath(): void
    {
        $vision = $this->createMock(VisionAdapterInterface::class);
        $vision->expects($this->never())->method('describe');

        $this->assertSame('N', $this->generator($vision)->generate('', '', null, ['name' => 'N'])['alt']);
    }

    public function testVisionFailureIsLoggedAndFallsBackToName(): void
    {
        $vision = $this->createStub(VisionAdapterInterface::class);
        $vision->method('describe')->willThrowException(new \RuntimeException('api down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] vision adapter failed: api down');

        $result = $this->generator($vision, $logger)
            ->generate('', '', $this->entity('Named'), [], $this->tempImage());

        $this->assertSame(['alt' => 'Named', 'title' => 'Named'], $result);
    }

    public function testNullVisionAdapterLeavesTheNameFallbackInPlace(): void
    {
        $adapter = new NullVisionAdapter();
        $this->assertNull($adapter->describe('/any/path.jpg', ['name' => 'x']));

        $result = $this->generator($adapter)->generate('', '', $this->entity('Plain'), [], $this->tempImage());

        $this->assertSame(['alt' => 'Plain', 'title' => 'Plain'], $result);
    }

    public function testNameFallbackExceptionDoesNotEscapeGenerate(): void
    {
        $entity = new class {
            public function getName(): string
            {
                throw new \RuntimeException('name unavailable');
            }
        };
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())->method('warning');

        $this->assertSame(
            ['alt' => '', 'title' => ''],
            $this->generator(null, $logger)->generate('', '', $entity)
        );
    }
}
