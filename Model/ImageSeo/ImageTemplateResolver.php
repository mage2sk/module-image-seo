<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Model\ImageSeo;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves SEO-optimized alt and title text for product images by
 * rendering configurable templates through the local AltGenerator.
 *
 * Config paths:
 *   panth_image_seo/image/image_seo_enabled   yes/no master toggle
 *   panth_image_seo/image/alt_template        default "{{name}} - {{store}}"
 *   panth_image_seo/image/title_template      default "{{name}}"
 *   panth_image_seo/image/gallery_seo_enabled apply to gallery JSON too
 */
class ImageTemplateResolver
{
    public const XML_PATH_ENABLED         = 'panth_image_seo/image/image_seo_enabled';
    public const XML_PATH_ALT_TEMPLATE    = 'panth_image_seo/image/alt_template';
    public const XML_PATH_TITLE_TEMPLATE  = 'panth_image_seo/image/title_template';
    public const XML_PATH_GALLERY_ENABLED = 'panth_image_seo/image/gallery_seo_enabled';

    public function __construct(
        private readonly AltGenerator $altGenerator,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Whether template-based image alt/title generation is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Whether gallery JSON injection is also enabled.
     */
    public function isGalleryEnabled(): bool
    {
        return $this->isEnabled()
            && $this->scopeConfig->isSetFlag(self::XML_PATH_GALLERY_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Resolve an SEO alt text for the given product.
     *
     * Falls back to product name when template rendering fails or the
     * feature is disabled.
     */
    public function getAlt(ProductInterface $product): string
    {
        if (!$this->isEnabled()) {
            return $this->fallbackName($product);
        }
        $result = $this->resolve($product);
        return $result['alt'];
    }

    /**
     * Resolve an SEO title text for the given product.
     *
     * Falls back to the resolved alt text (or product name) when the
     * title template is empty or rendering fails.
     */
    public function getTitle(ProductInterface $product): string
    {
        if (!$this->isEnabled()) {
            return $this->fallbackName($product);
        }
        $result = $this->resolve($product);
        return $result['title'];
    }

    /**
     * Resolve both alt and title in a single call.
     *
     * @return array{alt: string, title: string}
     */
    public function resolve(ProductInterface $product): array
    {
        $altTpl   = $this->getAltTemplate();
        $titleTpl = $this->getTitleTemplate();
        $context  = $this->buildContext($product);

        try {
            return $this->altGenerator->generate(
                $altTpl,
                $titleTpl,
                $product,
                $context
            );
        } catch (\Throwable $e) {
            $this->logger->warning(
                '[PanthImageSeo] ImageTemplateResolver failed',
                ['error' => $e->getMessage(), 'product_id' => $product->getId()]
            );
            $name = $this->fallbackName($product);
            return ['alt' => $name, 'title' => $name];
        }
    }

    private function getAltTemplate(): string
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_ALT_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        ));
    }

    private function getTitleTemplate(): string
    {
        return trim((string) $this->scopeConfig->getValue(
            self::XML_PATH_TITLE_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        ));
    }

    /**
     * Build a context array expected by AltGenerator.
     *
     * @return array<string, mixed>
     */
    private function buildContext(ProductInterface $product): array
    {
        $context = [
            'name' => (string) $product->getName(),
        ];
        if (method_exists($product, 'getSku')) {
            $context['sku'] = (string) $product->getSku();
        }
        return $context;
    }

    private function fallbackName(ProductInterface $product): string
    {
        return (string) $product->getName();
    }
}
