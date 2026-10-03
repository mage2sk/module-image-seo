<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Block\Product\View\Gallery;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

class GalleryImageSeoPlugin
{
    private const PLACEHOLDER_LABELS = [
        'image',
        'main product photo',
    ];

    public function __construct(
        private readonly ImageTemplateResolver $templateResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterGetGalleryImagesJson(Gallery $subject, $result): string
    {
        if (!is_string($result) || $result === '') {
            return (string) $result;
        }

        if (!$this->templateResolver->isGalleryEnabled()) {
            return $result;
        }

        try {
            $product = $subject->getProduct();
            if ($product === null) {
                return $result;
            }

            $images = json_decode($result, true);
            if (!is_array($images) || $images === []) {
                return $result;
            }

            $resolved    = $this->templateResolver->resolve($product);
            $alt         = $resolved['alt'] ?? '';
            $title       = $resolved['title'] ?? '';
            $productName = (string) $product->getName();

            foreach ($images as $index => &$image) {
                if (!is_array($image)) {
                    continue;
                }

                $currentCaption = trim((string) ($image['caption'] ?? ''));
                $imageFile      = (string) ($image['file'] ?? '');

                if ($alt !== '' && $this->isReplaceable($currentCaption, $productName, $imageFile)) {
                    $image['caption'] = $this->appendPosition($alt, $index, count($images));
                }

                $currentTitle = trim((string) ($image['title'] ?? ''));
                if ($this->isReplaceable($currentTitle, $productName, $imageFile)) {
                    $image['title'] = $title !== '' ? $title : ($image['caption'] ?? $productName);
                }
            }
            unset($image);

            $encoded = json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
            if ($encoded === false) {
                return $result;
            }
            return $encoded;
        } catch (\Throwable $e) {
            $this->logger->warning(
                '[PanthImageSeo] GalleryImageSeoPlugin failed',
                ['error' => $e->getMessage()]
            );
        }

        return $result;
    }

    private function appendPosition(string $alt, int $index, int $total): string
    {
        if ($total <= 1) {
            return $alt;
        }
        return $alt . ' - Image ' . ($index + 1) . ' of ' . $total;
    }

    private function isReplaceable(string $caption, string $productName, string $imageFile): bool
    {
        if ($caption === '') {
            return true;
        }
        if ($caption === $productName) {
            return true;
        }
        if (in_array(mb_strtolower($caption), self::PLACEHOLDER_LABELS, true)) {
            return true;
        }
        if ($imageFile !== '') {
            $basename = pathinfo($imageFile, PATHINFO_FILENAME);
            if ($basename !== '' && $caption === $basename) {
                return true;
            }

            $withExt = basename($imageFile);
            if ($withExt !== '' && $caption === $withExt) {
                return true;
            }
        }
        return false;
    }
}
