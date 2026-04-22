<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

/**
 * Theme-agnostic plugin on Magento\Catalog\Helper\Image::getLabel that
 * falls back to the product name when the stored image label is empty.
 * Works in both Hyva and Luma (no JS dependencies).
 */
class ProductImagePlugin
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param ImageHelper $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetLabel(ImageHelper $subject, $result)
    {
        if (!$this->isEnabled()) {
            return $result;
        }
        if (is_string($result) && trim($result) !== '') {
            return $result;
        }
        try {
            $product = $this->extractProduct($subject);
            if ($product !== null) {
                $name = (string) $product->getName();
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PanthImageSeo] getLabel fallback failed: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Read the protected _product property from the image helper via a
     * cached ReflectionProperty. Helper\Image::getProduct() itself is
     * protected and cannot be called from a plugin.
     */
    private function extractProduct(ImageHelper $subject): ?ProductInterface
    {
        static $property = null;
        if ($property === null) {
            try {
                $property = new \ReflectionProperty(ImageHelper::class, '_product');
                $property->setAccessible(true);
            } catch (\Throwable $e) {
                return null;
            }
        }
        $product = $property->getValue($subject);
        return $product instanceof ProductInterface ? $product : null;
    }

    /**
     * Module enabled flag.
     */
    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            ImageTemplateResolver::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
