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

/**
 * Normalizes uploaded catalog image filenames to SEO-friendly slugs.
 *
 * Operates after ImageUploader::moveFileFromTmp. Best-effort: logs and
 * returns the original path if rename is not possible (missing source,
 * existing destination, unwritable directory).
 */
class UploaderPlugin
{
    public function __construct(
        private readonly FilenameNormalizer $normalizer,
        private readonly Filesystem $filesystem,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ImageUploader $subject
     * @param mixed $result The relative path returned by moveFileFromTmp.
     * @return mixed
     */
    public function afterMoveFileFromTmp(ImageUploader $subject, $result)
    {
        if (!is_string($result) || $result === '') {
            return $result;
        }
        if (!$this->isEnabled()) {
            return $result;
        }
        try {
            $normalizedBase = $this->normalizer->normalize(basename($result));
            if ($normalizedBase === basename($result)) {
                return $result;
            }
            $newRelative = rtrim(dirname($result), '/') . '/' . $normalizedBase;
            $mediaDir    = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $basePath    = method_exists($subject, 'getBasePath') ? (string) $subject->getBasePath() : '';
            if ($basePath === '') {
                return $result;
            }
            $oldAbs = $mediaDir->getAbsolutePath($basePath . '/' . ltrim($result, '/'));
            $newAbs = $mediaDir->getAbsolutePath($basePath . '/' . ltrim($newRelative, '/'));
            if ($oldAbs === $newAbs || !file_exists($oldAbs) || file_exists($newAbs)) {
                return $result;
            }
            try {
                if (rename($oldAbs, $newAbs)) {
                    return $newRelative;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('[PanthImageSeo] image rename failed: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthImageSeo] image filename normalize failed: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Module enabled flag — uses the panth_seo/image master switch so the
     * module is self-contained and does not require Panth_AdvancedSEO.
     */
    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            ImageTemplateResolver::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
