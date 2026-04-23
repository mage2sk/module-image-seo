<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Block\Product\View\Gallery;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

/**
 * Plugin on Magento\Catalog\Block\Product\View\Gallery::getGalleryImagesJson.
 *
 * Injects template-rendered alt and title text into every gallery image's
 * "caption" field. The native Magento gallery JSON uses "caption" for the
 * alt/title of each image (rendered into both the alt attribute and the
 * caption overlay by the fotorama/gallery widget).
 *
 * Replacement policy — the caption is overwritten when it is either empty,
 * matches the product name verbatim, or matches a Magento placeholder
 * string (the literal "Image" label, the image filename). A label that
 * looks like a merchant-authored description is preserved so custom
 * per-image alt text keeps working.
 *
 * The title is added as a separate "title" key in each image object so
 * frontend templates can distinguish between alt and title if needed.
 */
class GalleryImageSeoPlugin
{
    /**
     * Known Magento placeholder labels that should be treated as "empty"
     * and replaced with the template-rendered alt text. "Image" in
     * particular is the literal default Magento writes to the
     * catalog_product_entity_media_gallery_value.label column on a
     * non-merchant upload path (sample data, some import flows, some
     * admin UI states).
     *
     * Matching is case-insensitive to cover translated placeholders.
     */
    private const PLACEHOLDER_LABELS = [
        'image',
        'main product photo',
    ];

    public function __construct(
        private readonly ImageTemplateResolver $templateResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Gallery $subject
     * @param mixed $result JSON-encoded gallery images array.
     * @return string
     */
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

                // Always inject a "title" key for frontend consumption.
                // Per-image label takes precedence over template title.
                $currentTitle = trim((string) ($image['title'] ?? ''));
                if ($this->isReplaceable($currentTitle, $productName, $imageFile)) {
                    $image['title'] = $title !== '' ? $title : ($image['caption'] ?? $productName);
                }
            }
            unset($image);

            $encoded = json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    /**
     * For galleries with multiple images, append a position suffix to
     * differentiate alt text (e.g. "Product Name - Image 2 of 5").
     * Single-image galleries get no suffix.
     */
    private function appendPosition(string $alt, int $index, int $total): string
    {
        if ($total <= 1) {
            return $alt;
        }
        return $alt . ' - Image ' . ($index + 1) . ' of ' . $total;
    }

    /**
     * Whether a caption/title should be overwritten with the template-rendered
     * value. Replaceable values are: empty strings, exact product-name
     * matches (Magento's default fallback), the literal placeholder labels
     * defined in {@see self::PLACEHOLDER_LABELS}, and captions that are
     * really just the underlying image filename (e.g. "wb04-blue-0",
     * "wb04-blue-0.jpg") — those give no SEO value and are clearly not
     * merchant-authored copy.
     */
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
            // Also compare against the raw filename + extension.
            $withExt = basename($imageFile);
            if ($withExt !== '' && $caption === $withExt) {
                return true;
            }
        }
        return false;
    }
}
