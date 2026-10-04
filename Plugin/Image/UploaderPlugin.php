<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\ScopeInterface;
use Panth\ImageSeo\Model\ImageSeo\FilenameNormalizer;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

class UploaderPlugin
{
    public function __construct(
        private readonly FilenameNormalizer $normalizer,
        private readonly Filesystem $filesystem,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterMoveFileFromTmp(ImageUploader $subject, $result)
    {
        if (!is_string($result) || $result === '') {
            return $result;
        }
        if (!$this->isEnabled()) {
            return $result;
        }
        try {
            $basePath = method_exists($subject, 'getBasePath') ? trim((string) $subject->getBasePath(), '/') : '';
            if ($basePath === '') {
                return $result;
            }
            $normalizedBase = $this->normalizer->normalize(basename($result));
            if ($normalizedBase === basename($result)) {
                return $result;
            }
            $dir        = dirname($result);
            $newResult  = ($dir === '.' || $dir === '') ? $normalizedBase : rtrim($dir, '/') . '/' . $normalizedBase;
            $isRelative = str_starts_with(ltrim($result, '/'), $basePath . '/');
            $oldPath    = $isRelative ? ltrim($result, '/') : $basePath . '/' . ltrim($result, '/');
            $newPath    = $isRelative ? ltrim($newResult, '/') : $basePath . '/' . ltrim($newResult, '/');
            $mediaDir   = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            if ($oldPath === $newPath || !$mediaDir->isFile($oldPath) || $mediaDir->isExist($newPath)) {
                return $result;
            }
            $mediaDir->renameFile($oldPath, $newPath);
            return $newResult;
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthImageSeo] image filename normalize failed: ' . $e->getMessage());
        }
        return $result;
    }

    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            ImageTemplateResolver::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
