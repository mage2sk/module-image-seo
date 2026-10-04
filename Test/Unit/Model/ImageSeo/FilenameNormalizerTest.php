<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Model\ImageSeo;

use Panth\ImageSeo\Model\ImageSeo\FilenameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FilenameNormalizerTest extends TestCase
{
    public static function filenameProvider(): array
    {
        return [
            'spaces and upper-case extension' => ['My Photo.JPG', 'my-photo.jpg'],
            'already clean' => ['red-shoe.png', 'red-shoe.png'],
            'underscores and symbols' => ['Red_Shoe (1) final!!.jpeg', 'red-shoe-1-final.jpeg'],
            'leading and trailing separators' => ['--__Hello__--.gif', 'hello.gif'],
            'no extension' => ['Hello World', 'hello-world'],
            'multiple dots keep only last extension' => ['my.file.name.TAR.GZ', 'my-file-name-tar.gz'],
            'only symbols becomes image' => ['###.png', 'image.png'],
            'empty string becomes image' => ['', 'image'],
            'dot file keeps its suffix' => ['.htaccess', 'image.htaccess'],
            'accented letters are transliterated' => ["Gr\u{fc}ne \u{c4}pfel.webp", 'grune-apfel.webp'],
            'digits kept' => ['IMG 2024 05.jpg', 'img-2024-05.jpg'],
        ];
    }

    #[DataProvider('filenameProvider')]
    public function testNormalize(string $input, string $expected): void
    {
        $this->assertSame($expected, (new FilenameNormalizer())->normalize($input));
    }

    public function testLongBaseIsCappedAt120Characters(): void
    {
        $result = (new FilenameNormalizer())->normalize(str_repeat('a', 200) . '.jpg');

        $this->assertSame(str_repeat('a', 120) . '.jpg', $result);
    }

    public function testCappedBaseDoesNotEndWithADash(): void
    {
        $result = (new FilenameNormalizer())->normalize(str_repeat('a', 119) . ' bbbb.png');

        $this->assertSame(str_repeat('a', 119) . '.png', $result);
    }

    public function testOutputOnlyContainsSafeCharacters(): void
    {
        $result = (new FilenameNormalizer())
            ->normalize("Caf\u{e9} \u{2013} \u{201c}Special\u{201d} Offer 50%.PNG");

        $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*\.png$/', $result);
        $this->assertStringStartsWith('cafe-', $result);
    }
}
