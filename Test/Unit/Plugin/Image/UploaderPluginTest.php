<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Test\Unit\Plugin\Image;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Panth\ImageSeo\Model\ImageSeo\FilenameNormalizer;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Panth\ImageSeo\Plugin\Image\UploaderPlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UploaderPluginTest extends TestCase
{
    private function config(bool $enabled): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnCallback(
            static fn(string $path) => $enabled && $path === ImageTemplateResolver::XML_PATH_ENABLED
        );
        return $config;
    }

    private function uploader(string $basePath = 'catalog/category'): ImageUploader
    {
        $uploader = $this->createStub(ImageUploader::class);
        $uploader->method('getBasePath')->willReturn($basePath);
        return $uploader;
    }

    private function filesystem(WriteInterface $dir): Filesystem
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturnCallback(
            static function (string $code) use ($dir) {
                if ($code !== DirectoryList::MEDIA) {
                    throw new \LogicException('Unexpected directory ' . $code);
                }
                return $dir;
            }
        );
        return $filesystem;
    }

    private function plugin(
        bool $enabled,
        ?WriteInterface $dir = null,
        ?LoggerInterface $logger = null
    ): UploaderPlugin {
        return new UploaderPlugin(
            new FilenameNormalizer(),
            $this->filesystem($dir ?? $this->createStub(WriteInterface::class)),
            $this->config($enabled),
            $logger ?? $this->createStub(LoggerInterface::class)
        );
    }

    private function mediaDir(array $existing): WriteInterface
    {
        $dir = $this->createMock(WriteInterface::class);
        $dir->method('isFile')->willReturnCallback(static fn(string $p) => in_array($p, $existing, true));
        $dir->method('isExist')->willReturnCallback(static fn(string $p) => in_array($p, $existing, true));
        return $dir;
    }

    public function testNonStringOrEmptyResultIsReturnedAsIs(): void
    {
        $plugin = $this->plugin(true);

        $this->assertNull($plugin->afterMoveFileFromTmp($this->uploader(), null));
        $this->assertSame('', $plugin->afterMoveFileFromTmp($this->uploader(), ''));
        $this->assertSame(['x'], $plugin->afterMoveFileFromTmp($this->uploader(), ['x']));
    }

    public function testDisabledKeepsOriginalName(): void
    {
        $dir = $this->createMock(WriteInterface::class);
        $dir->expects($this->never())->method('renameFile');

        $this->assertSame('My Photo.JPG', $this->plugin(false, $dir)->afterMoveFileFromTmp($this->uploader(), 'My Photo.JPG'));
    }

    public function testEmptyBasePathKeepsOriginalName(): void
    {
        $dir = $this->createMock(WriteInterface::class);
        $dir->expects($this->never())->method('renameFile');

        $this->assertSame(
            'My Photo.JPG',
            $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader('/'), 'My Photo.JPG')
        );
    }

    public function testAlreadyNormalizedNameIsNotRenamed(): void
    {
        $dir = $this->createMock(WriteInterface::class);
        $dir->expects($this->never())->method('isFile');
        $dir->expects($this->never())->method('renameFile');

        $this->assertSame('my-photo.jpg', $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader(), 'my-photo.jpg'));
    }

    public function testBareFilenameIsRenamedInsideTheBasePath(): void
    {
        $dir = $this->mediaDir(['catalog/category/My Photo.JPG']);
        $dir->expects($this->once())
            ->method('renameFile')
            ->with('catalog/category/My Photo.JPG', 'catalog/category/my-photo.jpg');

        $this->assertSame(
            'my-photo.jpg',
            $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader('/catalog/category/'), 'My Photo.JPG')
        );
    }

    public function testSubdirectoryIsPreserved(): void
    {
        $dir = $this->mediaDir(['catalog/category/2024/Big Banner.png']);
        $dir->expects($this->once())
            ->method('renameFile')
            ->with('catalog/category/2024/Big Banner.png', 'catalog/category/2024/big-banner.png');

        $this->assertSame(
            '2024/big-banner.png',
            $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader(), '2024/Big Banner.png')
        );
    }

    public function testResultAlreadyPrefixedWithBasePathIsNotDoublePrefixed(): void
    {
        $dir = $this->mediaDir(['catalog/category/Big Banner.png']);
        $dir->expects($this->once())
            ->method('renameFile')
            ->with('catalog/category/Big Banner.png', 'catalog/category/big-banner.png');

        $this->assertSame(
            'catalog/category/big-banner.png',
            $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader(), 'catalog/category/Big Banner.png')
        );
    }

    public function testMissingSourceFileIsNotRenamed(): void
    {
        $dir = $this->mediaDir([]);
        $dir->expects($this->never())->method('renameFile');

        $this->assertSame('My Photo.jpg', $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader(), 'My Photo.jpg'));
    }

    public function testExistingTargetIsNeverOverwritten(): void
    {
        $dir = $this->mediaDir(['catalog/category/My Photo.jpg', 'catalog/category/my-photo.jpg']);
        $dir->expects($this->never())->method('renameFile');

        $this->assertSame('My Photo.jpg', $this->plugin(true, $dir)->afterMoveFileFromTmp($this->uploader(), 'My Photo.jpg'));
    }

    public function testRenameFailureIsLoggedAndOriginalNameReturned(): void
    {
        $dir = $this->mediaDir(['catalog/category/My Photo.jpg']);
        $dir->expects($this->once())
            ->method('renameFile')
            ->willThrowException(new \RuntimeException('read-only'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('[PanthImageSeo] image filename normalize failed: read-only');

        $this->assertSame(
            'My Photo.jpg',
            $this->plugin(true, $dir, $logger)->afterMoveFileFromTmp($this->uploader(), 'My Photo.jpg')
        );
    }
}
