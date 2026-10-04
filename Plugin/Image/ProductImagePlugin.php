<?php
declare(strict_types=1);

namespace Panth\ImageSeo\Plugin\Image;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Panth\ImageSeo\Model\ImageSeo\ImageTemplateResolver;
use Psr\Log\LoggerInterface;

class ProductImagePlugin
{
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

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

    private function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            ImageTemplateResolver::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
